<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add intranet validation quiz questions, options and student attempts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE session_validation_tests ADD COLUMN IF NOT EXISTS pass_threshold NUMERIC(5,4) NOT NULL DEFAULT 0.7");
        $this->addSql("ALTER TABLE session_validation_tests ADD COLUMN IF NOT EXISTS is_published BOOLEAN NOT NULL DEFAULT TRUE");
        $this->addSql("ALTER TABLE session_validation_tests ADD COLUMN IF NOT EXISTS source_type VARCHAR(20) NOT NULL DEFAULT 'intranet'");

        $this->addSql('CREATE TABLE validation_questions (
            id BIGSERIAL NOT NULL,
            validation_test_id BIGINT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            prompt TEXT NOT NULL,
            points NUMERIC(6,2) NOT NULL DEFAULT 1,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_INTR_VQ_TEST ON validation_questions (validation_test_id)');
        $this->addSql('ALTER TABLE validation_questions ADD CONSTRAINT FK_INTR_VQ_TEST FOREIGN KEY (validation_test_id) REFERENCES session_validation_tests (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE validation_question_options (
            id BIGSERIAL NOT NULL,
            question_id BIGINT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            label VARCHAR(500) NOT NULL,
            is_correct BOOLEAN NOT NULL DEFAULT FALSE,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_INTR_VQO_QUESTION ON validation_question_options (question_id)');
        $this->addSql('ALTER TABLE validation_question_options ADD CONSTRAINT FK_INTR_VQO_QUESTION FOREIGN KEY (question_id) REFERENCES validation_questions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE student_validation_attempts (
            id BIGSERIAL NOT NULL,
            validation_test_id BIGINT NOT NULL,
            student_id BIGINT NOT NULL,
            score NUMERIC(6,2) NOT NULL DEFAULT 0,
            max_score NUMERIC(6,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            answers_json TEXT DEFAULT NULL,
            started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            submitted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )");
        $this->addSql("ALTER TABLE student_validation_attempts ADD CONSTRAINT CHK_INTR_VALIDATION_ATTEMPT_STATUS CHECK (status IN ('pending', 'passed', 'failed'))");
        $this->addSql('CREATE INDEX IDX_INTR_VA_TEST ON student_validation_attempts (validation_test_id)');
        $this->addSql('CREATE INDEX IDX_INTR_VA_STUDENT ON student_validation_attempts (student_id)');
        $this->addSql('ALTER TABLE student_validation_attempts ADD CONSTRAINT FK_INTR_VA_TEST FOREIGN KEY (validation_test_id) REFERENCES session_validation_tests (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE student_validation_attempts ADD CONSTRAINT FK_INTR_VA_STUDENT FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE student_validation_attempts DROP CONSTRAINT FK_INTR_VA_STUDENT');
        $this->addSql('ALTER TABLE student_validation_attempts DROP CONSTRAINT FK_INTR_VA_TEST');
        $this->addSql('DROP TABLE student_validation_attempts');
        $this->addSql('ALTER TABLE validation_question_options DROP CONSTRAINT FK_INTR_VQO_QUESTION');
        $this->addSql('DROP TABLE validation_question_options');
        $this->addSql('ALTER TABLE validation_questions DROP CONSTRAINT FK_INTR_VQ_TEST');
        $this->addSql('DROP TABLE validation_questions');
        $this->addSql('ALTER TABLE session_validation_tests DROP COLUMN IF EXISTS source_type');
        $this->addSql('ALTER TABLE session_validation_tests DROP COLUMN IF EXISTS is_published');
        $this->addSql('ALTER TABLE session_validation_tests DROP COLUMN IF EXISTS pass_threshold');
    }
}
