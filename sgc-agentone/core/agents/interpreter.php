<?php
/**
 * SGC-AgentOne - Interpréteur de langage naturel
 * Traduit les phrases utilisateur en commandes internes
 * Supporte français et anglais
 */

/**
 * Interprète un message utilisateur
 * @param string $message
 * @return array|false ['action' => string, 'params' => array]
 */
function interpretMessage($message) {
    $projectRoot = getcwd();
    $rulesFile = $projectRoot . '/sgc-agentone/core/config/rules.json';
    
    // Chargement des règles
    if (!file_exists($rulesFile)) {
        return false;
    }
    
    $rules = json_decode(file_get_contents($rulesFile), true);
    if (!$rules) {
        return false;
    }
    
    $message = trim(strtolower($message));
    
    // Recherche de correspondance avec les patterns
    foreach ($rules as $rule) {
        if (matchesPattern($message, $rule['pattern'])) {
            $params = extractParams($message, $rule['pattern'], $rule['action']);
            return [
                'action' => $rule['action'],
                'params' => $params
            ];
        }
    }
    
    return false;
}

/**
 * Vérifie si un message correspond à un pattern
 */
function matchesPattern($message, $pattern) {
    $pattern = strtolower($pattern);
    
    // Patterns avec wildcards
    if (strpos($pattern, '*') !== false) {
        $regex = '/' . str_replace('*', '.*', preg_quote($pattern, '/')) . '/';
        return preg_match($regex, $message);
    }
    
    // Correspondance exacte d'abord (priorité)
    if (trim($message) === trim($pattern)) {
        return true;
    }
    
    // Recherche de mots-clés (pour les patterns plus complexes)
    $keywords = explode(' ', $pattern);
    $messageWords = explode(' ', $message);
    
    // Pour les patterns help, exiger une correspondance plus stricte
    if (strpos($pattern, 'help') === 0 || strpos($pattern, 'aide') === 0) {
        // Pour les patterns help spécifiques (ex: "help chat", "help ide")
        if (count($keywords) > 1) {
            // Tous les mots du pattern doivent être présents dans l'ordre
            $patternIndex = 0;
            foreach ($messageWords as $word) {
                if ($patternIndex < count($keywords) && $word === $keywords[$patternIndex]) {
                    $patternIndex++;
                }
            }
            return $patternIndex === count($keywords);
        }
        // Pour les patterns help simples (ex: "help", "aide")
        else {
            return in_array(trim($pattern), $messageWords);
        }
    }
    
    // Logique normale pour les autres patterns
    $matchCount = 0;
    foreach ($keywords as $keyword) {
        if (strpos($message, trim($keyword)) !== false) {
            $matchCount++;
        }
    }
    
    // Au moins 50% des mots-clés doivent correspondre
    return $matchCount >= ceil(count($keywords) * 0.5);
}

/**
 * Extrait les paramètres du message selon l'action
 */
function extractParams($message, $pattern, $action) {
    $params = [];
    
    switch ($action) {
        case 'create file':
            $params = extractFileParams($message, 'create');
            break;
            
        case 'update file':
            $params = extractFileParams($message, 'update');
            break;
            
        case 'read file':
            $params = extractFileParams($message, 'read');
            break;
            
        case 'delete file':
            $params = extractFileParams($message, 'delete');
            break;
            
        case 'create folder':
            $params = extractFolderParams($message, 'create');
            break;
            
        case 'delete folder':
            $params = extractFolderParams($message, 'delete');
            break;
            
        case 'execute query':
            $params = extractQueryParams($message);
            break;
            
        case 'create database':
            $params = ['name' => 'app.db'];
            break;
            
        default:
            $params = ['raw_message' => $message];
    }
    
    return $params;
}

/**
 * Extrait les paramètres liés aux fichiers
 */
function extractFileParams($message, $operation) {
    $params = [];
    
    // Recherche de nom de fichier
    if (preg_match('/(?:fichier|file)\s+([a-zA-Z0-9\-_\.\/]+)/', $message, $matches)) {
        $params['filename'] = $matches[1];
    } elseif (preg_match('/([a-zA-Z0-9\-_\.\/]+\.[a-zA-Z]+)/', $message, $matches)) {
        $params['filename'] = $matches[1];
    }
    
    // Extraction de contenu pour création/modification
    if ($operation === 'create' || $operation === 'update') {
        if (preg_match('/(?:contenu|content|avec)\s+"([^"]+)"/', $message, $matches)) {
            $params['content'] = $matches[1];
        } elseif (preg_match('/(?:contenu|content|avec)\s+(.+)/', $message, $matches)) {
            $params['content'] = trim($matches[1]);
        }
        
        // Détection du type de fichier
        if (isset($params['filename'])) {
            $ext = pathinfo($params['filename'], PATHINFO_EXTENSION);
            $params['type'] = $ext;
            
            // Contenu par défaut selon le type
            if (!isset($params['content'])) {
                $params['content'] = getDefaultContent($ext);
            }
        }
    }
    
    return $params;
}

/**
 * Extrait les paramètres liés aux dossiers
 */
function extractFolderParams($message, $operation) {
    $params = [];
    
    // Recherche de nom de dossier
    if (preg_match('/(?:dossier|folder|répertoire|directory)\s+([a-zA-Z0-9\-_\.\/]+)/', $message, $matches)) {
        $params['foldername'] = $matches[1];
    } elseif (preg_match('/(?:dans|vers|le)\s+([a-zA-Z0-9\-_\/]+)/', $message, $matches)) {
        // Alternative pour les patterns comme "crée dans [nom]"
        $params['foldername'] = $matches[1];
    }
    
    // Pour la suppression, détecter si c'est récursif (français et anglais)
    if ($operation === 'delete') {
        if (strpos($message, 'avec contenu') !== false || 
            strpos($message, 'récursif') !== false || 
            strpos($message, 'force') !== false ||
            strpos($message, 'tout') !== false ||
            strpos($message, 'with content') !== false ||
            strpos($message, 'recursive') !== false ||
            strpos($message, 'all') !== false) {
            $params['recursive'] = true;
        }
    }
    
    return $params;
}

/**
 * Extrait les paramètres de requête SQL
 */
function extractQueryParams($message) {
    $params = [];
    
    // Recherche de requête SQL directe
    if (preg_match('/(SELECT|INSERT|UPDATE|CREATE|DELETE)\s+.+/i', $message, $matches)) {
        $params['query'] = $matches[0];
    } else {
        // Génération de requête basée sur l'intention
        if (strpos($message, 'table') !== false && strpos($message, 'crée') !== false) {
            if (preg_match('/table\s+([a-zA-Z_]+)/', $message, $matches)) {
                $tableName = $matches[1];
                $params['query'] = "CREATE TABLE IF NOT EXISTS {$tableName} (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)";
            }
        }
    }
    
    return $params;
}

/**
 * Retourne le contenu par défaut selon le type de fichier
 */
function getDefaultContent($extension) {
    $templates = [
        'html' => '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>Contenu</h1></body></html>',
        'css' => '/* Styles CSS */\nbody { margin: 0; padding: 0; }',
        'js' => '// JavaScript\nconsole.log("Hello World");',
        'php' => '<?php\n// PHP Code\necho "Hello World";\n?>',
        'json' => '{\n  "name": "config",\n  "version": "1.0.0"\n}',
        'txt' => 'Contenu texte',
        'md' => '# Titre\n\nContenu markdown.'
    ];
    
    return $templates[$extension] ?? '';
}
?>