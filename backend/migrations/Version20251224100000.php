<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter le champ videoUrl aux témoignages
 */
final class Version20251224100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ videoUrl à la table testimonials';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne video_url à la table testimonials
        $this->addSql('ALTER TABLE testimonials ADD COLUMN video_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la colonne video_url
        $this->addSql('ALTER TABLE testimonials DROP COLUMN video_url');
    }
}
