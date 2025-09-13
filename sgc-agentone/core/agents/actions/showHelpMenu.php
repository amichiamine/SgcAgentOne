<?php
/**
 * Action: Afficher le menu d'aide principal
 * Répond aux commandes "help", "aide", etc.
 */

function executeAction_showhelpmenufromnoun($params, $projectPath) {
    return executeAction_showhelpmenu($params, $projectPath);
}

function executeAction_showhelpmenu($params, $projectPath) {
    $helpMenu = "🆘 **AIDE SGC-AGENTONE**\n\n";
    $helpMenu .= "📚 **Documentation disponible :**\n\n";
    $helpMenu .= "1️⃣ **Guide du Chat** - `help chat`\n";
    $helpMenu .= "   💬 Commandes, patterns et utilisation du chat IA\n\n";
    $helpMenu .= "2️⃣ **Mode d'emploi IDE** - `help ide`\n";
    $helpMenu .= "   🚀 Installation, configuration et workflow complet\n\n";
    $helpMenu .= "✨ **Utilisation :**\n";
    $helpMenu .= "• Tapez `help chat` pour le guide du chat\n";
    $helpMenu .= "• Tapez `help ide` pour le mode d'emploi IDE\n\n";
    $helpMenu .= "🎯 Ou posez directement votre question !";
    
    return [
        'success' => true,
        'response' => $helpMenu
    ];
}
?>