<?php
/**
 * SGC-AgentOne - Serveur HTTP Principal
 * Utilise le serveur de développement PHP built-in avec router personnalisé
 * Port: 5000 (configurable via settings.json)
 * Contrainte: Utilise getcwd() comme racine, jamais __DIR__ ou chemins absolus
 */

// Configuration par défaut
$defaultPort = 5000;
$projectRoot = getcwd();

// Lecture des paramètres depuis settings.json si disponible
$settingsFile = $projectRoot . '/sgc-agentone/core/config/settings.json';
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    if ($settings && isset($settings['port'])) {
        $defaultPort = $settings['port'];
    }
}

// Configuration du serveur
$host = '0.0.0.0';  // Obligatoire pour Replit
$port = $defaultPort;
$routerFile = $projectRoot . '/sgc-agentone/core/router.php';

// Vérification du router
if (!file_exists($routerFile)) {
    die("❌ Erreur: Router non trouvé à {$routerFile}\n");
}

// Affichage des informations de démarrage
echo "🚀 Démarrage SGC-AgentOne Server\n";
echo "📁 Racine projet: {$projectRoot}\n";
echo "🌐 Serveur: http://{$host}:{$port}\n";
echo "🔗 API: http://{$host}:{$port}/api/chat\n";
echo "📄 Router: {$routerFile}\n";
echo "⏰ " . date('Y-m-d H:i:s') . "\n\n";

// Création du répertoire des logs si nécessaire
$logDir = $projectRoot . '/sgc-agentone/core/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
    echo "📝 Répertoire logs créé: {$logDir}\n";
}

echo "✅ Serveur SGC-AgentOne prêt!\n";
echo "🚀 Démarrage du serveur PHP built-in...\n\n";

// Changement vers la racine du projet pour le serveur
chdir($projectRoot);

// Commande pour démarrer le serveur PHP built-in
$command = "php -S {$host}:{$port} -t . " . escapeshellarg($routerFile);

// Exécution du serveur
echo "Commande: {$command}\n";
echo "=== LOGS DU SERVEUR ===\n";

// Redirection des erreurs vers stdout pour les voir dans les logs
$descriptors = [
    0 => array("pipe", "r"),   // stdin
    1 => array("pipe", "w"),   // stdout
    2 => array("pipe", "w")    // stderr
];

$process = proc_open($command, $descriptors, $pipes);

if (is_resource($process)) {
    // Fermer stdin
    fclose($pipes[0]);
    
    // Lecture en temps réel des sorties
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    
    while (true) {
        $stdout = fread($pipes[1], 8192);
        $stderr = fread($pipes[2], 8192);
        
        if ($stdout !== false && $stdout !== '') {
            echo $stdout;
            flush();
        }
        
        if ($stderr !== false && $stderr !== '') {
            // Filtrage amélioré des logs stderr - ne préfixer que les vraies erreurs
            $stderrLines = explode("\n", $stderr);
            foreach ($stderrLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Ignorer les logs de connexion normaux
                if (preg_match('/\d+\.\d+\.\d+\.\d+:\d+ (Accepted|Closing)/', $line)) {
                    continue;
                }
                
                // Ignorer les messages d'information standards
                if (preg_match('/(Development Server|Document root|Listening on|started)/', $line)) {
                    echo $line . "\n";
                    continue;
                }
                
                // Préfixer seulement les vrais messages d'erreur
                if (preg_match('/(Fatal|Error|Warning|Notice|Parse error|Undefined)/', $line)) {
                    echo "ERROR: " . $line . "\n";
                } else {
                    echo $line . "\n";
                }
            }
            flush();
        }
        
        // Vérifier si le processus est encore en cours
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        
        // Petite pause pour éviter une boucle trop intensive
        usleep(10000); // 10ms
    }
    
    // Fermeture des pipes
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    // Fermeture du processus
    proc_close($process);
} else {
    die("❌ Impossible de démarrer le serveur PHP\n");
}
?>