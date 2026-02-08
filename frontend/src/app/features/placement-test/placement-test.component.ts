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
        if (!test) {
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

    // Calculer le score
    let totalScore = 0;
    let correctAnswers = 0;
    const totalQuestions = this.test.questions?.length || 0;

    this.test.questions?.forEach(question => {
      const selectedAnswerId = this.answers[question.id!];
      if (selectedAnswerId) {
        const selectedAnswer = question.answers?.find(a => a.id === selectedAnswerId);
        if (selectedAnswer) {
          totalScore += parseFloat(selectedAnswer.score.toString());
          if (selectedAnswer.isCorrect) {
            correctAnswers++;
          }
        }
      }
    });

    const finalScore = totalQuestions > 0 ? (totalScore / totalQuestions) * 100 : 0;
    const passed = finalScore >= this.getPassingScore();

    // Créer le résultat
    const result: PlacementTestResult = {
      placementTest: this.test,
      score: finalScore,
      totalQuestions,
      correctAnswers,
      passed,
      answers: this.answers
    };

    this.loading = true;
    this.apiService.submitPlacementTestResult(result).subscribe({
      next: (savedResult) => {
        this.result = savedResult;
        this.testCompleted = true;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error submitting result:', err);
        // Afficher quand même le résultat même si l'enregistrement échoue
        this.result = result;
        this.testCompleted = true;
        this.loading = false;
      }
    });
  }

  goToContact() {
    this.router.navigate(['/contact']);
  }
}
