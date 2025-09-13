<?php
/**
 * SGC-AgentOne - API REST Files Management
 * Gestion des fichiers pour l'éditeur Monaco
 * Endpoints: GET/POST /api/files/*
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
$allowedOrigin = '*'; // fallback pour développement

foreach ($allowedOrigins as $allowed) {
    if (fnmatch($allowed, $origin)) {
        $allowedOrigin = $origin;
        break;
    }
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

// Log de l'activité
$logFile = $projectRoot . '/sgc-agentone/core/logs/files.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logActivity($action, $details = '') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] FILES API: {$action} | {$details}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Sécurité des chemins de fichiers
function sanitizePath($path) {
    // Suppression des caractères dangereux
    $path = preg_replace('/[^a-zA-Z0-9\/_.-]/', '', $path);
    
    // Suppression des tentatives de remontée de répertoire
    $path = str_replace(['../', '..\\', '../'], '', $path);
    
    // Normalisation des séparateurs
    $path = str_replace('\\', '/', $path);
    
    return ltrim($path, '/');
}

function isAllowedFile($filepath) {
    $allowedExtensions = [
        'txt', 'md', 'json', 'js', 'ts', 'php', 'html', 'css', 'scss',
        'sql', 'py', 'java', 'cpp', 'c', 'h', 'xml', 'yaml', 'yml',
        'ini', 'conf', 'log'
    ];
    
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    return in_array($ext, $allowedExtensions);
}

function getFileInfo($filepath) {
    if (!file_exists($filepath)) {
        return null;
    }
    
    $info = [
        'name' => basename($filepath),
        'path' => $filepath,
        'size' => filesize($filepath),
        'modified' => filemtime($filepath),
        'type' => is_dir($filepath) ? 'directory' : 'file',
        'extension' => pathinfo($filepath, PATHINFO_EXTENSION),
        'readable' => is_readable($filepath),
        'writable' => is_writable($filepath)
    ];
    
    return $info;
}

function buildFileTree($directory, $maxDepth = 3, $currentDepth = 0) {
    if ($currentDepth >= $maxDepth || !is_dir($directory)) {
        return [];
    }
    
    $items = [];
    $files = scandir($directory);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.git') {
            continue;
        }
        
        $fullPath = $directory . '/' . $file;
        $relativePath = str_replace($GLOBALS['projectRoot'] . '/', '', $fullPath);
        
        $item = [
            'name' => $file,
            'path' => $relativePath,
            'type' => is_dir($fullPath) ? 'directory' : 'file'
        ];
        
        if (is_dir($fullPath)) {
            $item['children'] = buildFileTree($fullPath, $maxDepth, $currentDepth + 1);
        } else {
            $item['size'] = filesize($fullPath);
            $item['extension'] = pathinfo($file, PATHINFO_EXTENSION);
        }
        
        $items[] = $item;
    }
    
    // Tri : dossiers d'abord, puis fichiers alphabétiquement
    usort($items, function($a, $b) {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'directory' ? -1 : 1;
        }
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $items;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    
    // Extraction du segment après /api/files
    $pathSegments = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    $action = isset($pathSegments[2]) ? $pathSegments[2] : 'list';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Liste des fichiers dans un répertoire
                    $directory = $_GET['dir'] ?? '.';
                    $directory = sanitizePath($directory);
                    $fullPath = $projectRoot . '/' . $directory;
                    
                    if (!is_dir($fullPath)) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Répertoire non trouvé']);
                        break;
                    }
                    
                    $files = [];
                    $items = scandir($fullPath);
                    
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..' || $item === '.git') {
                            continue;
                        }
                        
                        $itemPath = $fullPath . '/' . $item;
                        $relativePath = $directory . '/' . $item;
                        
                        if (is_file($itemPath) && isAllowedFile($itemPath)) {
                            $files[] = getFileInfo($itemPath);
                        } elseif (is_dir($itemPath)) {
                            $files[] = getFileInfo($itemPath);
                        }
                    }
                    
                    logActivity('LIST', "Directory: {$directory}, Files: " . count($files));
                    echo json_encode($files);
                    break;
                    
                case 'tree':
                    // Arbre complet des fichiers
                    $tree = [
                        'root' => $projectRoot,
                        'items' => buildFileTree($projectRoot)
                    ];
                    
                    logActivity('TREE', 'File tree generated');
                    echo json_encode($tree);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Action non trouvée']);
                    break;
            }
            break;
            
        case 'POST':
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            switch ($action) {
                case 'read':
                    // Lecture d'un fichier
                    if (!isset($data['filepath'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Chemin de fichier requis']);
                        break;
                    }
                    
                    $filepath = sanitizePath($data['filepath']);
                    $fullPath = $projectRoot . '/' . $filepath;
                    
                    if (!file_exists($fullPath) || !is_file($fullPath)) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Fichier non trouvé']);
                        break;
                    }
                    
                    if (!isAllowedFile($fullPath)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Type de fichier non autorisé']);
                        break;
                    }
                    
                    if (!is_readable($fullPath)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Fichier non lisible']);
                        break;
                    }
                    
                    $content = file_get_contents($fullPath);
                    $fileInfo = getFileInfo($fullPath);
                    
                    $response = [
                        'filepath' => $filepath,
                        'content' => $content,
                        'info' => $fileInfo,
                        'encoding' => 'utf-8'
                    ];
                    
                    logActivity('READ', "File: {$filepath}, Size: " . strlen($content) . " bytes");
                    echo json_encode($response);
                    break;
                    
                case 'save':
                    // Sauvegarde d'un fichier
                    if (!isset($data['filepath']) || !isset($data['content'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Chemin et contenu requis']);
                        break;
                    }
                    
                    $filepath = sanitizePath($data['filepath']);
                    $content = $data['content'];
                    $fullPath = $projectRoot . '/' . $filepath;
                    
                    if (!isAllowedFile($fullPath)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Type de fichier non autorisé']);
                        break;
                    }
                    
                    // Créer le répertoire parent si nécessaire
                    $directory = dirname($fullPath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    
                    // Sauvegarde avec verrou
                    $result = file_put_contents($fullPath, $content, LOCK_EX);
                    
                    if ($result === false) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erreur lors de la sauvegarde']);
                        break;
                    }
                    
                    $fileInfo = getFileInfo($fullPath);
                    
                    $response = [
                        'success' => true,
                        'filepath' => $filepath,
                        'bytes_written' => $result,
                        'info' => $fileInfo,
                        'message' => 'Fichier sauvegardé avec succès'
                    ];
                    
                    logActivity('SAVE', "File: {$filepath}, Size: {$result} bytes");
                    echo json_encode($response);
                    break;
                    
                case 'create':
                    // Création d'un nouveau fichier
                    if (!isset($data['filepath'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Chemin de fichier requis']);
                        break;
                    }
                    
                    $filepath = sanitizePath($data['filepath']);
                    $content = $data['content'] ?? '';
                    $fullPath = $projectRoot . '/' . $filepath;
                    
                    if (file_exists($fullPath)) {
                        http_response_code(409);
                        echo json_encode(['error' => 'Le fichier existe déjà']);
                        break;
                    }
                    
                    if (!isAllowedFile($fullPath)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Type de fichier non autorisé']);
                        break;
                    }
                    
                    // Créer le répertoire parent si nécessaire
                    $directory = dirname($fullPath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    
                    $result = file_put_contents($fullPath, $content, LOCK_EX);
                    
                    if ($result === false) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erreur lors de la création']);
                        break;
                    }
                    
                    $fileInfo = getFileInfo($fullPath);
                    
                    $response = [
                        'success' => true,
                        'filepath' => $filepath,
                        'created' => true,
                        'info' => $fileInfo,
                        'message' => 'Fichier créé avec succès'
                    ];
                    
                    logActivity('CREATE', "File: {$filepath}");
                    echo json_encode($response);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Action non trouvée']);
                    break;
            }
            break;
            
        case 'DELETE':
            switch ($action) {
                case 'delete':
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    
                    if (!isset($data['filepath'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Chemin de fichier requis']);
                        break;
                    }
                    
                    $filepath = sanitizePath($data['filepath']);
                    $fullPath = $projectRoot . '/' . $filepath;
                    
                    if (!file_exists($fullPath)) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Fichier non trouvé']);
                        break;
                    }
                    
                    if (!isAllowedFile($fullPath)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Type de fichier non autorisé']);
                        break;
                    }
                    
                    if (unlink($fullPath)) {
                        logActivity('DELETE', "File: {$filepath}");
                        echo json_encode([
                            'success' => true,
                            'filepath' => $filepath,
                            'message' => 'Fichier supprimé avec succès'
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Erreur lors de la suppression']);
                    }
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Action non trouvée']);
                    break;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    logActivity('ERROR', $e->getMessage());
    echo json_encode([
        'error' => 'Erreur serveur',
        'message' => 'Une erreur interne est survenue'
    ]);
}
?>