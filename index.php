<?php
/**
 * SGC-AgentOne - Point d'entrée universel
 * Compatible avec Replit, XAMPP, LAMP, MAMP et hébergement mutualisé
 */

// Détection de l'environnement
$isReplit = isset($_SERVER['REPL_ID']) || 
           (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.replit.dev') !== false);

$isLocalDev = isset($_SERVER['HTTP_HOST']) && 
             ($_SERVER['HTTP_HOST'] === 'localhost:5000' || 
              strpos($_SERVER['HTTP_HOST'], '127.0.0.1:5000') !== false);

$isXAMPP = isset($_SERVER['HTTP_HOST']) && 
          (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
           strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) &&
          !$isLocalDev;

// Configuration des chemins selon l'environnement
$projectRoot = __DIR__;
$chatInterface = $projectRoot . '/sgc-agentone/extensions/webview/chat.html';

// Vérification que l'interface existe
if (!file_exists($chatInterface)) {
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>SGC-AgentOne - Erreur</title></head><body>";
    echo "<h1>Erreur de configuration</h1>";
    echo "<p>L'interface chat n'a pas été trouvée à : <code>" . htmlspecialchars($chatInterface) . "</code></p>";
    echo "<p>Vérifiez que tous les fichiers SGC-AgentOne sont correctement installés.</p>";
    echo "</body></html>";
    exit();
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Configuration CORS sécurisée pour développement - pas de wildcard
if ($isXAMPP || $isLocalDev) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = ['http://localhost', 'http://127.0.0.1', 'http://localhost:80', 'http://localhost:8080'];
    $allowedOrigin = null;
    
    foreach ($allowedOrigins as $allowed) {
        if ($origin === $allowed || fnmatch($allowed . ':*', $origin)) {
            $allowedOrigin = $origin;
            break;
        }
    }
    
    if ($allowedOrigin) {
        header("Access-Control-Allow-Origin: {$allowedOrigin}");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, HEAD');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    }
}

// Cache control
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Type de contenu
header('Content-Type: text/html; charset=utf-8');

// Servir l'interface chat
readfile($chatInterface);
?>