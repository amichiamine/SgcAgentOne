<?php
/**
 * SGC-AgentOne - Authentication API
 * Secure token generation for webview clients
 * Prevents API key exposure in client-side code
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

$projectRoot = getcwd();
$settingsFile = $projectRoot . '/sgc-agentone/core/config/settings.json';
$settings = [];

if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
}

// Check if authentication is required
if (!($settings['security']['require_auth'] ?? false)) {
    // Authentication disabled, return success without token
    echo json_encode(['token' => null, 'auth_required' => false]);
    exit();
}

// Generate session token for webview clients
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || ($data['client_type'] ?? '') !== 'webview') {
    http_response_code(400);
    echo json_encode(['error' => 'Type de client non valide']);
    exit();
}

// Generate a temporary session token (for webview use)
$sessionToken = 'webview_' . bin2hex(random_bytes(16)) . '_' . time();

// Store session token (in production, use proper session storage)
$sessionFile = $projectRoot . '/sgc-agentone/core/config/webview_sessions.json';
$sessions = [];

if (file_exists($sessionFile)) {
    $sessions = json_decode(file_get_contents($sessionFile), true) ?: [];
}

// Clean old sessions (older than 1 hour)
$sessions = array_filter($sessions, function($session) {
    return (time() - $session['created']) < 3600;
});

// Add new session
$sessions[$sessionToken] = [
    'created' => time(),
    'client_type' => 'webview',
    'permissions' => ['chat', 'file_operations']
];

file_put_contents($sessionFile, json_encode($sessions, JSON_PRETTY_PRINT));

echo json_encode([
    'token' => $sessionToken,
    'auth_required' => true,
    'expires_in' => 3600
]);
?>