<?php
/**
 * SGC-AgentOne Plug & Play - Point d'entrée intelligent
 * Auto-détecte l'environnement et calcule les chemins dynamiquement
 */

// Configuration d'erreurs pour production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Détection intelligente des chemins
$currentPath = dirname($_SERVER['SCRIPT_NAME']);
$interfaceUrl = $currentPath . '/extensions/webview/chat.html';

// Vérification que l'interface existe
$interfaceFile = __DIR__ . '/extensions/webview/chat.html';
if (!file_exists($interfaceFile)) {
    // Interface non trouvée - affichage d'erreur avec diagnostic
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>SGC-AgentOne - Configuration</title>
        <style>body{font-family:Arial,sans-serif;margin:50px;background:#f5f5f5}.error{background:#fff3cd;border-left:4px solid #ffc107;padding:15px}</style>
    </head>
    <body>
        <h1>🔌 SGC-AgentOne Plug & Play</h1>
        <div class="error">
            <h3>❌ Configuration incomplète</h3>
            <p>L'interface SGC-AgentOne n'est pas trouvée à l'emplacement attendu.</p>
            <p><strong>Fichier recherché :</strong> <?php echo $interfaceFile; ?></p>
            <p><strong>Répertoire actuel :</strong> <?php echo __DIR__; ?></p>
            <hr>
            <p><strong>Solution :</strong> Assurez-vous que tous les fichiers SGC-AgentOne sont présents dans ce dossier.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Redirection automatique vers l'interface
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="1;url=<?php echo $interfaceUrl; ?>">
    <title>SGC-AgentOne</title>
    <style>
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;margin:0;padding:0;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;text-align:center;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .container{background:rgba(255,255,255,0.1);padding:50px;border-radius:20px;backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);box-shadow:0 8px 32px rgba(0,0,0,0.3)}
        h1{font-size:3em;margin:0;margin-bottom:20px}
        .loader{border:4px solid #f3f3f3;border-top:4px solid #007bff;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;margin:20px auto}
        @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
        a{color:#007bff;text-decoration:none}a:hover{text-decoration:underline}
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 SGC-AgentOne</h1>
        <div class="loader"></div>
        <p>Redirection vers l'interface...</p>
        <p><a href="<?php echo $interfaceUrl; ?>">Cliquez ici si la redirection ne fonctionne pas</a></p>
        <div style="margin-top:20px;font-size:12px;color:#fff">
            <strong>Interface :</strong> <code><?php echo $interfaceUrl; ?></code>
        </div>
    </div>
</body>
</html>