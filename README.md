MANUAL DE INSTALAÇÃO E EXECUÇÃO DO PROJETO ANTIFISH
Este manual explica como instalar e executar o projeto Antifish, um sistema de quiz educacional sobre phishing e engenharia social, desenvolvido em PHP com banco de dados MySQL. O projeto inclui páginas como login, dashboard, quiz e perfil.
Assuma que você tem um ambiente de desenvolvimento ou servidor web. Se for local, use ferramentas como XAMPP, WAMP ou MAMP. Para produção, use um servidor como Hostinger, AWS ou VPS com LAMP stack.
Requisitos
•	Servidor Web: Apache(Xamp) ou Nginx.
•	PHP: Versão 7.4 ou superior (recomendado 8.1+).
•	Banco de Dados: MySQL 5.7+ ou MariaDB.
•	Extensões PHP: pdo, pdo_mysql, mbstring, json.
•	Outros: Acesso a um navegador web; opcional: Composer para dependências (não obrigatório aqui).
•	Espaço em Disco: Mínimo 50MB.
•	Sistema Operacional: Windows, Linux ou macOS.
Passo 1: Baixar ou Clonar o Projeto
1.	Crie uma pasta para o projeto, ex: antifish
2.	Copie todos os arquivos PHP fornecidos (como quiz.php, perfil.php, dashboard.php, index.php) para essa pasta.
o	Se tiver um repositório Git, clone:
text
https://github.com/Oliveira1011/Projeto_Antiphish.git




Passo 2: Configurar o Banco de Dados
1.	Acesse o MySQL via terminal ou phpMyAdmin:
o	Crie o banco:
text
mysql -u root -p
CREATE DATABASE antifish CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
2.	Importe as tabelas (copie e execute o SQL fornecido nas conversas anteriores para usuarios, perguntas e respostas).
3.	Insira as perguntas: Execute os INSERT INTO perguntas (phishing e engenharia social, IDs 46-105).
Exemplo de criação de tabelas (cole no phpMyAdmin ou terminal):
SQL
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    -- ... (demais campos como pontuacao, etc.)
);

CREATE TABLE perguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta TEXT NOT NULL,
    a TEXT NOT NULL,
    b TEXT NOT NULL,
    c TEXT NOT NULL,
    d TEXT NOT NULL,
    correta CHAR(1) NOT NULL,
    explicacao TEXT NOT NULL,
    dificuldade ENUM('facil','medio','dificil') DEFAULT 'medio',
    categoria VARCHAR(50) DEFAULT 'phishing'
);

CREATE TABLE respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    acertou TINYINT(1) NOT NULL,
    data_resposta DATETIME DEFAULT CURRENT_TIMESTAMP
);
•	Adicione índices e chaves estrangeiras se necessário.
Passo 3: Configurar a Conexão com o Banco
1.	Crie config/db.php:
PHP
<?php
$host = 'localhost';
$dbname = 'antifish';
$user = 'root';  // Altere para seu usuário
$pass = '';      // Altere para sua senha

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
2.	Inclua isso em todos os arquivos PHP que precisam de BD:
PHP
require_once 'config/db.php';
Passo 4: Configurar o Servidor Local (Desenvolvimento)
•	Usando XAMPP (Windows/macOS):
1.	Baixe e instale XAMPP (xampp.org).
2.	Inicie Apache e MySQL no painel de controle.
3.	Coloque a pasta do projeto em C:\xampp\htdocs\ (Windows) ou /Applications/XAMPP/htdocs/ (macOS).
4.	Acesse via navegador: http://localhost/antifish/.
•	Usando Docker (opcional, para ambientes isolados):
1.	Crie um Dockerfile simples:
text
FROM php:8.1-apache
RUN docker-php-ext-install pdo_mysql
COPY . /var/www/html/
2.	Use docker-compose.yml para MySQL:
text
version: '3'
services:
  web:
    build: .
    ports: ["80:80"]
    volumes: [".:/var/www/html"]
  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: antifish
3.	Rode: docker-compose up -d.
4.	Acesse: http://localhost/.
Passo 5: Configurar em Servidor de Produção
1.	Faça upload via FTP ou SSH para /var/www/html/antifish/ (ou subdomínio).
2.	Configure o virtual host no Apache:
o	Edite /etc/apache2/sites-available/000-default.conf:
text
<VirtualHost *:80>
    ServerName antifish.seudominio.ao
    DocumentRoot /var/www/html/antifish
    <Directory /var/www/html/antifish>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
o	Ative: a2enmod rewrite && systemctl restart apache2.
3.	Configure HTTPS (recomendado): Use Certbot (certbot --apache).
4.	Permissões: chown -R www-data:www-data /var/www/html/antifish e chmod -R 755 /var/www/html/antifish.
Passo 6: Executar e Testar
1.	Acesse o site: http://localhost/antifish-angola/index.php (ou URL de produção).
2.	Crie uma conta (implemente cadastro no index.php se não tiver).
3.	Teste fluxos:
o	Login → Dashboard → Quiz (verifique embaralhamento de opções).
o	Perfil: Edite e verifique atualizações.
4.	Debug erros: Verifique logs do PHP (error_log) ou ative erros no código: ini_set('display_errors', 1);.
Passo 7: Manutenção e Segurança
•	Backup: Use mysqldump -u root -p antifish > backup.sql.
•	Segurança: Use prepared statements (já no PDO), hash senhas com password_hash, valide entradas.
•	Atualizações: Mantenha PHP/MySQL atualizados.
•	Problemas comuns:
o	Erro de conexão: Verifique credenciais em db.php.
o	Perguntas não carregam: Verifique INSERTs no BD.
o	Embaralhamento falha: Certifique-se da função embaralharOpcoes() em quiz.php.
