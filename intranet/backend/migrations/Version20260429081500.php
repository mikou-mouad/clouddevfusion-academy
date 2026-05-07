<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429081500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create intranet base schema (users, roles, permissions, sessions, news).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE roles (id SERIAL NOT NULL, code VARCHAR(50) NOT NULL, label VARCHAR(120) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B63E2EC77E3C61F9 ON roles (code)');

        $this->addSql('CREATE TABLE permissions (id SERIAL NOT NULL, code VARCHAR(80) NOT NULL, label VARCHAR(160) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FED90CCA44B56EA ON permissions (code)');

        $this->addSql('CREATE TABLE role_permissions (role_id INT NOT NULL, permission_id INT NOT NULL, granted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(role_id, permission_id))');
        $this->addSql('CREATE INDEX IDX_ROLE_PERMISSIONS_ROLE ON role_permissions (role_id)');
        $this->addSql('CREATE INDEX IDX_ROLE_PERMISSIONS_PERMISSION ON role_permissions (permission_id)');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_ROLE_PERMISSIONS_ROLE FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_ROLE_PERMISSIONS_PERMISSION FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE users (id SERIAL NOT NULL, role_id INT NOT NULL, first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E9D60322AC ON users (role_id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE sessions (id UUID NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, ip_address VARCHAR(64) DEFAULT NULL, user_agent VARCHAR(512) DEFAULT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7A2D8165F37A13B ON sessions (token)');
        $this->addSql('CREATE INDEX IDX_C7A2D816A76ED395 ON sessions (user_id)');
        $this->addSql('ALTER TABLE sessions ADD CONSTRAINT FK_C7A2D816A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE news (id SERIAL NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, body TEXT NOT NULL, is_published BOOLEAN NOT NULL DEFAULT FALSE, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E237E06D16C4DD7 ON news (slug)');
        $this->addSql('CREATE INDEX IDX_5E237E06F675F31B ON news (author_id)');
        $this->addSql('ALTER TABLE news ADD CONSTRAINT FK_5E237E06F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news DROP CONSTRAINT FK_5E237E06F675F31B');
        $this->addSql('ALTER TABLE sessions DROP CONSTRAINT FK_C7A2D816A76ED395');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E9D60322AC');
        $this->addSql('ALTER TABLE role_permissions DROP CONSTRAINT FK_ROLE_PERMISSIONS_ROLE');
        $this->addSql('ALTER TABLE role_permissions DROP CONSTRAINT FK_ROLE_PERMISSIONS_PERMISSION');

        $this->addSql('DROP TABLE news');
        $this->addSql('DROP TABLE sessions');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE roles');
    }
}
