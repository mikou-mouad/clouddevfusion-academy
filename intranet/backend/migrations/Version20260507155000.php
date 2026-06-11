<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507155000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add admin session document workflow and validation score tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE session_documents_generic (id BIGSERIAL NOT NULL, formation_id VARCHAR(191) NOT NULL, session_id VARCHAR(191) DEFAULT NULL, category VARCHAR(40) NOT NULL, document_type VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(500) NOT NULL, is_mandatory BOOLEAN NOT NULL DEFAULT TRUE, created_by_admin_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE session_documents_generic ADD CONSTRAINT CHK_INTR_DOC_GENERIC_CATEGORY CHECK (category IN ('pre-inscription', 'inscription', 'en-formation', 'cloture'))");
        $this->addSql('CREATE INDEX IDX_INTR_DOC_GENERIC_FORMATION ON session_documents_generic (formation_id)');
        $this->addSql('CREATE INDEX IDX_INTR_DOC_GENERIC_SESSION ON session_documents_generic (session_id)');
        $this->addSql('CREATE INDEX IDX_INTR_DOC_GENERIC_CATEGORY ON session_documents_generic (category)');
        $this->addSql('ALTER TABLE session_documents_generic ADD CONSTRAINT FK_INTR_DOC_GENERIC_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE session_documents_generic ADD CONSTRAINT FK_INTR_DOC_GENERIC_SESSION FOREIGN KEY (session_id) REFERENCES formation_sessions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE session_documents_generic ADD CONSTRAINT FK_INTR_DOC_GENERIC_ADMIN FOREIGN KEY (created_by_admin_id) REFERENCES intranet_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE student_documents (id BIGSERIAL NOT NULL, student_id BIGINT NOT NULL, formation_id VARCHAR(191) NOT NULL, session_id VARCHAR(191) DEFAULT NULL, category VARCHAR(40) NOT NULL, document_type VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(500) NOT NULL, source VARCHAR(20) NOT NULL DEFAULT 'admin', signature_status VARCHAR(20) NOT NULL DEFAULT 'pending', signed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, signed_by_user_id INT DEFAULT NULL, created_by_admin_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE student_documents ADD CONSTRAINT CHK_INTR_STUDENT_DOC_CATEGORY CHECK (category IN ('pre-inscription', 'inscription', 'en-formation', 'cloture'))");
        $this->addSql("ALTER TABLE student_documents ADD CONSTRAINT CHK_INTR_STUDENT_DOC_SOURCE CHECK (source IN ('admin', 'student', 'trainer', 'system'))");
        $this->addSql("ALTER TABLE student_documents ADD CONSTRAINT CHK_INTR_STUDENT_DOC_SIGNATURE CHECK (signature_status IN ('pending', 'signed', 'rejected'))");
        $this->addSql('CREATE INDEX IDX_INTR_STUDENT_DOC_STUDENT ON student_documents (student_id)');
        $this->addSql('CREATE INDEX IDX_INTR_STUDENT_DOC_FORMATION ON student_documents (formation_id)');
        $this->addSql('CREATE INDEX IDX_INTR_STUDENT_DOC_SESSION ON student_documents (session_id)');
        $this->addSql('CREATE INDEX IDX_INTR_STUDENT_DOC_CATEGORY ON student_documents (category)');
        $this->addSql('ALTER TABLE student_documents ADD CONSTRAINT FK_INTR_STUDENT_DOC_STUDENT FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE student_documents ADD CONSTRAINT FK_INTR_STUDENT_DOC_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE student_documents ADD CONSTRAINT FK_INTR_STUDENT_DOC_SESSION FOREIGN KEY (session_id) REFERENCES formation_sessions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE student_documents ADD CONSTRAINT FK_INTR_STUDENT_DOC_SIGNED_USER FOREIGN KEY (signed_by_user_id) REFERENCES intranet_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE student_documents ADD CONSTRAINT FK_INTR_STUDENT_DOC_ADMIN FOREIGN KEY (created_by_admin_id) REFERENCES intranet_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE session_validation_tests (id BIGSERIAL NOT NULL, formation_id VARCHAR(191) NOT NULL, session_id VARCHAR(191) DEFAULT NULL, title VARCHAR(255) NOT NULL, external_link VARCHAR(500) DEFAULT NULL, max_score NUMERIC(6,2) NOT NULL DEFAULT 100, created_by_admin_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE INDEX IDX_INTR_VALIDATION_FORMATION ON session_validation_tests (formation_id)');
        $this->addSql('CREATE INDEX IDX_INTR_VALIDATION_SESSION ON session_validation_tests (session_id)');
        $this->addSql('ALTER TABLE session_validation_tests ADD CONSTRAINT FK_INTR_VALIDATION_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE session_validation_tests ADD CONSTRAINT FK_INTR_VALIDATION_SESSION FOREIGN KEY (session_id) REFERENCES formation_sessions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE session_validation_tests ADD CONSTRAINT FK_INTR_VALIDATION_ADMIN FOREIGN KEY (created_by_admin_id) REFERENCES intranet_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE student_validation_results (id BIGSERIAL NOT NULL, validation_test_id BIGINT NOT NULL, student_id BIGINT NOT NULL, score NUMERIC(6,2) NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'pending', scored_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, scored_by_admin_id INT DEFAULT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE student_validation_results ADD CONSTRAINT CHK_INTR_VALIDATION_RESULT_STATUS CHECK (status IN ('pending', 'passed', 'failed'))");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INTR_VALIDATION_STUDENT ON student_validation_results (validation_test_id, student_id)');
        $this->addSql('CREATE INDEX IDX_INTR_VALIDATION_RESULT_STUDENT ON student_validation_results (student_id)');
        $this->addSql('ALTER TABLE student_validation_results ADD CONSTRAINT FK_INTR_VALIDATION_RESULT_TEST FOREIGN KEY (validation_test_id) REFERENCES session_validation_tests (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE student_validation_results ADD CONSTRAINT FK_INTR_VALIDATION_RESULT_STUDENT FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE student_validation_results ADD CONSTRAINT FK_INTR_VALIDATION_RESULT_ADMIN FOREIGN KEY (scored_by_admin_id) REFERENCES intranet_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student_validation_results DROP CONSTRAINT FK_INTR_VALIDATION_RESULT_ADMIN');
        $this->addSql('ALTER TABLE student_validation_results DROP CONSTRAINT FK_INTR_VALIDATION_RESULT_STUDENT');
        $this->addSql('ALTER TABLE student_validation_results DROP CONSTRAINT FK_INTR_VALIDATION_RESULT_TEST');
        $this->addSql('ALTER TABLE session_validation_tests DROP CONSTRAINT FK_INTR_VALIDATION_ADMIN');
        $this->addSql('ALTER TABLE session_validation_tests DROP CONSTRAINT FK_INTR_VALIDATION_SESSION');
        $this->addSql('ALTER TABLE session_validation_tests DROP CONSTRAINT FK_INTR_VALIDATION_FORMATION');
        $this->addSql('ALTER TABLE student_documents DROP CONSTRAINT FK_INTR_STUDENT_DOC_ADMIN');
        $this->addSql('ALTER TABLE student_documents DROP CONSTRAINT FK_INTR_STUDENT_DOC_SIGNED_USER');
        $this->addSql('ALTER TABLE student_documents DROP CONSTRAINT FK_INTR_STUDENT_DOC_SESSION');
        $this->addSql('ALTER TABLE student_documents DROP CONSTRAINT FK_INTR_STUDENT_DOC_FORMATION');
        $this->addSql('ALTER TABLE student_documents DROP CONSTRAINT FK_INTR_STUDENT_DOC_STUDENT');
        $this->addSql('ALTER TABLE session_documents_generic DROP CONSTRAINT FK_INTR_DOC_GENERIC_ADMIN');
        $this->addSql('ALTER TABLE session_documents_generic DROP CONSTRAINT FK_INTR_DOC_GENERIC_SESSION');
        $this->addSql('ALTER TABLE session_documents_generic DROP CONSTRAINT FK_INTR_DOC_GENERIC_FORMATION');
        $this->addSql('DROP TABLE student_validation_results');
        $this->addSql('DROP TABLE session_validation_tests');
        $this->addSql('DROP TABLE student_documents');
        $this->addSql('DROP TABLE session_documents_generic');
    }
}
