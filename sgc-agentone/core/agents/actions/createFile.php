<?php
/**
 * Action: Création de fichier
 * Crée un fichier avec contenu optionnel et support des templates
 */

// Inclusion de l'utilitaire de rendu de templates
require_once getcwd() . '/sgc-agentone/core/utils/TemplateRenderer.php';

function executeAction_createfile($params, $projectPath) {
    if (!isset($params['filename'])) {
        return ['success' => false, 'error' => 'Nom de fichier requis'];
    }
    
    $filename = $params['filename'];
    $content = $params['content'] ?? '';
    $template = $params['template'] ?? null;
    $variables = $params['variables'] ?? [];
    
    // Si un template est spécifié, l'utiliser pour générer le contenu
    if ($template && empty($content)) {
        $renderedContent = TemplateRenderer::quickRenderFile($template, $variables);
        if ($renderedContent === false) {
            return ['success' => false, 'error' => 'Template non trouvé ou erreur de rendu: ' . $template];
        }
        $content = $renderedContent;
    } elseif (!empty($content) && !empty($variables)) {
        // Si des variables sont fournies avec du contenu, effectuer le rendu
        $content = TemplateRenderer::quickRender($content, $variables);
    }
    
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
    
    $fileSize = strlen($content);
    $templateInfo = $template ? " à partir du template {$template}" : '';
    
    return [
        'success' => true,
        'response' => "✅ Fichier créé: {$filename}{$templateInfo} ({$fileSize} caractères)",
        'details' => [
            'filename' => $filename,
            'size' => $fileSize,
            'template_used' => $template,
            'variables_count' => count($variables)
        ]
    ];
}
?>