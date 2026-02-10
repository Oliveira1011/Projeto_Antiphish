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

$pontos = $user['pontuacao'] ?? 0;
$quizzes = $user['quizzes_feitos'] ?? 0;
$taxa = $quizzes > 0 ? round(($pontos / ($quizzes * 10)) * 100) : 0;

// Sistema de níveis com tema de jogo
if ($pontos < 200) {
    $nivel = "Iniciante"; $emoji = "🛡️"; $cor = "#4ade80"; $next_level = 200;
} elseif ($pontos < 500) {
    $nivel = "Guardião"; $emoji = "⚔️"; $cor = "#3b82f6"; $next_level = 500;
} elseif ($pontos < 1000) {
    $nivel = "Mestre"; $emoji = "👑"; $cor = "#f59e0b"; $next_level = 1000;
} else {
    $nivel = "Lenda"; $emoji = "🌟"; $cor = "#8b5cf6"; $next_level = "MAX";
}

// Progresso para próximo nível
$progresso = $next_level === "MAX" ? 100 : ($pontos / $next_level) * 100;
$pontos_restantes = $next_level === "MAX" ? 0 : $next_level - $pontos;

// CORREÇÃO: Posição no ranking - Consulta corrigida
// Método 1: Usando subconsulta
$stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 
    FROM usuarios 
    WHERE pontuacao > ? OR (pontuacao = ? AND id < ?)
");
$stmt->execute([$pontos, $pontos, $_SESSION['user_id']]);
$posicao = $stmt->fetchColumn();

// Método alternativo 2: Trazendo todo o ranking e encontrando a posição
// $stmt = $pdo->query("SELECT id, pontuacao FROM usuarios ORDER BY pontuacao DESC");
// $ranking = $stmt->fetchAll();
// $posicao = 1;
// foreach($ranking as $i => $r) {
//     if ($r['id'] == $_SESSION['user_id']) {
//         $posicao = $i + 1;
//         break;
//     }
// }

// Se ainda tiver problema, usar esta consulta mais robusta:
if (!$posicao || $posicao < 1) {
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) + 1 
             FROM usuarios u2 
             WHERE u2.pontuacao > u1.pontuacao) as posicao
        FROM usuarios u1
        WHERE u1.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $posicao = $result['posicao'] ?? 1;
}

