-- Script SQL combiné pour exécuter toutes les migrations
-- À exécuter directement dans PostgreSQL si Doctrine migrations ne fonctionne pas
-- Compatible avec PostgreSQL 11 et versions antérieures

-- ============================================
-- 1. Migration: Ajout colonne access_delay
-- ============================================
ALTER TABLE courses 
ADD COLUMN IF NOT EXISTS access_delay VARCHAR(100) DEFAULT NULL;

-- ============================================
-- 2. Migration: Création table home_banners
-- ============================================
CREATE SEQUENCE IF NOT EXISTS home_banners_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE IF NOT EXISTS home_banners (
    id INT NOT NULL DEFAULT nextval('home_banners_id_seq'),
    logo_path VARCHAR(255) DEFAULT NULL,
    kpi1_number VARCHAR(100) DEFAULT NULL,
    kpi1_label VARCHAR(255) DEFAULT NULL,
    kpi2_number VARCHAR(100) DEFAULT NULL,
    kpi2_label VARCHAR(255) DEFAULT NULL,
    kpi3_number VARCHAR(100) DEFAULT NULL,
    kpi3_label VARCHAR(255) DEFAULT NULL,
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
);

COMMENT ON COLUMN home_banners.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN home_banners.updated_at IS '(DC2Type:datetime_immutable)';

INSERT INTO home_banners (id, logo_path, kpi1_number, kpi1_label, kpi2_number, kpi2_label, kpi3_number, kpi3_label, active, created_at) 
VALUES (1, 'assets/cdfL.png', '500+', 'Étudiants formés', '98%', 'Taux de réussite', '50+', 'Certifications disponibles', true, NOW())
ON CONFLICT (id) DO NOTHING;

-- ============================================
-- 3. Migration: Création table users
-- ============================================
CREATE SEQUENCE IF NOT EXISTS users_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

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

CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_1483A5E9E7927C74 ON users (email);

COMMENT ON COLUMN users.roles IS '(DC2Type:json)';
COMMENT ON COLUMN users.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN users.updated_at IS '(DC2Type:datetime_immutable)';

INSERT INTO users (id, email, roles, password, username, active, created_at) 
VALUES (1, 'admin@clouddevfusion.com', '["ROLE_ADMIN"]', '$2y$12$rcTi/.dppi/umYBbJTMdBuiGT0NENg.cMaH/aIoIU9wCri4Fkli1y', 'Admin', true, NOW())
ON CONFLICT (email) DO NOTHING;

INSERT INTO users (id, email, roles, password, username, active, created_at) 
VALUES (2, 'superadmin@clouddevfusion.com', '["ROLE_SUPER_ADMIN"]', '$2y$12$qRGOAn7QgBnem40Z1BGrxeN7FoOg1inQyMMo1MzKoF0mzKi0UbOve', 'Super Admin', true, NOW())
ON CONFLICT (email) DO NOTHING;

-- ============================================
-- 4. Migration: Création table audit_logs
-- ============================================
CREATE SEQUENCE IF NOT EXISTS audit_logs_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

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

CREATE INDEX IF NOT EXISTS idx_audit_log_created_at ON audit_logs (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_log_user_email ON audit_logs (user_email);
CREATE INDEX IF NOT EXISTS idx_audit_log_entity ON audit_logs (entity_type, entity_id);

COMMENT ON COLUMN audit_logs.changes IS '(DC2Type:json)';
COMMENT ON COLUMN audit_logs.created_at IS '(DC2Type:datetime_immutable)';

-- ============================================
-- Vérification
-- ============================================
SELECT '✅ Migration access_delay' as status WHERE EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'courses' AND column_name = 'access_delay')
UNION ALL
SELECT '✅ Migration home_banners' WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'home_banners')
UNION ALL
SELECT '✅ Migration users' WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'users')
UNION ALL
SELECT '✅ Migration audit_logs' WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'audit_logs');


