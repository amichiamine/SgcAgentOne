<?php
/**
 * Action: Suppression de fichier
 * Supprime un fichier en toute sécurité
 */

function executeAction_deletefile($params, $projectPath) {
    if (!isset($params['filename'])) {
        return ['success' => false, 'error' => 'Nom de fichier requis'];
    }
    
    $filename = $params['filename'];
    
    // Sécurisation du chemin
    if (strpos($filename, '..') !== false || strpos($filename, '\\') !== false) {
        return ['success' => false, 'error' => 'Chemin de fichier non autorisé'];
    }
    
    // Chemin complet avec validation de sécurité
    $projectRoot = getcwd();
    $fullPath = $projectRoot . '/' . ltrim($filename, '/');
    
    // Vérification de l'existence
    if (!file_exists($fullPath)) {
        return ['success' => false, 'error' => 'Fichier non trouvé: ' . $filename];
    }
    
    // Normalisation et validation de sécurité avec realpath
    $realFullPath = realpath($fullPath);
    $realProjectRoot = realpath($projectRoot);
    
    if (!$realFullPath || !str_starts_with($realFullPath, $realProjectRoot . '/')) {
        return ['success' => false, 'error' => 'Chemin non autorisé pour des raisons de sécurité'];
    }
    
    // Vérification que c'est bien un fichier et non un dossier
    if (!is_file($fullPath)) {
        return ['success' => false, 'error' => 'Le chemin spécifié n\'est pas un fichier: ' . $filename];
    }
    
    // Suppression du fichier
    if (unlink($fullPath)) {
        return [
            'success' => true,
            'response' => "🗑️ Fichier supprimé: {$filename}",
            'details' => [
                'filename' => $filename,
                'path' => $fullPath
            ]
        ];
    } else {
        return ['success' => false, 'error' => 'Impossible de supprimer le fichier'];
    }
}
?>