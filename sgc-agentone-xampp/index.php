<?php
/**
 * SGC-AgentOne - Point d'entrée XAMPP
 * Configuration Apache avec routage intelligent
 * Optimisé pour Windows XAMPP
 */

// Configuration de base pour XAMPP
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

// Racine du projet pour XAMPP
$projectRoot = $_SERVER['DOCUMENT_ROOT'] . '/sgc-agentone';
if (!is_dir($projectRoot)) {
    $projectRoot = __DIR__;
}

// Chargement de la configuration XAMPP
$xamppConfig = $projectRoot . '/config/xampp-settings.json';
$settings = [];
if (file_exists($xamppConfig)) {
    $settings = json_decode(file_get_contents($xamppConfig), true) ?: [];
}

// Configuration par défaut pour XAMPP
$defaultSettings = [
    'port' => 80,
    'base_url' => 'http://localhost/sgc-agentone',
    'environment' => 'xampp',
    'security' => [
        'api_key' => 'sgc-agent-xampp-key-2024',
        'allowed_origins' => ['http://localhost', 'http://127.0.0.1'],
        'require_auth' => false  // Plus simple pour XAMPP local
    ],
    'apache' => [
        'mod_rewrite' => true,
        'compression' => true,
        'caching' => true
    ]
];

// Fusion des paramètres
$settings = array_merge_recursive($defaultSettings, $settings);

// Variables globales pour les APIs
$GLOBALS['projectRoot'] = $projectRoot;
$GLOBALS['settings'] = $settings;

// Détection de l'URI demandée
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Nettoyage de l'URI (retirer le préfixe si dans un sous-dossier)
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && strpos($requestUri, $scriptName) === 0) {
    $requestUri = substr($requestUri, strlen($scriptName));
}
$requestUri = '/' . ltrim($requestUri, '/');

// Log des requêtes pour debugging XAMPP
$logFile = $projectRoot . '/core/logs/xampp-access.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$timestamp = date('[Y-m-d H:i:s]');
$logEntry = "{$timestamp} {$requestMethod} {$requestUri} - {$_SERVER['HTTP_USER_AGENT']}\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Headers CORS pour XAMPP
$allowedOrigins = $settings['security']['allowed_origins'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : '*';

header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Cache-Control: no-cache');

// Gestion des requêtes OPTIONS (preflight)
if ($requestMethod === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// === ROUTAGE INTELLIGENT XAMPP ===

// API Routes (déjà gérées par .htaccess, mais fallback)
if (strpos($requestUri, '/api/') === 0) {
    $apiPath = substr($requestUri, 5); // Retirer '/api/'
    
    if (strpos($apiPath, 'auth/token') === 0) {
        include $projectRoot . '/core/api/auth.php';
        exit();
    } elseif (strpos($apiPath, 'prompts') === 0) {
        include $projectRoot . '/core/api/prompts.php';
        exit();
    } elseif (strpos($apiPath, 'chat') === 0) {
        include $projectRoot . '/core/api/chat.php';
        exit();
    } elseif (strpos($apiPath, 'server') === 0) {
        include $projectRoot . '/core/api/server.php';
        exit();
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API endpoint non trouvé']);
        exit();
    }
}

// Assets statiques
if (preg_match('/\.(css|js|woff|woff2|ttf|png|jpg|svg|ico)$/', $requestUri)) {
    $filePath = $projectRoot . $requestUri;
    if (file_exists($filePath)) {
        // Déterminer le type MIME
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];
        
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        header("Content-Type: {$mimeType}");
        header('Cache-Control: public, max-age=2592000'); // 30 jours
        readfile($filePath);
        exit();
    }
}

// Page principale - Interface SGC-AgentOne
$chatInterface = $projectRoot . '/extensions/vscode/src/webview/chat.html';

if ($requestUri === '/' || $requestUri === '/index.php' || $requestUri === '/index.html') {
    if (file_exists($chatInterface)) {
        // Injection des paramètres XAMPP dans l'interface
        $content = file_get_contents($chatInterface);
        
        // Remplacement des URLs pour XAMPP
        $baseUrl = $settings['base_url'];
        $content = str_replace(
            ['http://localhost:5000', 'localhost:5000'],
            [$baseUrl, str_replace(['http://', 'https://'], '', $baseUrl)],
            $content
        );
        
        // Injection de la configuration XAMPP
        $xamppConfig = "
            <script>
                // Configuration XAMPP
                window.SGC_CONFIG = " . json_encode([
                    'environment' => 'xampp',
                    'base_url' => $settings['base_url'],
                    'api_base' => $settings['base_url'] . '/api',
                    'version' => '1.0-xampp'
                ]) . ";
                console.log('SGC-AgentOne XAMPP Ready!', window.SGC_CONFIG);
            </script>
        ";
        
        $content = str_replace('</head>', $xamppConfig . '</head>', $content);
        
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit();
    } else {
        // Fallback si chat.html n'existe pas
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
<html>
<head>
    <title>SGC-AgentOne XAMPP</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .status { padding: 20px; background: #e8f5e8; border-radius: 8px; border-left: 4px solid #4caf50; }
        .error { background: #ffeaea; border-left-color: #f44336; }
        .info { background: #e3f2fd; border-left-color: #2196f3; }
    </style>
</head>
<body>
    <h1>🚀 SGC-AgentOne XAMPP</h1>
    <div class="status">
        <h3>✅ Serveur Apache fonctionnel</h3>
        <p><strong>Version:</strong> XAMPP Optimisé</p>
        <p><strong>Base URL:</strong> ' . $settings['base_url'] . '</p>
        <p><strong>Environnement:</strong> ' . $settings['environment'] . '</p>
    </div>
    <div class="error">
        <h3>⚠️ Interface chat non trouvée</h3>
        <p>Le fichier <code>extensions/vscode/src/webview/chat.html</code> n\'a pas été trouvé.</p>
        <p>Assurez-vous que tous les fichiers ont été copiés correctement.</p>
    </div>
    <div class="info">
        <h3>🔧 Tests API disponibles</h3>
        <ul>
            <li><a href="api/server/status">Status Serveur</a></li>
            <li><a href="api/server/logs">Logs Système</a></li>
            <li><a href="api/prompts">Gestion Prompts</a></li>
        </ul>
    </div>
</body>
</html>';
        exit();
    }
}

// 404 - Page non trouvée
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html>
<html>
<head><title>404 - SGC-AgentOne XAMPP</title></head>
<body style="font-family: Arial, sans-serif; text-align: center; margin-top: 100px;">
    <h1>404 - Page non trouvée</h1>
    <p>La ressource demandée <code>' . htmlspecialchars($requestUri) . '</code> n\'existe pas.</p>
    <p><a href="/">← Retour à l\'accueil SGC-AgentOne</a></p>
</body>
</html>';
?>