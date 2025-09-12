<?php
/**
 * Action: Création de fichier
 * Crée un fichier avec contenu optionnel
 */

function executeAction_createfile($params, $projectPath) {
    if (!isset($params['filename'])) {
        return ['success' => false, 'error' => 'Nom de fichier requis'];
    }
    
    $filename = $params['filename'];
    $content = $params['content'] ?? '';
    
    // Sécurisation du chemin
    if (strpos($filename, '..') !== false || strpos($filename, '\\') !== false) {
        return ['success' => false, 'error' => 'Chemin de fichier non autorisé'];
    }
    
    // Chemin complet
    $projectRoot = getcwd();
    $fullPath = $projectRoot . '/' . ltrim($filename, '/');
    
    // Création des dossiers parents
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return ['success' => false, 'error' => 'Impossible de créer le dossier parent'];
        }
    }
    
    // Écriture du fichier
    if (file_put_contents($fullPath, $content) === false) {
        return ['success' => false, 'error' => 'Impossible d\'écrire le fichier'];
    }
    
    return [
        'success' => true,
        'response' => "✅ Fichier créé: {$filename} (" . strlen($content) . " caractères)"
    ];
}
?>