<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209132305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE courses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE labs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE syllabus_modules_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE testimonials_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE courses (id INT NOT NULL, title VARCHAR(255) NOT NULL, code VARCHAR(50) NOT NULL, level VARCHAR(50) NOT NULL, duration VARCHAR(50) NOT NULL, format VARCHAR(50) NOT NULL, price NUMERIC(10, 2) NOT NULL, role VARCHAR(50) NOT NULL, product VARCHAR(100) NOT NULL, language VARCHAR(10) NOT NULL, next_date DATE DEFAULT NULL, description TEXT DEFAULT NULL, certification VARCHAR(255) DEFAULT NULL, popular BOOLEAN NOT NULL, objectives JSON NOT NULL, outcomes JSON NOT NULL, prerequisites JSON NOT NULL, target_roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN courses.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN courses.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE labs (id INT NOT NULL, module_id INT NOT NULL, name VARCHAR(255) NOT NULL, duration VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_87661F13AFC2B591 ON labs (module_id)');
        $this->addSql('CREATE TABLE syllabus_modules (id INT NOT NULL, course_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, order_index INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5863F4D591CC992 ON syllabus_modules (course_id)');
        $this->addSql('CREATE TABLE testimonials (id INT NOT NULL, quote TEXT NOT NULL, author VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, rating INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN testimonials.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN testimonials.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE labs ADD CONSTRAINT FK_87661F13AFC2B591 FOREIGN KEY (module_id) REFERENCES syllabus_modules (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE syllabus_modules ADD CONSTRAINT FK_5863F4D591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE product DROP CONSTRAINT fk_d34a04ad83fa6dd0');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT fk_e52ffdee83fa6dd0');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE commercant');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE courses_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE labs_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE syllabus_modules_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE testimonials_id_seq CASCADE');
        $this->addSql('CREATE TABLE product (id INT NOT NULL, commercant_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, category VARCHAR(255) NOT NULL, stock INT NOT NULL, options JSON DEFAULT NULL, photos JSON DEFAULT NULL, price DOUBLE PRECISION NOT NULL, shipping DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_d34a04ad83fa6dd0 ON product (commercant_id)');
        $this->addSql('CREATE TABLE orders (id INT NOT NULL, commercant_id INT NOT NULL, products JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_e52ffdee83fa6dd0 ON orders (commercant_id)');
        $this->addSql('CREATE TABLE commercant (id INT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, nom_commerce VARCHAR(255) NOT NULL, categorie VARCHAR(255) NOT NULL, description TEXT NOT NULL, numero_siret VARCHAR(14) NOT NULL, telephone VARCHAR(20) NOT NULL, adresse_postale VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, ville VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, horaires JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_ecb4268fe7927c74 ON commercant (email)');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, adresse_postale VARCHAR(255) NOT NULL, code_postal VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_1483a5e9e7927c74 ON users (email)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_75ea56e016ba31db ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0e3bd61ce ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0fb7336f0 ON messenger_messages (queue_name)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT fk_d34a04ad83fa6dd0 FOREIGN KEY (commercant_id) REFERENCES commercant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT fk_e52ffdee83fa6dd0 FOREIGN KEY (commercant_id) REFERENCES commercant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE labs DROP CONSTRAINT FK_87661F13AFC2B591');
        $this->addSql('ALTER TABLE syllabus_modules DROP CONSTRAINT FK_5863F4D591CC992');
        $this->addSql('DROP TABLE courses');
        $this->addSql('DROP TABLE labs');
        $this->addSql('DROP TABLE syllabus_modules');
        $this->addSql('DROP TABLE testimonials');
    }
}
