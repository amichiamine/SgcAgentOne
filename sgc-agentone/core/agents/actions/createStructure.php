<?php
/**
 * Action: Création de structure de projet
 * Crée une structure complète de dossiers et fichiers à partir d'une description textuelle
 */

function executeAction_createstructure($params, $projectPath) {
    if (!isset($params['structure'])) {
        return ['success' => false, 'error' => 'Description de structure requise'];
    }
    
    $structure = $params['structure'];
    $projectRoot = getcwd();
    $realProjectRoot = realpath($projectRoot);
    
    // Parser la structure selon différents formats
    $items = parseStructure($structure);
    
    if (empty($items)) {
        return ['success' => false, 'error' => 'Aucun élément détecté dans la structure'];
    }
    
    $created = ['folders' => [], 'files' => []];
    $errors = [];
    
    // Trier les éléments : dossiers d'abord, puis fichiers
    usort($items, function($a, $b) {
        if ($a['type'] === 'folder' && $b['type'] === 'file') return -1;
        if ($a['type'] === 'file' && $b['type'] === 'folder') return 1;
        return strlen($a['path']) - strlen($b['path']); // Plus courts d'abord
    });
    
    foreach ($items as $item) {
        $path = $item['path'];
        $type = $item['type'];
        
        // Validation sécurité - vérifier path traversal
        if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
            $errors[] = "Chemin non autorisé: $path";
            continue;
        }
        
        // Construire le chemin complet
        $fullPath = $realProjectRoot . '/' . ltrim($path, '/');
        
        // Vérifier containment dans le projet
        $probe = dirname($fullPath);
        while (!file_exists($probe) && $probe !== dirname($probe)) {
            $probe = dirname($probe);
        }
        
        $realProbe = realpath($probe);
        if (!$realProbe || ($realProbe !== $realProjectRoot && !str_starts_with($realProbe, $realProjectRoot . '/'))) {
            $errors[] = "Chemin non autorisé pour des raisons de sécurité: $path";
            continue;
        }
        
        // Protection contre les dossiers système
        $sgcPath = realpath($projectRoot . '/sgc-agentone');
        $corePath = realpath($projectRoot . '/sgc-agentone/core');
        $protectedPrefixes = array_filter([$sgcPath, $corePath]);
        
        $isProtected = false;
        foreach ($protectedPrefixes as $protectedPrefix) {
            if (str_starts_with($fullPath, $protectedPrefix . '/')) {
                $isProtected = true;
                break;
            }
        }
        
        if ($isProtected) {
            $errors[] = "Création dans dossier protégé interdite: $path";
            continue;
        }
        
        try {
            if ($type === 'folder') {
                // Créer le dossier
                if (!file_exists($fullPath)) {
                    if (mkdir($fullPath, 0755, true)) {
                        $created['folders'][] = $path;
                    } else {
                        $errors[] = "Impossible de créer le dossier: $path";
                    }
                } else if (is_dir($fullPath)) {
                    // Dossier existe déjà, on continue
                    $created['folders'][] = $path . ' (existait déjà)';
                } else {
                    $errors[] = "Un fichier existe déjà avec ce nom: $path";
                }
            } else if ($type === 'file') {
                // Créer le fichier
                if (!file_exists($fullPath)) {
                    // S'assurer que le dossier parent existe
                    $parentDir = dirname($fullPath);
                    if (!file_exists($parentDir)) {
                        mkdir($parentDir, 0755, true);
                    }
                    
                    if (file_put_contents($fullPath, '') !== false) {
                        $created['files'][] = $path;
                    } else {
                        $errors[] = "Impossible de créer le fichier: $path";
                    }
                } else {
                    $errors[] = "Le fichier existe déjà: $path";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Erreur lors de la création de $path: " . $e->getMessage();
        }
    }
    
    // Construire le message de réponse
    $response = [];
    
    if (!empty($created['folders'])) {
        $response[] = "📁 Dossiers créés: " . implode(', ', $created['folders']);
    }
    
    if (!empty($created['files'])) {
        $response[] = "📄 Fichiers créés: " . implode(', ', $created['files']);
    }
    
    if (!empty($errors)) {
        $response[] = "⚠️ Erreurs: " . implode(', ', $errors);
    }
    
    if (empty($created['folders']) && empty($created['files'])) {
        return ['success' => false, 'error' => 'Aucun élément créé. ' . implode(', ', $errors)];
    }
    
    return [
        'success' => true,
        'response' => implode(' | ', $response),
        'created' => $created,
        'errors' => $errors
    ];
}

/**
 * Parse une description de structure en éléments créables
 * Supporte les formats: arbre ASCII, liste, mixte
 */
function parseStructure($structure) {
    $items = [];
    $lines = explode("\n", $structure);
    
    // Détecter si c'est un format arbre ASCII
    $isTreeFormat = false;
    foreach ($lines as $line) {
        if (preg_match('/[├└│─]/', $line)) {
            $isTreeFormat = true;
            break;
        }
    }
    
    if ($isTreeFormat) {
        $items = parseTreeStructure($lines);
    } else {
        // Traitement ligne par ligne pour autres formats
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Format liste avec mots-clés
            if (preg_match('/(?:dossiers?|folders?)\s*[:]*\s*(.+)/i', $line, $matches)) {
                $paths = extractPathsFromList($matches[1]);
                foreach ($paths as $path) {
                    $items[] = ['path' => $path, 'type' => 'folder'];
                }
            }
            else if (preg_match('/(?:fichiers?|files?)\s*[:]*\s*(.+)/i', $line, $matches)) {
                $paths = extractPathsFromList($matches[1]);
                foreach ($paths as $path) {
                    $items[] = ['path' => $path, 'type' => 'file'];
                }
            }
            // Format simple avec éléments séparés par espaces
            else if (preg_match('/[\w\-\/.]+/', $line)) {
                // Extraire tous les éléments de la ligne
                preg_match_all('/[\w\-\/\.]+/', $line, $matches);
                foreach ($matches[0] as $element) {
                    $items[] = [
                        'path' => $element,
                        'type' => (str_ends_with($element, '/') || !str_contains($element, '.')) ? 'folder' : 'file'
                    ];
                }
            }
        }
    }
    
    return $items;
}

