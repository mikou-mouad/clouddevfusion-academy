<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table exam_vouchers
 */
final class Version20251224130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table exam_vouchers pour gérer les bons d\'examen Microsoft';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE exam_vouchers (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(50) NOT NULL,
            exam_code VARCHAR(50) NOT NULL,
            type VARCHAR(50) NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            validity_period INT NOT NULL,
            description LONGTEXT DEFAULT NULL,
            booking_steps JSON DEFAULT NULL,
            reschedule_rules LONGTEXT DEFAULT NULL,
            redemption_info LONGTEXT DEFAULT NULL,
            schedule_location LONGTEXT DEFAULT NULL,
            id_requirements LONGTEXT DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_exam_vouchers_code (code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE exam_vouchers');
    }
}