// Conquistas
$conquistas = [
    ['icon' => '🏆', 'nome' => 'Primeiro Quiz', 'desc' => 'Complete seu primeiro quiz', 'concluida' => $quizzes >= 1],
    ['icon' => '⭐', 'nome' => 'Estrela Nascente', 'desc' => 'Alcance 100 pontos', 'concluida' => $pontos >= 100],
    ['icon' => '🔥', 'nome' => 'Sequência Quente', 'desc' => 'Complete 5 quizzes', 'concluida' => $quizzes >= 5],
    ['icon' => '💯', 'nome' => 'Perfeccionista', 'desc' => 'Taxa de acertos de 90%+', 'concluida' => $taxa >= 90],
];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antifish Angola – Jogo</title>
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
            --level-bg: <?=$cor?>;
        }
        
        * { 
            margin:0; 
            padding:0; 
            box-sizing:border-box; 
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: 
                radial-gradient(circle at 20% 80%, rgba(79, 70, 229, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(236, 72, 153, 0.15) 0%, transparent 50%),
                linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
        }
        
        .game-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }
        
        /* Header do Jogo */
        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .game-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .game-logo-icon {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .game-logo-text {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(90deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .player-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .player-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid var(--level-bg);
            overflow: hidden;
            box-shadow: 0 0 20px var(--level-bg);
        }
        
        .player-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .player-stats {
            text-align: right;
        }
        
        .player-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .player-level {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--level-bg);
            font-weight: 600;
        }
        
        /* Área Principal do Jogo */
        .game-main {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        /* Card de Missão Principal */
        .mission-card {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(236, 72, 153, 0.1));
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            z-index: 0;
        }
        
        .mission-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .mission-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .mission-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(90deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .mission-description {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .play-button {
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
        
        .play-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(79, 70, 229, 0.6);
        }
        
        /* Stats Sidebar */
        .stats-sidebar {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            font-size: 1.5rem;
            color: var(--level-bg);
        }
        
        .stat-title {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        /* Barra de Progresso */
        .progress-container {
            margin-top: 10px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--level-bg), var(--accent));
            border-radius: 10px;
            transition: width 1s ease;
            box-shadow: 0 0 10px var(--level-bg);
        }
        
        /* Seção de Conquistas */
        .achievements-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .achievement-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }
        
        .achievement-card.locked {
            opacity: 0.5;
        }
        
        .achievement-card.unlocked {
            border-color: var(--success);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }
        
        .achievement-icon {
            font-size: 2rem;
        }
        
        .achievement-info {
            flex-grow: 1;
        }
        
        .achievement-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .achievement-desc {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Ações Rápidas */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .action-title {
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .action-desc {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
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
        
        /* Responsivo */
        @media (max-width: 900px) {
            .game-main {
                grid-template-columns: 1fr;
            }
            
            .game-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .player-info {
                justify-content: center;
            }
            
            .player-stats {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <!-- Header do Jogo -->
        <div class="game-header">
            <div class="game-logo">
                <div class="game-logo-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="game-logo-text">ANTIFISH ANGOLA</div>
            </div>
            
            <div class="player-info">
                <div class="player-avatar">
                    <!-- CORREÇÃO: Usar o avatar selecionado pelo usuário -->
                    <img src="https://api.dicebear.com/7.x/<?=$user['avatar']?>/svg?seed=<?=urlencode($user['nome'])?>" alt="Avatar">
                </div>
                <div class="player-stats">
                    <div class="player-name"><?=htmlspecialchars($user['nome'])?></div>
                    <div class="player-level">
                        <span><?=$emoji?></span>
                        <span>Nível <?=$nivel?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Área Principal -->
        <div class="game-main">
            <!-- Missão Principal -->
            <div class="mission-card">
                <div class="mission-content">
                    <div class="mission-icon float">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <h2 class="mission-title">Missão Principal</h2>
                    <p class="mission-description">
                        Teste seus conhecimentos sobre phishing e proteja Angola contra ciberataques. 
                        Cada resposta correta fortalece suas defesas e aumenta seu ranking!
                    </p>
                    <a href="quiz.php" class="play-button pulse">
                        <i class="fas fa-play"></i>
                        <span>INICIAR MISSÃO</span>
                    </a>
                </div>
            </div>
            
            <!-- Sidebar de Stats -->
            <div class="stats-sidebar">
                <!-- Pontuação -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-title">Pontuação Total</div>
                    </div>
                    <div class="stat-value"><?=$pontos?></div>
                    <div class="stat-label">Sua pontuação acumulada</div>
                </div>
                
                <!-- Progresso do Nível -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-title">Progresso do Nível</div>
                    </div>
                    <div class="progress-container">
                        <div class="progress-info">
                            <span>Nível <?=$nivel?></span>
                            <span><?=$pontos?>/<?=$next_level === "MAX" ? "MAX" : $next_level?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?=$progresso?>%"></div>
                        </div>
                    </div>
                    <div class="stat-label">
                        <?php if($next_level !== "MAX"): ?>
                            Faltam <?=$pontos_restantes?> pontos para <?=$nivel == "Iniciante" ? "Guardião" : ($nivel == "Guardião" ? "Mestre" : "Lenda")?>
                        <?php else: ?>
                            Nível máximo alcançado!
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Ranking -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-title">Ranking Global</div>
                    </div>
                    <div class="stat-value">#<?=$posicao?></div>
                    <div class="stat-label">Sua posição no ranking</div>
                </div>
            </div>
        </div>
        
        <!-- Conquistas -->
        <div class="achievements-section">
            <h2 class="section-title">
                <i class="fas fa-trophy"></i>
                <span>Conquistas</span>
            </h2>
            
            <div class="achievements-grid">
                <?php foreach($conquistas as $conquista): ?>
                <div class="achievement-card <?=$conquista['concluida'] ? 'unlocked' : 'locked'?>">
                    <div class="achievement-icon">
                        <?=$conquista['icon']?>
                    </div>
                    <div class="achievement-info">
                        <div class="achievement-name"><?=$conquista['nome']?></div>
                        <div class="achievement-desc"><?=$conquista['desc']?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Ações Rápidas -->
        <div class="quick-actions">
            <a href="ranking.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-ranking-star"></i>
                </div>
                <div class="action-title">Ranking</div>
                <div class="action-desc">Veja sua posição entre os jogadores</div>
            </a>
            
            <a href="perfil.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="action-title">Perfil</div>
                <div class="action-desc">Personalize seu perfil de jogador</div>
            </a>
            
            <a href="tutorial.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="action-title">Tutorial</div>
                <div class="action-desc">Aprenda sobre phishing</div>
            </a>
            
            <a href="logout.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="action-title">Sair</div>
                <div class="action-desc">Sair do jogo</div>
            </a>
        </div>
    </div>

    <script>
        // Efeitos visuais para o jogo
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar efeito de digitação ao título da missão
            const missionTitle = document.querySelector('.mission-title');
            const originalText = missionTitle.textContent;
            missionTitle.textContent = '';
            
            let i = 0;
            function typeWriter() {
                if (i < originalText.length) {
                    missionTitle.textContent += originalText.charAt(i);
                    i++;
                    setTimeout(typeWriter, 50);
                }
            }
            
            setTimeout(typeWriter, 1000);
            
            // Efeito de confetti ao carregar a página
            setTimeout(() => {
                createConfetti();
            }, 1500);
            
            function createConfetti() {
                const colors = ['#4f46e5', '#ec4899', '#10b981', '#f59e0b', '#8b5cf6'];
                const confettiCount = 50;
                
                for (let i = 0; i < confettiCount; i++) {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'fixed';
                    confetti.style.width = '10px';
                    confetti.style.height = '10px';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.borderRadius = '50%';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.top = '-10px';
                    confetti.style.opacity = '0.8';
                    confetti.style.zIndex = '9999';
                    document.body.appendChild(confetti);
                    
                    // Animação
                    const animation = confetti.animate([
                        { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                        { transform: `translateY(${window.innerHeight + 100}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                    ], {
                        duration: 2000 + Math.random() * 3000,
                        easing: 'cubic-bezier(0.1, 0.8, 0.3, 1)'
                    });
                    
                    animation.onfinish = () => confetti.remove();
                }
            }
            
            // Depuração: Verificar posição no ranking
            console.log('Posição no ranking: #<?=$posicao?>');
            console.log('Pontuação: <?=$pontos?>');
        });
    </script>
</body>
</html>