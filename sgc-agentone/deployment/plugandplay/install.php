<?php
/**
 * SGC-AgentOne Plug & Play - Script d'installation automatique universelle
 * Compatible: Hébergement web, XAMPP, LAMP, MAMP
 * Lance depuis le dossier sgc-agentone/ pour configurer l'environnement
 */

// Détection intelligente de l'environnement
function detectInstallEnvironment() {
    $env = [
        'script_dir' => dirname(__FILE__),
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
        'is_localhost' => false,
        'install_path' => '',
        'base_url' => ''
    ];
    
    // Détection localhost
    if (in_array($env['server_name'], ['localhost', '127.0.0.1']) || 
        strpos($env['server_name'], 'localhost') !== false) {
        $env['is_localhost'] = true;
    }
    
    // Calcul du chemin d'installation selon la position du script
    if (basename($env['script_dir']) === 'plugandplay') {
        // Script dans deployment/plugandplay/ - installer dans sgc-agentone/
        $env['install_path'] = dirname($env['script_dir'], 2);
        $env['base_url'] = dirname($_SERVER['SCRIPT_NAME'], 3);
    } else {
        // Script copié ailleurs - installer dans le dossier courant
        $env['install_path'] = $env['script_dir'];
        $env['base_url'] = dirname($_SERVER['SCRIPT_NAME']);
    }
    
    return $env;
}

// Configuration dynamique
$env = detectInstallEnvironment();
$installPath = $env['install_path'];

echo "🔌 <strong>SGC-AgentOne Plug & Play - Installation Automatique</strong><br><br>";
echo "<div style='background: #e8f4fd; padding: 10px; border-left: 4px solid #2196F3; margin-bottom: 15px;'>";
echo "✨ <strong>Compatible avec tous les environnements :</strong><br>";
echo "🌐 Hébergement web mutualisé • 🖥️ XAMPP • 🐧 LAMP • 🍎 MAMP";
echo "</div>";

// Vérification de l'environnement
function checkEnvironment() {
    $checks = [];
    
    // Vérification PHP
    $checks[] = [
        'test' => 'PHP Version >= 7.4',
        'result' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'details' => 'Version actuelle: ' . PHP_VERSION
    ];
    
    // Vérification SQLite3
    $checks[] = [
        'test' => 'Extension SQLite3',
        'result' => class_exists('SQLite3'),
        'details' => class_exists('SQLite3') ? SQLite3::version()['versionString'] : 'Non disponible'
    ];
    
    // Vérification PDO SQLite
    $checks[] = [
        'test' => 'PDO SQLite',
        'result' => extension_loaded('pdo_sqlite'),
        'details' => extension_loaded('pdo_sqlite') ? 'Disponible' : 'Non disponible'
    ];
    
    return $checks;
}

