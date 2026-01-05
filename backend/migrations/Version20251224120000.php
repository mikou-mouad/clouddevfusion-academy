<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour rendre les champs de témoignage optionnels si une vidéo est fournie
 */
final class Version20251224120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rendre les champs quote, author, role, company et rating nullable pour permettre des témoignages avec seulement une vidéo';
    }

    public function up(Schema $schema): void
    {
        // Modifier la colonne quote pour la rendre nullable
        $this->addSql('ALTER TABLE testimonials MODIFY quote TEXT NULL');
        
        // Modifier les colonnes author, role, company pour les rendre nullable
        $this->addSql('ALTER TABLE testimonials MODIFY author VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE testimonials MODIFY role VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE testimonials MODIFY company VARCHAR(255) NULL');
        
        // Modifier la colonne rating pour la rendre nullable
        $this->addSql('ALTER TABLE testimonials MODIFY rating INT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remettre les colonnes comme NOT NULL avec des valeurs par défaut
        $this->addSql('UPDATE testimonials SET quote = "" WHERE quote IS NULL');
        $this->addSql('UPDATE testimonials SET author = "" WHERE author IS NULL');
        $this->addSql('UPDATE testimonials SET role = "" WHERE role IS NULL');
        $this->addSql('UPDATE testimonials SET company = "" WHERE company IS NULL');
        $this->addSql('UPDATE testimonials SET rating = 5 WHERE rating IS NULL');
        
        $this->addSql('ALTER TABLE testimonials MODIFY quote TEXT NOT NULL');
        $this->addSql('ALTER TABLE testimonials MODIFY author VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE testimonials MODIFY role VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE testimonials MODIFY company VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE testimonials MODIFY rating INT NOT NULL');
    }
}
