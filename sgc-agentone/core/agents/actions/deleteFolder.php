<?php
/**
 * Action: Suppression de dossier
 * Supprime un dossier en toute sécurité (vide ou avec contenu selon paramètre)
 */

// Fonction récursive globale pour supprimer un dossier et son contenu
if (!function_exists('removeDirRecursive')) {
    function removeDirRecursive($dir) {
        if (!is_dir($dir)) return false;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                removeDirRecursive($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }
}

function executeAction_deletefolder($params, $projectPath) {
    if (!isset($params['foldername'])) {
        return ['success' => false, 'error' => 'Nom de dossier requis'];
    }
    
    $foldername = $params['foldername'];
    $recursive = $params['recursive'] ?? false; // Par défaut, ne supprime que les dossiers vides
    
    // Sécurisation du chemin
    if (strpos($foldername, '..') !== false || strpos($foldername, '\\') !== false) {
        return ['success' => false, 'error' => 'Chemin de dossier non autorisé'];
    }
    
    // Chemin complet avec validation de sécurité
    $projectRoot = getcwd();
    $fullPath = $projectRoot . '/' . ltrim($foldername, '/');
    
    // Vérification de l'existence
    if (!file_exists($fullPath)) {
        return ['success' => false, 'error' => 'Dossier non trouvé: ' . $foldername];
    }
    
    // Vérification que c'est bien un dossier
    if (!is_dir($fullPath)) {
        return ['success' => false, 'error' => 'Le chemin spécifié n\'est pas un dossier: ' . $foldername];
    }
    
    // Normalisation et validation de sécurité avec realpath
    $realFullPath = realpath($fullPath);
    $realProjectRoot = realpath($projectRoot);
    
    if (!$realFullPath || !str_starts_with($realFullPath, $realProjectRoot . '/')) {
        return ['success' => false, 'error' => 'Chemin non autorisé pour des raisons de sécurité'];
    }
    
    // Protection contre la suppression du root projet
    if ($realFullPath === $realProjectRoot) {
        return ['success' => false, 'error' => 'Suppression de ce dossier interdite pour des raisons de sécurité'];
    }
    
    // Protection contre la suppression des dossiers applicatifs critiques et leurs sous-dossiers
    $sgcPath = realpath($projectRoot . '/sgc-agentone');
    $corePath = realpath($projectRoot . '/sgc-agentone/core');
    $protectedPrefixes = array_filter([$sgcPath, $corePath]);
    
    foreach ($protectedPrefixes as $protectedPrefix) {
        if ($realFullPath === $protectedPrefix || str_starts_with($realFullPath, $protectedPrefix . '/')) {
            return ['success' => false, 'error' => 'Suppression de ce dossier interdite pour des raisons de sécurité'];
        }
    }
    
    if ($foldername === '' || $foldername === '.') {
        return ['success' => false, 'error' => 'Suppression de ce dossier interdite pour des raisons de sécurité'];
    }
    
    
    // Vérification si le dossier est vide
    $isEmpty = count(array_diff(scandir($fullPath), ['.', '..'])) === 0;
    
    if (!$isEmpty && !$recursive) {
        return [
            'success' => false, 
            'error' => 'Le dossier n\'est pas vide. Utilisez "supprime le dossier [nom] avec contenu" pour forcer la suppression.'
        ];
    }
    
    // Suppression du dossier
    $result = false;
    if ($recursive && !$isEmpty) {
        $result = removeDirRecursive($fullPath);
        $message = "🗑️ Dossier et contenu supprimés: {$foldername}";
    } else {
        $result = rmdir($fullPath);
        $message = "🗑️ Dossier supprimé: {$foldername}";
    }
    
    if ($result) {
        return [
            'success' => true,
            'response' => $message,
            'details' => [
                'foldername' => $foldername,
                'path' => $fullPath,
                'was_recursive' => $recursive,
                'was_empty' => $isEmpty
            ]
        ];
    } else {
        return ['success' => false, 'error' => 'Impossible de supprimer le dossier'];
    }
}
?>