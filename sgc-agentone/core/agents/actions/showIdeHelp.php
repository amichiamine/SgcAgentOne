<?php
/**
 * Action: Afficher l'aide IDE
 * Affiche les sections clés du guide MODE_EMPLOI_IDE.md
 */

function executeAction_showidehelp($params, $projectPath) {
    $projectRoot = getcwd();
    $ideHelpFile = $projectRoot . '/sgc-agentone/MODE_EMPLOI_IDE.md';
    
    if (!file_exists($ideHelpFile)) {
        return [
            'success' => false,
            'error' => 'Guide IDE non trouvé'
        ];
    }
    
    $content = file_get_contents($ideHelpFile);
    
    // Extraction des sections importantes
    $helpResponse = "🚀 **MODE D'EMPLOI SGC-AGENTONE IDE**\n\n";
    
    $helpResponse .= "🎯 **COMMENT ÇA FONCTIONNE :**\n";
    $helpResponse .= "• **Serveur local** : Interface web sur `http://localhost:5000`\n";
    $helpResponse .= "• **Chat IA** : Interprète vos commandes en langage naturel\n";
    $helpResponse .= "• **Actions directes** : Modifie vos fichiers automatiquement\n\n";
    
    $helpResponse .= "🚀 **DÉMARRAGE RAPIDE :**\n";
    $helpResponse .= "```bash\n";
    $helpResponse .= "cd votre-projet/\n";
    $helpResponse .= "php sgc-agentone/core/server.php\n";
    $helpResponse .= "# Serveur prêt sur http://localhost:5000\n";
    $helpResponse .= "```\n\n";
    
    $helpResponse .= "🎮 **INTERFACE UTILISATEUR :**\n";
    $helpResponse .= "• **Chat** - Commandes intelligentes\n";
    $helpResponse .= "• **Files** - Gestionnaire de fichiers\n";
    $helpResponse .= "• **Database** - Gestion SQLite\n";
    $helpResponse .= "• **Prompts** - Patterns de commandes\n";
    $helpResponse .= "• **Settings** - Configuration\n";
    $helpResponse .= "• **Server** - Monitoring temps réel\n";
    $helpResponse .= "• **Editor** - Monaco Editor intégré\n\n";
    
    $helpResponse .= "💻 **WORKFLOW AVEC VOTRE IDE :**\n";
    $helpResponse .= "```\n";
    $helpResponse .= "┌─────────────────┬─────────────────┐\n";
    $helpResponse .= "│                 │                 │\n";
    $helpResponse .= "│   VOTRE IDE     │   SGC-AGENTONE  │\n";
    $helpResponse .= "│   (VSCode,      │   (Navigateur   │\n";
    $helpResponse .= "│    WebStorm,    │    localhost:   │\n";
    $helpResponse .= "│    Sublime...)  │      5000)      │\n";
    $helpResponse .= "│                 │                 │\n";
    $helpResponse .= "└─────────────────┴─────────────────┘\n";
    $helpResponse .= "```\n\n";
    
    $helpResponse .= "🎯 **AVANTAGES CLÉS :**\n";
    $helpResponse .= "• **Multilingue** : Français et Anglais\n";
    $helpResponse .= "• **Intelligent** : Comprend le langage naturel\n";
    $helpResponse .= "• **Complet** : 7 vues intégrées\n";
    $helpResponse .= "• **Sécurisé** : Authentification et logging\n\n";
    
    $helpResponse .= "📖 **Document complet** : `/sgc-agentone/MODE_EMPLOI_IDE.md`";
    
    return [
        'success' => true,
        'response' => $helpResponse
    ];
}
?>