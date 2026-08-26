<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cpf_eligible flag to courses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE courses ADD cpf_eligible BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE courses DROP COLUMN cpf_eligible');
    }
}
