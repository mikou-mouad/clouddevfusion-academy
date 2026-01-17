-- Migration pour créer la table home_banners
-- Exécutez ce script directement dans PostgreSQL si Doctrine migrations ne fonctionne pas

-- Créer la séquence
CREATE SEQUENCE IF NOT EXISTS home_banners_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

-- Créer la table
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

-- Ajouter les commentaires
COMMENT ON COLUMN home_banners.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN home_banners.updated_at IS '(DC2Type:datetime_immutable)';

-- Insérer une entrée par défaut
INSERT INTO home_banners (id, logo_path, kpi1_number, kpi1_label, kpi2_number, kpi2_label, kpi3_number, kpi3_label, active, created_at) 
VALUES (1, 'assets/cdfL.png', '100+', 'Professionnels formés', '98%', 'Taux de réussite', '50+', 'Certifications disponibles', true, NOW())
ON CONFLICT (id) DO NOTHING;
-- Vérification
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'home_banners'
ORDER BY ordinal_position;
