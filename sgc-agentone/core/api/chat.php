<?php
/**
 * SGC-AgentOne - API REST Chat
 * Endpoint principal pour les messages utilisateur
 * Format: POST { "message": "...", "projectPath": ".", "blind": false }
 * Réponse: { "response": "...", "actions": [...] }
 */

// Chargement des paramètres de sécurité
$projectRoot = getcwd();
$settingsFile = $projectRoot . '/sgc-agentone/core/config/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
}

// Configuration CORS sécurisée
$allowedOrigins = $settings['security']['allowed_origins'] ?? ['http://localhost:5000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigin = '*'; // fallback pour développement

foreach ($allowedOrigins as $allowed) {
    if (fnmatch($allowed, $origin)) {
        $allowedOrigin = $origin;
        break;
    }
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// Vérification de l'authentification si requise
if (($settings['security']['require_auth'] ?? false)) {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $expectedKey = $settings['security']['api_key'] ?? '';
    
    // Nettoyage de l'en-tête Authorization (Bearer token)
    if (strpos($apiKey, 'Bearer ') === 0) {
        $apiKey = substr($apiKey, 7);
    }
    
    // Check for session token (webview)
    $isSessionValid = false;
    if (strpos($apiKey, 'webview_') === 0) {
        $sessionFile = $projectRoot . '/sgc-agentone/core/config/webview_sessions.json';
        if (file_exists($sessionFile)) {
            $sessions = json_decode(file_get_contents($sessionFile), true) ?: [];
            if (isset($sessions[$apiKey])) {
                // Check if session is still valid (not expired)
                if ((time() - $sessions[$apiKey]['created']) < 3600) {
                    $isSessionValid = true;
                }
            }
        }
    }
    
    if (!$isSessionValid && (!$expectedKey || $apiKey !== $expectedKey)) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentification requise']);
        exit();
    }
}

// Lecture du contenu JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Message requis']);
    exit();
}

$message = trim($data['message']);
$projectPath = $data['projectPath'] ?? '.';
$blindMode = $data['blind'] ?? false;

// Log de la conversation
$projectRoot = getcwd();
$logFile = $projectRoot . '/sgc-agentone/core/logs/chat.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$timestamp = date('Y-m-d H:i:s');
$logEntry = "[{$timestamp}] USER: \"{$message}\"\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Chargement de l'interpréteur
$interpreterFile = $projectRoot . '/sgc-agentone/core/agents/interpreter.php';
if (!file_exists($interpreterFile)) {
    $response = [
        'error' => 'Interpréteur non disponible',
        'response' => '❌ Service temporairement indisponible.'
    ];
    echo json_encode($response);
    exit();
}

include_once $interpreterFile;

try {
    // Interprétation du message
    $interpretation = interpretMessage($message);
    
    if (!$interpretation) {
        $response = [
            'response' => '🤔 Je n\'ai pas compris votre demande. Pouvez-vous la reformuler ?',
            'actions' => []
        ];
        
        // Log de la réponse
        $logEntry = "[{$timestamp}] AI: \"{$response['response']}\"\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        echo json_encode($response);
        exit();
    }
    
    $actions = [];
    $responseText = '';
    
    // Vérification du mode Blind-Exec
    if ($blindMode) {
        $enabledFile = $projectRoot . '/sgc-agentone/core/tools/blind-exec/enabled.txt';
        $whitelistFile = $projectRoot . '/sgc-agentone/core/config/whitelist.json';
        
        if (!file_exists($enabledFile) || trim(file_get_contents($enabledFile)) !== 'true') {
            $response = [
                'response' => '🔒 Mode Blind-Exec désactivé.',
                'actions' => []
            ];
            echo json_encode($response);
            exit();
        }
        
        if (file_exists($whitelistFile)) {
            $whitelist = json_decode(file_get_contents($whitelistFile), true);
            if (!in_array($interpretation['action'], $whitelist)) {
                $response = [
                    'response' => '⚠️ Action non autorisée en mode Blind-Exec.',
                    'actions' => []
                ];
                echo json_encode($response);
                exit();
            }
        }
    }
    
    // Exécution de l'action
    $actionResult = executeAction($interpretation, $projectPath);
    
    if ($actionResult['success']) {
        $responseText = $actionResult['response'];
        $actions[] = [
            'action' => $interpretation['action'],
            'params' => $interpretation['params'],
            'result' => 'success'
        ];
    } else {
        $responseText = '❌ ' . $actionResult['error'];
        $actions[] = [
            'action' => $interpretation['action'],
            'params' => $interpretation['params'],
            'result' => 'error',
            'error' => $actionResult['error']
        ];
    }
    
    // Log des actions
    $actionLogFile = $projectRoot . '/sgc-agentone/core/logs/actions.log';
    $actionLogEntry = "[{$timestamp}] ACTION: {$interpretation['action']} | PARAMS: " . json_encode($interpretation['params']) . "\n";
    file_put_contents($actionLogFile, $actionLogEntry, FILE_APPEND | LOCK_EX);
    
    $response = [
        'response' => $responseText,
        'actions' => $actions
    ];
    
    // Log de la réponse
    $logEntry = "[{$timestamp}] AI: \"{$responseText}\"\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    $errorResponse = [
        'error' => 'Erreur serveur',
        'response' => '❌ Une erreur est survenue: ' . $e->getMessage()
    ];
    echo json_encode($errorResponse);
}

/**
 * Exécute une action interprétée
 */
function executeAction($interpretation, $projectPath) {
    $projectRoot = getcwd();
    $action = $interpretation['action'];
    $params = $interpretation['params'];
    
    // Mapping des actions vers les fichiers
    $actionFiles = [
        'create file' => 'createFile.php',
        'update file' => 'updateFile.php',
        'read file' => 'readFile.php',
        'create database' => 'createDB.php',
        'execute query' => 'executeQuery.php',
        'show help menu' => 'showHelpMenu.php',
        'show chat help' => 'showChatHelp.php',
        'show ide help' => 'showIdeHelp.php'
    ];
    
    if (!isset($actionFiles[$action])) {
        return ['success' => false, 'error' => 'Action non supportée: ' . $action];
    }
    
    $actionFile = $projectRoot . '/sgc-agentone/core/agents/actions/' . $actionFiles[$action];
    
    if (!file_exists($actionFile)) {
        return ['success' => false, 'error' => 'Module d\'action non trouvé: ' . $actionFiles[$action]];
    }
    
    // Inclusion et exécution de l'action
    include_once $actionFile;
    
    // Chaque fichier d'action doit définir une fonction execute()
    if (function_exists('executeAction_' . str_replace(' ', '', $action))) {
        $functionName = 'executeAction_' . str_replace(' ', '', $action);
        return $functionName($params, $projectPath);
    }
    
    return ['success' => false, 'error' => 'Fonction d\'action non trouvée'];
}
?>