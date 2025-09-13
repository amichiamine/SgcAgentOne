<?php
/**
 * SGC-AgentOne - Script d'installation automatique pour hébergement mutualisé
 * Lance depuis le dossier sgc-agentone/ pour configurer l'environnement
 */

// Configuration
$projectRoot = dirname(__FILE__, 3); // Remonte de 3 niveaux depuis deployment/shared-hosting/
$installPath = dirname($projectRoot) . '/sgc-agentone'; // public_html/sgc-agentone/

echo "🚀 <strong>SGC-AgentOne - Installation Automatique</strong><br><br>";

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

    // Contenu index.php principal
    $indexContent = '<?php
/**
 * SGC-AgentOne - Point d\'entrée pour hébergement mutualisé
 * Redirige vers l\'interface principale
 */

// Configuration d\'erreurs pour production
error_reporting(E_ALL);
ini_set(\'display_errors\', 0);
ini_set(\'log_errors\', 1);

// Redirige vers l\'interface SGC-AgentOne
$interfaceUrl = \'/\' . basename(__DIR__) . \'/extensions/vscode/src/webview/chat.html\';

// Headers pour redirection propre
header(\'HTTP/1.1 302 Found\');
header(\'Location: \' . $interfaceUrl);
header(\'Cache-Control: no-cache, no-store, must-revalidate\');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGC-AgentOne</title>
    <meta http-equiv="refresh" content="0; url=<?php echo $interfaceUrl; ?>">
</head>
<body>
    <div style="text-align: center; margin-top: 50px; font-family: Arial, sans-serif;">
        <h1>🚀 SGC-AgentOne</h1>
        <p>Redirection vers l\'interface...</p>
        <p><a href="<?php echo $interfaceUrl; ?>">Cliquez ici si la redirection ne fonctionne pas</a></p>
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

// Déterminer le chemin d'installation (là où se trouve ce script)
$currentDir = dirname(__FILE__, 2); // Remonte de 2 niveaux depuis deployment/shared-hosting/
$files = createFiles($currentDir);

foreach ($files as $file) {
    $result = file_put_contents($file['path'], $file['content']);
    if ($result !== false) {
        echo "✅ {$file['name']} créé: {$file['path']}<br>";
    } else {
        echo "❌ Erreur lors de la création de {$file['name']}<br>";
    }
}

echo "<br><h3>🎯 Installation terminée !</h3>";

// Instructions finales
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$sgcPath = '/' . basename($currentDir);

echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #007cba;'>";
echo "<strong>🚀 Votre SGC-AgentOne est prêt !</strong><br><br>";
echo "<strong>Accès principal :</strong><br>";
echo "🌐 <a href='{$baseUrl}{$sgcPath}/' target='_blank'>{$baseUrl}{$sgcPath}/</a><br><br>";
echo "<strong>Accès direct :</strong><br>";
echo "💬 <a href='{$baseUrl}{$sgcPath}/extensions/vscode/src/webview/chat.html' target='_blank'>Interface Chat</a><br>";
echo "🔧 <a href='{$baseUrl}{$sgcPath}/api/chat' target='_blank'>API REST</a><br><br>";
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