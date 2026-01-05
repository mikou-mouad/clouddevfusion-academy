-- Script SQL direct pour exécuter les migrations manuellement
-- Ce script contourne le problème de compatibilité Doctrine/PostgreSQL
-- Exécutez ce script directement dans votre base de données PostgreSQL

-- ============================================
-- Migration 1: Version20251224100000
-- Ajout colonne video_url à la table testimonials
-- ============================================
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'testimonials' AND column_name = 'video_url'
    ) THEN
        ALTER TABLE testimonials ADD COLUMN video_url VARCHAR(500) DEFAULT NULL;
        RAISE NOTICE 'Colonne video_url ajoutée à testimonials';
    ELSE
        RAISE NOTICE 'Colonne video_url existe déjà';
    END IF;
END $$;

-- ============================================
-- Migration 2: Version20251224120000
-- Rendre les colonnes nullable dans testimonials
-- ============================================
DO $$
BEGIN
    -- Modifier quote pour la rendre nullable
    ALTER TABLE testimonials ALTER COLUMN quote DROP NOT NULL;
    RAISE NOTICE 'Colonne quote rendue nullable';
    
    -- Modifier author pour la rendre nullable
    ALTER TABLE testimonials ALTER COLUMN author DROP NOT NULL;
    RAISE NOTICE 'Colonne author rendue nullable';
    
    -- Modifier role pour la rendre nullable
    ALTER TABLE testimonials ALTER COLUMN role DROP NOT NULL;
    RAISE NOTICE 'Colonne role rendue nullable';
    
    -- Modifier company pour la rendre nullable
    ALTER TABLE testimonials ALTER COLUMN company DROP NOT NULL;
    RAISE NOTICE 'Colonne company rendue nullable';
    
    -- Modifier rating pour la rendre nullable
    ALTER TABLE testimonials ALTER COLUMN rating DROP NOT NULL;
    RAISE NOTICE 'Colonne rating rendue nullable';
END $$;

-- ============================================
-- Migration 3: Version20251224130000
-- Création de la table exam_vouchers
-- ============================================
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_name = 'exam_vouchers'
    ) THEN
        CREATE TABLE exam_vouchers (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            exam_code VARCHAR(50) NOT NULL,
            type VARCHAR(50) NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            validity_period INTEGER NOT NULL,
            description TEXT DEFAULT NULL,
            booking_steps JSONB DEFAULT NULL,
            reschedule_rules TEXT DEFAULT NULL,
            redemption_info TEXT DEFAULT NULL,
            schedule_location TEXT DEFAULT NULL,
            id_requirements TEXT DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE NOT NULL,
            created_at TIMESTAMP NOT NULL,
            updated_at TIMESTAMP DEFAULT NULL
        );
        
        CREATE UNIQUE INDEX UNIQ_exam_vouchers_code ON exam_vouchers(code);
        
        RAISE NOTICE 'Table exam_vouchers créée avec succès';
    ELSE
        RAISE NOTICE 'Table exam_vouchers existe déjà';
    END IF;
END $$;

-- ============================================
-- Vérification finale
-- ============================================
SELECT 'Migrations exécutées avec succès!' as status;

-- Vérifier les colonnes de testimonials
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name = 'testimonials' 
ORDER BY ordinal_position;

-- Vérifier la table exam_vouchers
SELECT table_name 
FROM information_schema.tables 
WHERE table_name = 'exam_vouchers';
