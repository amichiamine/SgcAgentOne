<?php
/**
 * SGC-AgentOne - API REST Server Management
 * Gestion et monitoring du serveur PHP intégré
 * Endpoints: GET/POST /api/server/*
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
$logFile = $projectRoot . '/sgc-agentone/core/logs/server.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logActivity($action, $details = '') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] SERVER API: {$action} | {$details}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Fichier pour stocker les statistiques du serveur
$statsFile = $projectRoot . '/sgc-agentone/core/config/server_stats.json';

// Fonctions utilitaires pour les statistiques serveur
function getServerStats() {
    global $statsFile, $projectRoot;
    
    $stats = [];
    if (file_exists($statsFile)) {
        $stats = json_decode(file_get_contents($statsFile), true) ?: [];
    }
    
    // Données par défaut si pas de fichier
    if (empty($stats)) {
        $stats = [
            'start_time' => time(),
            'total_requests' => 0,
            'routes' => [],
            'last_updated' => time()
        ];
    }
    
    return $stats;
}

function updateServerStats($route = '', $increment = true) {
    global $statsFile;
    
    $stats = getServerStats();
    
    if ($increment) {
        $stats['total_requests'] = ($stats['total_requests'] ?? 0) + 1;
    }
    
    if ($route) {
        if (!isset($stats['routes'][$route])) {
            $stats['routes'][$route] = 0;
        }
        $stats['routes'][$route]++;
    }
    
    $stats['last_updated'] = time();
    
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT), LOCK_EX);
    return $stats;
}

function getSystemInfo() {
    return [
        'php_version' => phpversion(),
        'memory_limit' => ini_get('memory_limit'),
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2),
        'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2),
        'process_id' => getmypid(),
        'server_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];
}

function getServerLogs($lines = 50) {
    global $projectRoot;
    
    $logs = [];
    $logFiles = [
        $projectRoot . '/sgc-agentone/core/logs/server.log',
        $projectRoot . '/sgc-agentone/core/logs/chat.log',
        $projectRoot . '/sgc-agentone/core/logs/actions.log'
    ];
    
    foreach ($logFiles as $logFile) {
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $fileLines = array_filter(explode("\n", $content));
            $recentLines = array_slice($fileLines, -$lines);
            
            foreach ($recentLines as $line) {
                if (preg_match('/\[([^\]]+)\]\s*([^:]+):\s*(.*)/', $line, $matches)) {
                    $logs[] = [
                        'timestamp' => $matches[1],
                        'source' => trim($matches[2]),
                        'message' => trim($matches[3]),
                        'level' => detectLogLevel($line)
                    ];
                }
            }
        }
    }
    
    // Tri par timestamp (plus récent en premier)
    usort($logs, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    return array_slice($logs, 0, $lines);
}

function detectLogLevel($logLine) {
    $lower = strtolower($logLine);
    if (strpos($lower, 'error') !== false || strpos($lower, 'fatal') !== false) {
        return 'error';
    } elseif (strpos($lower, 'warning') !== false || strpos($lower, 'warn') !== false) {
        return 'warning';
    } elseif (strpos($lower, 'debug') !== false) {
        return 'debug';
    } else {
        return 'info';
    }
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    
    // Extraction du segment après /api/server
    $pathSegments = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    $action = isset($pathSegments[2]) ? $pathSegments[2] : 'status';
    
    // Mise à jour des stats pour cette requête
    updateServerStats($path);
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'status':
                    // Statut complet du serveur
                    $stats = getServerStats();
                    $systemInfo = getSystemInfo();
                    
                    $uptime = time() - ($stats['start_time'] ?? time());
                    
                    // Top routes (les plus appelées)
                    $topRoutes = [];
                    if (isset($stats['routes']) && is_array($stats['routes'])) {
                        arsort($stats['routes']);
                        $topRoutes = array_slice($stats['routes'], 0, 10, true);
                        $topRoutes = array_map(function($count, $route) {
                            return ['route' => $route, 'count' => $count];
                        }, $topRoutes, array_keys($topRoutes));
                    }
                    
                    $response = [
                        'status' => 'running',
                        'uptime' => $uptime,
                        'port' => $settings['port'] ?? 5000,
                        'pid' => $systemInfo['process_id'],
                        'memory' => $systemInfo['memory_usage'],
                        'memoryPeak' => $systemInfo['memory_peak'],
                        'memoryLimit' => $systemInfo['memory_limit'],
                        'phpVersion' => $systemInfo['php_version'],
                        'totalRequests' => $stats['total_requests'] ?? 0,
                        'requestsPerSecond' => $uptime > 0 ? round(($stats['total_requests'] ?? 0) / $uptime, 2) : 0,
                        'activeConnections' => 1, // Estimation basique
                        'topRoutes' => $topRoutes,
                        'serverTime' => $systemInfo['server_time'],
                        'timezone' => $systemInfo['timezone']
                    ];
                    
                    logActivity('STATUS', "Uptime: {$uptime}s, Memory: {$systemInfo['memory_usage']}MB");
                    echo json_encode($response);
                    break;
                    
                case 'logs':
                    // Logs du serveur en temps réel
                    $lines = $_GET['lines'] ?? 50;
                    $logs = getServerLogs(min($lines, 200)); // Limite maximum de 200 lignes
                    
                    logActivity('LOGS', count($logs) . ' lignes récupérées');
                    echo json_encode($logs);
                    break;
                    
                case 'config':
                    // Configuration actuelle du serveur
                    $config = [
                        'port' => $settings['port'] ?? 5000,
                        'security' => $settings['security'] ?? [],
                        'memory_limit' => ini_get('memory_limit'),
                        'max_execution_time' => ini_get('max_execution_time'),
                        'upload_max_filesize' => ini_get('upload_max_filesize'),
                        'post_max_size' => ini_get('post_max_size'),
                        'timezone' => date_default_timezone_get()
                    ];
                    
                    logActivity('CONFIG', 'Configuration récupérée');
                    echo json_encode($config);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Action non trouvée']);
                    break;
            }
            break;
            
        case 'POST':
            if ($action === 'control') {
                // Contrôle du serveur (restart, stop, health, etc.)
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data || !isset($data['action'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Action requise']);
                    break;
                }
                
                $controlAction = $data['action'];
                
                switch ($controlAction) {
                    case 'restart':
                        // Simulation du redémarrage
                        logActivity('RESTART', 'Redémarrage demandé');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Redémarrage du serveur en cours...',
                            'estimated_time' => 3
                        ]);
                        break;
                        
                    case 'stop':
                        // Simulation de l'arrêt
                        logActivity('STOP', 'Arrêt du serveur demandé');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Arrêt du serveur en cours...'
                        ]);
                        break;
                        
                    case 'health':
                        // Test de santé du serveur
                        $health = [
                            'status' => 'healthy',
                            'checks' => [
                                'memory' => memory_get_usage() < (1024 * 1024 * 100), // < 100MB
                                'disk_space' => disk_free_space('.') > (1024 * 1024 * 100), // > 100MB libre
                                'config_files' => file_exists($settingsFile),
                                'log_directory' => is_writable(dirname($logFile)),
                                'database' => file_exists($projectRoot . '/sgc-agentone/core/db/app.db')
                            ]
                        ];
                        
                        $allHealthy = array_reduce($health['checks'], function($carry, $check) {
                            return $carry && $check;
                        }, true);
                        
                        $health['status'] = $allHealthy ? 'healthy' : 'warning';
                        $health['message'] = $allHealthy ? 'Tous les tests passés' : 'Quelques problèmes détectés';
                        
                        logActivity('HEALTH', $health['status']);
                        echo json_encode($health);
                        break;
                        
                    case 'changePort':
                        // Changement de port
                        $newPort = $data['port'] ?? 5000;
                        if ($newPort < 3000 || $newPort > 65535) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Port invalide (3000-65535)']);
                            break;
                        }
                        
                        // Mise à jour du fichier settings
                        $settings['port'] = $newPort;
                        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT), LOCK_EX);
                        
                        logActivity('PORT_CHANGE', "Nouveau port: {$newPort}");
                        echo json_encode([
                            'success' => true,
                            'message' => "Port changé vers {$newPort}. Redémarrage nécessaire.",
                            'new_port' => $newPort
                        ]);
                        break;
                        
                    case 'flushCache':
                        // Vidage du cache PHP (si opcache est activé)
                        $cacheCleared = false;
                        if (function_exists('opcache_reset')) {
                            opcache_reset();
                            $cacheCleared = true;
                        }
                        
                        // Nettoyage des fichiers temporaires
                        $tempFiles = glob($projectRoot . '/sgc-agentone/core/logs/*.tmp');
                        foreach ($tempFiles as $file) {
                            unlink($file);
                        }
                        
                        logActivity('FLUSH_CACHE', $cacheCleared ? 'OpCache + temp files' : 'Temp files only');
                        echo json_encode([
                            'success' => true,
                            'message' => $cacheCleared ? 'Cache PHP et fichiers temporaires vidés' : 'Fichiers temporaires vidés',
                            'opcache_cleared' => $cacheCleared
                        ]);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Action de contrôle inconnue']);
                        break;
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint non trouvé']);
            }
            break;
            
        case 'PUT':
            if ($action === 'config') {
                // Mise à jour de la configuration
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Données de configuration requises']);
                    break;
                }
                
                // Validation et application de la configuration
                $updatedSettings = $settings;
                
                if (isset($data['memoryLimit'])) {
                    ini_set('memory_limit', $data['memoryLimit']);
                }
                
                if (isset($data['timeout'])) {
                    ini_set('max_execution_time', $data['timeout']);
                }
                
                if (isset($data['debugMode'])) {
                    $updatedSettings['debug'] = $data['debugMode'];
                }
                
                // Sauvegarde des paramètres
                file_put_contents($settingsFile, json_encode($updatedSettings, JSON_PRETTY_PRINT), LOCK_EX);
                
                logActivity('CONFIG_UPDATE', json_encode($data));
                echo json_encode([
                    'success' => true,
                    'message' => 'Configuration mise à jour',
                    'applied_settings' => $data
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint non trouvé']);
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