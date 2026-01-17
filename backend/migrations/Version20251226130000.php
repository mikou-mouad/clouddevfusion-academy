<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table home_banners
 */
final class Version20251226130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table home_banners pour gérer les KPIs et le logo de la bannière d\'accueil';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS home_banners_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE IF NOT EXISTS home_banners (
            id INT NOT NULL,
            logo_path VARCHAR(255) DEFAULT NULL,
            kpi1_number VARCHAR(100) DEFAULT NULL,
            kpi1_label VARCHAR(255) DEFAULT NULL,
            kpi2_number VARCHAR(100) DEFAULT NULL,
            kpi2_label VARCHAR(255) DEFAULT NULL,
            kpi3_number VARCHAR(100) DEFAULT NULL,
            kpi3_label VARCHAR(255) DEFAULT NULL,
            active BOOLEAN NOT NULL DEFAULT true,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN home_banners.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN home_banners.updated_at IS \'(DC2Type:datetime_immutable)\'');
        
        // Insérer une entrée par défaut
        $this->addSql("INSERT INTO home_banners (id, logo_path, kpi1_number, kpi1_label, kpi2_number, kpi2_label, kpi3_number, kpi3_label, active, created_at) 
            VALUES (1, 'assets/cdfL.png', '100+', 'Professionnels formés', '98%', 'Taux de réussite', '50+', 'Certifications disponibles', true, NOW()) 
            ON CONFLICT (id) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE IF EXISTS home_banners_id_seq CASCADE');
        $this->addSql('DROP TABLE IF EXISTS home_banners');
    }
}
