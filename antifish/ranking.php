<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

try {
    $pdo = new PDO("mysql:host=localhost;dbname=antifish;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("Erro de conexão.");
}

// Obter ranking global
$stmt = $pdo->query("SELECT id, nome, pontuacao, avatar, nivel FROM usuarios ORDER BY pontuacao DESC");
$ranking = $stmt->fetchAll();

// Obter dados do usuário atual para destacar
$stmt = $pdo->prepare("SELECT pontuacao, nome FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario_atual = $stmt->fetch();
$posicao_usuario = 1;

// Encontrar posição do usuário atual
foreach($ranking as $i => $r) {
    if ($r['id'] == $_SESSION['user_id']) {
        $posicao_usuario = $i + 1;
        break;
    }
}

// Estatísticas
$total_jogadores = count($ranking);
$top_3 = array_slice($ranking, 0, 3);
$restantes = array_slice($ranking, 3);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking Global - Antifish Angola</title>
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
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
            --diamond: #b9f2ff;
            --dark: #0f172a;
            --light-dark: #1e293b;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: 
                radial-gradient(circle at 20% 80%, rgba(79, 70, 229, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(236, 72, 153, 0.15) 0%, transparent 50%),
                linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        
        .ranking-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .ranking-header {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }
        
        .back-btn {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
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
            transform: translateY(-50%) translateX(-5px);
        }
        
        .header-title h1 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .header-title p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Pódio */
        .podium-section {
            margin-bottom: 60px;
        }
        
        .podium-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 40px;
            color: white;
        }
        
        .podium-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 30px;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            padding-top: 80px;
        }
        
        .podium-item {
            text-align: center;
            flex: 1;
            position: relative;
        }
        
        /* Posições do pódio */
        .podium-item:nth-child(1) { order: 2; } /* 1º lugar no meio */
        .podium-item:nth-child(2) { order: 1; } /* 2º lugar à esquerda */
        .podium-item:nth-child(3) { order: 3; } /* 3º lugar à direita */
        
        .podium-rank {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            z-index: 10;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border: 4px solid var(--dark);
        }
        
        .podium-item:nth-child(1) .podium-rank {
            background: linear-gradient(135deg, var(--gold), #ffed4e);
            color: #b8860b;
        }
        
        .podium-item:nth-child(2) .podium-rank {
            background: linear-gradient(135deg, var(--silver), #e8e8e8);
            color: #808080;
        }
        
        .podium-item:nth-child(3) .podium-rank {
            background: linear-gradient(135deg, var(--bronze), #d9a066);
            color: #8b4513;
        }
        
        .podium-platform {
            border-radius: 20px 20px 0 0;
            padding: 30px 20px 20px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .podium-item:nth-child(1) .podium-platform {
            height: 220px;
            background: linear-gradient(180deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.05));
            border-color: rgba(255, 215, 0, 0.3);
        }
        
        .podium-item:nth-child(2) .podium-platform {
            height: 180px;
            background: linear-gradient(180deg, rgba(192, 192, 192, 0.2), rgba(192, 192, 192, 0.05));
            border-color: rgba(192, 192, 192, 0.3);
        }
        
        .podium-item:nth-child(3) .podium-platform {
            height: 160px;
            background: linear-gradient(180deg, rgba(205, 127, 50, 0.2), rgba(205, 127, 50, 0.05));
            border-color: rgba(205, 127, 50, 0.3);
        }
        
        .podium-platform:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.4);
        }
        
        .podium-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            border: 4px solid;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .podium-item:nth-child(1) .podium-avatar {
            border-color: var(--gold);
        }
        
        .podium-item:nth-child(2) .podium-avatar {
            border-color: var(--silver);
        }
        
        .podium-item:nth-child(3) .podium-avatar {
            border-color: var(--bronze);
        }
        
        .podium-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .podium-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: white;
        }
        
        .podium-points {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--warning);
        }
        
        /* Tabela de Ranking */
        .ranking-table-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            margin-bottom: 40px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .table-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
        }
        
        .total-players {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .ranking-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        
        .ranking-table thead {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
        }
        
        .ranking-table th {
            padding: 20px;
            text-align: left;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            border: none;
        }
        
        .ranking-table th:first-child {
            border-radius: 12px 0 0 12px;
            padding-left: 30px;
        }
        
        .ranking-table th:last-child {
            border-radius: 0 12px 12px 0;
        }
        
        .ranking-table tbody tr {
            background: rgba(255, 255, 255, 0.03);
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        
        .ranking-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .ranking-table tbody tr.current-user {
            background: rgba(79, 70, 229, 0.15);
            border-left: 4px solid var(--primary);
        }
        
        .ranking-table tbody tr.current-user:hover {
            background: rgba(79, 70, 229, 0.25);
        }
        
        .ranking-table td {
            padding: 25px 20px;
            border: none;
            color: white;
        }
        
        .ranking-table td:first-child {
            border-radius: 12px 0 0 12px;
            padding-left: 30px;
        }
        
        .ranking-table td:last-child {
            border-radius: 0 12px 12px 0;
        }
        
        .rank-cell {
            font-size: 1.3rem;
            font-weight: 800;
            text-align: center;
            width: 80px;
        }
        
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .rank-1 { background: linear-gradient(135deg, var(--gold), #ffed4e); color: #b8860b; }
        .rank-2 { background: linear-gradient(135deg, var(--silver), #e8e8e8); color: #808080; }
        .rank-3 { background: linear-gradient(135deg, var(--bronze), #d9a066); color: #8b4513; }
        .rank-4-10 { background: rgba(255, 255, 255, 0.15); color: white; }
        .rank-other { background: rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.7); }
        
        .player-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .player-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .player-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .player-details {
            display: flex;
            flex-direction: column;
        }
        
        .player-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .player-level {
            font-size: 0.9rem;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .level-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        
        .medal-cell {
            text-align: center;
        }
        
        .medal {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.08);
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .medal-icon {
            font-size: 1.8rem;
        }
        
        .medal-name {
            font-size: 0.95rem;
        }
        
        .points-cell {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--warning);
            text-align: right;
            padding-right: 30px;
        }
        
        /* Seção do Usuário Atual */
        .user-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .user-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .user-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .user-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .user-stat {
            text-align: center;
            padding: 25px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .user-stat:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 0.95rem;
            opacity: 0.8;
        }
        
        .position-stat .stat-value {
            color: var(--primary);
        }
        
        .points-stat .stat-value {
            color: var(--warning);
        }
        
        .percentile-stat .stat-value {
            color: var(--success);
        }
        
        /* Responsivo */
        @media (max-width: 900px) {
            .podium-container {
                flex-direction: column;
                align-items: center;
                gap: 40px;
                padding-top: 0;
            }
            
            .podium-item {
                width: 100%;
                max-width: 300px;
            }
            
            .podium-item:nth-child(1) { order: 1; }
            .podium-item:nth-child(2) { order: 2; }
            .podium-item:nth-child(3) { order: 3; }
            
            .podium-rank {
                top: -40px;
                width: 70px;
                height: 70px;
            }
            
            .ranking-table {
                display: block;
                overflow-x: auto;
            }
            
            .user-stats {
                grid-template-columns: 1fr;
            }
            
            .ranking-table th,
            .ranking-table td {
                white-space: nowrap;
            }
        }
        
        @media (max-width: 600px) {
            .header-title h1 {
                font-size: 2.5rem;
            }
            
            .ranking-table-section {
                padding: 25px;
            }
            
            .player-info {
                gap: 10px;
            }
            
            .player-avatar {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="ranking-container">
        <!-- Header -->
        <div class="ranking-header">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Voltar ao Jogo
            </a>
            
            <div class="header-title">
                <h1>RANKING GLOBAL</h1>
                <p>Competição entre os melhores jogadores de Angola</p>
            </div>
        </div>
        
        <?php if(count($top_3) > 0): ?>
        <!-- Pódio -->
        <div class="podium-section">
            <h2 class="podium-title">🏆 TOP 3 JOGADORES 🏆</h2>
            <div class="podium-container">
                <?php foreach($top_3 as $i => $jogador): ?>
                <div class="podium-item">
                    <div class="podium-rank">
                        <?= $i+1 ?>º
                    </div>
                    <div class="podium-platform">
                        <div class="podium-avatar">
                            <img src="https://api.dicebear.com/7.x/<?= $jogador['avatar'] ?? 'bottts' ?>/svg?seed=<?= urlencode($jogador['nome']) ?>" alt="Avatar">
                        </div>
                        <div class="podium-name"><?= htmlspecialchars($jogador['nome']) ?></div>
                        <div class="podium-points"><?= number_format($jogador['pontuacao'], 0, ',', '.') ?> pts</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Tabela de Ranking -->
        <div class="ranking-table-section">
            <div class="table-header">
                <h2 class="table-title">📊 CLASSIFICAÇÃO COMPLETA</h2>
                <div class="total-players">
                    <i class="fas fa-users"></i>
                    <span><?= $total_jogadores ?> Jogadores</span>
                </div>
            </div>
            
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Posição</th>
                        <th>Jogador</th>
                        <th style="width: 180px;">Medalha</th>
                        <th style="width: 120px; text-align: right;">Pontuação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ranking as $i => $jogador): 
                        $posicao = $i + 1;
                        $is_current_user = $jogador['id'] == $_SESSION['user_id'];
                        
                        // Determinar medalha
                        if ($jogador['pontuacao'] < 50) {
                            $medalha = "Novato"; $emoji = "🌱";
                        } elseif ($jogador['pontuacao'] < 100) {
                            $medalha = "Bronze"; $emoji = "🥉";
                        } elseif ($jogador['pontuacao'] < 300) {
                            $medalha = "Prata"; $emoji = "🥈";
                        } elseif ($jogador['pontuacao'] < 600) {
                            $medalha = "Ouro"; $emoji = "🥇";
                        } else {
                            $medalha = "Diamante"; $emoji = "💎";
                        }
                        
                        // Classe da posição
                        $rank_class = 'rank-other';
                        if ($posicao == 1) $rank_class = 'rank-1';
                        elseif ($posicao == 2) $rank_class = 'rank-2';
                        elseif ($posicao == 3) $rank_class = 'rank-3';
                        elseif ($posicao <= 10) $rank_class = 'rank-4-10';
                    ?>
                    <tr <?= $is_current_user ? 'class="current-user"' : '' ?>>
                        <td class="rank-cell">
                            <div class="rank-badge <?= $rank_class ?>">
                                <?= $posicao ?>º
                            </div>
                        </td>
                        <td>
                            <div class="player-info">
                                <div class="player-avatar">
                                    <img src="https://api.dicebear.com/7.x/<?= $jogador['avatar'] ?? 'bottts' ?>/svg?seed=<?= urlencode($jogador['nome']) ?>" alt="Avatar">
                                </div>
                                <div class="player-details">
                                    <div class="player-name"><?= htmlspecialchars($jogador['nome']) ?></div>
                                    <div class="player-level">
                                        <span>Nível <?= $jogador['nivel'] ?? 1 ?></span>
                                        <span class="level-badge">⭐</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="medal-cell">
                            <div class="medal">
                                <span class="medal-icon"><?= $emoji ?></span>
                                <span class="medal-name"><?= $medalha ?></span>
                            </div>
                        </td>
                        <td class="points-cell">
                            <?= number_format($jogador['pontuacao'], 0, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Seção do Usuário Atual -->
        <div class="user-section">
            <div class="user-header">
                <div class="user-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2 class="user-title">SUA POSIÇÃO NO RANKING</h2>
            </div>
            
            <div class="user-stats">
                <div class="user-stat position-stat">
                    <div class="stat-value">#<?= $posicao_usuario ?></div>
                    <div class="stat-label">Posição Global</div>
                </div>
                
                <div class="user-stat points-stat">
                    <div class="stat-value"><?= number_format($usuario_atual['pontuacao'], 0, ',', '.') ?></div>
                    <div class="stat-label">Pontuação Total</div>
                </div>
                
                <div class="user-stat percentile-stat">
                    <div class="stat-value"><?= $total_jogadores > 0 ? round(($posicao_usuario / $total_jogadores) * 100) : 100 ?>%</div>
                    <div class="stat-label">Top Percentil</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animação suave ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Animar elementos do pódio
            const podiumItems = document.querySelectorAll('.podium-item');
            podiumItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(50px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.8s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, 300 * (index + 1));
            });
            
            // Animar linhas da tabela
            const tableRows = document.querySelectorAll('.ranking-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-30px)';
                
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 100 * index);
            });
            
            // Efeito de destaque no usuário atual
            const currentUserRow = document.querySelector('.current-user');
            if (currentUserRow) {
                setInterval(() => {
                    currentUserRow.style.boxShadow = '0 0 30px rgba(79, 70, 229, 0.5)';
                    setTimeout(() => {
                        currentUserRow.style.boxShadow = '';
                    }, 1000);
                }, 3000);
            }
            
            // Efeitos de hover nas medalhas
            const medals = document.querySelectorAll('.medal');
            medals.forEach(medal => {
                medal.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                    this.style.transition = 'transform 0.3s ease';
                });
                
                medal.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
        
        // Scroll suave para o usuário atual
        function scrollToUser() {
            const userRow = document.querySelector('.current-user');
            if (userRow) {
                userRow.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Efeito visual
                userRow.style.animation = 'pulse 1s';
                setTimeout(() => {
                    userRow.style.animation = '';
                }, 1000);
            }
        }
        
        // Adicionar CSS para animação de pulso
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7); }
                70% { box-shadow: 0 0 0 20px rgba(79, 70, 229, 0); }
                100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
            }
        `;
        document.head.appendChild(style);
        
        // Botão para localizar usuário
        setTimeout(() => {
            const userSection = document.querySelector('.user-section');
            if (userSection) {
                const locateButton = document.createElement('button');
                locateButton.innerHTML = '<i class="fas fa-location-arrow"></i> Localizar Minha Posição';
                locateButton.style.cssText = `
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    background: linear-gradient(135deg, var(--primary), var(--accent));
                    color: white;
                    border: none;
                    padding: 12px 25px;
                    border-radius: 10px;
                    font-weight: 600;
                    cursor: pointer;
                    margin-top: 15px;
                    transition: all 0.3s ease;
                `;
                locateButton.onmouseenter = () => locateButton.style.transform = 'translateY(-3px)';
                locateButton.onmouseleave = () => locateButton.style.transform = 'translateY(0)';
                locateButton.onclick = scrollToUser;
                
                userSection.appendChild(locateButton);
            }
        }, 1000);
    </script>
</body>
</html>