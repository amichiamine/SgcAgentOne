-- SGC-AgentOne - Template SQL Schema
-- Schéma de base pour tables SQLite avec support des variables

-- Table principale: {{TABLE_NAME}}
CREATE TABLE IF NOT EXISTS {{TABLE_NAME}} (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    data TEXT,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table pour les métadonnées système
CREATE TABLE IF NOT EXISTS system_metadata (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT UNIQUE NOT NULL,
    value TEXT,
    type TEXT DEFAULT 'string',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table pour les logs d'activité
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    entity_type TEXT,
    entity_id INTEGER,
    user_context TEXT,
    details TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_{{TABLE_NAME}}_status ON {{TABLE_NAME}}(status);
CREATE INDEX IF NOT EXISTS idx_{{TABLE_NAME}}_created_at ON {{TABLE_NAME}}(created_at);
CREATE INDEX IF NOT EXISTS idx_system_metadata_key ON system_metadata(key);
CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at);

-- Trigger pour mettre à jour updated_at automatiquement
CREATE TRIGGER IF NOT EXISTS update_{{TABLE_NAME}}_updated_at
    AFTER UPDATE ON {{TABLE_NAME}}
    FOR EACH ROW
BEGIN
    UPDATE {{TABLE_NAME}} 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_system_metadata_updated_at
    AFTER UPDATE ON system_metadata
    FOR EACH ROW
BEGIN
    UPDATE system_metadata 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.id;
END;

-- Insertion de données de base
INSERT OR REPLACE INTO system_metadata (key, value, type, description) VALUES
    ('schema_version', '1.0.0', 'version', 'Version du schéma de base de données'),
    ('created_by', 'SGC-AgentOne', 'string', 'Créé par le système SGC-AgentOne'),
    ('table_name', '{{TABLE_NAME}}', 'string', 'Nom de la table principale générée'),
    ('initialized_at', datetime('now'), 'datetime', 'Date d''initialisation du schéma');

-- Vue pour les statistiques rapides
CREATE VIEW IF NOT EXISTS {{TABLE_NAME}}_stats AS
SELECT 
    COUNT(*) as total_records,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_records,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_records,
    MIN(created_at) as first_record,
    MAX(created_at) as last_record
FROM {{TABLE_NAME}};

-- Commentaires pour la documentation
-- Cette table {{TABLE_NAME}} est générée dynamiquement par SGC-AgentOne
-- Elle contient les colonnes de base recommandées pour la plupart des cas d'usage
-- Les triggers maintiennent automatiquement les timestamps updated_at