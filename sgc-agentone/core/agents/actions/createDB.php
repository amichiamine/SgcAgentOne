<?php
/**
 * Action: Création de base de données SQLite
 * Crée le fichier app.db s'il n'existe pas
 */

function executeAction_createdatabase($params, $projectPath) {
    $projectRoot = getcwd();
    $dbDir = $projectRoot . '/sgc-agentone/core/db';
    $dbFile = $dbDir . '/app.db';
    
    // Création du dossier db
    if (!is_dir($dbDir)) {
        if (!mkdir($dbDir, 0755, true)) {
            return ['success' => false, 'error' => 'Impossible de créer le dossier db/'];
        }
    }
    
    // Vérification si la base existe déjà
    if (file_exists($dbFile)) {
        return [
            'success' => true,
            'response' => "✅ Base de données SQLite déjà présente: db/app.db"
        ];
    }
    
    // Création du fichier SQLite vide
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Création d'une table de test
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_info (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE,
            value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insertion d'une entrée système
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO system_info (key, value) VALUES (?, ?)");
        $stmt->execute(['created_at', date('Y-m-d H:i:s')]);
        $stmt->execute(['version', '1.0.0']);
        
        return [
            'success' => true,
            'response' => "✅ Base de données SQLite créée: db/app.db avec table system_info"
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Erreur SQLite: ' . $e->getMessage()];
    }
}
?>