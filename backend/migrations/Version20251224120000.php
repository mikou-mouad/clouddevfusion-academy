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
        // PostgreSQL : ALTER COLUMN ... DROP NOT NULL (MySQL utilise MODIFY)
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN quote DROP NOT NULL');
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN author DROP NOT NULL');
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN role DROP NOT NULL');
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN company DROP NOT NULL');
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN rating DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE testimonials SET quote = \'\' WHERE quote IS NULL');
        $this->addSql('UPDATE testimonials SET author = \'\' WHERE author IS NULL');
        $this->addSql('UPDATE testimonials SET role = \'\' WHERE role IS NULL');
        $this->addSql('UPDATE testimonials SET company = \'\' WHERE company IS NULL');
        $this->addSql('UPDATE testimonials SET rating = 5 WHERE rating IS NULL');

        $this->addSql('ALTER TABLE testimonials ALTER COLUMN quote SET NOT NULL');
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN author SET NOT NULL');
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN role SET NOT NULL');
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN company SET NOT NULL');
        $this->addSql('ALTER TABLE testimonials ALTER COLUMN rating SET NOT NULL');
    }
}
