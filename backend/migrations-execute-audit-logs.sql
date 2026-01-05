-- Migration pour créer la table audit_logs
-- Exécutez ce script directement dans PostgreSQL si Doctrine migrations ne fonctionne pas

-- Créer la séquence
CREATE SEQUENCE IF NOT EXISTS audit_logs_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

-- Créer la table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT NOT NULL DEFAULT nextval('audit_logs_id_seq'),
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT DEFAULT NULL,
    entity_title VARCHAR(255) DEFAULT NULL,
    user_email VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    changes TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    PRIMARY KEY(id)
);

-- Créer les index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_audit_log_created_at ON audit_logs (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_log_user_email ON audit_logs (user_email);
CREATE INDEX IF NOT EXISTS idx_audit_log_entity ON audit_logs (entity_type, entity_id);

-- Ajouter les commentaires
COMMENT ON COLUMN audit_logs.changes IS '(DC2Type:json)';
COMMENT ON COLUMN audit_logs.created_at IS '(DC2Type:datetime_immutable)';

-- Vérification
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'audit_logs'
ORDER BY ordinal_position;
