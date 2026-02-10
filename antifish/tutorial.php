<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

try {
    $pdo = new PDO("mysql:host=localhost;dbname=antifish;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("Erro de conexão.");
}

// Obter dados do usuário
$stmt = $pdo->prepare("SELECT nome, avatar FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Conteúdo dos tutoriais
$tutoriais = [
    [
        'id' => 'o-que-e-phishing',
        'titulo' => 'O que é Phishing?',
        'icone' => '🎣',
        'descricao' => 'Aprenda o conceito básico de phishing e como identificar ataques',
        'conteudo' => '
            <h3>🎣 O Que é Phishing?</h3>
            <p>Phishing é um tipo de ataque cibernético onde criminosos se passam por entidades legítimas para enganar vítimas e obter informações sensíveis como:</p>
            <ul>
                <li>🔑 Senhas e credenciais de login</li>
                <li>💳 Números de cartão de crédito</li>
                <li>🆔 Documentos pessoais</li>
                <li>🏦 Dados bancários</li>
            </ul>
            
            <h4>📈 Como Funciona:</h4>
            <div class="processo">
                <div class="etapa">
                    <span class="num">1</span>
                    <span class="text">O atacante cria um site ou email falso</span>
                </div>
                <div class="etapa">
                    <span class="num">2</span>
                    <span class="text">A vítima recebe uma mensagem convincente</span>
                </div>
                <div class="etapa">
                    <span class="num">3</span>
                    <span class="text">A vítima clica no link e insere dados</span>
                </div>
                <div class="etapa">
                    <span class="num">4</span>
                    <span class="text">Os dados são roubados pelo atacante</span>
                </div>
            </div>
            
            <h4>🎭 Táticas Comuns:</h4>
            <ul>
                <li><strong>Imitação de marcas famosas</strong> (bancos, redes sociais)</li>
                <li><strong>Urgência artificial</strong> ("Sua conta será bloqueada!")</li>
                <li><strong>Ofertas irreais</strong> (prêmios, descontos)</li>
                <li><strong>Links encurtados</strong> para esconder URLs suspeitas</li>
            </ul>
        '
    ],
    [
        'id' => 'tipos-phishing',
        'titulo' => 'Tipos de Phishing',
        'icone' => '🕵️',
        'descricao' => 'Conheça as diferentes formas de ataques de phishing',
        'conteudo' => '
            <h3>🕵️ Tipos de Phishing</h3>
            
            <div class="tipo-card email">
                <h4>📧 Email Phishing</h4>
                <p>O tipo mais comum. Ataques via email que parecem legítimos.</p>
                <ul>
                    <li>Emails falsos de bancos</li>
                    <li>Notificações de redes sociais</li>
                    <li>Faturas ou boletos falsos</li>
                </ul>
            </div>
            
            <div class="tipo-card smishing">
                <h4>📱 Smishing (SMS Phishing)</h4>
                <p>Ataques via mensagens SMS/WhatsApp.</p>
                <ul>
                    <li>Links maliciosos por SMS</li>
                    <li>Códigos de verificação falsos</li>
                    <li>Promoções por WhatsApp</li>
                </ul>
            </div>
            
            <div class="tipo-card spear">
                <h4>🎯 Spear Phishing</h4>
                <p>Ataques personalizados para uma pessoa específica.</p>
                <ul>
                    <li>Usam informações pessoais</li>
                    <li>Direcionados a funcionários</li>
                    <li>Mais difíceis de detectar</li>
                </ul>
            </div>
            
            <div class="tipo-card vishing">
                <h4>📞 Vishing (Voice Phishing)</h4>
                <p>Ataques por telefone.</p>
                <ul>
                    <li>Ligações falsas de suporte</li>
                    <li>Falsos técnicos de informática</li>
                    <li>Golpes de "parente em apuros"</li>
                </ul>
            </div>
            
            <div class="tipo-card whaling">
                <h4>🐋 Whaling</h4>
                <p>Ataques a executivos de alto nível.</p>
                <ul>
                    <li>Direcionado a CEOs e diretores</li>
                    <li>Pedidos de transferências urgentes</li>
                    <li>Falsos emails corporativos</li>
                </ul>
            </div>
        '
    ],
    [
        'id' => 'identificar-phishing',
        'titulo' => 'Como Identificar',
        'icone' => '🔍',
        'descricao' => 'Aprenda a detectar tentativas de phishing',
        'conteudo' => '
            <h3>🔍 Como Identificar Phishing</h3>
            
            <h4>📧 Verifique o Remetente:</h4>
            <ul>
                <li>✅ Email oficial: contato@banco.com</li>
                <li>❌ Email suspeito: suporte-banco@gmail.com</li>
                <li>❌ Domínio estranho: banco-seguranca.net</li>
            </ul>
            
            <h4>🔗 Analise os Links:</h4>
            <div class="dica-importante">
                <p><strong>Passe o mouse sobre os links</strong> para ver o destino real antes de clicar!</p>
            </div>
            <ul>
                <li>www.banco.com ✅</li>
                <li>www.banco-seguro.xyz ❌</li>
                <li>banco-login.secure-page.com ❌</li>
            </ul>
            
            <h4>📝 Procure por Erros:</h4>
            <ul>
                <li>Erros de ortografia e gramática</li>
                <li>Formatação estranha</li>
                <li>Logos de baixa qualidade</li>
            </ul>
            
            <h4>⚠️ Sinais de Alerta:</h4>
            <div class="sinais-grid">
                <div class="sinal">
                    <span class="emoji">🚨</span>
                    <span>URGÊNCIA excessiva</span>
                </div>
                <div class="sinal">
                    <span class="emoji">🎁</span>
                    <span>Ofertas muito boas</span>
                </div>
                <div class="sinal">
                    <span class="emoji">🔒</span>
                    <span>Solicitação de dados</span>
                </div>
                <div class="sinal">
                    <span class="emoji">📎</span>
                    <span>Anexos suspeitos</span>
                </div>
            </div>
            
            <h4>🧪 Teste Rápido:</h4>
            <div class="quiz-rapido">
                <p><strong>Um email do seu banco pede para clicar em um link para "atualizar seus dados por segurança". O que fazer?</strong></p>
                <ul>
                    <li>✅ Acessar diretamente o site oficial do banco</li>
                    <li>✅ Ligar para o banco usando número oficial</li>
                    <li>❌ Clicar no link do email</li>
                    <li>❌ Responder ao email com seus dados</li>
                </ul>
            </div>
        '
    ],
    [
        'id' => 'prevencao',
        'titulo' => 'Prevenção e Proteção',
        'icone' => '🛡️',
        'descricao' => 'Métodos para se proteger contra ataques de phishing',
        'conteudo' => '
            <h3>🛡️ Prevenção e Proteção</h3>
            
            <h4>🛡️ Boas Práticas Essenciais:</h4>
            
            <div class="pratica">
                <h5>🔐 Use Autenticação de Dois Fatores (2FA)</h5>
                <p>Sempre ative 2FA em todos os serviços importantes. Mesmo que sua senha seja roubada, o atacante não conseguirá acessar.</p>
            </div>
            
            <div class="pratica">
                <h5>🔄 Atualizações Constantes</h5>
                <p>Mantenha seu sistema operacional, navegador e antivírus sempre atualizados.</p>
            </div>
            
            <div class="pratica">
                <h5>🔍 Verifique URLs</h5>
                <p>Sempre verifique se o site usa HTTPS (cadeado verde) e se o domínio está correto.</p>
            </div>
            
            <h4>📱 No Celular:</h4>
            <ul>
                <li>⏬ Baixe apps apenas das lojas oficiais</li>
                <li>🔒 Use bloqueio de tela</li>
                <li>📵 Desconfie de mensagens não solicitadas</li>
            </ul>
            
            <h4>💻 No Computador:</h4>
            <ul>
                <li>🛡️ Use antivírus confiável</li>
                <li>🌐 Navegador com proteção anti-phishing</li>
                <li>🔍 Extensões de segurança</li>
            </ul>
            
            <h4>🔐 Senhas Seguras:</h4>
            <div class="senhas-grid">
                <div class="senha-boa">
                    <h5>✅ Senha Forte</h5>
                    <p>Ang0l@2024#Segur@</p>
                    <small>Maiúsculas, minúsculas, números, símbolos</small>
                </div>
                <div class="senha-ruim">
                    <h5>❌ Senha Fraca</h5>
                    <p>password123</p>
                    <small>Comum, sequencial, sem símbolos</small>
                </div>
            </div>
            
            <h4>📞 O Que Fazer Se For Vítima:</h4>
            <ol>
                <li>Mude todas as senhas imediatamente</li>
                <li>Contate a instituição falsificada</li>
                <li>Monitore suas contas bancárias</li>
                <li>Denuncie às autoridades</li>
            </ol>
        '
    ],
    [
        'id' => 'exemplos-reais',
        'titulo' => 'Exemplos Reais',
        'icone' => '📊',
        'descricao' => 'Casos reais de phishing para aprender com exemplos',
        'conteudo' => '
            <h3>📊 Exemplos Reais de Phishing</h3>
            
            <h4>🏦 Caso 1: Banco Falso</h4>
            <div class="exemplo-card">
                <div class="email-falso">
                    <div class="email-header">
                        <span class="remetente">suporte@bancodeangola-seguranca.com</span>
                        <span class="assunto">URGENTE: Sua conta será bloqueada!</span>
                    </div>
                    <div class="email-body">
                        <p>Prezado cliente,</p>
                        <p>Detectamos atividade suspeita em sua conta. Para evitar o bloqueio permanente, clique no link abaixo para verificar seus dados:</p>
                        <p><a href="#">www.bancodeangola-verificacao.com/login</a></p>
                        <p><em>Este link expira em 24 horas.</em></p>
                    </div>
                </div>
                <div class="analise">
                    <h5>🔍 Análise:</h5>
                    <ul>
                        <li>❌ Domínio falso (não é o oficial do banco)</li>
                        <li>❌ Tom de urgência artificial</li>
                        <li>❌ Solicitação de dados sensíveis</li>
                        <li>✅ Banco de Angola não pede dados por email</li>
                    </ul>
                </div>
            </div>
            
            <h4>📱 Caso 2: SMS de Prêmio</h4>
            <div class="exemplo-card">
                <div class="sms-falso">
                    <div class="sms-header">
                        <span class="numero">+244 900 123 456</span>
                    </div>
                    <div class="sms-body">
                        <p>🎉 PARABÉNS! Você ganhou um iPhone 15! Clique para resgatar: bit.ly/premio-iphone-angola</p>
                    </div>
                </div>
                <div class="analise">
                    <h5>🔍 Análise:</h5>
                    <ul>
                        <li>❌ Ofertas irreais</li>
                        <li>❌ Link encurtado (esconde destino)</li>
                        <li>❌ Número não oficial</li>
                        <li>✅ Prêmios legítimos usam canais oficiais</li>
                    </ul>
                </div>
            </div>
            
            <h4>📧 Caso 3: Email de Rede Social</h4>
            <div class="exemplo-card">
                <div class="email-falso">
                    <div class="email-header">
                        <span class="remetente">facebook-security@gmail.com</span>
                        <span class="assunto">Alerta de Segurança - Conta Comprometida</span>
                    </div>
                    <div class="email-body">
                        <p>Olá, detectamos um login suspeito na sua conta do Facebook de Angola. Para proteger sua conta, faça login:</p>
                        <p><a href="#">facebook-seguranca-login.com/verify</a></p>
                        <p>Se não fizer login em 48 horas, sua conta será desativada.</p>
                    </div>
                </div>
                <div class="analise">
                    <h5>🔍 Análise:</h5>
                    <ul>
                        <li>❌ Gmail não é usado pelo Facebook oficial</li>
                        <li>❌ Domínio diferente de facebook.com</li>
                        <li>❌ Ameaça de desativação</li>
                        <li>✅ Facebook envia alertas dentro do app/site</li>
                    </ul>
                </div>
            </div>
            
            <h4>📈 Estatísticas em Angola:</h4>
            <div class="estatisticas">
                <div class="estat">
                    <span class="valor">73%</span>
                    <span class="label">Empresas angolanas sofreram tentativas</span>
                </div>
                <div class="estat">
                    <span class="valor">42%</span>
                    <span class="label">Funcionários clicam em links suspeitos</span>
                </div>
                <div class="estat">
                    <span class="valor">28%</span>
                    <span class="label">Vítimas relatam prejuízos financeiros</span>
                </div>
            </div>
        '
    ],
    [
        'id' => 'recursos',
        'titulo' => 'Recursos Úteis',
        'icone' => '📚',
        'descricao' => 'Ferramentas e recursos para proteção adicional',
        'conteudo' => '
            <h3>📚 Recursos e Ferramentas Úteis</h3>
            
            <h4>🛠️ Ferramentas de Verificação:</h4>
            
            <div class="recurso">
                <h5>🌐 Verificador de URLs</h5>
                <ul>
                    <li><a href="https://transparencyreport.google.com/safe-browsing/search" target="_blank">Google Safe Browsing</a> - Verifica segurança de sites</li>
                    <li><a href="https://www.virustotal.com" target="_blank">VirusTotal</a> - Analisa URLs e arquivos</li>
                    <li><a href="https://urlscan.io" target="_blank">URLScan</a> - Inspeciona sites em busca de ameaças</li>
                </ul>
            </div>
            
            <div class="recurso">
                <h5>🔐 Gerenciadores de Senhas</h5>
                <ul>
                    <li><strong>Bitwarden</strong> - Gratuito e open source</li>
                    <li><strong>LastPass</strong> - Facilidade de uso</li>
                    <li><strong>1Password</strong> - Excelente para famílias</li>
                </ul>
            </div>
            
            <h4>📖 Materiais Educativos:</h4>
            
            <div class="recurso">
                <h5>🇦🇴 Para Angola:</h5>
                <ul>
                    <li>INAC - Instituto Nacional de Cibersegurança de Angola</li>
                    <li>BNA - Banco Nacional de Angola (alertas)</li>
                    <li>MITIC - Ministério das Telecomunicações</li>
                </ul>
            </div>
            
            <div class="recurso">
                <h5>🌍 Internacionais:</h5>
                <ul>
                    <li><a href="https://www.cisa.gov/stopransomware/phishing" target="_blank">CISA (EUA)</a> - Guias completos</li>
                    <li><a href="https://www.ncsc.gov.uk/phishing" target="_blank">NCSC (UK)</a> - Melhores práticas</li>
                    <li><a href="https://www.sans.org/security-awareness-training/" target="_blank">SANS Institute</a> - Treinamentos</li>
                </ul>
            </div>
            
            <h4>📱 Aplicativos Recomendados:</h4>
            
            <div class="apps-grid">
                <div class="app">
                    <span class="emoji">🛡️</span>
                    <h5>Antivírus</h5>
                    <p>Malwarebytes, Avast, Bitdefender</p>
                </div>
                <div class="app">
                    <span class="emoji">🔒</span>
                    <h5>VPN</h5>
                    <p>ProtonVPN, NordVPN (em redes públicas)</p>
                </div>
                <div class="app">
                    <span class="emoji">🌐</span>
                    <h5>Navegador</h5>
                    <p>Chrome/Firefox com extensões de segurança</p>
                </div>
            </div>
            
            <h4>📞 Contatos de Emergência em Angola:</h4>
            
            <div class="contatos">
                <div class="contato">
                    <span class="nome">Polícia Nacional</span>
                    <span class="numero">111</span>
                </div>
                <div class="contato">
                    <span class="nome">INAC (Cibersegurança)</span>
                    <span class="numero">+244 222 000 000</span>
                </div>
                <div class="contato">
                    <span class="nome">BNA (Fraudes Bancárias)</span>
                    <span class="numero">+244 222 330 000</span>
                </div>
            </div>
            
            <div class="dica-final">
                <h5>💡 Dica Final:</h5>
                <p><strong>Quando em dúvida, não clique!</strong> Sempre verifique por outros canais antes de tomar qualquer ação.</p>
            </div>
        '
    ]
];

// Determinar tutorial ativo
$tutorial_ativo = $_GET['tutorial'] ?? $tutoriais[0]['id'];
$tutorial_encontrado = array_filter($tutoriais, fn($t) => $t['id'] == $tutorial_ativo);
$tutorial_atual = reset($tutorial_encontrado) ?: $tutoriais[0];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorial - Antifish Angola</title>
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
        
        .tutorial-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .tutorial-header {
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
        
        .header-title {
            text-align: center;
            flex-grow: 1;
        }
        
        .header-title h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header-title p {
            opacity: 0.8;
            font-size: 1.1rem;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Layout Principal */
        .tutorial-main {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        /* Sidebar de Tópicos */
        .topics-sidebar {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            height: fit-content;
        }
        
        .topics-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }
        
        .topics-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .topic-item {
            padding: 18px 20px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
        }
        
        .topic-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .topic-item.active {
            background: rgba(79, 70, 229, 0.2);
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
        
        .topic-icon {
            font-size: 1.5rem;
            width: 40px;
            text-align: center;
        }
        
        .topic-info {
            flex-grow: 1;
        }
        
        .topic-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .topic-desc {
            font-size: 0.85rem;
            opacity: 0.7;
        }
        
        /* Área de Conteúdo */
        .content-area {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .content-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .content-icon {
            font-size: 2.5rem;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(79, 70, 229, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--primary);
        }
        
        .content-title h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: white;
        }
        
        .content-title p {
            opacity: 0.8;
            font-size: 1rem;
        }
        
        .content-body {
            line-height: 1.8;
            font-size: 1.05rem;
        }
        
        .content-body h3 {
            font-size: 1.5rem;
            margin: 30px 0 15px;
            color: var(--primary);
        }
        
        .content-body h4 {
            font-size: 1.2rem;
            margin: 25px 0 12px;
            color: var(--accent);
        }
        
        .content-body h5 {
            font-size: 1.1rem;
            margin: 20px 0 10px;
            color: var(--warning);
        }
        
        .content-body ul, .content-body ol {
            margin: 15px 0 15px 25px;
        }
        
        .content-body li {
            margin-bottom: 8px;
        }
        
        .content-body strong {
            color: var(--warning);
        }
        
        /* Estilos específicos para o conteúdo */
        .processo {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .etapa {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .etapa .num {
            display: block;
            width: 35px;
            height: 35px;
            background: var(--primary);
            border-radius: 50%;
            margin: 0 auto 10px;
            line-height: 35px;
            font-weight: 700;
        }
        
        .tipo-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        
        .tipo-card.email { border-left-color: #4f46e5; }
        .tipo-card.smishing { border-left-color: #ec4899; }
        .tipo-card.spear { border-left-color: #10b981; }
        .tipo-card.vishing { border-left-color: #f59e0b; }
        .tipo-card.whaling { border-left-color: #8b5cf6; }
        
        .dica-importante {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            font-weight: 600;
        }
        
        .sinais-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .sinal {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sinal .emoji {
            font-size: 1.5rem;
        }
        
        .quiz-rapido {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }
        
        .pratica {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .senhas-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .senha-boa, .senha-ruim {
            padding: 20px;
            border-radius: 12px;
        }
        
        .senha-boa {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .senha-ruim {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .exemplo-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .email-falso, .sms-falso {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
        }
        
        .email-header, .sms-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .remetente, .assunto, .numero {
            display: block;
            margin-bottom: 5px;
        }
        
        .analise {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .estatisticas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 25px 0;
        }
        
        .estat {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .estat .valor {
            display: block;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--accent);
            margin-bottom: 8px;
        }
        
        .recurso {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .apps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        
        .app {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .app .emoji {
            font-size: 2rem;
            display: block;
            margin-bottom: 10px;
        }
        
        .contatos {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
        }
        
        .contato {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .dica-final {
            background: rgba(79, 70, 229, 0.2);
            border: 1px solid var(--primary);
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        /* Progresso */
        .progress-section {
            margin: 30px 0;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 10px;
            transition: width 1s ease;
            box-shadow: 0 0 10px var(--primary);
        }
        
        /* Botões de Navegação */
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .nav-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .nav-btn.prev {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-btn.next {
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }
        
        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        /* Footer */
        .tutorial-footer {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-text {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-top: 15px;
        }
        
        /* Responsivo */
        @media (max-width: 900px) {
            .tutorial-main {
                grid-template-columns: 1fr;
            }
            
            .tutorial-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .topics-sidebar {
                order: 2;
            }
            
            .processo, .sinais-grid, .estatisticas, .apps-grid {
                grid-template-columns: 1fr;
            }
            
            .senhas-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 600px) {
            .content-area {
                padding: 25px;
            }
            
            .content-header {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="tutorial-container">
        <!-- Header -->
        <div class="tutorial-header">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Voltar ao Jogo
            </a>
            
            <div class="header-title">
                <h1>TUTORIAL DE SEGURANÇA</h1>
                <p>Aprenda a se proteger contra phishing em Angola</p>
            </div>
            
            <div class="user-avatar">
                <img src="https://api.dicebear.com/7.x/<?=$user['avatar']?>/svg?seed=<?=urlencode($user['nome'])?>" alt="Avatar">
            </div>
        </div>
        
        <!-- Conteúdo Principal -->
        <div class="tutorial-main">
            <!-- Sidebar de Tópicos -->
            <div class="topics-sidebar">
                <h2 class="topics-title">
                    <i class="fas fa-book"></i>
                    <span>Lições Disponíveis</span>
                </h2>
                
                <div class="topics-list">
                    <?php foreach($tutoriais as $tutorial): ?>
                    <a href="?tutorial=<?=$tutorial['id']?>" 
                       class="topic-item <?=$tutorial['id'] == $tutorial_ativo ? 'active' : ''?>">
                        <div class="topic-icon">
                            <?=$tutorial['icone']?>
                        </div>
                        <div class="topic-info">
                            <div class="topic-title"><?=$tutorial['titulo']?></div>
                            <div class="topic-desc"><?=$tutorial['descricao']?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Progresso -->
                <div class="progress-section">
                    <div class="progress-info">
                        <span>Progresso do Tutorial</span>
                        <span><?=array_search($tutorial_ativo, array_column($tutoriais, 'id')) + 1?>/<?=count($tutoriais)?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?=((array_search($tutorial_ativo, array_column($tutoriais, 'id')) + 1) / count($tutoriais)) * 100?>%"></div>
                    </div>
                    <div class="footer-text">
                        Complete todos os tutoriais para dominar a segurança digital!
                    </div>
                </div>
            </div>
            
            <!-- Área de Conteúdo -->
            <div class="content-area">
                <div class="content-header">
                    <div class="content-icon">
                        <?=$tutorial_atual['icone']?>
                    </div>
                    <div class="content-title">
                        <h2><?=$tutorial_atual['titulo']?></h2>
                        <p><?=$tutorial_atual['descricao']?></p>
                    </div>
                </div>
                
                <div class="content-body">
                    <?=$tutorial_atual['conteudo']?>
                </div>
                
                <!-- Navegação -->
                <div class="nav-buttons">
                    <?php 
                    $current_index = array_search($tutorial_ativo, array_column($tutoriais, 'id'));
                    $prev_tutorial = $current_index > 0 ? $tutoriais[$current_index - 1] : null;
                    $next_tutorial = $current_index < count($tutoriais) - 1 ? $tutoriais[$current_index + 1] : null;
                    ?>
                    
                    <?php if($prev_tutorial): ?>
                    <a href="?tutorial=<?=$prev_tutorial['id']?>" class="nav-btn prev">
                        <i class="fas fa-arrow-left"></i>
                        <span>Anterior: <?=$prev_tutorial['titulo']?></span>
                    </a>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    
                    <?php if($next_tutorial): ?>
                    <a href="?tutorial=<?=$next_tutorial['id']?>" class="nav-btn next">
                        <span>Próximo: <?=$next_tutorial['titulo']?></span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <?php else: ?>
                    <a href="dashboard.php" class="nav-btn next">
                        <span>Voltar ao Dashboard</span>
                        <i class="fas fa-home"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="tutorial-footer">
            <h3>🎓 Educação é a Melhor Proteção</h3>
            <p>Continue aprendendo e praticando para se tornar um especialista em segurança digital.</p>
            <div class="footer-text">
                <i class="fas fa-shield-alt"></i> Antifish Angola - Protegendo Angola contra ciberataques
            </div>
        </div>
    </div>

    <script>
        // Animações
        document.addEventListener('DOMContentLoaded', function() {
            // Animar entrada dos tópicos
            const topicItems = document.querySelectorAll('.topic-item');
            topicItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 100 * index);
            });
            
            // Animar conteúdo
            const contentBody = document.querySelector('.content-body');
            if (contentBody) {
                contentBody.style.opacity = '0';
                setTimeout(() => {
                    contentBody.style.transition = 'opacity 0.8s ease';
                    contentBody.style.opacity = '1';
                }, 300);
            }
            
            // Marcar tópico como lido
            const currentTopic = document.querySelector('.topic-item.active');
            if (currentTopic) {
                currentTopic.style.background = 'rgba(16, 185, 129, 0.2)';
                currentTopic.style.borderColor = 'var(--success)';
            }
            
            // Efeito de realce em elementos importantes
            const highlights = document.querySelectorAll('h3, h4, .dica-importante, .quiz-rapido');
            highlights.forEach((el, index) => {
                el.style.opacity = '0';
                setTimeout(() => {
                    el.style.transition = 'all 0.6s ease';
                    el.style.opacity = '1';
                }, 500 + (index * 100));
            });
        });
        
        // Salvar progresso (simulação)
        function markAsRead(tutorialId) {
            // Em um sistema real, salvaria no banco de dados
            localStorage.setItem(`tutorial_${tutorialId}_read`, 'true');
            
            // Feedback visual
            const topic = document.querySelector(`[href="?tutorial=${tutorialId}"]`);
            if (topic) {
                topic.classList.add('read');
                topic.innerHTML += '<span class="checkmark">✓</span>';
            }
        }
        
        // Marcar como lido automaticamente ao visualizar
        setTimeout(() => {
            markAsRead('<?=$tutorial_ativo?>');
        }, 3000);
        
        // Adicionar CSS para marcação de lido
        const style = document.createElement('style');
        style.textContent = `
            .topic-item.read {
                position: relative;
            }
            
            .topic-item.read::after {
                content: '✓';
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                background: var(--success);
                color: white;
                width: 25px;
                height: 25px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }
            
            .content-area {
                animation: pulse 2s ease-in-out;
            }
        `;
        document.head.appendChild(style);
        
        // Scroll suave para âncoras dentro do conteúdo
        document.querySelectorAll('.content-body a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            });
        });
    </script>
</body>
</html>