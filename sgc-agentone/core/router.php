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
$allowedOrigin = null; // Pas de fallback wildcard

foreach ($allowedOrigins as $allowed) {
    if (fnmatch($allowed, $origin)) {
        $allowedOrigin = $origin;
        break;
    }
}

// Fallback sécurisé pour développement local explicite uniquement
if (!$allowedOrigin) {
    $parsedOrigin = parse_url($origin);
    $host = $parsedOrigin['host'] ?? '';
    $allowedLocalHosts = ['localhost', '127.0.0.1'];
    
    if (in_array($host, $allowedLocalHosts)) {
        $allowedOrigin = $origin;
    }
}

// Appliquer CORS seulement si origine autorisée
if ($allowedOrigin) {
    header("Access-Control-Allow-Origin: {$allowedOrigin}");
}
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
if (strpos($requestUri, '/api/auth') === 0 || $requestUri === '/api/auth' || $requestUri === '/api/auth/token') {
    // Inclusion de l'API auth (support pour /api/auth et /api/auth/token)
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

// Route Files Explorer
if ($requestUri === '/files' || $requestUri === '/files.html') {
    $filesInterface = $projectRoot . '/sgc-agentone/extensions/webview/files.html';
    if (file_exists($filesInterface)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($filesInterface);
        exit();
    } else {
        http_response_code(404);
        echo "Interface Files non disponible";
        exit();
    }
}

// Route Browser
if ($requestUri === '/browser' || $requestUri === '/browser.html') {
    $browserInterface = $projectRoot . '/sgc-agentone/extensions/webview/browser.html';
    if (file_exists($browserInterface)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($browserInterface);
        exit();
    } else {
        http_response_code(404);
        echo "Interface Browser non disponible";
        exit();
    }
}

// Gestion des fichiers statiques
$staticFile = $projectRoot . '/sgc-agentone' . $requestUri;

// Vérification de sécurité pour éviter l'accès aux fichiers système
$realPath = realpath($staticFile);
if ($realPath === false || strpos($realPath, $projectRoot) !== 0) {
    http_response_code(403);
    // Détecter si c'est une requête API pour renvoyer du JSON
    if (strpos($requestUri, '/api/') === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Accès interdit']);
    } else {
        echo "Accès interdit";
    }
    exit();
}

// SÉCURITÉ RENFORCÉE - Vérifier le chemin réel résolu pour éviter les contournements
$coreDir = $projectRoot . '/sgc-agentone/core';
if ($realPath && strpos($realPath, $coreDir) === 0) {
    http_response_code(403);
    if (strpos($requestUri, '/api/') === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Accès interdit au répertoire core']);
    } else {
        echo "Accès interdit au répertoire core";
    }
    exit();
}

// SÉCURITÉ - Bloquer les fichiers PHP et JSON basé sur le chemin réel
if ($realPath && file_exists($realPath)) {
    $extension = pathinfo($realPath, PATHINFO_EXTENSION);
    $isSgcFile = strpos($realPath, $projectRoot . '/sgc-agentone') === 0;
    
    if (in_array($extension, ['php', 'json']) && $isSgcFile) {
        http_response_code(403);
        if (strpos($requestUri, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Type de fichier non autorisé']);
        } else {
            echo "Type de fichier non autorisé";
        }
        exit();
    }
}

// Servir le fichier statique si il existe ET est sécurisé
if (file_exists($staticFile) && is_file($staticFile)) {
    $extension = pathinfo($staticFile, PATHINFO_EXTENSION);
    
    // Types MIME supportés - SANS JSON et PHP pour sécurité
    $mimeTypes = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css',
        'js' => 'application/javascript',
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
// Détecter si c'est une requête API pour renvoyer du JSON
if (strpos($requestUri, '/api/') === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => '404 - API endpoint non trouvé', 'path' => $requestUri]);
} else {
    header('Content-Type: text/plain');
    echo "404 - Page non trouvée: {$requestUri}";
}
?>