#!/bin/bash
# Script simple pour exécuter les migrations SQL directement dans PostgreSQL
# À exécuter dans le terminal O2Switch

echo "🚀 Exécution des migrations SQL..."

# Connexion PostgreSQL O2Switch
psql -U race8462_race8462 -d race8462_academy << 'EOF'

-- Migration 1: Ajouter colonne video_url
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'testimonials' AND column_name = 'video_url'
    ) THEN
        ALTER TABLE testimonials ADD COLUMN video_url VARCHAR(500) DEFAULT NULL;
        RAISE NOTICE '✅ Colonne video_url ajoutée';
    ELSE
        RAISE NOTICE 'ℹ️ Colonne video_url existe déjà';
    END IF;
END $$;

-- Migration 2: Rendre les colonnes nullable
DO $$
BEGIN
    ALTER TABLE testimonials ALTER COLUMN quote DROP NOT NULL;
    ALTER TABLE testimonials ALTER COLUMN author DROP NOT NULL;
    ALTER TABLE testimonials ALTER COLUMN role DROP NOT NULL;
    ALTER TABLE testimonials ALTER COLUMN company DROP NOT NULL;
    ALTER TABLE testimonials ALTER COLUMN rating DROP NOT NULL;
    RAISE NOTICE '✅ Colonnes rendues nullable';
END $$;

-- Migration 3: Créer table exam_vouchers
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
        RAISE NOTICE '✅ Table exam_vouchers créée';
    ELSE
        RAISE NOTICE 'ℹ️ Table exam_vouchers existe déjà';
    END IF;
END $$;

-- Vérifications
SELECT '✅ Migrations terminées!' as status;

EOF

echo ""
echo "✅ Migrations exécutées!"
echo ""
echo "📋 Testez les APIs:"
echo "  - https://academy.clouddevfusion.com/api/exam_vouchers"
