<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table users avec système de rôles
 */
final class Version20251226140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table users pour l\'authentification admin/super_admin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE IF NOT EXISTS users (
            id INT NOT NULL DEFAULT nextval(\'users_id_seq\'),
            email VARCHAR(180) NOT NULL,
            roles TEXT NOT NULL,
            password VARCHAR(255) NOT NULL,
            username VARCHAR(100) NOT NULL,
            active BOOLEAN NOT NULL DEFAULT true,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id),
            UNIQUE(email)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('COMMENT ON COLUMN users.roles IS \'(DC2Type:json)\'');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.updated_at IS \'(DC2Type:datetime_immutable)\'');
        
        // Insérer un admin par défaut (mot de passe: admin123)
        // Hash généré avec: php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
        $adminPassword = '$2y$12$rcTi/.dppi/umYBbJTMdBuiGT0NENg.cMaH/aIoIU9wCri4Fkli1y';
        $this->addSql("INSERT INTO users (id, email, roles, password, username, active, created_at) 
            VALUES (1, 'admin@clouddevfusion.com', '[\"ROLE_ADMIN\"]', :password, 'Admin', true, NOW())
            ON CONFLICT (email) DO NOTHING", ['password' => $adminPassword]);
        
        // Insérer un super admin par défaut (mot de passe: superadmin123)
        // Hash généré avec: php -r "echo password_hash('superadmin123', PASSWORD_DEFAULT);"
        $superAdminPassword = '$2y$12$qRGOAn7QgBnem40Z1BGrxeN7FoOg1inQyMMo1MzKoF0mzKi0UbOve';
        $this->addSql("INSERT INTO users (id, email, roles, password, username, active, created_at) 
            VALUES (2, 'superadmin@clouddevfusion.com', '[\"ROLE_SUPER_ADMIN\"]', :password, 'Super Admin', true, NOW())
            ON CONFLICT (email) DO NOTHING", ['password' => $superAdminPassword]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE IF EXISTS users_id_seq CASCADE');
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}
