<?php
/**
 * SGC-AgentOne Plug & Play - Point d'entrée intelligent
 * Auto-détecte l'environnement et calcule les chemins dynamiquement
 * Compatible: Hébergement web, XAMPP, LAMP, MAMP
 */

// Configuration d'erreurs pour production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Détection intelligente de l'environnement et des chemins
function detectEnvironment() {
    $info = [
        'current_dir' => dirname(__FILE__),
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
        'is_localhost' => false,
        'base_url' => '',
        'sgc_path' => '',
        'interface_url' => ''
    ];
    
    // Détection localhost/XAMPP/LAMP/MAMP
    if (in_array($info['server_name'], ['localhost', '127.0.0.1']) || 
        strpos($info['server_name'], 'localhost') !== false) {
        $info['is_localhost'] = true;
    }
    
    // Calcul du chemin de base depuis l'URL
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    $info['base_url'] = $scriptPath;
    
    // Adaptation selon que ce script est dans sgc-agentone/ ou à la racine
    if (basename($info['current_dir']) === 'plugandplay') {
        // Script dans deployment/plugandplay/ - remonter vers sgc-agentone/
        $info['sgc_path'] = $scriptPath . '/../../..';
        $info['interface_url'] = $info['sgc_path'] . '/extensions/vscode/src/webview/chat.html';
    } else {
        // Script copié à la racine de sgc-agentone/
        $info['sgc_path'] = $scriptPath;
        $info['interface_url'] = $info['sgc_path'] . '/extensions/vscode/src/webview/chat.html';
    }
    
    return $info;
}

// Détection de l'environnement
$env = detectEnvironment();

// Vérification de l'installation SGC-AgentOne
$coreCheck = $env['current_dir'] . '/core/router.php';
$extensionsCheck = $env['current_dir'] . '/extensions/vscode/src/webview/chat.html';

// Si on est dans plugandplay, chercher au bon endroit
if (basename($env['current_dir']) === 'plugandplay') {
    $coreCheck = dirname($env['current_dir'], 2) . '/core/router.php';
    $extensionsCheck = dirname($env['current_dir'], 2) . '/extensions/vscode/src/webview/chat.html';
}

if (!file_exists($coreCheck) || !file_exists($extensionsCheck)) {
    // Affichage d'erreur avec diagnostic
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SGC-AgentOne - Configuration</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
            .error { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; }
            .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
            pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>🔌 SGC-AgentOne Plug & Play</h1>
        
        <div class="error">
            <h3>❌ Installation SGC-AgentOne non détectée</h3>
            <p>Les fichiers SGC-AgentOne ne sont pas trouvés dans cette configuration.</p>
        </div>
        
        <div class="info">
            <h3>🔍 Diagnostic de l'environnement</h3>
            <pre><?php print_r($env); ?></pre>
            
            <h4>Fichiers recherchés :</h4>
            <ul>
                <li><code><?php echo $coreCheck; ?></code> <?php echo file_exists($coreCheck) ? '✅' : '❌'; ?></li>
                <li><code><?php echo $extensionsCheck; ?></code> <?php echo file_exists($extensionsCheck) ? '✅' : '❌'; ?></li>
            </ul>
        </div>
        
        <div class="success">
            <h3>📋 Instructions d'installation</h3>
            <ol>
                <li><strong>Copiez ce fichier</strong> dans le dossier racine de SGC-AgentOne</li>
                <li><strong>Ou copiez tout SGC-AgentOne</strong> selon votre environnement :
                    <ul>
                        <li><strong>XAMPP :</strong> <code>C:\xampp\htdocs\sgc-agentone\</code></li>
                        <li><strong>LAMP :</strong> <code>/var/www/html/sgc-agentone/</code></li>
                        <li><strong>MAMP :</strong> <code>/Applications/MAMP/htdocs/sgc-agentone/</code></li>
                        <li><strong>Hébergement :</strong> <code>public_html/sgc-agentone/</code></li>
                    </ul>
                </li>
                <li><strong>Lancez le script d'installation :</strong> <code>install.php</code></li>
            </ol>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Redirection vers l'interface SGC-AgentOne
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$fullUrl = $protocol . '://' . $env['server_name'] . $env['interface_url'];

// Headers pour redirection propre
header('HTTP/1.1 302 Found');
header('Location: ' . $env['interface_url']);
header('Cache-Control: no-cache, no-store, must-revalidate');

// Message de fallback si la redirection échoue
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGC-AgentOne</title>
    <meta http-equiv="refresh" content="2; url=<?php echo $env['interface_url']; ?>">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; background: #f5f5f5; }
        .container { background: white; padding: 50px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 SGC-AgentOne</h1>
        <div class="loader"></div>
        <p>Redirection vers l'interface...</p>
        <p><a href="<?php echo $env['interface_url']; ?>">Cliquez ici si la redirection ne fonctionne pas</a></p>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666;">
            <strong>Environnement détecté :</strong><br>
            <?php echo $env['is_localhost'] ? '🖥️ Local' : '🌐 Web'; ?> • 
            <?php echo $env['server_name']; ?><br>
            <strong>Interface :</strong> <code><?php echo $env['interface_url']; ?></code>
        </div>
    </div>
</body>
</html>