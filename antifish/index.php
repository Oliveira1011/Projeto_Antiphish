<?php
session_start();
if (isset($_SESSION['user_id'])) { 
    header("Location: dashboard.php"); 
    exit; 
}

$pdo = new PDO("mysql:host=localhost;dbname=antifish;charset=utf8mb4", "root", "");
$msg = "";

if ($_POST && isset($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $genero = $_POST['genero'];
    $idade = intval($_POST['idade']);
    $avatar = $_POST['avatar'] ?? 'default';

    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $msg = "Este email já está registado!";
    } elseif (strlen($senha) < 6) {
        $msg = "A senha deve ter pelo menos 6 caracteres!";
    } elseif ($idade < 13 || $idade > 100) {
        $msg = "Idade deve ser entre 13 e 100 anos!";
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, genero, idade, avatar) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$nome, $email, $hash, $genero, $idade, $avatar])) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['nome'] = $nome;
            header("Location: dashboard.php");
            exit;
        } else {
            $msg = "Erro no registo!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Antifish Angola</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --accent: #ec4899;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .logo h1 {
            font-size: 2.2rem;
            font-weight: 900;
            background: linear-gradient(90deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        
        .logo p {
            opacity: 0.8;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        
        .form-select option {
            background: #1e293b;
            color: white;
        }
        
        .avatar-selection {
            margin: 25px 0;
        }
        
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .avatar-option {
            text-align: center;
            cursor: pointer;
            padding: 15px 10px;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .avatar-option:hover {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }
        
        .avatar-option.selected {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.2);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        .avatar-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 10px;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .avatar-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-option.selected .avatar-img {
            border-color: var(--primary);
        }
        
        .avatar-name {
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.6);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            opacity: 0.8;
        }
        
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 600px) {
            .auth-card {
                padding: 30px 25px;
            }
            
            .avatar-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>ANTIFISH ANGOLA</h1>
                <p>Crie sua conta</p>
            </div>
            
            <?php if($msg): ?>
                <div class="message">
                    <?=$msg?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="registerForm">
                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="nome" class="form-input" placeholder="Seu nome" required minlength="2" value="<?=htmlspecialchars($_POST['nome'] ?? '')?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="seu@email.com" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Gênero</label>
                        <select name="genero" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="masculino" <?=($_POST['genero'] ?? '') == 'masculino' ? 'selected' : ''?>>Masculino</option>
                            <option value="feminino" <?=($_POST['genero'] ?? '') == 'feminino' ? 'selected' : ''?>>Feminino</option>
                            <option value="outro" <?=($_POST['genero'] ?? '') == 'outro' ? 'selected' : ''?>>Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Idade</label>
                        <input type="number" name="idade" class="form-input" placeholder="18" min="13" max="100" required value="<?=htmlspecialchars($_POST['idade'] ?? '')?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-input" placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>
                
                <div class="avatar-selection">
                    <label class="form-label">Escolha seu Avatar</label>
                    <div class="avatar-grid" id="avatarGrid">
                        <?php
                        $avatars = ['avataaars', 'micah', 'miniavs', 'open-peeps', 'personas', 'bottts', 'jdenticon', 'gridy', 'identicon'];
                        $avatar_names = [
                            'avataaars' => 'Humano',
                            'micah' => 'Micah', 
                            'miniavs' => 'Mini',
                            'open-peeps' => 'Peeps',
                            'personas' => 'Persona',
                            'bottts' => 'Robô',
                            'jdenticon' => 'Geométrico',
                            'gridy' => 'Grid',
                            'identicon' => 'Identicon'
                        ];
                        
                        foreach($avatars as $avatar):
                        ?>
                        <div class="avatar-option" data-avatar="<?=$avatar?>">
                            <div class="avatar-img">
                                <img src="https://api.dicebear.com/7.x/<?=$avatar?>/svg?seed=<?=urlencode($_POST['nome'] ?? 'User')?>" alt="Avatar">
                            </div>
                            <div class="avatar-name"><?=$avatar_names[$avatar]?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="avatar" id="selectedAvatar" value="<?=$_POST['avatar'] ?? 'avataaars'?>" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i>
                    CRIAR CONTA
                </button>
            </form>
            
            <div class="login-link">
                Já tem uma conta? <a href="login.php">Faça login</a>
            </div>
        </div>
    </div>

    <script>
        // Sistema de seleção de avatar
        document.addEventListener('DOMContentLoaded', function() {
            const avatarOptions = document.querySelectorAll('.avatar-option');
            const selectedAvatar = document.getElementById('selectedAvatar');
            
            // Selecionar avatar padrão
            const defaultAvatar = '<?=$_POST['avatar'] ?? 'avataaars'?>';
            selectAvatar(defaultAvatar);
            
            avatarOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const avatar = this.getAttribute('data-avatar');
                    selectAvatar(avatar);
                });
            });
            
            function selectAvatar(avatar) {
                // Remover seleção anterior
                avatarOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Adicionar seleção atual
                const currentOption = document.querySelector(`[data-avatar="${avatar}"]`);
                if (currentOption) {
                    currentOption.classList.add('selected');
                    selectedAvatar.value = avatar;
                    
                    // Atualizar todas as imagens de avatar
                    updateAvatarImages(avatar);
                }
            }
            
            function updateAvatarImages(avatarType) {
                const nome = document.querySelector('input[name="nome"]').value || 'User';
                const avatarImgs = document.querySelectorAll('.avatar-img img');
                
                avatarImgs.forEach(img => {
                    const currentAvatar = img.closest('.avatar-option').getAttribute('data-avatar');
                    img.src = `https://api.dicebear.com/7.x/${currentAvatar}/svg?seed=${encodeURIComponent(nome)}`;
                });
            }
            
            // Atualizar avatares quando o nome mudar
            document.querySelector('input[name="nome"]').addEventListener('input', function() {
                const selectedAvatar = document.getElementById('selectedAvatar').value;
                updateAvatarImages(selectedAvatar);
            });
            
            // Validação do formulário
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const idade = document.querySelector('input[name="idade"]').value;
                const genero = document.querySelector('select[name="genero"]').value;
                
                if (!genero) {
                    e.preventDefault();
                    alert('Por favor, selecione seu gênero.');
                    return;
                }
                
                if (idade < 13 || idade > 100) {
                    e.preventDefault();
                    alert('A idade deve ser entre 13 e 100 anos.');
                    return;
                }
            });
        });
    </script>
</body>
</html>