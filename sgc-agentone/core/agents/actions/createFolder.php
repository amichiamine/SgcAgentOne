<?php
/**
 * Action: Création de dossier
 * Crée un nouveau dossier avec gestion des dossiers parents
 */

function executeAction_createfolder($params, $projectPath) {
    if (!isset($params['foldername'])) {
        return ['success' => false, 'error' => 'Nom de dossier requis'];
    }
    
    $foldername = $params['foldername'];
    
    // Sécurisation du chemin
    if (strpos($foldername, '..') !== false || strpos($foldername, '\\') !== false) {
        return ['success' => false, 'error' => 'Chemin de dossier non autorisé'];
    }
    
    // Chemin complet avec validation de sécurité  
    $projectRoot = getcwd();
    $realProjectRoot = realpath($projectRoot);
    $target = $realProjectRoot . '/' . ltrim($foldername, '/');
    
    // Trouver l'ancêtre existant le plus profond pour validation de sécurité
    $probe = $target;
    while (!file_exists($probe) && $probe !== dirname($probe)) {
        $probe = dirname($probe);
    }
    
    // Vérifier que l'ancêtre existant est dans le projet
    $realProbe = realpath($probe);
    if (!$realProbe || ($realProbe !== $realProjectRoot && !str_starts_with($realProbe, $realProjectRoot . '/'))) {
        return ['success' => false, 'error' => 'Chemin non autorisé pour des raisons de sécurité'];
    }
    
    $fullPath = $target;
    
    // Vérification si le dossier existe déjà
    if (file_exists($fullPath)) {
        if (is_dir($fullPath)) {
            return ['success' => false, 'error' => 'Le dossier existe déjà: ' . $foldername];
        } else {
            return ['success' => false, 'error' => 'Un fichier avec ce nom existe déjà: ' . $foldername];
        }
    }
    
    // Création du dossier (avec dossiers parents si nécessaire)
    if (mkdir($fullPath, 0755, true)) {
        return [
            'success' => true,
            'response' => "📁 Dossier créé: {$foldername}",
            'details' => [
                'foldername' => $foldername,
                'path' => $fullPath,
                'permissions' => '0755'
            ]
        ];
    } else {
        return ['success' => false, 'error' => 'Impossible de créer le dossier'];
    }
}
?>