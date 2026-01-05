<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter le champ délai d'accès (access_delay) à la table courses
 */
final class Version20251226120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ access_delay (délai d\'accès) à la table courses';
    }

    public function up(Schema $schema): void
    {
        // Pour PostgreSQL
        $this->addSql('ALTER TABLE courses ADD COLUMN IF NOT EXISTS access_delay VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE courses DROP COLUMN IF EXISTS access_delay');
    }
}
