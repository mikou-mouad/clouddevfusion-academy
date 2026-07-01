import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ApiService, PlacementTest, PlacementQuestion, PlacementAnswer, PlacementTestResult } from '../../core/services/api.service';

@Component({
  selector: 'app-placement-test',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './placement-test.component.html',
  styleUrls: ['./placement-test.component.scss']
})
export class PlacementTestComponent implements OnInit {
  test: PlacementTest | null = null;
  currentQuestionIndex = 0;
  answers: { [questionId: number]: number } = {}; // questionId -> answerId
  timeRemaining = 0; // en secondes
  timerInterval: any;
  testStarted = false;
  testCompleted = false;
  result: PlacementTestResult | null = null;
  loading = false;
  error: string | null = null;
  saveError: string | null = null;   // erreur d'enregistrement côté API
  saveSuccess = false;               // enregistrement réussi

  userFirstName = '';
  userLastName = '';
  userEmail = '';
  userPhone = '';
  userInfoValid = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private apiService: ApiService
  ) {}

  ngOnInit() {
    const courseId = this.route.snapshot.paramMap.get('courseId');
    if (courseId) {
      this.loadTest(parseInt(courseId, 10));
    }
  }

  loadTest(courseId: number) {
    this.loading = true;
    this.apiService.getPlacementTestByCourse(courseId).subscribe({
      next: (test) => {
        if (!test || !test.isActive) {
          this.error = 'Aucun test de positionnement disponible pour cette formation';
          this.loading = false;
          return;
        }
        this.test = test;
        // Trier les questions par orderIndex
        if (this.test.questions) {
          this.test.questions.sort((a, b) => (a.orderIndex || 0) - (b.orderIndex || 0));
          this.test.questions.forEach(q => {
            if (q.answers) {
              q.answers.sort((a, b) => (a.orderIndex || 0) - (b.orderIndex || 0));
            }
          });
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading test:', err);
        this.error = 'Erreur lors du chargement du test';
        this.loading = false;
      }
    });
  }

  validateUserInfo() {
    if (this.userFirstName?.trim() && this.userLastName?.trim() && this.userEmail?.trim()) {
      this.userInfoValid = true;
    }
  }

  startTest() {
    if (!this.test) return;
    
    this.testStarted = true;
    this.timeRemaining = (this.test.timeLimit || 30) * 60; // convertir en secondes
    this.startTimer();
  }

  startTimer() {
    this.timerInterval = setInterval(() => {
      this.timeRemaining--;
      if (this.timeRemaining <= 0) {
        this.submitTest();
      }
    }, 1000);
  }

  formatTime(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }

  getAnswerLabel(index: number): string {
    return String.fromCharCode(65 + index); // A, B, C, D, etc.
  }

  getQuestionsLength(): number {
    return this.test?.questions?.length || 0;
  }

  getPassingScore(): number {
    return this.test?.passingScore ?? 70;
  }

  selectAnswer(questionId: number, answerId: number) {
    this.answers[questionId] = answerId;
  }

  getCurrentQuestion(): PlacementQuestion | null {
    if (!this.test || !this.test.questions) return null;
    return this.test.questions[this.currentQuestionIndex] || null;
  }

  nextQuestion() {
    if (this.test && this.currentQuestionIndex < this.test.questions.length - 1) {
      this.currentQuestionIndex++;
    }
  }

  previousQuestion() {
    if (this.currentQuestionIndex > 0) {
      this.currentQuestionIndex--;
    }
  }

  submitTest() {
    if (this.timerInterval) {
      clearInterval(this.timerInterval);
    }

    if (!this.test) return;

    const { finalScore, correctAnswers, answeredQuestions } = this.calculateTestScore();
    const totalQuestions = this.test.questions?.length || 0;
    const passed = finalScore >= this.getPassingScore();

    const userName = `${this.userFirstName.trim()} ${this.userLastName.trim()}`;

    // Payload pour l'API (placementTest en IRI, score en string pour le backend)
    const payload = {
      placementTest: `/api/placement_tests/${this.test!.id}`,
      userName,
      userEmail: this.userEmail.trim(),
      userPhone: this.userPhone?.trim() || undefined,
      score: finalScore.toFixed(2), // string pour l'entité backend (DECIMAL 0-100)
      totalQuestions,
      correctAnswers,
      passed,
      answers: this.answers
    };

    const result: PlacementTestResult = {
      placementTest: this.test,
      userName,
      userEmail: this.userEmail.trim(),
      userPhone: this.userPhone?.trim() || undefined,
      score: finalScore,
      totalQuestions,
      correctAnswers,
      answeredQuestions,
      passed,
      answers: this.answers
    };

    this.saveError = null;
    this.saveSuccess = false;
    this.loading = true;
    this.apiService.submitPlacementTestResult(payload as unknown as PlacementTestResult).subscribe({
      next: (savedResult) => {
        this.result = { ...result, ...savedResult, answeredQuestions };
        this.testCompleted = true;
        this.loading = false;
        this.saveSuccess = true;
      },
      error: (err) => {
        console.error('Error submitting result:', err);
        this.saveError = err.error?.detail || err.error?.message || err.message || 'Le résultat n\'a pas pu être enregistré.';
        this.result = result;
        this.testCompleted = true;
        this.loading = false;
      }
    });
  }

  private calculateTestScore(): { finalScore: number; correctAnswers: number; answeredQuestions: number } {
    let earnedScore = 0;
    let maxScore = 0;
    let correctAnswers = 0;
    let answeredQuestions = 0;

    this.test?.questions?.forEach((question) => {
      const answers = question.answers ?? [];
      const questionMax = answers.reduce((max, answer) => {
        const value = parseFloat(String(answer.score));
        return Number.isFinite(value) ? Math.max(max, value) : max;
      }, 0);
      maxScore += questionMax;

      const selectedAnswerId = this.answers[question.id!];
      if (!selectedAnswerId) {
        return;
      }

      answeredQuestions++;
      const selectedAnswer = answers.find((answer) => answer.id === selectedAnswerId);
      if (!selectedAnswer) {
        return;
      }

      const selectedScore = parseFloat(String(selectedAnswer.score));
      if (Number.isFinite(selectedScore)) {
        earnedScore += selectedScore;
      }

      const isCorrect = selectedAnswer.isCorrect
        || (questionMax > 0 && Number.isFinite(selectedScore) && selectedScore >= questionMax);
      if (isCorrect) {
        correctAnswers++;
      }
    });

    const finalScore = maxScore > 0
      ? Math.min(100, Math.max(0, (earnedScore / maxScore) * 100))
      : 0;

    return { finalScore, correctAnswers, answeredQuestions };
  }

  getAnsweredQuestionsCount(): number {
    if (this.result?.answeredQuestions !== undefined) {
      return this.result.answeredQuestions;
    }
    if (!this.result?.answers) {
      return 0;
    }
    return Object.keys(this.result.answers).length;
  }

  getResultScore(): string {
    if (!this.result) return '0';
    const s = this.result.score;
    const n = typeof s === 'number' ? s : parseFloat(String(s));
    return isNaN(n) ? '0' : n.toFixed(1);
  }

  goToContact() {
    this.router.navigate(['/contact']);
  }
}
