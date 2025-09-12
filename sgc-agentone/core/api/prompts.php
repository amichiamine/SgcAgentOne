<?php
/**
 * SGC-AgentOne - API REST Prompts
 * Gestion CRUD des patterns de commandes (rules.json)
 * Endpoints: GET, POST, DELETE /api/prompts
 * Authentification: X-API-Key header
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
$allowedOrigin = null;

// Validation stricte des origines - pas de fallback wildcard
foreach ($allowedOrigins as $allowed) {
    if (fnmatch($allowed, $origin)) {
        $allowedOrigin = $origin;
        break;
    }
}

// Si aucune origine valide trouvée, utiliser la première autorisée
if (!$allowedOrigin) {
    $allowedOrigin = $allowedOrigins[0];
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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

// Chemin vers le fichier rules.json
$rulesFile = $projectRoot . '/sgc-agentone/core/config/rules.json';

// Log de l'activité
$logFile = $projectRoot . '/sgc-agentone/core/logs/prompts.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logActivity($action, $details = '') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] PROMPTS: {$action} | {$details}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Chargement des patterns depuis rules.json
function loadPatterns() {
    global $rulesFile;
    
    if (!file_exists($rulesFile)) {
        return getDefaultPatterns();
    }
    
    $content = file_get_contents($rulesFile);
    $patterns = json_decode($content, true);
    
    if (!$patterns || !is_array($patterns)) {
        return getDefaultPatterns();
    }
    
    return $patterns;
}

// Sauvegarde atomique des patterns dans rules.json
function savePatterns($patterns) {
    global $rulesFile;
    
    // Validation stricte des patterns
    if (!validatePatternsArray($patterns)) {
        return false;
    }
    
    // Sauvegarde avec backup automatique
    if (file_exists($rulesFile)) {
        $backupFile = $rulesFile . '.backup.' . date('Y-m-d_H-i-s');
        if (!copy($rulesFile, $backupFile)) {
            return false;
        }
    }
    
    // Écriture atomique via fichier temporaire
    $tempFile = $rulesFile . '.tmp.' . uniqid();
    $jsonContent = json_encode($patterns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($tempFile, $jsonContent, LOCK_EX) === false) {
        return false;
    }
    
    // Déplacement atomique
    if (!rename($tempFile, $rulesFile)) {
        @unlink($tempFile);
        return false;
    }
    
    return true;
}

// Patterns par défaut
function getDefaultPatterns() {
    return [
        ['pattern' => 'crée un fichier *', 'action' => 'create file'],
        ['pattern' => 'create file *', 'action' => 'create file'],
        ['pattern' => 'génère le fichier *', 'action' => 'create file'],
        ['pattern' => 'ajoute un fichier *', 'action' => 'create file'],
        ['pattern' => 'modifie le fichier *', 'action' => 'update file'],
        ['pattern' => 'update file *', 'action' => 'update file'],
        ['pattern' => 'change le contenu *', 'action' => 'update file'],
        ['pattern' => 'lis le fichier *', 'action' => 'read file'],
        ['pattern' => 'read file *', 'action' => 'read file'],
        ['pattern' => 'affiche le contenu *', 'action' => 'read file'],
        ['pattern' => 'connecte à la base *', 'action' => 'create database'],
        ['pattern' => 'create database', 'action' => 'create database'],
        ['pattern' => 'crée la base de données', 'action' => 'create database'],
        ['pattern' => 'exécute la requête *', 'action' => 'execute query'],
        ['pattern' => 'execute query *', 'action' => 'execute query'],
        ['pattern' => 'lance la requête SQL *', 'action' => 'execute query']
    ];
}

// Validation stricte d'un pattern individuel
function validatePattern($pattern, $action) {
    // Validation des types
    if (!is_string($pattern) || !is_string($action)) {
        return ['valid' => false, 'error' => 'Pattern et action doivent être des chaînes'];
    }
    
    // Validation du contenu
    $pattern = trim($pattern);
    $action = trim($action);
    
    if (empty($pattern) || empty($action)) {
        return ['valid' => false, 'error' => 'Pattern et action requis'];
    }
    
    if (strlen($pattern) > 200) {
        return ['valid' => false, 'error' => 'Pattern trop long (max 200 caractères)'];
    }
    
    if (strlen($action) > 50) {
        return ['valid' => false, 'error' => 'Action trop longue (max 50 caractères)'];
    }
    
    $validActions = ['create file', 'update file', 'read file', 'create database', 'execute query'];
    if (!in_array($action, $validActions)) {
        return ['valid' => false, 'error' => 'Action non valide: ' . implode(', ', $validActions)];
    }
    
    // Vérification de caractères dangereux (incluant caractères de contrôle)
    if (preg_match('/[<>\'"\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]/', $pattern)) {
        return ['valid' => false, 'error' => 'Caractères non autorisés dans le pattern'];
    }
    
    return ['valid' => true];
}

// Validation d'un tableau de patterns
function validatePatternsArray($patterns) {
    if (!is_array($patterns)) {
        return false;
    }
    
    foreach ($patterns as $pattern) {
        if (!is_array($pattern) || !isset($pattern['pattern']) || !isset($pattern['action'])) {
            return false;
        }
        
        $validation = validatePattern($pattern['pattern'], $pattern['action']);
        if (!$validation['valid']) {
            return false;
        }
    }
    
    return true;
}

// Fonction utilitaire pour réponses JSON cohérentes
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Analyse et validation des routes
function parseRoute() {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Nettoyage et normalisation du chemin
    $path = trim($path, '/');
    $pathSegments = array_filter(explode('/', $path));
    
    // Structure attendue: [api, prompts, ?param]
    if (count($pathSegments) < 2 || $pathSegments[0] !== 'api' || $pathSegments[1] !== 'prompts') {
        return ['valid' => false, 'error' => 'Route invalide'];
    }
    
    $param = isset($pathSegments[2]) ? urldecode($pathSegments[2]) : null;
    
    return [
        'valid' => true,
        'method' => $method,
        'param' => $param,
        'is_reset' => $param === 'reset'
    ];
}

try {
    // Analyse et validation de la route
    $route = parseRoute();
    if (!$route['valid']) {
        sendJsonResponse(['error' => $route['error']], 400);
    }
    
    $method = $route['method'];
    $param = $route['param'];
    $isReset = $route['is_reset'];
    
    switch ($method) {
        case 'GET':
            // Lister tous les patterns
            $patterns = loadPatterns();
            logActivity('LIST', count($patterns) . ' patterns');
            sendJsonResponse($patterns);
            break;
            
        case 'POST':
            if ($isReset) {
                // Réinitialiser aux patterns par défaut
                $defaultPatterns = getDefaultPatterns();
                if (savePatterns($defaultPatterns)) {
                    logActivity('RESET', 'Patterns réinitialisés');
                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Patterns réinitialisés aux valeurs par défaut',
                        'count' => count($defaultPatterns),
                        'patterns' => $defaultPatterns
                    ]);
                } else {
                    logActivity('ERROR', 'Échec réinitialisation patterns');
                    sendJsonResponse(['error' => 'Erreur lors de la réinitialisation'], 500);
                }
            }
            
            // Ajouter un nouveau pattern
            $input = file_get_contents('php://input');
            if (!$input) {
                sendJsonResponse(['error' => 'Données JSON requises dans le corps de la requête'], 400);
            }
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(['error' => 'Format JSON invalide: ' . json_last_error_msg()], 400);
            }
            
            if (!is_array($data) || !isset($data['pattern']) || !isset($data['action'])) {
                sendJsonResponse(['error' => 'Champs requis: pattern, action'], 400);
            }
            
            $pattern = trim($data['pattern']);
            $action = trim($data['action']);
            
            // Validation stricte
            $validation = validatePattern($pattern, $action);
            if (!$validation['valid']) {
                sendJsonResponse(['error' => $validation['error']], 400);
            }
            
            // Chargement des patterns existants
            $patterns = loadPatterns();
            
            // Vérification des doublons
            foreach ($patterns as $existingPattern) {
                if ($existingPattern['pattern'] === $pattern) {
                    sendJsonResponse(['error' => 'Ce pattern existe déjà'], 409);
                }
            }
            
            // Ajout du nouveau pattern
            $newPattern = ['pattern' => $pattern, 'action' => $action];
            $patterns[] = $newPattern;
            
            if (savePatterns($patterns)) {
                logActivity('ADD', "Pattern: {$pattern} -> {$action}");
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Pattern ajouté avec succès',
                    'pattern' => $newPattern,
                    'total_patterns' => count($patterns)
                ], 201);
            } else {
                logActivity('ERROR', "Échec ajout pattern: {$pattern}");
                sendJsonResponse(['error' => 'Erreur lors de la sauvegarde'], 500);
            }
            break;
            
        case 'DELETE':
            if (!$param || $param === 'reset') {
                sendJsonResponse(['error' => 'Pattern à supprimer non spécifié dans l\'URL'], 400);
            }
            
            $patternToDelete = $param;
            
            // Chargement des patterns existants
            $patterns = loadPatterns();
            $originalCount = count($patterns);
            
            // Suppression du pattern
            $patterns = array_filter($patterns, function($p) use ($patternToDelete) {
                return $p['pattern'] !== $patternToDelete;
            });
            
            // Réindexation du tableau
            $patterns = array_values($patterns);
            
            if (count($patterns) === $originalCount) {
                sendJsonResponse(['error' => 'Pattern non trouvé'], 404);
            }
            
            if (savePatterns($patterns)) {
                logActivity('DELETE', "Pattern: {$patternToDelete}");
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Pattern supprimé avec succès',
                    'deleted_pattern' => $patternToDelete,
                    'remaining_patterns' => count($patterns)
                ]);
            } else {
                logActivity('ERROR', "Échec suppression pattern: {$patternToDelete}");
                sendJsonResponse(['error' => 'Erreur lors de la sauvegarde'], 500);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Méthode non autorisée', 'allowed_methods' => ['GET', 'POST', 'DELETE']], 405);
            break;
    }
    
} catch (Exception $e) {
    logActivity('ERROR', 'Exception: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    sendJsonResponse([
        'error' => 'Erreur serveur interne',
        'message' => 'Une erreur critique est survenue',
        'timestamp' => date('c')
    ], 500);
} catch (Error $e) {
    logActivity('FATAL', 'Fatal Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    sendJsonResponse([
        'error' => 'Erreur fatale du serveur',
        'message' => 'Une erreur fatale est survenue',
        'timestamp' => date('c')
    ], 500);
}
?>