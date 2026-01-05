-- Migration pour créer la table users
-- Exécutez ce script directement dans PostgreSQL si Doctrine migrations ne fonctionne pas

-- Créer la séquence
CREATE SEQUENCE IF NOT EXISTS users_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

-- Créer la table
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL DEFAULT nextval('users_id_seq'),
    email VARCHAR(180) NOT NULL,
    roles TEXT NOT NULL,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id),
    UNIQUE(email)
);

-- Créer l'index unique sur email
CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_1483A5E9E7927C74 ON users (email);

-- Ajouter les commentaires
COMMENT ON COLUMN users.roles IS '(DC2Type:json)';
COMMENT ON COLUMN users.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN users.updated_at IS '(DC2Type:datetime_immutable)';

-- Insérer un admin par défaut
-- Email: admin@clouddevfusion.com
-- Mot de passe: admin123
-- Hash généré: php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
INSERT INTO users (id, email, roles, password, username, active, created_at) 
VALUES (1, 'admin@clouddevfusion.com', '["ROLE_ADMIN"]', '$2y$12$rcTi/.dppi/umYBbJTMdBuiGT0NENg.cMaH/aIoIU9wCri4Fkli1y', 'Admin', true, NOW())
ON CONFLICT (email) DO NOTHING;

-- Insérer un super admin par défaut
-- Email: superadmin@clouddevfusion.com
-- Mot de passe: superadmin123
-- Hash généré: php -r "echo password_hash('superadmin123', PASSWORD_DEFAULT);"
INSERT INTO users (id, email, roles, password, username, active, created_at) 
VALUES (2, 'superadmin@clouddevfusion.com', '["ROLE_SUPER_ADMIN"]', '$2y$12$qRGOAn7QgBnem40Z1BGrxeN7FoOg1inQyMMo1MzKoF0mzKi0UbOve', 'Super Admin', true, NOW())
ON CONFLICT (email) DO NOTHING;

-- Vérification
SELECT id, email, username, roles, active, created_at
FROM users
ORDER BY id;
