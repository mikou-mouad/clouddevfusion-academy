<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table audit_logs
 */
final class Version20251226150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table audit_logs pour la traçabilité des actions admin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS audit_logs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_logs (
            id INT NOT NULL DEFAULT nextval(\'audit_logs_id_seq\'),
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT DEFAULT NULL,
            entity_title VARCHAR(255) DEFAULT NULL,
            user_email VARCHAR(255) NOT NULL,
            username VARCHAR(100) NOT NULL,
            changes TEXT DEFAULT NULL,
            description TEXT DEFAULT NULL,
            ip_address VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_audit_log_created_at ON audit_logs (created_at DESC)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_audit_log_user_email ON audit_logs (user_email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_audit_log_entity ON audit_logs (entity_type, entity_id)');
        $this->addSql('COMMENT ON COLUMN audit_logs.changes IS \'(DC2Type:json)\'');
        $this->addSql('COMMENT ON COLUMN audit_logs.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE IF EXISTS audit_logs_id_seq CASCADE');
        $this->addSql('DROP TABLE IF EXISTS audit_logs');
    }
}
