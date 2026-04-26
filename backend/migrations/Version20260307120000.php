<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_phone to placement_test_results';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE placement_test_results ADD user_phone VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE placement_test_results DROP user_phone');
    }
}
