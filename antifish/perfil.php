<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

try {
    $pdo = new PDO("mysql:host=localhost;dbname=antifish;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("Erro de conexão.");
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$msg = "";
$msg_type = "";
$edit_mode = isset($_GET['edit']);

// Lista de avatares disponíveis
$avatares = [
    'adventurer', 'adventurer-neutral', 'avataaars', 'big-ears', 'big-ears-neutral',
    'big-smile', 'bottts', 'croodles', 'croodles-neutral', 'identicon',
    'initials', 'micah', 'miniavs', 'open-peeps', 'personas',
    'pixel-art', 'pixel-art-neutral'
];

// PROCESSAR EDIÇÃO DO PERFIL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'editar_perfil') {
    // Obter valores do formulário
    $nome = $_POST['nome'] ?? $user['nome'];
    $email = $_POST['email'] ?? $user['email'];
    $idade = $_POST['idade'] ?? $user['idade'];
    $genero = $_POST['genero'] ?? $user['genero'];
    $avatar = $_POST['avatar'] ?? $user['avatar'];
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    try {
        // Validar email único
        if ($email != $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception("Este email já está em uso por outro usuário.");
            }
        }

        // Validar idade
        if (!empty($idade) && (!is_numeric($idade) || $idade < 13 || $idade > 120)) {
            throw new Exception("Idade deve ser entre 13 e 120 anos.");
        }

        // Preparar SQL de atualização
        $sql = "UPDATE usuarios SET nome = ?, email = ?, idade = ?, genero = ?, avatar = ?";
        $params = [$nome, $email, $idade, $genero, $avatar];

        // Alterar senha se fornecida
        if (!empty($senha_atual) && !empty($nova_senha) && !empty($confirmar_senha)) {
            // Verificar senha atual
            if (!password_verify($senha_atual, $user['senha'])) {
                throw new Exception("Senha atual incorreta.");
            }
            
            if ($nova_senha != $confirmar_senha) {
                throw new Exception("A nova senha e a confirmação não coincidem.");
            }
            
            if (strlen($nova_senha) < 6) {
                throw new Exception("A nova senha deve ter pelo menos 6 caracteres.");
            }
            
            $sql .= ", senha = ?";
            $params[] = password_hash($nova_senha, PASSWORD_DEFAULT);
        }
        // Se preencheu algum campo de senha mas não todos
        elseif (!empty($senha_atual) || !empty($nova_senha) || !empty($confirmar_senha)) {
            if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
                throw new Exception("Para alterar a senha, preencha todos os 3 campos de senha.");
            }
        }

        $sql .= " WHERE id = ?";
        $params[] = $_SESSION['user_id'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $msg = "✅ Perfil atualizado com sucesso!";
        $msg_type = "success";
        
        // Atualizar dados locais
        $user['nome'] = $nome;
        $user['email'] = $email;
        $user['idade'] = $idade;
        $user['genero'] = $genero;
        $user['avatar'] = $avatar;
        
        // Atualizar dados do usuário no banco
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Sair do modo edição após salvar
        $edit_mode = false;

    } catch(Exception $e) {
        $msg = "❌ Erro: " . $e->getMessage();
        $msg_type = "error";
    }
}

