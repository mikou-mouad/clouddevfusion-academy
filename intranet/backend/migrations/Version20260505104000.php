<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505104000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create intranet domain tables (providers, trainers, students, formations, sessions, attendance, resources).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE providers (id BIGSERIAL NOT NULL, company_name VARCHAR(255) NOT NULL, siret VARCHAR(32) NOT NULL, address TEXT NOT NULL, phone VARCHAR(32) NOT NULL, activity_declaration_number VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INTR_PROVIDERS_SIRET ON providers (siret)');
        $this->addSql('CREATE INDEX IDX_INTR_PROVIDERS_COMPANY ON providers (company_name)');

        $this->addSql("CREATE TABLE provider_documents (id BIGSERIAL NOT NULL, provider_id BIGINT NOT NULL, document_type VARCHAR(50) NOT NULL, label VARCHAR(160) NOT NULL, url VARCHAR(500) NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE INDEX IDX_INTR_PROVIDER_DOC_PROVIDER ON provider_documents (provider_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INTR_PROVIDER_DOC_TYPE ON provider_documents (provider_id, document_type)');
        $this->addSql('ALTER TABLE provider_documents ADD CONSTRAINT FK_INTR_PROVIDER_DOC_PROVIDER FOREIGN KEY (provider_id) REFERENCES providers (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE trainers (id BIGSERIAL NOT NULL, user_id INT DEFAULT NULL, provider_id BIGINT DEFAULT NULL, first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(32) NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'salarie', company_name VARCHAR(255) DEFAULT NULL, microsoft_transcript_url VARCHAR(500) DEFAULT NULL, cv_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INTR_TRAINERS_USER ON trainers (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INTR_TRAINERS_EMAIL ON trainers (email)');
        $this->addSql('CREATE INDEX IDX_INTR_TRAINERS_PROVIDER ON trainers (provider_id)');
        $this->addSql("ALTER TABLE trainers ADD CONSTRAINT CHK_INTR_TRAINERS_STATUS CHECK (status IN ('salarie', 'freelance', 'partenaire'))");
        $this->addSql('ALTER TABLE trainers ADD CONSTRAINT FK_INTR_TRAINERS_USER FOREIGN KEY (user_id) REFERENCES intranet_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE trainers ADD CONSTRAINT FK_INTR_TRAINERS_PROVIDER FOREIGN KEY (provider_id) REFERENCES providers (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE trainer_certifications (id BIGSERIAL NOT NULL, trainer_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, issuer VARCHAR(255) DEFAULT NULL, expires_at DATE DEFAULT NULL, proof_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE INDEX IDX_INTR_TRAINER_CERT_TRAINER ON trainer_certifications (trainer_id)');
        $this->addSql('ALTER TABLE trainer_certifications ADD CONSTRAINT FK_INTR_TRAINER_CERT_TRAINER FOREIGN KEY (trainer_id) REFERENCES trainers (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE trainer_completed_trainings (id BIGSERIAL NOT NULL, trainer_id BIGINT NOT NULL, domain VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, objective TEXT DEFAULT NULL, training_organization VARCHAR(255) DEFAULT NULL, training_date DATE DEFAULT NULL, duration_hours NUMERIC(6,2) DEFAULT NULL, attestation_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE INDEX IDX_INTR_TRAINER_DONE_TRAINER ON trainer_completed_trainings (trainer_id)');
        $this->addSql('ALTER TABLE trainer_completed_trainings ADD CONSTRAINT FK_INTR_TRAINER_DONE_TRAINER FOREIGN KEY (trainer_id) REFERENCES trainers (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE students (id BIGSERIAL NOT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, birth_date DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INTR_STUDENTS_USER ON students (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_INTR_STUDENTS_EMAIL ON students (email)');
        $this->addSql('ALTER TABLE students ADD CONSTRAINT FK_INTR_STUDENTS_USER FOREIGN KEY (user_id) REFERENCES intranet_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE formations (id VARCHAR(191) NOT NULL, catalog_course_id VARCHAR(64) DEFAULT NULL, catalog_course_title VARCHAR(255) DEFAULT NULL, title VARCHAR(255) NOT NULL, mode VARCHAR(80) NOT NULL DEFAULT 'En ligne', teams_link VARCHAR(500) DEFAULT NULL, trainer_id BIGINT DEFAULT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, is_archived BOOLEAN NOT NULL DEFAULT FALSE, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE INDEX IDX_INTR_FORMATIONS_TRAINER ON formations (trainer_id)');
        $this->addSql('CREATE INDEX IDX_INTR_FORMATIONS_DATE ON formations (start_date, end_date)');
        $this->addSql('ALTER TABLE formations ADD CONSTRAINT FK_INTR_FORMATIONS_TRAINER FOREIGN KEY (trainer_id) REFERENCES trainers (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE classes (id VARCHAR(191) NOT NULL, formation_id VARCHAR(191) NOT NULL, label VARCHAR(255) NOT NULL, trainer_id BIGINT DEFAULT NULL, capacity INT NOT NULL DEFAULT 20, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE INDEX IDX_INTR_CLASSES_FORMATION ON classes (formation_id)');
        $this->addSql('CREATE INDEX IDX_INTR_CLASSES_TRAINER ON classes (trainer_id)');
        $this->addSql('ALTER TABLE classes ADD CONSTRAINT FK_INTR_CLASSES_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE classes ADD CONSTRAINT FK_INTR_CLASSES_TRAINER FOREIGN KEY (trainer_id) REFERENCES trainers (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE class_enrollments (class_id VARCHAR(191) NOT NULL, student_id BIGINT NOT NULL, enrolled_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(class_id, student_id))");
        $this->addSql('CREATE INDEX IDX_INTR_CLASS_ENROLL_STUDENT ON class_enrollments (student_id)');
        $this->addSql('ALTER TABLE class_enrollments ADD CONSTRAINT FK_INTR_CLASS_ENROLL_CLASS FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE class_enrollments ADD CONSTRAINT FK_INTR_CLASS_ENROLL_STUDENT FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE formation_sessions (id VARCHAR(191) NOT NULL, formation_id VARCHAR(191) NOT NULL, day_label VARCHAR(50) DEFAULT NULL, session_date DATE NOT NULL, slot_label VARCHAR(80) NOT NULL, topic VARCHAR(255) NOT NULL, slot_start TIME(0) WITHOUT TIME ZONE DEFAULT NULL, slot_end TIME(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id))");
        $this->addSql('CREATE INDEX IDX_INTR_SESSIONS_FORMATION ON formation_sessions (formation_id)');
        $this->addSql('CREATE INDEX IDX_INTR_SESSIONS_DATE ON formation_sessions (session_date)');
        $this->addSql('ALTER TABLE formation_sessions ADD CONSTRAINT FK_INTR_SESSIONS_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE attendance_windows (session_id VARCHAR(191) NOT NULL, is_open BOOLEAN NOT NULL DEFAULT FALSE, opened_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, opened_by_role VARCHAR(30) DEFAULT NULL, opened_by_id INT DEFAULT NULL, PRIMARY KEY(session_id))");
        $this->addSql('ALTER TABLE attendance_windows ADD CONSTRAINT FK_INTR_ATT_WIN_SESSION FOREIGN KEY (session_id) REFERENCES formation_sessions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE attendance_records (session_id VARCHAR(191) NOT NULL, student_id BIGINT NOT NULL, status VARCHAR(20) NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(session_id, student_id))");
        $this->addSql('CREATE INDEX IDX_INTR_ATT_RECORD_STUDENT ON attendance_records (student_id)');
        $this->addSql("ALTER TABLE attendance_records ADD CONSTRAINT CHK_INTR_ATT_RECORD_STATUS CHECK (status IN ('present', 'late', 'absent', 'excused'))");
        $this->addSql('ALTER TABLE attendance_records ADD CONSTRAINT FK_INTR_ATT_RECORD_SESSION FOREIGN KEY (session_id) REFERENCES formation_sessions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE attendance_records ADD CONSTRAINT FK_INTR_ATT_RECORD_STUDENT FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("CREATE TABLE resources (id VARCHAR(191) NOT NULL, audience VARCHAR(20) NOT NULL DEFAULT 'formation', formation_id VARCHAR(191) DEFAULT NULL, session_id VARCHAR(191) DEFAULT NULL, formation_title VARCHAR(255) DEFAULT NULL, session_label VARCHAR(255) DEFAULT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(40) NOT NULL, url VARCHAR(500) NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, uploaded_by_role VARCHAR(30) DEFAULT NULL, uploaded_by_trainer_id BIGINT DEFAULT NULL, uploaded_by_admin_id INT DEFAULT NULL, uploaded_by_admin_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))");
        $this->addSql("ALTER TABLE resources ADD CONSTRAINT CHK_INTR_RESOURCES_AUDIENCE CHECK (audience IN ('all', 'formation', 'session', 'student', 'trainer'))");
        $this->addSql('CREATE INDEX IDX_INTR_RESOURCES_FORMATION ON resources (formation_id)');
        $this->addSql('CREATE INDEX IDX_INTR_RESOURCES_SESSION ON resources (session_id)');
        $this->addSql('CREATE INDEX IDX_INTR_RESOURCES_TRAINER ON resources (uploaded_by_trainer_id)');
        $this->addSql('CREATE INDEX IDX_INTR_RESOURCES_ADMIN ON resources (uploaded_by_admin_id)');
        $this->addSql('ALTER TABLE resources ADD CONSTRAINT FK_INTR_RESOURCES_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resources ADD CONSTRAINT FK_INTR_RESOURCES_SESSION FOREIGN KEY (session_id) REFERENCES formation_sessions (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resources ADD CONSTRAINT FK_INTR_RESOURCES_TRAINER FOREIGN KEY (uploaded_by_trainer_id) REFERENCES trainers (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resources ADD CONSTRAINT FK_INTR_RESOURCES_ADMIN FOREIGN KEY (uploaded_by_admin_id) REFERENCES intranet_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resources DROP CONSTRAINT FK_INTR_RESOURCES_ADMIN');
        $this->addSql('ALTER TABLE resources DROP CONSTRAINT FK_INTR_RESOURCES_TRAINER');
        $this->addSql('ALTER TABLE resources DROP CONSTRAINT FK_INTR_RESOURCES_SESSION');
        $this->addSql('ALTER TABLE resources DROP CONSTRAINT FK_INTR_RESOURCES_FORMATION');

        $this->addSql('ALTER TABLE attendance_records DROP CONSTRAINT FK_INTR_ATT_RECORD_STUDENT');
        $this->addSql('ALTER TABLE attendance_records DROP CONSTRAINT FK_INTR_ATT_RECORD_SESSION');
        $this->addSql('ALTER TABLE attendance_windows DROP CONSTRAINT FK_INTR_ATT_WIN_SESSION');
        $this->addSql('ALTER TABLE formation_sessions DROP CONSTRAINT FK_INTR_SESSIONS_FORMATION');

        $this->addSql('ALTER TABLE class_enrollments DROP CONSTRAINT FK_INTR_CLASS_ENROLL_STUDENT');
        $this->addSql('ALTER TABLE class_enrollments DROP CONSTRAINT FK_INTR_CLASS_ENROLL_CLASS');
        $this->addSql('ALTER TABLE classes DROP CONSTRAINT FK_INTR_CLASSES_TRAINER');
        $this->addSql('ALTER TABLE classes DROP CONSTRAINT FK_INTR_CLASSES_FORMATION');

        $this->addSql('ALTER TABLE formations DROP CONSTRAINT FK_INTR_FORMATIONS_TRAINER');
        $this->addSql('ALTER TABLE students DROP CONSTRAINT FK_INTR_STUDENTS_USER');
        $this->addSql('ALTER TABLE trainer_completed_trainings DROP CONSTRAINT FK_INTR_TRAINER_DONE_TRAINER');
        $this->addSql('ALTER TABLE trainer_certifications DROP CONSTRAINT FK_INTR_TRAINER_CERT_TRAINER');
        $this->addSql('ALTER TABLE trainers DROP CONSTRAINT FK_INTR_TRAINERS_PROVIDER');
        $this->addSql('ALTER TABLE trainers DROP CONSTRAINT FK_INTR_TRAINERS_USER');
        $this->addSql('ALTER TABLE provider_documents DROP CONSTRAINT FK_INTR_PROVIDER_DOC_PROVIDER');

        $this->addSql('DROP TABLE resources');
        $this->addSql('DROP TABLE attendance_records');
        $this->addSql('DROP TABLE attendance_windows');
        $this->addSql('DROP TABLE formation_sessions');
        $this->addSql('DROP TABLE class_enrollments');
        $this->addSql('DROP TABLE classes');
        $this->addSql('DROP TABLE formations');
        $this->addSql('DROP TABLE students');
        $this->addSql('DROP TABLE trainer_completed_trainings');
        $this->addSql('DROP TABLE trainer_certifications');
        $this->addSql('DROP TABLE trainers');
        $this->addSql('DROP TABLE provider_documents');
        $this->addSql('DROP TABLE providers');
    }
}