// Création des fichiers nécessaires
function createFiles($installPath) {
    $files = [];
    
    // Contenu .htaccess principal
    $htaccessContent = 'RewriteEngine On

# Protection des fichiers sensibles
<Files ~ "\.(json|log|sqlite3?)$">
    Deny from all
</Files>

# Protection du dossier data (base de données)
<IfModule mod_rewrite.c>
    RewriteRule ^data/ - [F,L]
</IfModule>

# Redirection de toutes les requêtes vers le router SGC-AgentOne
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ core/router.php [QSA,L]

# Cache désactivé pour développement
<IfModule mod_headers.c>
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires "0"
</IfModule>

# Optimisations PHP pour hébergement mutualisé
<IfModule mod_php.c>
    php_value max_execution_time 60
    php_value memory_limit 128M
    php_value post_max_size 16M
    php_value upload_max_filesize 16M
</IfModule>';

    // Contenu index.php principal avec détection intelligente
    $indexContent = '<?php
/**
 * SGC-AgentOne Plug & Play - Point d\'entrée intelligent
 * Auto-détecte l\'environnement et calcule les chemins dynamiquement
 */

// Configuration d\'erreurs pour production
error_reporting(E_ALL);
ini_set(\'display_errors\', 0);
ini_set(\'log_errors\', 1);

// Détection intelligente des chemins
$currentPath = dirname($_SERVER[\'SCRIPT_NAME\']);
$interfaceUrl = $currentPath . \'/extensions/webview/chat.html\';

// Vérification que l\'interface existe
$interfaceFile = __DIR__ . \'/extensions/webview/chat.html\';
if (!file_exists($interfaceFile)) {
    // Interface non trouvée - affichage d\'erreur avec diagnostic
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>SGC-AgentOne - Configuration</title>
        <style>body{font-family:Arial,sans-serif;margin:50px;background:#f5f5f5}.error{background:#fff3cd;border-left:4px solid #ffc107;padding:15px}</style>
    </head>
    <body>
        <h1>🔌 SGC-AgentOne Plug & Play</h1>
        <div class="error">
            <h3>❌ Configuration incomplète</h3>
            <p>L\'interface SGC-AgentOne n\'est pas trouvée à l\'emplacement attendu.</p>
            <p><strong>Fichier recherché :</strong> <?php echo $interfaceFile; ?></p>
            <p><strong>Répertoire actuel :</strong> <?php echo __DIR__; ?></p>
            <hr>
            <p><strong>Solution :</strong> Assurez-vous que tous les fichiers SGC-AgentOne sont présents dans ce dossier.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Headers pour redirection propre
header(\'HTTP/1.1 302 Found\');
header(\'Location: \' . $interfaceUrl);
header(\'Cache-Control: no-cache, no-store, must-revalidate\');

// Message de fallback si la redirection échoue
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGC-AgentOne</title>
    <meta http-equiv="refresh" content="2; url=<?php echo $interfaceUrl; ?>">
    <style>
        body{font-family:Arial,sans-serif;text-align:center;margin-top:100px;background:#f5f5f5}
        .container{background:white;padding:50px;border-radius:10px;display:inline-block;box-shadow:0 4px 6px rgba(0,0,0,0.1)}
        .loader{border:4px solid #f3f3f3;border-top:4px solid #007bff;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;margin:20px auto}
        @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
        a{color:#007bff;text-decoration:none}a:hover{text-decoration:underline}
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 SGC-AgentOne</h1>
        <div class="loader"></div>
        <p>Redirection vers l\'interface...</p>
        <p><a href="<?php echo $interfaceUrl; ?>">Cliquez ici si la redirection ne fonctionne pas</a></p>
        <div style="margin-top:20px;font-size:12px;color:#666">
            <strong>Interface :</strong> <code><?php echo $interfaceUrl; ?></code>
        </div>
    </div>
</body>
</html>';

    // Contenu .htaccess pour data/
    $dataHtaccessContent = '# Protection totale du dossier data (base de données SQLite)
Deny from all

# Protection spécifique des fichiers de base de données
<Files ~ "\.sqlite3?$">
    Order allow,deny
    Deny from all
</Files>

# Protection des fichiers de log
<Files ~ "\.log$">
    Order allow,deny
    Deny from all
</Files>';

    // Création des fichiers
    $files[] = [
        'path' => $installPath . '/.htaccess',
        'content' => $htaccessContent,
        'name' => '.htaccess principal'
    ];
    
    $files[] = [
        'path' => $installPath . '/index.php',
        'content' => $indexContent,
        'name' => 'index.php d\'entrée'
    ];
    
    // Création du dossier data/ si nécessaire
    $dataDir = $installPath . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    $files[] = [
        'path' => $dataDir . '/.htaccess',
        'content' => $dataHtaccessContent,
        'name' => '.htaccess protection data/'
    ];
    
    return $files;
}

// Affichage des résultats
echo "<h3>🔍 Vérification de l'environnement</h3>";
$checks = checkEnvironment();
$allOk = true;

foreach ($checks as $check) {
    $status = $check['result'] ? '✅' : '❌';
    $color = $check['result'] ? 'green' : 'red';
    echo "<div style='color: {$color};'>{$status} {$check['test']}: {$check['details']}</div>";
    if (!$check['result']) $allOk = false;
}

if (!$allOk) {
    echo "<br><div style='color: red; font-weight: bold;'>❌ Certaines vérifications ont échoué. Contactez votre hébergeur.</div>";
    exit();
}

echo "<br><h3>📁 Création des fichiers d'installation</h3>";

// Utiliser le chemin d'installation détecté
$files = createFiles($installPath);

foreach ($files as $file) {
    $result = file_put_contents($file['path'], $file['content']);
    if ($result !== false) {
        echo "✅ {$file['name']} créé: {$file['path']}<br>";
    } else {
        echo "❌ Erreur lors de la création de {$file['name']}<br>";
    }
}

echo "<br><h3>🎯 Installation terminée !</h3>";

// Instructions finales avec URLs dynamiques
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $env['server_name'];
$sgcPath = $env['base_url'];

// Calcul des URLs d'accès selon l'environnement
$mainUrl = $baseUrl . $sgcPath . '/';
$chatUrl = $baseUrl . $sgcPath . '/extensions/webview/chat.html';
$apiUrl = $baseUrl . $sgcPath . '/api/chat';

echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #007cba;'>";
echo "<strong>🚀 Votre SGC-AgentOne est prêt !</strong><br><br>";

echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>🌍 Environnement détecté :</strong> " . ($env['is_localhost'] ? '🖥️ Local' : '🌐 Web') . "<br>";
echo "<strong>📍 Serveur :</strong> " . $env['server_name'] . "<br>";
echo "<strong>📂 Installation :</strong> " . $installPath . "<br>";
echo "</div>";

echo "<strong>🔗 Accès principal :</strong><br>";
echo "🌐 <a href='{$mainUrl}' target='_blank'>{$mainUrl}</a><br><br>";
echo "<strong>🔗 Accès direct :</strong><br>";
echo "💬 <a href='{$chatUrl}' target='_blank'>Interface Chat</a><br>";
echo "🔧 <a href='{$apiUrl}' target='_blank'>API REST</a><br><br>";
echo "<strong>Fonctionnalités disponibles :</strong><br>";
echo "• Chat intelligent multilingue (français/anglais)<br>";
echo "• Gestionnaire de fichiers complet<br>";
echo "• Éditeur Monaco intégré<br>";
echo "• Base de données SQLite<br>";
echo "• 7 vues professionnelles<br>";
echo "</div>";

echo "<br><div style='color: #666; font-size: 12px;'>";
echo "⚠️ Ce script peut être supprimé après installation.<br>";
echo "📝 Logs d'erreurs disponibles dans les logs de votre hébergeur.";
echo "</div>";
?>