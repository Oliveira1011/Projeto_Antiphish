<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

try {
    $pdo = new PDO("mysql:host=localhost;dbname=antifish;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("Erro: " . $e->getMessage());
}

// Função SIMPLIFICADA para embaralhar opções
function embaralharOpcoes($a, $b, $c, $d, $correta_original) {
    $opcoes = [
        'a' => $a,
        'b' => $b,
        'c' => $c,
        'd' => $d
    ];
    
    // Criar array com letras e seus valores
    $opcoes_array = [
        ['letra' => 'a', 'texto' => $a],
        ['letra' => 'b', 'texto' => $b],
        ['letra' => 'c', 'texto' => $c],
        ['letra' => 'd', 'texto' => $d]
    ];
    
    // Embaralhar o array
    shuffle($opcoes_array);
    
    // Mapear de volta para letras fixas (a, b, c, d)
    $resultado = [];
    $nova_correta = '';
    
    $letras_fixas = ['a', 'b', 'c', 'd'];
    foreach ($letras_fixas as $index => $letra_fixa) {
        $resultado[$letra_fixa] = $opcoes_array[$index]['texto'];
        
        // Se esta opção embaralhada era a correta original
        if ($opcoes_array[$index]['letra'] == $correta_original) {
            $nova_correta = $letra_fixa;
        }
    }
    
    return ['opcoes' => $resultado, 'nova_correta' => $nova_correta];
}

// Verificar e criar colunas se necessário
try {
    $pdo->query("SELECT dificuldade FROM perguntas LIMIT 1");
} catch(Exception $e) {
    // Se a coluna não existe, criar as colunas necessárias
    $pdo->exec("ALTER TABLE perguntas ADD COLUMN dificuldade ENUM('facil', 'medio', 'dificil') NOT NULL DEFAULT 'medio'");
    $pdo->exec("ALTER TABLE perguntas ADD COLUMN categoria VARCHAR(50) NOT NULL DEFAULT 'phishing'");
}

// Obter dados do usuário
$stmt = $pdo->prepare("SELECT nome, pontuacao, avatar FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$feedback = "";
$tempo_total = 60;
$streak_atual = 0;
$pontos_ganhos = 0;
$bonus_streak = 0;

// Verificar se o usuário quer recomeçar
if (isset($_GET['recomecar'])) {
    // Limpar respostas do dia atual
    $stmt = $pdo->prepare("DELETE FROM respostas WHERE usuario_id = ? AND DATE(data_resposta) = CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    header("Location: quiz.php");
    exit;
}

if ($_POST && isset($_POST['opcao'])) {
    $pid = $_POST['pid'];
    $opcao_usuario = $_POST['opcao'];
    
    // Verificar se temos nova_correta no POST (embaralhada)
    if (isset($_POST['nova_correta'])) {
        $nova_correta = $_POST['nova_correta'];
        $acertou = ($opcao_usuario == $nova_correta);
    } else {
        // Fallback para o método antigo
        $stmt = $pdo->prepare("SELECT correta FROM perguntas WHERE id = ?");
        $stmt->execute([$pid]);
        $p_correta = $stmt->fetch();
        $acertou = ($opcao_usuario == $p_correta['correta']);
    }
    
    $foi_timeout = ($opcao_usuario == 'timeout');
    
    // Obter a pergunta original para a explicação
    $stmt = $pdo->prepare("SELECT correta, explicacao FROM perguntas WHERE id = ?");
    $stmt->execute([$pid]);
    $p = $stmt->fetch();
    
    // Tentar obter dificuldade, se existir
    try {
        $stmt_dif = $pdo->prepare("SELECT dificuldade FROM perguntas WHERE id = ?");
        $stmt_dif->execute([$pid]);
        $dificuldade_data = $stmt_dif->fetch();
        $dificuldade = $dificuldade_data['dificuldade'] ?? 'medio';
    } catch(Exception $e) {
        $dificuldade = 'medio';
    }
    
    // Calcular streak atual
    $stmt = $pdo->prepare("SELECT acertou FROM respostas WHERE usuario_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $ultimas_respostas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $streak_atual = 0;
    foreach($ultimas_respostas as $resposta) {
        if ($resposta) $streak_atual++;
        else break;
    }
    
    if ($foi_timeout) {
        // TEMPO ESGOTADO - Tratamento especial
        $acertou = false;
        $streak_atual = 0;
        $pontos_ganhos = 0;
        $bonus_streak = 0;
        
        // Mostrar apenas TEMPO ESGOTADO (SEM mostrar a resposta correta)
        $feedback = "<div class='game-result erro'>
            <div class='result-animation'>
                <div class='fail-icon'>⏰</div>
            </div>
            <h2>TEMPO ESGOTADO!</h2>
            <p class='streak-reset'>⚡ Sequência reiniciada</p>
         </div>";
         
        $feedback .= "<div class='mission-debrief'>
            <h3>📋 ANÁLISE DA MENSAGEM</h3>
            <div class='explanation-box'>
                <p><strong>SEM ANÁLISE, POIS NÃO FOI SELECIONADA UMA OPÇÃO!</strong></p>
                <p style='margin-top: 10px; opacity: 0.8;'>O tempo para responder acabou antes de você selecionar uma resposta.</p>
            </div>
            <button onclick='proximaMissao()' class='btn-next-mission'>
                <span>PRÓXIMA MENSAGEM</span>
                <i class='fas fa-arrow-right'></i>
            </button>
        </div>";
        
        // Registrar como erro no banco
        $stmt = $pdo->prepare("INSERT INTO respostas (usuario_id, pergunta_id, acertou) VALUES (?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $pid, 0]);
        
    } elseif ($acertou) {
        $streak_atual++;
        
        // Pontuação base por dificuldade
        $pontos_base = [
            'facil' => 5,
            'medio' => 10,
            'dificil' => 20
        ];
        
        // Bonus por streak
        $bonus_streak = $streak_atual >= 3 ? min(10, floor($streak_atual / 3)) * 2 : 0;
        $pontos_ganhos = $pontos_base[$dificuldade] + $bonus_streak;
        
        $stmt = $pdo->prepare("INSERT INTO respostas (usuario_id, pergunta_id, acertou) VALUES (?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $pid, $acertou]);
        
        $pdo->prepare("UPDATE usuarios SET pontuacao = pontuacao + ?, quizzes_feitos = quizzes_feitos + 1 WHERE id = ?")
            ->execute([$pontos_ganhos, $_SESSION['user_id']]);
            
        $feedback = "<div class='game-result acerto' data-acertou='true' data-streak='$streak_atual' data-bonus='$bonus_streak'>
            <div class='result-animation'>
                <div class='confetti-container'></div>
                <div class='success-icon'>🎯</div>
            </div>
            <h2>SEGURO!</h2>
            <p class='points-earned'>+{$pontos_ganhos} pontos</p>
            " . ($bonus_streak > 0 ? "<p class='bonus-info'>🔥 Streak Bonus: +{$bonus_streak} pontos</p>" : "") . "
            <p class='streak-counter'>Sequência: {$streak_atual} acertos</p>
            <p class='dificuldade-info'>Dificuldade: " . ucfirst($dificuldade) . "</p>
         </div>";
         
        $feedback .= "<div class='mission-debrief'>
            <h3>📋 ANÁLISE DA MISSÃO</h3>
            <div class='explanation-box'>
                <p>" . nl2br(htmlspecialchars($p['explicacao'])) . "</p>
            </div>
            <button onclick='proximaMissao()' class='btn-next-mission'>
                <span>PRÓXIMA MENSAGEM</span>
                <i class='fas fa-arrow-right'></i>
            </button>
        </div>";
        
    } else {
        $streak_atual = 0;
        $pontos_ganhos = 0;
        $bonus_streak = 0;
        
        $stmt = $pdo->prepare("INSERT INTO respostas (usuario_id, pergunta_id, acertou) VALUES (?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $pid, $acertou]);
        
        // Usar a nova correta se disponível, senão a original
        $correta_mostrar = isset($nova_correta) ? strtoupper($nova_correta) : strtoupper($p['correta']);
        
        $feedback = "<div class='game-result erro'>
            <div class='result-animation'>
                <div class='fail-icon'>💥</div>
            </div>
            <h2>MALICIOSO!</h2>
            <p class='correct-answer'>Resposta correta: <strong>" . $correta_mostrar . "</strong></p>
            <p class='streak-reset'>⚡ Sequência reiniciada</p>
            <p class='dificuldade-info'>Dificuldade: " . ucfirst($dificuldade) . "</p>
         </div>";
         
        $feedback .= "<div class='mission-debrief'>
            <h3>📋 ANÁLISE DA MISSÃO</h3>
            <div class='explanation-box'>
                <p>" . nl2br(htmlspecialchars($p['explicacao'])) . "</p>
            </div>
            <button onclick='proximaMissao()' class='btn-next-mission'>
                <span>PRÓXIMA MENSAGEM</span>
                <i class='fas fa-arrow-right'></i>
            </button>
        </div>";
    }
}

// Obter perguntas não respondidas hoje
$stmt = $pdo->prepare("SELECT pergunta_id FROM respostas WHERE usuario_id = ? AND DATE(data_resposta)=CURDATE()");
$stmt->execute([$_SESSION['user_id']]);
$feitas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar pergunta aleatória não respondida
$feitas_sql = $feitas ? "AND id NOT IN (".implode(',', $feitas).")" : "";

// Tentar buscar com dificuldade, se a coluna existir
try {
    // Calcular dificuldade baseada no desempenho
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM respostas WHERE usuario_id = ? AND acertou = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $acertos_totais = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM respostas WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_respostas = $stmt->fetchColumn();

    $taxa_acerto_geral = $total_respostas > 0 ? ($acertos_totais / $total_respostas) * 100 : 0;

    // Definir dificuldade baseada no desempenho
    if ($taxa_acerto_geral > 80) {
        $dificuldade_alvo = 'dificil';
    } elseif ($taxa_acerto_geral > 50) {
        $dificuldade_alvo = 'medio';
    } else {
        $dificuldade_alvo = 'facil';
    }

    // Buscar pergunta com a dificuldade apropriada
    $stmt = $pdo->query("SELECT * FROM perguntas WHERE 1 $feitas_sql AND dificuldade = '$dificuldade_alvo' ORDER BY RAND() LIMIT 1");
    $pergunta = $stmt->fetch();

    // Se não encontrar pergunta na dificuldade alvo, buscar em qualquer dificuldade
    if (!$pergunta) {
        $stmt = $pdo->query("SELECT * FROM perguntas WHERE 1 $feitas_sql ORDER BY RAND() LIMIT 1");
        $pergunta = $stmt->fetch();
    }
} catch(Exception $e) {
    // Se houver erro (coluna dificuldade não existe), buscar qualquer pergunta
    $stmt = $pdo->query("SELECT * FROM perguntas WHERE 1 $feitas_sql ORDER BY RAND() LIMIT 1");
    $pergunta = $stmt->fetch();
    $dificuldade_alvo = 'medio';
}

// Definir dificuldade padrão se não existir
if ($pergunta && !isset($pergunta['dificuldade'])) {
    $pergunta['dificuldade'] = $dificuldade_alvo;
}

// Se houver pergunta, embaralhar as opções
if ($pergunta) {
    $embaralhado = embaralharOpcoes(
        $pergunta['a'],
        $pergunta['b'],
        $pergunta['c'],
        $pergunta['d'],
        $pergunta['correta']
    );
    
    // Substituir as opções originais pelas embaralhadas
    $pergunta['a'] = $embaralhado['opcoes']['a'];
    $pergunta['b'] = $embaralhado['opcoes']['b'];
    $pergunta['c'] = $embaralhado['opcoes']['c'];
    $pergunta['d'] = $embaralhado['opcoes']['d'];
    $pergunta['nova_correta'] = $embaralhado['nova_correta'];
}

// Contar quantas perguntas foram respondidas hoje
$perguntas_hoje = count($feitas);
$total_perguntas = 50; // Total de perguntas disponíveis por dia

// Calcular streak atual para mostrar
$stmt = $pdo->prepare("SELECT acertou FROM respostas WHERE usuario_id = ? ORDER BY id DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$ultimas_respostas = $stmt->fetchAll(PDO::FETCH_COLUMN);

$streak_display = 0;
foreach($ultimas_respostas as $resposta) {
    if ($resposta) $streak_display++;
    else break;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antifish Angola – Missão de Segurança</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* MANTENHA TODO O SEU CSS AQUI - É O MESMO */
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --accent: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
        }
        
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
        
        body {
            background: 
                radial-gradient(circle at 20% 80%, rgba(79, 70, 229, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(236, 72, 153, 0.15) 0%, transparent 50%),
                linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
            overflow-x: hidden;
        }
        
        .game-container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }
        
        /* Header do Jogo */
        .game-header {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .game-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .game-title {
            text-align: center;
            flex-grow: 1;
        }
        
        .game-title h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .game-title p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .player-stats {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .player-info {
            text-align: right;
        }
        
        .player-name {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .player-score {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--warning);
        }
        
        .streak-counter {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn {
            color: white;
            font-size: 1.3rem;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
        
        /* Área de Jogo */
        .game-area {
            padding: 30px;
        }
        
        /* Progresso do Quiz */
        .quiz-progress {
            margin-bottom: 30px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .progress-bar-container {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        /* Timer Estilizado */
        .mission-timer {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .timer-container {
            display: inline-block;
            position: relative;
        }
        
        .timer-circle {
            width: 120px;
            height: 120px;
            position: relative;
        }
        
        .timer-circle svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .circle-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.1);
            stroke-width: 8;
        }
        
        .circle-fg {
            fill: none;
            stroke: var(--primary);
            stroke-width: 8;
            stroke-linecap: round;
            transition: all 0.3s ease;
        }
        
        .timer-display {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .timer-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
        }
        
        .timer-label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        /* Mensagem da Missão */
        .mission-message {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .mission-label {
            position: absolute;
            top: -15px;
            left: 20px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 8px 20px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .dificuldade-badge {
            position: absolute;
            top: -15px;
            right: 20px;
            background: var(--warning);
            color: white;
            padding: 8px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .message-content {
            font-size: 1.3rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* Opções de Resposta */
        .mission-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 40px 0;
        }
        
        .option-card {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(236, 72, 153, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .option-card:hover::before {
            opacity: 1;
        }
        
        .option-content {
            position: relative;
            z-index: 2;
        }
        
        .option-letter {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .option-text {
            font-size: 1.1rem;
            font-weight: 500;
            line-height: 1.5;
        }
        
        /* Resultados */
        .game-result {
            text-align: center;
            padding: 40px;
            border-radius: 20px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        
        .game-result.acerto {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(34, 197, 94, 0.1));
            border: 2px solid rgba(16, 185, 129, 0.3);
        }
        
        .game-result.erro {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.1));
            border: 2px solid rgba(239, 68, 68, 0.3);
        }
        
        .result-animation {
            margin-bottom: 20px;
        }
        
        .success-icon, .fail-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .game-result h2 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .points-earned {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 10px;
        }
        
        .bonus-info {
            font-size: 1.1rem;
            color: var(--warning);
            margin-bottom: 10px;
        }
        
        .correct-answer {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        
        .streak-counter, .streak-reset {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .dificuldade-info {
            font-size: 1rem;
            opacity: 0.8;
            margin-top: 10px;
        }
        
        /* Análise da Missão */
        .mission-debrief {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .mission-debrief h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            text-align: center;
        }
        
        .explanation-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .btn-next-mission {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.4);
        }
        
        .btn-next-mission:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 40px rgba(79, 70, 229, 0.6);
        }
        
        /* Tela de Conclusão */
        .completion-screen {
            text-align: center;
            padding: 80px 40px;
        }
        
        .completion-icon {
            font-size: 5rem;
            margin-bottom: 30px;
        }
        
        .completion-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(90deg, var(--success), var(--warning));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .completion-stats {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 30px;
            margin: 30px auto;
            max-width: 500px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--warning);
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .completion-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            padding: 18px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.4);
        }
        
        .completion-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 40px rgba(79, 70, 229, 0.6);
        }
        
        .completion-btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .completion-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            .mission-options {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .player-stats {
                justify-content: center;
            }
            
            .game-title h1 {
                font-size: 2rem;
            }
            
            .message-content {
                font-size: 1.1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .completion-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
        
        /* Animações */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <!-- Header do Jogo -->
        <div class="game-header">
            <div class="header-content">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
                
                <div class="game-title">
                    <h1>ANTIFISH ANGOLA</h1>
                    <p>MISSÃO DE SEGURANÇA</p>
                </div>
                
                <div class="player-stats">
                    <div class="player-info">
                        <div class="player-name"><?=htmlspecialchars($user['nome'])?></div>
                        <div class="player-score"><?=$user['pontuacao']?> pts</div>
                    </div>
                    <?php if($streak_display > 0): ?>
                    <div class="streak-counter">
                        <i class="fas fa-fire"></i>
                        <span><?=$streak_display?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="game-area">
            <?php if($feedback): ?>
                <?=$feedback?>
            <?php elseif(!$pergunta): ?>
                <!-- Tela de Conclusão -->
                <div class="completion-screen">
                    <div class="completion-icon">🎉</div>
                    <h2 class="completion-title">MISSÕES CONCLUÍDAS!</h2>
                    <p style="font-size: 1.3rem; margin-bottom: 20px; opacity: 0.9;">
                        Você completou todos os desafios de hoje. Volte amanhã para novas missões!
                    </p>
                    
                    <div class="completion-stats">
                        <div class="stat-row">
                            <span>Missões de Hoje:</span>
                            <span class="stat-value"><?=$perguntas_hoje?>/<?=$total_perguntas?></span>
                        </div>
                        <div class="stat-row">
                            <span>Pontuação Total:</span>
                            <span class="stat-value"><?=$user['pontuacao']?> pontos</span>
                        </div>
                        <div class="stat-row">
                            <span>Sequência Atual:</span>
                            <span class="stat-value"><?=$streak_display?> acertos</span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="?recomecar=1" class="completion-btn">
                            <i class="fas fa-redo"></i>
                            <span>RECOMEÇAR QUIZ</span>
                        </a>
                        <a href="dashboard.php" class="completion-btn secondary">
                            <i class="fas fa-trophy"></i>
                            <span>VOLTAR AO DASHBOARD</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Progresso do Quiz -->
                <div class="quiz-progress">
                    <div class="progress-info">
                        <span>Missão <?=$perguntas_hoje + 1?> de <?=$total_perguntas?></span>
                        <span><?=round(($perguntas_hoje / $total_perguntas) * 100)?>% Concluído</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: <?=($perguntas_hoje / $total_perguntas) * 100?>%"></div>
                    </div>
                </div>

                <!-- Timer da Missão -->
                <div class="mission-timer">
                    <div class="timer-container">
                        <div class="timer-circle">
                            <svg>
                                <circle class="circle-bg" cx="60" cy="60" r="54"></circle>
                                <circle class="circle-fg" cx="60" cy="60" r="54" stroke-dasharray="339" stroke-dashoffset="339"></circle>
                            </svg>
                            <div class="timer-display">
                                <div class="timer-number" id="timer">60</div>
                                <div class="timer-label">TEMPO RESTANTE</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensagem da Missão -->
                <div class="mission-message">
                    <div class="mission-label">ANALISE ESTA MENSAGEM</div>
                    <?php if(isset($pergunta['dificuldade'])): ?>
                    <div class="dificuldade-badge" style="background: 
                        <?php 
                        switch($pergunta['dificuldade']) {
                            case 'facil': echo '#10b981'; break;
                            case 'medio': echo '#f59e0b'; break;
                            case 'dificil': echo '#ef4444'; break;
                            default: echo '#6b7280';
                        }
                        ?>">
                        <?=strtoupper($pergunta['dificuldade'])?>
                    </div>
                    <?php endif; ?>
                    <div class="message-content">
                        <?=$pergunta['pergunta']?>
                    </div>
                </div>

                <!-- Opções de Resposta (AGORA EMBARALHADAS) -->
                <form method="post" id="quizForm">
                    <input type="hidden" name="pid" value="<?=$pergunta['id']?>">
                    <?php if(isset($pergunta['nova_correta'])): ?>
                    <input type="hidden" name="nova_correta" value="<?=$pergunta['nova_correta']?>">
                    <?php endif; ?>
                    <?php if(isset($pergunta['dificuldade'])): ?>
                    <input type="hidden" name="dificuldade" value="<?=$pergunta['dificuldade']?>">
                    <?php else: ?>
                    <input type="hidden" name="dificuldade" value="medio">
                    <?php endif; ?>
                    <div class="mission-options">
                        <div class="option-card" onclick="selectOption('a')">
                            <div class="option-content">
                                <div class="option-letter">A</div>
                                <div class="option-text"><?=$pergunta['a']?></div>
                            </div>
                        </div>
                        
                        <div class="option-card" onclick="selectOption('b')">
                            <div class="option-content">
                                <div class="option-letter">B</div>
                                <div class="option-text"><?=$pergunta['b']?></div>
                            </div>
                        </div>
                        
                        <div class="option-card" onclick="selectOption('c')">
                            <div class="option-content">
                                <div class="option-letter">C</div>
                                <div class="option-text"><?=$pergunta['c']?></div>
                            </div>
                        </div>
                        
                        <div class="option-card" onclick="selectOption('d')">
                            <div class="option-content">
                                <div class="option-letter">D</div>
                                <div class="option-text"><?=$pergunta['d']?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botão de submissão oculto -->
                    <input type="hidden" name="opcao" id="selectedOption">
                    <button type="submit" style="display: none;" id="submitBtn"></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        // Sistema de Timer
        let tempo = <?=$tempo_total?>;
        let rodando = true;
        const timerNum = document.getElementById('timer');
        const circle = document.querySelector('.circle-fg');
        const circumference = 339;

        function atualizarTimer() {
            if (!rodando) return;
            const offset = circumference - (circumference * (tempo / <?=$tempo_total?>));
            circle.style.strokeDashoffset = offset;
            timerNum.textContent = tempo;
            
            // Efeito visual quando o tempo está acabando
            if (tempo <= 15) {
                timerNum.style.color = '#ef4444';
                circle.style.stroke = '#ef4444';
                timerNum.classList.add('pulse');
            }
            
            if (tempo <= 0) {
                document.getElementById('selectedOption').value = 'timeout';
                document.getElementById('submitBtn').click();
            }
        }

        const intervalo = setInterval(() => {
            if (rodando && tempo > 0) {
                tempo--;
                atualizarTimer();
            }
        }, 1000);

        // Sistema de Seleção de Opções
        function selectOption(option) {
            document.getElementById('selectedOption').value = option;
            document.getElementById('submitBtn').click();
        }

        // Efeitos de Feedback
        <?php if($feedback): ?>
        rodando = false;
        atualizarTimer();

        // Efeitos visuais para acertos
        if (document.querySelector('[data-acertou="true"]')) {
            // Confetti explosion
            confetti({
                particleCount: 150,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#10b981', '#3b82f6', '#8b5cf6', '#ec4899']
            });
            
            // Efeito de streak
            const streak = document.querySelector('[data-streak]').getAttribute('data-streak');
            if (streak >= 3) {
                setTimeout(() => {
                    confetti({
                        particleCount: 50,
                        spread: 100,
                        origin: { y: 0.6 },
                        colors: ['#f59e0b', '#f97316']
                    });
                }, 500);
            }
        }
        <?php endif; ?>

        function proximaMissao() {
            location.href = 'quiz.php';
        }

        // Efeitos de hover nas opções
        document.querySelectorAll('.option-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>