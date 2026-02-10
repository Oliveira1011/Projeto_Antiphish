<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'admin') { header("Location: index.php"); exit; }

$pdo = new PDO("mysql:host=localhost;dbname=antifish;charset=utf8mb4", "root", "");
$total = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Antifish</title>
    <style>
        body{font-family:Arial;background:#f0f2f5;padding:30px;}
        .box{max-width:700px;margin:0 auto;background:white;padding:40px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.2);}
        h1{color:#764ba2;text-align:center;}
        .btn{background:#e67e22;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;display:inline-block;margin:10px;}
    </style>
</head>
<body>
<div class="box">
    <h1>Painel Administrador</h1>
    <p>Total de usuários: <strong><?=$total?></strong></p>
    <p><a href="dashboard.php" class="btn">Dashboard</a></p>
</div>
</body>
</html>