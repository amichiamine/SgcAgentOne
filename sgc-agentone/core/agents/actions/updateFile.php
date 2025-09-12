<?php
/**
 * Action: Modification de fichier
 * Remplace le contenu d'un fichier existant
 */

function executeAction_updatefile($params, $projectPath) {
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
    
    // Vérification de l'existence
    if (!file_exists($fullPath)) {
        return ['success' => false, 'error' => 'Fichier non trouvé: ' . $filename];
    }
    
    // Écriture du nouveau contenu
    if (file_put_contents($fullPath, $content) === false) {
        return ['success' => false, 'error' => 'Impossible de modifier le fichier'];
    }
    
    return [
        'success' => true,
        'response' => "✅ Fichier modifié: {$filename} (" . strlen($content) . " caractères)"
    ];
}
?>