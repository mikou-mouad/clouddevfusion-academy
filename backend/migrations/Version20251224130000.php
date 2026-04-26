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
        $this->addSql('CREATE SEQUENCE exam_vouchers_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE exam_vouchers (
            id INT NOT NULL DEFAULT nextval(\'exam_vouchers_id_seq\'),
            code VARCHAR(50) NOT NULL,
            exam_code VARCHAR(50) NOT NULL,
            type VARCHAR(50) NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            validity_period INT NOT NULL,
            description TEXT DEFAULT NULL,
            booking_steps JSON DEFAULT NULL,
            reschedule_rules TEXT DEFAULT NULL,
            redemption_info TEXT DEFAULT NULL,
            schedule_location TEXT DEFAULT NULL,
            id_requirements TEXT DEFAULT NULL,
            is_active BOOLEAN DEFAULT true NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_exam_vouchers_code ON exam_vouchers (code)');
        $this->addSql('COMMENT ON COLUMN exam_vouchers.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN exam_vouchers.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE exam_vouchers');
        $this->addSql('DROP SEQUENCE exam_vouchers_id_seq CASCADE');
    }
}
