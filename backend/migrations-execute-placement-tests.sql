-- Migration SQL pour les tests de positionnement
-- À exécuter directement en SQL si Doctrine migrations échoue

-- Table placement_tests
CREATE TABLE IF NOT EXISTS placement_tests (
    id SERIAL PRIMARY KEY,
    course_id INT NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    passing_score INT NOT NULL DEFAULT 70,
    time_limit INT NOT NULL DEFAULT 30,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_placement_test_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Table placement_questions
CREATE TABLE IF NOT EXISTS placement_questions (
    id SERIAL PRIMARY KEY,
    placement_test_id INT NOT NULL,
    question TEXT NOT NULL,
    explanation TEXT,
    order_index INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_placement_question_test FOREIGN KEY (placement_test_id) REFERENCES placement_tests(id) ON DELETE CASCADE
);

-- Table placement_answers
CREATE TABLE IF NOT EXISTS placement_answers (
    id SERIAL PRIMARY KEY,
    question_id INT NOT NULL,
    text TEXT NOT NULL,
    score NUMERIC(5, 2) NOT NULL DEFAULT 0.00,
    is_correct BOOLEAN NOT NULL DEFAULT false,
    order_index INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_placement_answer_question FOREIGN KEY (question_id) REFERENCES placement_questions(id) ON DELETE CASCADE
);

-- Table placement_test_results
CREATE TABLE IF NOT EXISTS placement_test_results (
    id SERIAL PRIMARY KEY,
    placement_test_id INT NOT NULL,
    user_email VARCHAR(255),
    user_name VARCHAR(255),
    score NUMERIC(5, 2) NOT NULL DEFAULT 0.00,
    total_questions INT NOT NULL DEFAULT 0,
    correct_answers INT NOT NULL DEFAULT 0,
    passed BOOLEAN NOT NULL DEFAULT false,
    answers JSONB,
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    CONSTRAINT fk_placement_result_test FOREIGN KEY (placement_test_id) REFERENCES placement_tests(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_placement_test_course ON placement_tests(course_id);
CREATE INDEX IF NOT EXISTS idx_placement_question_test ON placement_questions(placement_test_id);
CREATE INDEX IF NOT EXISTS idx_placement_answer_question ON placement_answers(question_id);
CREATE INDEX IF NOT EXISTS idx_placement_result_test ON placement_test_results(placement_test_id);
CREATE INDEX IF NOT EXISTS idx_placement_result_email ON placement_test_results(user_email);
