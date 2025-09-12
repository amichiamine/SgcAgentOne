<?php
/**
 * Action: Exécution de requête SQL
 * Exécute une requête sur db/app.db
 */

function executeAction_executequery($params, $projectPath) {
    if (!isset($params['query'])) {
        return ['success' => false, 'error' => 'Requête SQL requise'];
    }
    
    $query = trim($params['query']);
    $projectRoot = getcwd();
    $dbFile = $projectRoot . '/sgc-agentone/core/db/app.db';
    
    // Vérification de l'existence de la base
    if (!file_exists($dbFile)) {
        return ['success' => false, 'error' => 'Base de données non trouvée. Créez-la d\'abord.'];
    }
    
    // Sécurisation: blocage des requêtes dangereuses
    $queryUpper = strtoupper($query);
    $dangerousOperations = ['DROP', 'DELETE', 'ALTER'];
    foreach ($dangerousOperations as $op) {
        if (strpos($queryUpper, $op) === 0) {
            return ['success' => false, 'error' => 'Opération non autorisée: ' . $op];
        }
    }
    
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (strpos($queryUpper, 'SELECT') === 0) {
            // Requête de lecture
            $stmt = $pdo->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $responseText = "✅ Requête exécutée. " . count($results) . " résultat(s):\n\n";
            foreach ($results as $row) {
                $responseText .= json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
            
            return [
                'success' => true,
                'response' => $responseText,
                'result' => $results
            ];
            
        } else {
            // Requête de modification
            $affectedRows = $pdo->exec($query);
            
            return [
                'success' => true,
                'response' => "✅ Requête exécutée. {$affectedRows} ligne(s) affectée(s).",
                'result' => ['affected_rows' => $affectedRows]
            ];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Erreur SQL: ' . $e->getMessage()];
    }
}
?>