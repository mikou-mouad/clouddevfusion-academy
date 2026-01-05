-- Migration pour ajouter le champ délai d'accès (access_delay) à la table courses
-- Exécutez ce script directement dans PostgreSQL si Doctrine migrations ne fonctionne pas

-- Ajouter la colonne access_delay
ALTER TABLE courses 
ADD COLUMN IF NOT EXISTS access_delay VARCHAR(100) DEFAULT NULL;

-- Vérification
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'courses' AND column_name = 'access_delay';
