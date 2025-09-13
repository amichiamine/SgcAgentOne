<?php
/**
 * SGC-AgentOne - Router pour serveur PHP built-in
 * Gère le routage des requêtes HTTP pour l'API et les fichiers statiques
 */

// Récupération du chemin de la requête
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Chargement des paramètres de sécurité pour CORS
$projectRoot = getcwd();
$settingsFile = $projectRoot . '/sgc-agentone/core/config/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
}

// Configuration CORS sécurisée
$allowedOrigins = $settings['security']['allowed_origins'] ?? ['http://localhost:5000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigin = '*'; // fallback pour développement local

foreach ($allowedOrigins as $allowed) {
    if (fnmatch($allowed, $origin)) {
        $allowedOrigin = $origin;
        break;
    }
}

header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, HEAD');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Cache-Control: no-cache');

// Gestion des requêtes OPTIONS (preflight)
if ($requestMethod === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$projectRoot = getcwd();

// Gestion des requêtes HEAD pour /api
if ($requestMethod === 'HEAD' && ($requestUri === '/api' || strpos($requestUri, '/api') === 0)) {
    http_response_code(200);
    header('Content-Type: application/json');
    exit();
}

// Routes API - priorité absolue, pas d'output avant
if (strpos($requestUri, '/api/auth/token') === 0 || $requestUri === '/api/auth/token') {
    // Inclusion de l'API auth
    $authApiFile = $projectRoot . '/sgc-agentone/core/api/auth.php';
    if (file_exists($authApiFile)) {
        include $authApiFile;
        exit();
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API auth non disponible']);
        exit();
    }
}

if (strpos($requestUri, '/api/prompts') === 0 || $requestUri === '/api/prompts') {
    // Inclusion de l'API prompts
    $promptsApiFile = $projectRoot . '/sgc-agentone/core/api/prompts.php';
    if (file_exists($promptsApiFile)) {
        include $promptsApiFile;
        exit();
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API prompts non disponible']);
        exit();
    }
}

if (strpos($requestUri, '/api/chat') === 0 || $requestUri === '/api/chat') {
    // Inclusion de l'API chat
    $chatApiFile = $projectRoot . '/sgc-agentone/core/api/chat.php';
    if (file_exists($chatApiFile)) {
        include $chatApiFile;
        exit();
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API chat non disponible']);
        exit();
    }
}

if (strpos($requestUri, '/api/server') === 0 || $requestUri === '/api/server') {
    // Inclusion de l'API server management
    $serverApiFile = $projectRoot . '/sgc-agentone/core/api/server.php';
    if (file_exists($serverApiFile)) {
        include $serverApiFile;
        exit();
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API server non disponible']);
        exit();
    }
}

if (strpos($requestUri, '/api/files') === 0 || $requestUri === '/api/files') {
    // Inclusion de l'API files management
    $filesApiFile = $projectRoot . '/sgc-agentone/core/api/files.php';
    if (file_exists($filesApiFile)) {
        include $filesApiFile;
        exit();
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API files non disponible']);
        exit();
    }
}

if (strpos($requestUri, '/api/prompts') === 0 || $requestUri === '/api/prompts') {
    // Inclusion de l'API prompts
    $promptsApiFile = $projectRoot . '/sgc-agentone/core/api/prompts.php';
    if (file_exists($promptsApiFile)) {
        include $promptsApiFile;
        exit();
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API prompts non disponible']);
        exit();
    }
}

// Log simple des requêtes pour les autres routes (dans error_log, jamais echo avant headers)
$timestamp = date('[H:i:s]');
error_log("{$timestamp} {$requestMethod} {$requestUri}");

// Route par défaut (page d'accueil)
if ($requestUri === '/' || $requestUri === '/index.html') {
    $chatInterface = $projectRoot . '/sgc-agentone/extensions/webview/chat.html';
    if (file_exists($chatInterface)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($chatInterface);
        exit();
    } else {
        http_response_code(404);
        echo "Interface chat non disponible";
        exit();
    }
}

// Gestion des fichiers statiques
$staticFile = $projectRoot . '/sgc-agentone' . $requestUri;

// Vérification de sécurité pour éviter l'accès aux fichiers système
$realPath = realpath($staticFile);
if ($realPath === false || strpos($realPath, $projectRoot) !== 0) {
    http_response_code(403);
    echo "Accès interdit";
    exit();
}

// Servir le fichier statique si il existe
if (file_exists($staticFile) && is_file($staticFile)) {
    $extension = pathinfo($staticFile, PATHINFO_EXTENSION);
    
    // Types MIME supportés
    $mimeTypes = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf'
    ];
    
    $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
    header("Content-Type: {$contentType}");
    
    // Headers de cache pour les ressources statiques
    if (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'])) {
        header('Cache-Control: public, max-age=3600');
    }
    
    readfile($staticFile);
    exit();
}

// 404 - Ressource non trouvée
http_response_code(404);
header('Content-Type: text/plain');
echo "404 - Page non trouvée: {$requestUri}";
?>