// PROCESSAR REINÍCIO DO JOGO
if (isset($_POST['reiniciar_jogo'])) {
    $pdo->beginTransaction();
    try {
        // ZERAR estatísticas
        $stmt = $pdo->prepare("UPDATE usuarios SET pontuacao = 0, quizzes_feitos = 0 WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Limpar TODAS as respostas do usuário
        $stmt = $pdo->prepare("DELETE FROM respostas WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $pdo->commit();
        
        // Atualizar dados do usuário localmente
        $user['pontuacao'] = 0;
        $user['quizzes_feitos'] = 0;
        
        $msg = "✅ Perfil reiniciado com sucesso! Todas as estatísticas foram zeradas.";
        $msg_type = "success";
        
        // Atualizar sessão
        $_SESSION['user_pontuacao'] = 0;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $msg = "❌ Erro ao reiniciar perfil: " . $e->getMessage();
        $msg_type = "error";
    }
}

// Estatísticas do usuário
$pontos = $user['pontuacao'] ?? 0;
$quizzes = $user['quizzes_feitos'] ?? 0;

// Obter ranking atual
$stmt = $pdo->query("SELECT COUNT(*) + 1 FROM usuarios WHERE pontuacao > " . $user['pontuacao']);
$ranking_atual = $stmt->fetchColumn();

// Contar total de usuários
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
$total_usuarios = $stmt->fetchColumn();
$percentil_ranking = $total_usuarios > 0 ? round(($ranking_atual / $total_usuarios) * 100) : 0;

// Calcular streak atual
$stmt = $pdo->prepare("SELECT COUNT(*) as streak FROM (
    SELECT acertou FROM respostas 
    WHERE usuario_id = ? 
    ORDER BY id DESC
) t WHERE acertou = 1");
$stmt->execute([$_SESSION['user_id']]);
$streak_atual = $stmt->fetchColumn();

// Sistema de níveis
if ($pontos < 200) {
    $nivel = "Iniciante"; $emoji = "🛡️"; $cor = "#4ade80"; $cor_light = "#86efac";
} elseif ($pontos < 500) {
    $nivel = "Guardião"; $emoji = "⚔️"; $cor = "#3b82f6"; $cor_light = "#93c5fd";
} elseif ($pontos < 1000) {
    $nivel = "Mestre"; $emoji = "👑"; $cor = "#f59e0b"; $cor_light = "#fcd34d";
} else {
    $nivel = "Lenda"; $emoji = "🌟"; $cor = "#8b5cf6"; $cor_light = "#c4b5fd";
}

// Progresso para próximo nível
if ($pontos < 200) {
    $progresso = ($pontos / 200) * 100;
    $proximo_nivel = "Guardião";
    $pontos_restantes = 200 - $pontos;
} elseif ($pontos < 500) {
    $progresso = (($pontos - 200) / 300) * 100;
    $proximo_nivel = "Mestre";
    $pontos_restantes = 500 - $pontos;
} elseif ($pontos < 1000) {
    $progresso = (($pontos - 500) / 500) * 100;
    $proximo_nivel = "Lenda";
    $pontos_restantes = 1000 - $pontos;
} else {
    $progresso = 100;
    $proximo_nivel = "Máximo";
    $pontos_restantes = 0;
}

// SISTEMA DE CONQUISTAS
$conquistas = [
    [
        'nome' => 'Primeiro Passo',
        'descricao' => 'Completar o primeiro quiz',
        'emoji' => '👣',
        'desbloqueada' => $quizzes >= 1,
        'progresso' => min(100, ($quizzes >= 1 ? 100 : 0))
    ],
    [
        'nome' => 'Agente Iniciante',
        'descricao' => 'Alcançar 50 pontos',
        'emoji' => '⭐',
        'desbloqueada' => $pontos >= 50,
        'progresso' => min(100, ($pontos / 50) * 100)
    ],
    [
        'nome' => 'Guardião Digital',
        'descricao' => 'Alcançar 200 pontos',
        'emoji' => '🛡️',
        'desbloqueada' => $pontos >= 200,
        'progresso' => min(100, ($pontos / 200) * 100)
    ],
    [
        'nome' => 'Mestre da Segurança',
        'descricao' => 'Alcançar 500 pontos',
        'emoji' => '👑',
        'desbloqueada' => $pontos >= 500,
        'progresso' => min(100, ($pontos / 500) * 100)
    ],
    [
        'nome' => 'Lenda Antifish',
        'descricao' => 'Alcançar 1000 pontos',
        'emoji' => '🌟',
        'desbloqueada' => $pontos >= 1000,
        'progresso' => min(100, ($pontos / 1000) * 100)
    ],
    [
        'nome' => 'Streak Inicial',
        'descricao' => 'Conseguir 3 acertos seguidos',
        'emoji' => '🔥',
        'desbloqueada' => $streak_atual >= 3,
        'progresso' => min(100, ($streak_atual / 3) * 100)
    ],
    [
        'nome' => 'Sequência Imbatível',
        'descricao' => 'Conseguir 10 acertos seguidos',
        'emoji' => '⚡',
        'desbloqueada' => $streak_atual >= 10,
        'progresso' => min(100, ($streak_atual / 10) * 100)
    ],
    [
        'nome' => 'Estudioso Digital',
        'descricao' => 'Completar 10 quizzes',
        'emoji' => '📚',
        'desbloqueada' => $quizzes >= 10,
        'progresso' => min(100, ($quizzes / 10) * 100)
    ],
    [
        'nome' => 'Veterano',
        'descricao' => 'Completar 50 quizzes',
        'emoji' => '🎖️',
        'desbloqueada' => $quizzes >= 50,
        'progresso' => min(100, ($quizzes / 50) * 100)
    ],
    [
        'nome' => 'Perfeccionista',
        'descricao' => 'Acertar 5 perguntas consecutivas',
        'emoji' => '💯',
        'desbloqueada' => $streak_atual >= 5,
        'progresso' => min(100, ($streak_atual / 5) * 100)
    ],
    [
        'nome' => 'Piloto de Quiz',
        'descricao' => 'Responder em menos de 10 segundos',
        'emoji' => '🚀',
        'desbloqueada' => false, // Será implementado com sistema de tempo
        'progresso' => 0
    ],
    [
        'nome' => 'Protetor Digital',
        'descricao' => 'Editar seu perfil pela primeira vez',
        'emoji' => '🛡️',
        'desbloqueada' => isset($_SESSION['perfil_editado']) && $_SESSION['perfil_editado'] === true,
        'progresso' => isset($_SESSION['perfil_editado']) && $_SESSION['perfil_editado'] === true ? 100 : 0
    ]
];

// Contar conquistas desbloqueadas
$conquistas_desbloqueadas = 0;
foreach ($conquistas as $conquista) {
    if ($conquista['desbloqueada']) {
        $conquistas_desbloqueadas++;
    }
}
$progresso_conquistas = count($conquistas) > 0 ? round(($conquistas_desbloqueadas / count($conquistas)) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Antifish Angola</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --accent: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --level-color: <?=$cor?>;
            --level-light: <?=$cor_light?>;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 20% 80%, rgba(79, 70, 229, 0.15) 0%, transparent 50%),
                       radial-gradient(circle at 80% 20%, rgba(236, 72, 153, 0.15) 0%, transparent 50%),
                       linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        
        .profile-container { max-width: 1200px; margin: 0 auto; }
        
        /* Header */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .back-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
        
        .header-title { text-align: center; flex-grow: 1; }
        
        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(90deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        
        .header-title p { opacity: 0.8; font-size: 1.1rem; }
        
        .edit-main-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
        }
        
        .edit-main-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.6);
        }
        
        /* Layout Principal */
        .profile-main {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        /* Card de Perfil */
        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 25px;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid var(--level-color);
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
        }
        
        .profile-avatar:hover { 
            transform: scale(1.05); 
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.5); 
        }
        
        .profile-avatar img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        
        .level-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--level-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border: 3px solid var(--dark);
        }
        
        .user-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: white;
        }
        
        .user-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .user-level {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        
        /* FORMULÁRIO DE EDIÇÃO */
        .edit-form {
            display: none;
            text-align: left;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .edit-form.active {
            display: block;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-title i {
            color: var(--primary);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.07);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-select option {
            background: var(--dark);
            color: white;
        }
        
        .avatar-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-top: 10px;
        }
        
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-top: 15px;
        }
        
        .avatar-option {
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            aspect-ratio: 1;
        }
        
        .avatar-option:hover {
            transform: scale(1.05);
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        .avatar-option.selected {
            border-color: var(--success);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }
        
        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .password-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-top: 10px;
        }
        
        .password-note {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 8px;
            font-style: italic;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .form-btn.save {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
        }
        
        .form-btn.save:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(79, 70, 229, 0.4);
        }
        
        .form-btn.cancel {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.15);
        }
        
        .form-btn.cancel:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
            color: white;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Progresso */
        .progress-section {
            margin: 30px 0;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .progress-bar {
            height: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--level-color), var(--accent));
            border-radius: 10px;
            transition: width 1s ease;
            box-shadow: 0 0 15px var(--level-color);
        }
        
        /* Botão de Reinício */
        .reset-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .reset-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 18px;
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .reset-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);
        }
        
        .reset-warning {
            font-size: 0.9rem;
            opacity: 0.7;
            text-align: center;
            line-height: 1.4;
        }
        
        /* Modal de Confirmação */
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        
        .confirmation-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        
        .modal-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--warning);
        }
        
        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
        }
        
        .modal-message {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .warning-list {
            text-align: left;
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .warning-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .warning-item:last-child {
            margin-bottom: 0;
        }
        
        .warning-icon {
            color: var(--warning);
            font-size: 1.1rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .modal-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-btn.confirm {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }
        
        .modal-btn.confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }
        
        .modal-btn.cancel {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .modal-btn.cancel:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Mensagens */
        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .message.success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        
        .message.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        /* Sidebar */
        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .sidebar-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .card-icon {
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* CONQUISTAS - NOVO DESIGN */
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        
        .achievement-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .achievement-card.unlocked {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.1);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.2);
        }
        
        .achievement-card.locked {
            opacity: 0.6;
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .achievement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .achievement-emoji {
            font-size: 2rem;
            margin-bottom: 10px;
            filter: drop-shadow(0 3px 5px rgba(0,0,0,0.3));
        }
        
        .achievement-name {
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: white;
        }
        
        .achievement-desc {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .achievement-progress {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .achievement-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 2px;
            transition: width 1s ease;
        }
        
        .achievement-card.unlocked .achievement-progress-bar {
            background: var(--success);
        }
        
        .achievement-progress-text {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 4px;
        }
        
        .achievement-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        .achievement-card.unlocked .achievement-badge {
            background: var(--success);
            color: white;
        }
        
        .achievement-card.locked .achievement-badge {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Progresso das Conquistas */
        .achievements-progress {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .achievements-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .achievements-count {
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
        }
        
        .achievements-total {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* Responsivo */
        @media (max-width: 900px) {
            .profile-main { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .profile-header { flex-direction: column; gap: 20px; text-align: center; }
            .form-grid { grid-template-columns: 1fr; }
            .avatar-grid { grid-template-columns: repeat(4, 1fr); }
            .achievements-grid { grid-template-columns: repeat(3, 1fr); }
        }
        
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr; }
            .profile-card { padding: 25px; }
            .modal-actions { flex-direction: column; }
            .avatar-grid { grid-template-columns: repeat(3, 1fr); }
            .form-actions { flex-direction: column; }
            .achievements-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 400px) {
            .achievements-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Modal de Confirmação -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content">
            <div class="modal-icon">⚠️</div>
            <h2 class="modal-title">REINICIAR PERFIL COMPLETO</h2>
            <p class="modal-message">
                Tem certeza ABSOLUTA que deseja reiniciar TODO o seu progresso?<br>
                <strong>Esta ação NÃO pode ser desfeita!</strong>
            </p>
            
            <div class="warning-list">
                <div class="warning-item">
                    <span class="warning-icon">🗑️</span>
                    <span><strong>Todos os pontos serão zerados</strong> (<?=$pontos?> pts)</span>
                </div>
                <div class="warning-item">
                    <span class="warning-icon">📊</span>
                    <span><strong>Ranking resetado</strong> (atual: #<?=$ranking_atual?>)</span>
                </div>
                <div class="warning-item">
                    <span class="warning-icon">🔥</span>
                    <span><strong>Sequência atual perdida</strong> (<?=$streak_atual?> acertos)</span>
                </div>
                <div class="warning-item">
                    <span class="warning-icon">📝</span>
                    <span><strong>Histórico completo apagado</strong> (<?=$quizzes?> quizzes)</span>
                </div>
                <div class="warning-item">
                    <span class="warning-icon">⭐</span>
                    <span><strong>Conquistas mantidas</strong> (<?=$conquistas_desbloqueadas?> desbloqueadas)</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button class="modal-btn cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <form method="post" style="display: inline;">
                    <button type="submit" name="reiniciar_jogo" value="1" class="modal-btn confirm">
                        <i class="fas fa-bomb"></i>
                        SIM, REINICIAR TUDO
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Voltar ao menu
            </a>
            
            <div class="header-title">
                <h1>MEU PERFIL</h1>
                <p>Gerencie sua conta e acompanhe seu progresso</p>
            </div>
            
            <?php if(!$edit_mode): ?>
                <a href="?edit=1" class="edit-main-btn">
                    <i class="fas fa-edit"></i>
                    Editar Perfil
                </a>
            <?php else: ?>
                <a href="perfil.php" class="edit-main-btn" style="background: linear-gradient(135deg, #6b7280, #9ca3af);">
                    <i class="fas fa-times"></i>
                    Cancelar Edição
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Conteúdo Principal -->
        <div class="profile-main">
            <!-- Card de Perfil -->
            <div class="profile-card">
                <?php if($msg): ?>
                    <div class="message <?=$msg_type?>">
                        <?=$msg?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center;">
                    <div class="avatar-container">
                        <div class="profile-avatar">
                            <img src="https://api.dicebear.com/7.x/<?=$user['avatar']?>/svg?seed=<?=urlencode($user['nome'])?>" alt="Avatar">
                        </div>
                        <div class="level-badge">
                            <?=$emoji?>
                        </div>
                    </div>
                    
                    <h2 class="user-name"><?=htmlspecialchars($user['nome'])?></h2>
                    
                    <div class="user-info">
                        <div class="info-item">
                            <i class="fas fa-<?=$user['genero'] == 'masculino' ? 'mars' : ($user['genero'] == 'feminino' ? 'venus' : 'genderless')?>"></i>
                            <span><?=ucfirst($user['genero'])?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-birthday-cake"></i>
                            <span><?=$user['idade']?> anos</span>
                        </div>
                        <?php if($streak_atual > 0): ?>
                        <div class="info-item">
                            <i class="fas fa-fire"></i>
                            <span>Streak: <?=$streak_atual?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-level">
                        <span><?=$emoji?></span>
                        <span>Nível <?=$nivel?></span>
                    </div>
                </div>
                
                <!-- FORMULÁRIO DE EDIÇÃO -->
                <form method="post" class="edit-form <?=$edit_mode ? 'active' : ''?>" id="editProfileForm">
                    <input type="hidden" name="action" value="editar_perfil">
                    
                    <div class="form-title">
                        <i class="fas fa-user-edit"></i>
                        Editar Informações do Perfil
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome" class="form-input" value="<?=htmlspecialchars($user['nome'])?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" value="<?=htmlspecialchars($user['email'])?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Idade</label>
                            <input type="number" name="idade" class="form-input" value="<?=$user['idade']?>" min="13" max="120">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Gênero</label>
                            <select name="genero" class="form-select">
                                <option value="masculino" <?=$user['genero'] == 'masculino' ? 'selected' : ''?>>Masculino</option>
                                <option value="feminino" <?=$user['genero'] == 'feminino' ? 'selected' : ''?>>Feminino</option>
                                <option value="outro" <?=$user['genero'] == 'outro' ? 'selected' : ''?>>Outro</option>
                                <option value="prefiro-nao-dizer" <?=$user['genero'] == 'prefiro-nao-dizer' ? 'selected' : ''?>>Prefiro não dizer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Avatar</label>
                        <div class="avatar-section">
                            <p style="margin-bottom: 10px; opacity: 0.8; font-size: 0.9rem;">
                                Selecione um novo avatar:
                            </p>
                            <div class="avatar-grid" id="avatarGrid">
                                <?php foreach($avatares as $avatar): ?>
                                    <div class="avatar-option <?=$avatar == $user['avatar'] ? 'selected' : ''?>" 
                                         data-avatar="<?=$avatar?>"
                                         onclick="selectAvatar(this)">
                                        <img src="https://api.dicebear.com/7.x/<?=$avatar?>/svg?seed=<?=urlencode($user['nome'])?>" 
                                             class="avatar-img" 
                                             alt="<?=$avatar?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="avatar" id="selectedAvatar" value="<?=$user['avatar']?>">
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Alterar Senha (opcional)</label>
                        <div class="password-section">
                            <p style="margin-bottom: 15px; opacity: 0.8; font-size: 0.9rem;">
                                Deixe em branco se não quiser alterar a senha.
                            </p>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                                <div>
                                    <label class="form-label" style="font-size: 0.85rem;">Senha Atual</label>
                                    <input type="password" name="senha_atual" class="form-input" placeholder="Senha atual">
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 0.85rem;">Nova Senha</label>
                                    <input type="password" name="nova_senha" class="form-input" placeholder="Nova senha">
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 0.85rem;">Confirmar</label>
                                    <input type="password" name="confirmar_senha" class="form-input" placeholder="Confirmar senha">
                                </div>
                            </div>
                            <p class="password-note">
                                A senha deve ter no mínimo 6 caracteres. Para alterar, preencha todos os 3 campos.
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="form-btn cancel" onclick="window.location.href='perfil.php'">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="form-btn save">
                            <i class="fas fa-save"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
                
                <!-- ESTATÍSTICAS (aparece quando NÃO está editando) -->
                <?php if(!$edit_mode): ?>
                <div id="profileStats" style="text-align: center;">
                    <!-- Estatísticas -->
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?=$pontos?></div>
                            <div class="stat-label">Pontos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?=$quizzes?></div>
                            <div class="stat-label">Quizzes</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">#<?=$ranking_atual?></div>
                            <div class="stat-label">Ranking</div>
                        </div>
                    </div>
                    
                    <!-- Progresso -->
                    <div class="progress-section">
                        <div class="progress-header">
                            <span>Progresso para <?=$proximo_nivel?></span>
                            <span><?=$pontos?>/<?=$proximo_nivel == "Máximo" ? "MAX" : ($proximo_nivel == "Guardião" ? "200" : ($proximo_nivel == "Mestre" ? "500" : "1000"))?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?=$progresso?>%"></div>
                        </div>
                        <div class="progress-text">
                            <?php if($proximo_nivel != "Máximo"): ?>
                                Faltam <?=$pontos_restantes?> pontos para <?=$proximo_nivel?>
                            <?php else: ?>
                                Nível máximo alcançado! 🎉
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Seção de Reinício -->
                    <div class="reset-section">
                        <button type="button" onclick="showResetModal()" class="reset-btn">
                            <i class="fas fa-bomb"></i>
                            REINICIAR PERFIL COMPLETO
                        </button>
                        <p class="reset-warning">
                            ⚠️ Esta ação irá ZERAR TODAS as suas estatísticas: pontuação, ranking, nível, histórico e progresso!
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <?php if(!$edit_mode): ?>
            <div class="profile-sidebar">
                <!-- Informações da Conta -->
                <div class="sidebar-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <span>Informações da Conta</span>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Email:</span>
                            <span style="font-weight: 600;"><?=htmlspecialchars($user['email'])?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Gênero:</span>
                            <span style="font-weight: 600;"><?=ucfirst($user['genero'])?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Idade:</span>
                            <span style="font-weight: 600;"><?=$user['idade']?> anos</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Avatar:</span>
                            <span style="font-weight: 600;"><?=ucfirst(str_replace('-', ' ', $user['avatar']))?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Sequência Atual:</span>
                            <span style="font-weight: 600; color: #f59e0b;"><?=$streak_atual?> acertos</span>
                        </div>
                    </div>
                </div>
                
                <!-- Conquistas - NOVO DESIGN -->
                <div class="sidebar-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <span>Conquistas</span>
                    </div>
                    
                    <div class="achievements-grid">
                        <?php 
                        // Mostrar apenas 6 conquistas principais (ou as primeiras 6)
                        $conquistas_mostradas = array_slice($conquistas, 0, 6);
                        foreach ($conquistas_mostradas as $conquista): 
                        ?>
                            <div class="achievement-card <?=$conquista['desbloqueada'] ? 'unlocked' : 'locked'?>">
                                <div class="achievement-badge">
                                    <?=$conquista['desbloqueada'] ? '✓' : '?'?>
                                </div>
                                <div class="achievement-emoji">
                                    <?=$conquista['emoji']?>
                                </div>
                                <div class="achievement-name">
                                    <?=$conquista['nome']?>
                                </div>
                                <div class="achievement-desc">
                                    <?=$conquista['descricao']?>
                                </div>
                                <div class="achievement-progress">
                                    <div class="achievement-progress-bar" style="width: <?=$conquista['progresso']?>%"></div>
                                </div>
                                <div class="achievement-progress-text">
                                    <?=round($conquista['progresso'])?>%
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Progresso das Conquistas -->
                    <div class="achievements-progress">
                        <div class="achievements-stats">
                            <div class="achievements-count">
                                <?=$conquistas_desbloqueadas?> / <?=count($conquistas)?>
                            </div>
                            <div class="achievements-total">
                                <?=$progresso_conquistas?>% completado
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?=$progresso_conquistas?>%; background: linear-gradient(90deg, #f59e0b, #ec4899);"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Ranking e Status -->
                <div class="sidebar-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span>Ranking & Status</span>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Posição Global:</span>
                            <span style="font-weight: 600; color: var(--warning);">#<?=$ranking_atual?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Total de Usuários:</span>
                            <span style="font-weight: 600;"><?=$total_usuarios?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Seu Percentil:</span>
                            <span style="font-weight: 600;">Top <?=$percentil_ranking?>%</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Nível Atual:</span>
                            <span style="font-weight: 600; color: var(--level-color);"><?=$nivel?> <?=$emoji?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Pontos:</span>
                            <span style="font-weight: 600; color: #10b981;"><?=$pontos?> pts</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="opacity: 0.8;">Quizzes:</span>
                            <span style="font-weight: 600;"><?=$quizzes?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Modal de Confirmação
        function showResetModal() {
            document.getElementById('confirmationModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('confirmationModal').classList.remove('active');
        }

        // Fechar modal ao clicar fora
        document.getElementById('confirmationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Seleção de avatar no formulário
        function selectAvatar(element) {
            // Remover seleção anterior
            document.querySelectorAll('.avatar-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Adicionar seleção nova
            element.classList.add('selected');
            
            // Atualizar campo oculto
            const avatar = element.getAttribute('data-avatar');
            document.getElementById('selectedAvatar').value = avatar;
            
            // Efeito visual
            element.style.transform = 'scale(1.1)';
            setTimeout(() => {
                element.style.transform = 'scale(1.05)';
            }, 200);
        }

        // Efeitos visuais para o perfil
        document.addEventListener('DOMContentLoaded', function() {
            // Animação suave da barra de progresso
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                const computedStyle = getComputedStyle(progressFill);
                const finalWidth = computedStyle.width;
                
                progressFill.style.width = '0';
                setTimeout(() => {
                    progressFill.style.width = finalWidth;
                }, 500);
            }
            
            // Validação do formulário
            const editForm = document.getElementById('editProfileForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    const senhaAtual = document.querySelector('input[name="senha_atual"]').value;
                    const novaSenha = document.querySelector('input[name="nova_senha"]').value;
                    const confirmarSenha = document.querySelector('input[name="confirmar_senha"]').value;
                    
                    // Se algum campo de senha foi preenchido
                    if (senhaAtual || novaSenha || confirmarSenha) {
                        // Verificar se TODOS foram preenchidos
                        if (!senhaAtual || !novaSenha || !confirmarSenha) {
                            e.preventDefault();
                            alert('Para alterar a senha, é necessário preencher todos os 3 campos de senha.');
                            return;
                        }
                        
                        // Validar comprimento da nova senha
                        if (novaSenha.length < 6) {
                            e.preventDefault();
                            alert('A nova senha deve ter pelo menos 6 caracteres.');
                            return;
                        }
                        
                        // Validar se as senhas coincidem
                        if (novaSenha !== confirmarSenha) {
                            e.preventDefault();
                            alert('A nova senha e a confirmação não coincidem.');
                            return;
                        }
                    }
                });
            }
            
            // Efeitos nos inputs do formulário
            const formInputs = document.querySelectorAll('.form-input, .form-select');
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
        });
        
        // Auto-foco no primeiro campo quando entrar no modo edição
        <?php if($edit_mode): ?>
        setTimeout(() => {
            const firstInput = document.querySelector('.form-input');
            if (firstInput) {
                firstInput.focus();
            }
        }, 300);
        <?php endif; ?>
    </script>
</body>
</html>