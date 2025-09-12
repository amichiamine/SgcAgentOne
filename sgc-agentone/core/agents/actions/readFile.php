<?php
/**
 * Action: Lecture de fichier
 * Lit un fichier et retourne son contenu
 */

function executeAction_readfile($params, $projectPath) {
    if (!isset($params['filename'])) {
        return ['success' => false, 'error' => 'Nom de fichier requis'];
    }
    
    $filename = $params['filename'];
    
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
    
    // Lecture du contenu
    $content = file_get_contents($fullPath);
    if ($content === false) {
        return ['success' => false, 'error' => 'Impossible de lire le fichier'];
    }
    
    // Limitation pour affichage (500 caractères max dans les logs)
    $displayContent = strlen($content) > 500 ? substr($content, 0, 500) . '...' : $content;
    
    return [
        'success' => true,
        'response' => "📄 Contenu de {$filename}:\n\n{$displayContent}",
        'content' => $content
    ];
}
?>