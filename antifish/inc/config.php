<?php
// === MOSTRAR ERROS (só enquanto desenvolve!) ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$host = 'localhost';
$db   = 'antifish';
$user = 'root';      // ← mude se o seu usuário do MySQL for diferente
$pass = '';          // ← coloque a senha do seu MySQL aqui (se tiver)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão com o banco: " . $e->getMessage());
}

// Funções de segurança
function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

function ehAdmin() {
    return estaLogado() && $_SESSION['nivel'] === 'admin';
}

function protegerPagina() {
    if (!estaLogado()) {
        header("Location: index.php");
        exit();
    }
}
?>