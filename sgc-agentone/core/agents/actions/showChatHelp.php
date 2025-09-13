<?php
/**
 * Action: Afficher l'aide du chat
 * Affiche les sections clés du guide FONCTIONNEMENT_CHAT.md
 */

function executeAction_showchathelp($params, $projectPath) {
    $projectRoot = getcwd();
    $chatHelpFile = $projectRoot . '/sgc-agentone/FONCTIONNEMENT_CHAT.md';
    
    if (!file_exists($chatHelpFile)) {
        return [
            'success' => false,
            'error' => 'Guide du chat non trouvé'
        ];
    }
    
    $content = file_get_contents($chatHelpFile);
    
    // Extraction des sections importantes
    $helpResponse = "💬 **GUIDE DU CHAT SGC-AGENTONE**\n\n";
    
    // Section Architecture
    if (preg_match('/## 🔄 \*\*ARCHITECTURE DU SYSTÈME DE CHAT\*\*(.*?)(?=##|\z)/s', $content, $matches)) {
        $helpResponse .= "🔄 **ARCHITECTURE DU SYSTÈME**\n";
        $helpResponse .= "```\nUtilisateur → Interface Chat → API REST → Interpréteur → Actions → Réponse\n```\n\n";
    }
    
    // Section Commandes principales
    $helpResponse .= "🎯 **COMMANDES PRINCIPALES :**\n\n";
    $helpResponse .= "📁 **Fichiers :**\n";
    $helpResponse .= "• `crée un fichier nom.ext` - Créer un nouveau fichier\n";
    $helpResponse .= "• `modifie le fichier nom.ext` - Modifier un fichier\n";
    $helpResponse .= "• `lis le fichier nom.ext` - Lire le contenu\n\n";
    
    $helpResponse .= "🗄️ **Base de données :**\n";
    $helpResponse .= "• `connecte à la base` - Créer/connecter BDD\n";
    $helpResponse .= "• `exécute la requête SQL...` - Lancer une requête\n\n";
    
    $helpResponse .= "🆘 **Aide :**\n";
    $helpResponse .= "• `help` - Menu d'aide principal\n";
    $helpResponse .= "• `help ide` - Guide complet IDE\n\n";
    
    $helpResponse .= "✨ **Le chat comprend le français ET l'anglais !**\n";
    $helpResponse .= "📖 Document complet : `/sgc-agentone/FONCTIONNEMENT_CHAT.md`";
    
    return [
        'success' => true,
        'response' => $helpResponse
    ];
}
?>