/**
 * Parse une structure arbre ASCII avec reconstruction de hiérarchie
 */
function parseTreeStructure($lines) {
    $items = [];
    $pathStack = []; // Stack pour maintenir la hiérarchie
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        // Calculer la profondeur et extraire le nom
        $depth = calculateTreeDepth($line);
        $name = extractNameFromTreeLine($line);
        
        if ($name === null) continue;
        
        // Ajuster la stack selon la profondeur
        while (count($pathStack) > $depth) {
            array_pop($pathStack);
        }
        
        // Construire le chemin complet
        $fullPath = count($pathStack) > 0 ? implode('/', $pathStack) . '/' . $name : $name;
        
        // Déterminer le type (dossier ou fichier)
        $isFolder = str_ends_with($name, '/') || !str_contains($name, '.') || preg_match('/[├└│─].*\/$/', $line);
        
        $items[] = [
            'path' => $fullPath,
            'type' => $isFolder ? 'folder' : 'file'
        ];
        
        // Si c'est un dossier, l'ajouter à la stack
        if ($isFolder) {
            $folderName = rtrim($name, '/');
            if (count($pathStack) <= $depth) {
                $pathStack[] = $folderName;
            } else {
                $pathStack[$depth] = $folderName;
            }
        }
    }
    
    return $items;
}

/**
 * Calcule la profondeur d'une ligne d'arbre basée sur l'indentation
 */
function calculateTreeDepth($line) {
    // Détection des groupes de préfixes standard ("│   " ou "    ") suivis par un connecteur
    if (preg_match('/^((?:│\s{3}|\s{4})*)(?:├|└)/u', $line, $matches)) {
        // Compter le nombre de groupes de 4 caractères avant le connecteur
        $groupCount = preg_match_all('/(?:│\s{3}|\s{4})/u', $matches[1], $_);
        return $groupCount + 1; // +1 pour le niveau du connecteur
    }
    
    // Lignes sans connecteur (comme la racine "test-project/")
    if (preg_match('/^[^│├└─]*[^\s]/', $line)) {
        return 0; // Niveau racine
    }
    
    // Fallback pour les autres cas
    return 0;
}

/**
 * Extrait le nom du fichier/dossier d'une ligne d'arbre
 */
function extractNameFromTreeLine($line) {
    // Supprimer le préfixe standard de l'arbre ASCII (groupes + connecteur)
    $cleaned = preg_replace('/^(?:│\s{3}|\s{4})*(?:├|└)──\s*/u', '', $line);
    $cleaned = trim($cleaned);
    
    // Si pas de préfixe standard détecté, fallback vers la méthode simple
    if ($cleaned === trim($line)) {
        $cleaned = preg_replace('/^[\s├└│─]*/', '', $line);
        $cleaned = trim($cleaned);
    }
    
    if (empty($cleaned)) return null;
    
    return $cleaned;
}


/**
 * Extrait les chemins d'une liste séparée par virgules
 */
function extractPathsFromList($listText) {
    $paths = [];
    $items = explode(',', $listText);
    
    foreach ($items as $item) {
        $path = trim($item);
        if (!empty($path)) {
            $paths[] = $path;
        }
    }
    
    return $paths;
}
?>