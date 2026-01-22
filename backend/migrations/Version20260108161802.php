<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108161802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE audit_logs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE blog_posts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE contacts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE courses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE exam_vouchers_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE faqs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE home_banners_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE labs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE placement_answers_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE placement_questions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE placement_test_results_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE placement_tests_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE syllabus_modules_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE testimonials_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE audit_logs (id INT NOT NULL, action VARCHAR(100) NOT NULL, entity_type VARCHAR(100) NOT NULL, entity_id INT DEFAULT NULL, entity_title VARCHAR(255) DEFAULT NULL, user_email VARCHAR(255) NOT NULL, username VARCHAR(100) NOT NULL, changes JSON DEFAULT NULL, description TEXT DEFAULT NULL, ip_address VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN audit_logs.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE blog_posts (id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt TEXT DEFAULT NULL, content TEXT NOT NULL, image TEXT DEFAULT NULL, category VARCHAR(100) NOT NULL, author VARCHAR(255) NOT NULL, read_time INT DEFAULT NULL, published BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_78B2F932989D9B62 ON blog_posts (slug)');
        $this->addSql('COMMENT ON COLUMN blog_posts.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN blog_posts.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE contacts (id INT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, subject VARCHAR(100) NOT NULL, message TEXT NOT NULL, read BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN contacts.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN contacts.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE courses (id INT NOT NULL, title VARCHAR(255) NOT NULL, code VARCHAR(50) NOT NULL, level VARCHAR(50) NOT NULL, duration VARCHAR(50) NOT NULL, format VARCHAR(50) NOT NULL, access_delay VARCHAR(100) DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, role VARCHAR(50) NOT NULL, product VARCHAR(100) NOT NULL, language VARCHAR(10) NOT NULL, next_date DATE DEFAULT NULL, description TEXT DEFAULT NULL, certification VARCHAR(255) DEFAULT NULL, popular BOOLEAN NOT NULL, objectives JSON NOT NULL, outcomes JSON NOT NULL, prerequisites JSON NOT NULL, target_roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN courses.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN courses.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE exam_vouchers (id INT NOT NULL, code VARCHAR(50) NOT NULL, exam_code VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, price NUMERIC(10, 2) NOT NULL, validity_period INT NOT NULL, description TEXT DEFAULT NULL, booking_steps JSON DEFAULT NULL, reschedule_rules TEXT DEFAULT NULL, redemption_info TEXT DEFAULT NULL, schedule_location TEXT DEFAULT NULL, id_requirements TEXT DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D071487B77153098 ON exam_vouchers (code)');
        $this->addSql('COMMENT ON COLUMN exam_vouchers.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN exam_vouchers.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE faqs (id INT NOT NULL, question TEXT NOT NULL, answer TEXT NOT NULL, category VARCHAR(100) NOT NULL, order_index INT DEFAULT NULL, published BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN faqs.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN faqs.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE home_banners (id INT NOT NULL, logo_path VARCHAR(255) DEFAULT NULL, kpi1_number VARCHAR(100) DEFAULT NULL, kpi1_label VARCHAR(255) DEFAULT NULL, kpi2_number VARCHAR(100) DEFAULT NULL, kpi2_label VARCHAR(255) DEFAULT NULL, kpi3_number VARCHAR(100) DEFAULT NULL, kpi3_label VARCHAR(255) DEFAULT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN home_banners.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN home_banners.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE labs (id INT NOT NULL, module_id INT NOT NULL, name VARCHAR(255) NOT NULL, duration VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_87661F13AFC2B591 ON labs (module_id)');
        $this->addSql('CREATE TABLE placement_answers (id INT NOT NULL, question_id INT NOT NULL, text TEXT NOT NULL, score NUMERIC(5, 2) NOT NULL, is_correct BOOLEAN NOT NULL, order_index INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9F3835A11E27F6BF ON placement_answers (question_id)');
        $this->addSql('CREATE TABLE placement_questions (id INT NOT NULL, placement_test_id INT NOT NULL, question TEXT NOT NULL, explanation TEXT DEFAULT NULL, order_index INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4F2E12B5D1B5F7FE ON placement_questions (placement_test_id)');
        $this->addSql('CREATE TABLE placement_test_results (id INT NOT NULL, placement_test_id INT NOT NULL, user_email VARCHAR(255) DEFAULT NULL, user_name VARCHAR(255) DEFAULT NULL, score NUMERIC(5, 2) NOT NULL, total_questions INT NOT NULL, correct_answers INT NOT NULL, passed BOOLEAN NOT NULL, answers JSON DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FEA5808AD1B5F7FE ON placement_test_results (placement_test_id)');
        $this->addSql('CREATE TABLE placement_tests (id INT NOT NULL, course_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, passing_score INT NOT NULL, time_limit INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1CBCFCED591CC992 ON placement_tests (course_id)');
        $this->addSql('CREATE TABLE syllabus_modules (id INT NOT NULL, course_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, order_index INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5863F4D591CC992 ON syllabus_modules (course_id)');
        $this->addSql('CREATE TABLE testimonials (id INT NOT NULL, quote TEXT DEFAULT NULL, author VARCHAR(255) DEFAULT NULL, role VARCHAR(255) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, rating INT DEFAULT NULL, video_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN testimonials.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN testimonials.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE labs ADD CONSTRAINT FK_87661F13AFC2B591 FOREIGN KEY (module_id) REFERENCES syllabus_modules (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE placement_answers ADD CONSTRAINT FK_9F3835A11E27F6BF FOREIGN KEY (question_id) REFERENCES placement_questions (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE placement_questions ADD CONSTRAINT FK_4F2E12B5D1B5F7FE FOREIGN KEY (placement_test_id) REFERENCES placement_tests (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE placement_test_results ADD CONSTRAINT FK_FEA5808AD1B5F7FE FOREIGN KEY (placement_test_id) REFERENCES placement_tests (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE placement_tests ADD CONSTRAINT FK_1CBCFCED591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE syllabus_modules ADD CONSTRAINT FK_5863F4D591CC992 FOREIGN KEY (course_id) REFERENCES courses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE ohm_contracts');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT fk_1483a5e9b03a8386');
        $this->addSql('DROP INDEX idx_1483a5e9b03a8386');
        $this->addSql('DROP INDEX idx_email');
        $this->addSql('DROP INDEX idx_is_active');
        $this->addSql('DROP INDEX idx_role');
        $this->addSql('ALTER TABLE users ADD username VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE users ADD active BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE users DROP created_by_id');
        $this->addSql('ALTER TABLE users DROP first_name');
        $this->addSql('ALTER TABLE users DROP last_name');
        $this->addSql('ALTER TABLE users DROP phone');
        $this->addSql('ALTER TABLE users DROP role');
        $this->addSql('ALTER TABLE users DROP department');
        $this->addSql('ALTER TABLE users DROP is_active');
        $this->addSql('ALTER TABLE users DROP hire_date');
        $this->addSql('ALTER TABLE users DROP address');
        $this->addSql('ALTER TABLE users DROP postal_code');
        $this->addSql('ALTER TABLE users DROP city');
        $this->addSql('ALTER TABLE users DROP date_of_birth');
        $this->addSql('ALTER TABLE users DROP social_security_number');
        $this->addSql('ALTER TABLE users DROP contract_type');
        $this->addSql('ALTER TABLE users DROP base_salary');
        $this->addSql('ALTER TABLE users DROP iban');
        $this->addSql('ALTER TABLE users DROP notes');
        $this->addSql('ALTER TABLE users ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE users ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE users ALTER updated_at DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE audit_logs_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE blog_posts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE contacts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE courses_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE exam_vouchers_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE faqs_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE home_banners_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE labs_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE placement_answers_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE placement_questions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE placement_test_results_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE placement_tests_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE syllabus_modules_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE testimonials_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('CREATE TABLE ohm_contracts (id INT NOT NULL, contrat_refer VARCHAR(255) DEFAULT NULL, client_ref VARCHAR(255) DEFAULT NULL, civility VARCHAR(10) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, energy VARCHAR(50) DEFAULT NULL, point_power VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_switch TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, signature_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, contract_status VARCHAR(100) DEFAULT NULL, offer_name VARCHAR(255) DEFAULT NULL, agent_name VARCHAR(255) DEFAULT NULL, partner_name VARCHAR(255) DEFAULT NULL, green_option VARCHAR(50) DEFAULT NULL, fixed_option VARCHAR(50) DEFAULT NULL, estimation_source VARCHAR(100) DEFAULT NULL, car_turpe NUMERIC(10, 2) DEFAULT NULL, car_questionnaire NUMERIC(10, 2) DEFAULT NULL, percentage_price_change NUMERIC(5, 2) DEFAULT NULL, current_step VARCHAR(100) DEFAULT NULL, is_bad_payer BOOLEAN DEFAULT NULL, observations TEXT DEFAULT NULL, imported_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, import_file_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_client_ref ON ohm_contracts (client_ref)');
        $this->addSql('CREATE INDEX idx_contract_status ON ohm_contracts (contract_status)');
        $this->addSql('CREATE INDEX idx_contrat_refer ON ohm_contracts (contrat_refer)');
        $this->addSql('CREATE INDEX idx_created_at ON ohm_contracts (created_at)');
        $this->addSql('ALTER TABLE labs DROP CONSTRAINT FK_87661F13AFC2B591');
        $this->addSql('ALTER TABLE placement_answers DROP CONSTRAINT FK_9F3835A11E27F6BF');
        $this->addSql('ALTER TABLE placement_questions DROP CONSTRAINT FK_4F2E12B5D1B5F7FE');
        $this->addSql('ALTER TABLE placement_test_results DROP CONSTRAINT FK_FEA5808AD1B5F7FE');
        $this->addSql('ALTER TABLE placement_tests DROP CONSTRAINT FK_1CBCFCED591CC992');
        $this->addSql('ALTER TABLE syllabus_modules DROP CONSTRAINT FK_5863F4D591CC992');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE blog_posts');
        $this->addSql('DROP TABLE contacts');
        $this->addSql('DROP TABLE courses');
        $this->addSql('DROP TABLE exam_vouchers');
        $this->addSql('DROP TABLE faqs');
        $this->addSql('DROP TABLE home_banners');
        $this->addSql('DROP TABLE labs');
        $this->addSql('DROP TABLE placement_answers');
        $this->addSql('DROP TABLE placement_questions');
        $this->addSql('DROP TABLE placement_test_results');
        $this->addSql('DROP TABLE placement_tests');
        $this->addSql('DROP TABLE syllabus_modules');
        $this->addSql('DROP TABLE testimonials');
        $this->addSql('ALTER TABLE users ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD first_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE users ADD last_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE users ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD role VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE users ADD department VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD is_active BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE users ADD hire_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD postal_code VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD city VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD date_of_birth DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD social_security_number VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD contract_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD base_salary NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD iban VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE users DROP username');
        $this->addSql('ALTER TABLE users DROP active');
        $this->addSql('ALTER TABLE users ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE users ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE users ALTER updated_at SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN users.created_at IS NULL');
        $this->addSql('COMMENT ON COLUMN users.updated_at IS NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT fk_1483a5e9b03a8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_1483a5e9b03a8386 ON users (created_by_id)');
        $this->addSql('CREATE INDEX idx_email ON users (email)');
        $this->addSql('CREATE INDEX idx_is_active ON users (is_active)');
        $this->addSql('CREATE INDEX idx_role ON users (role)');
    }
}
