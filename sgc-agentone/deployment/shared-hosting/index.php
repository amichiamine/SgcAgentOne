<?php
/**
 * SGC-AgentOne - Point d'entrée pour hébergement mutualisé
 * Remplace le serveur PHP built-in par un routage web standard
 */

// Configuration d'erreurs pour production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Définir la racine du projet
define('PROJECT_ROOT', dirname(__FILE__));

// Vérification de l'installation SGC-AgentOne
$sgcPath = PROJECT_ROOT . '/sgc-agentone';
if (!is_dir($sgcPath)) {
    die('❌ Erreur: Dossier sgc-agentone non trouvé. Assurez-vous de copier le dossier sgc-agentone à la racine.');
}

// Redirige directement vers l'interface principale SGC-AgentOne
$interfaceUrl = '/sgc-agentone/extensions/vscode/src/webview/chat.html';

// Headers pour redirection propre
header('HTTP/1.1 302 Found');
header('Location: ' . $interfaceUrl);
header('Cache-Control: no-cache, no-store, must-revalidate');

// Message de fallback si la redirection échoue
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGC-AgentOne</title>
    <meta http-equiv="refresh" content="0; url=<?php echo $interfaceUrl; ?>">
</head>
<body>
    <div style="text-align: center; margin-top: 50px; font-family: Arial, sans-serif;">
        <h1>🚀 SGC-AgentOne</h1>
        <p>Redirection vers l'interface...</p>
        <p><a href="<?php echo $interfaceUrl; ?>">Cliquez ici si la redirection ne fonctionne pas</a></p>
    </div>
</body>
</html>