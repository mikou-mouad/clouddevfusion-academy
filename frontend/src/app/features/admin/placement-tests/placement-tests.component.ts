import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ApiService, PlacementTest, PlacementQuestion, PlacementAnswer, Course } from '../../../core/services/api.service';

@Component({
  selector: 'app-admin-placement-tests',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './placement-tests.component.html',
  styleUrls: ['./placement-tests.component.scss']
})
export class PlacementTestsComponent implements OnInit {
  tests: PlacementTest[] = [];
  courses: Course[] = [];
  selectedTest: PlacementTest | null = null;
  editingTest: PlacementTest | null = null;
  editingQuestion: PlacementQuestion | null = null;
  editingAnswer: PlacementAnswer | null = null;
  showTestModal = false;
  showQuestionModal = false;
  showAnswerModal = false;
  loading = false;
  error: string | null = null;
  successMessage: string | null = null;
  private answersCache = new Map<number, PlacementAnswer[]>();
  private loadingAnswers = new Set<number>();

  testForm: Partial<PlacementTest> = {
    title: '',
    description: '',
    passingScore: 70,
    timeLimit: 30,
    isActive: true,
    questions: []
  };

  questionForm: Partial<PlacementQuestion> = {
    question: '',
    explanation: '',
    orderIndex: 0,
    answers: []
  };

  answerForm: Partial<PlacementAnswer> = {
    text: '',
    score: 0,
    isCorrect: false,
    orderIndex: 0
  };

  constructor(
    private apiService: ApiService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.loadTests();
    this.loadCourses();
  }

  loadTests() {
    this.loading = true;
    this.answersCache.clear(); // Vider le cache
    this.loadingAnswers.clear();
    this.apiService.getPlacementTests().subscribe({
      next: (tests) => {
        this.tests = tests;
        this.loading = false;
        // Charger les réponses après l'affichage initial
        setTimeout(() => this.loadAnswersForAllQuestions(), 100);
      },
      error: (err) => {
        console.error('Error loading tests:', err);
        this.error = 'Erreur lors du chargement des tests';
        this.loading = false;
      }
    });
  }

  loadAnswersForAllQuestions() {
    // Charger les réponses pour toutes les questions en parallèle
    for (const test of this.tests) {
      if (test.questions) {
        for (const question of test.questions) {
          if (question.id && question.answers && question.answers.length > 0) {
            this.loadAnswersForQuestion(question);
          }
        }
      }
    }
  }

  loadAnswersForQuestion(question: PlacementQuestion) {
    if (!question.id || !question.answers) return;
    
    const answersToLoad: PlacementAnswer[] = [];
    const loadPromises: Promise<void>[] = [];
    
    for (const answerRef of question.answers) {
      if (typeof answerRef === 'string' && (answerRef as string).startsWith('/api/')) {
        // IRI - charger l'objet
        const answerId = parseInt((answerRef as string).split('/').pop() || '0');
        if (answerId) {
          const promise = firstValueFrom(this.apiService.getPlacementAnswer(answerId))
            .then(answer => {
              if (answer && answer.text) {
                answersToLoad.push(answer);
              }
            })
            .catch(() => {});
          loadPromises.push(promise);
        }
      } else if (typeof answerRef === 'object' && (answerRef as any).id) {
        // Objet
        if ((answerRef as any).text !== undefined) {
          answersToLoad.push(answerRef as PlacementAnswer);
        } else {
          // Objet partiel - charger
          const answerId = (answerRef as any).id;
          const promise = firstValueFrom(this.apiService.getPlacementAnswer(answerId))
            .then(answer => {
              if (answer && answer.text) {
                answersToLoad.push(answer);
              }
            })
            .catch(() => {});
          loadPromises.push(promise);
        }
      }
    }
    
    // Attendre le chargement et mettre à jour
    if (loadPromises.length > 0) {
      Promise.all(loadPromises).then(() => {
        // Créer un nouveau tableau pour forcer la détection
        const sortedAnswers = answersToLoad.sort((a, b) => (a.orderIndex || 0) - (b.orderIndex || 0));
        question.answers = [...sortedAnswers];
        // Mettre en cache
        if (question.id) {
          this.answersCache.set(question.id, sortedAnswers);
          this.loadingAnswers.delete(question.id);
        }
        this.cdr.detectChanges();
      });
    } else if (answersToLoad.length > 0) {
      const sorted = answersToLoad.sort((a, b) => (a.orderIndex || 0) - (b.orderIndex || 0));
      question.answers = [...sorted];
      if (question.id) {
        this.answersCache.set(question.id, sorted);
        this.loadingAnswers.delete(question.id);
      }
      this.cdr.detectChanges();
    } else if (question.id) {
      this.loadingAnswers.delete(question.id);
    }
  }

  loadCourses() {
    this.apiService.getCourses().subscribe({
      next: (courses) => {
        this.courses = courses;
      },
      error: (err) => {
        console.error('Error loading courses:', err);
      }
    });
  }

  createTest() {
    this.editingTest = null;
    this.testForm = {
      title: '',
      description: '',
      passingScore: 70,
      timeLimit: 30,
      isActive: true,
      questions: []
    };
    this.showTestModal = true;
  }

  editTest(test: PlacementTest) {
    this.editingTest = test;
    this.testForm = { ...test };
    this.showTestModal = true;
  }

  saveTest() {
    if (!this.testForm.course || !this.testForm.title) {
      this.error = 'Veuillez remplir tous les champs obligatoires';
      return;
    }

    this.loading = true;
    const testData: any = {
      ...this.testForm,
      course: `/api/courses/${this.testForm.course.id}`
    };

    const operation = this.editingTest
      ? this.apiService.updatePlacementTest(this.editingTest.id!, testData)
      : this.apiService.createPlacementTest(testData);

    operation.subscribe({
      next: () => {
        this.successMessage = `Test ${this.editingTest ? 'modifié' : 'créé'} avec succès`;
        this.loadTests();
        this.closeTestModal();
        setTimeout(() => this.successMessage = null, 3000);
      },
      error: (err) => {
        console.error('Error saving test:', err);
        this.error = 'Erreur lors de l\'enregistrement du test';
        this.loading = false;
      }
    });
  }

  deleteTest(test: PlacementTest) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer le test "${test.title}" ?`)) {
      return;
    }

    if (!test.id) return;

    this.loading = true;
    this.apiService.deletePlacementTest(test.id).subscribe({
      next: () => {
        this.loadTests();
        this.successMessage = 'Test supprimé avec succès';
        setTimeout(() => this.successMessage = null, 3000);
      },
      error: (err) => {
        console.error('Error deleting test:', err);
        this.error = 'Erreur lors de la suppression';
        this.loading = false;
      }
    });
  }

  // Gestion des questions
  addQuestion(test: PlacementTest) {
    this.editingQuestion = null;
    this.questionForm = {
      question: '',
      explanation: '',
      orderIndex: (test.questions?.length || 0),
      answers: []
    };
    this.selectedTest = test;
    this.showQuestionModal = true;
  }

  editQuestion(question: PlacementQuestion) {
    this.editingQuestion = question;
    this.questionForm = { ...question };
    this.showQuestionModal = true;
  }

  saveQuestion() {
    if (!this.selectedTest || !this.questionForm.question) {
      this.error = 'Veuillez remplir la question';
      return;
    }

    this.loading = true;
    const questionData: any = {
      ...this.questionForm,
      placementTest: `/api/placement_tests/${this.selectedTest.id}`
    };

    const operation = this.editingQuestion
      ? this.apiService.updatePlacementQuestion(this.editingQuestion.id!, questionData)
      : this.apiService.createPlacementQuestion(questionData);

    operation.subscribe({
      next: () => {
        this.successMessage = `Question ${this.editingQuestion ? 'modifiée' : 'créée'} avec succès`;
        this.loadTests();
        this.closeQuestionModal();
        setTimeout(() => this.successMessage = null, 3000);
      },
      error: (err) => {
        console.error('Error saving question:', err);
        this.error = 'Erreur lors de l\'enregistrement de la question';
        this.loading = false;
      }
    });
  }

  deleteQuestion(question: PlacementQuestion) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette question ?')) {
      return;
    }

    if (!question.id) return;

    this.loading = true;
    this.apiService.deletePlacementQuestion(question.id).subscribe({
      next: () => {
        this.loadTests();
        this.successMessage = 'Question supprimée avec succès';
        setTimeout(() => this.successMessage = null, 3000);
      },
      error: (err) => {
        console.error('Error deleting question:', err);
        this.error = 'Erreur lors de la suppression';
        this.loading = false;
      }
    });
  }

  // Gestion des réponses
  addAnswer(question: PlacementQuestion) {
    this.editingAnswer = null;
    this.answerForm = {
      text: '',
      score: 0,
      isCorrect: false,
      orderIndex: (question.answers?.length || 0)
    };
    this.editingQuestion = question;
    this.showAnswerModal = true;
  }

  editAnswer(answer: PlacementAnswer | string) {
    // Si answer est une IRI (string), la charger d'abord
    if (typeof answer === 'string' && answer.startsWith('/api/')) {
      const answerId = parseInt(answer.split('/').pop() || '0');
      if (answerId) {
        this.loading = true;
        this.apiService.getPlacementAnswer(answerId).subscribe({
          next: (loadedAnswer) => {
            this.loading = false;
            this.editAnswer(loadedAnswer);
          },
          error: (err) => {
            console.error('Error loading answer:', err);
            this.error = 'Erreur lors du chargement de la réponse';
            this.loading = false;
          }
        });
        return;
      }
    }
    
    // Vérifier que c'est un objet avec un ID
    if (!answer || typeof answer !== 'object' || !(answer as any).id) {
      console.error('Cannot edit answer: invalid format', answer);
      this.error = 'Impossible de modifier: format invalide';
      return;
    }
    
    const answerObj = answer as PlacementAnswer;
    
    this.editingAnswer = answerObj;
    this.answerForm = { 
      text: answerObj.text || '',
      score: typeof answerObj.score === 'string' ? parseFloat(answerObj.score) : (answerObj.score || 0),
      isCorrect: answerObj.isCorrect || false,
      orderIndex: answerObj.orderIndex || 0
    };
    
    // Trouver la question parente dans les tests chargés
    if (!this.editingQuestion) {
      for (const test of this.tests) {
        if (test.questions) {
          const question = test.questions.find(q => 
            q.answers && q.answers.some(a => {
              if (typeof a === 'string') {
                const str = a as string;
                return str.includes(`/${answerObj.id}`);
              }
              return (a as any).id === answerObj.id;
            })
          );
          if (question) {
            this.editingQuestion = question;
            break;
          }
        }
      }
    }
    
    console.log('Editing answer:', answerObj);
    console.log('Answer ID:', answerObj.id);
    console.log('Editing question:', this.editingQuestion);
    
    this.showAnswerModal = true;
  }

  saveAnswer() {
    // Réinitialiser l'erreur et le message de succès
    this.error = null;
    this.successMessage = null;
    
    console.log('saveAnswer called');
    console.log('answerForm:', this.answerForm);
    console.log('answerForm.text:', this.answerForm.text);
    console.log('editingQuestion:', this.editingQuestion);
    
    if (!this.editingQuestion) {
      this.error = 'Question invalide';
      console.error('No editing question');
      return;
    }
    
    // Vérifier que le texte est rempli (même avec des espaces)
    const textValue = this.answerForm.text || '';
    if (!textValue || (typeof textValue === 'string' && !textValue.trim())) {
      this.error = 'Veuillez remplir la réponse';
      console.error('Answer text is empty:', textValue);
      return;
    }

    if (!this.editingQuestion.id) {
      this.error = 'Question invalide';
      return;
    }

    // Vérifier si on modifie ou crée
    const isEdit = !!(this.editingAnswer && this.editingAnswer.id);
    
    if (isEdit && (!this.editingAnswer || !this.editingAnswer.id)) {
      this.error = 'ID de réponse manquant pour la modification';
      return;
    }

    this.loading = true;
    this.error = null;
    
    // Préparer les données avec le format correct pour l'API
    const answerData: any = {
      text: typeof textValue === 'string' ? textValue.trim() : '',
      score: (this.answerForm.score || 0).toString(),
      isCorrect: this.answerForm.isCorrect || false,
      orderIndex: this.answerForm.orderIndex || 0,
      question: `/api/placement_questions/${this.editingQuestion.id}`
    };

    console.log('Saving answer:', answerData);
    console.log('Is edit:', isEdit);
    console.log('Answer ID:', this.editingAnswer?.id);
    console.log('Question ID:', this.editingQuestion.id);

    const operation = isEdit
      ? this.apiService.updatePlacementAnswer(this.editingAnswer!.id!, answerData)
      : this.apiService.createPlacementAnswer(answerData);

    operation.subscribe({
      next: (response) => {
        console.log('Answer saved successfully:', response);
        this.successMessage = `Réponse ${isEdit ? 'modifiée' : 'créée'} avec succès`;
        this.loadTests();
        this.closeAnswerModal();
        setTimeout(() => this.successMessage = null, 3000);
        this.loading = false;
      },
      error: (err) => {
        console.error('Error saving answer:', err);
        console.error('Error status:', err.status);
        console.error('Error details:', err.error);
        const errorMessage = err.error?.message || err.error?.hydra?.description || err.message || 'Erreur inconnue';
        this.error = `Erreur lors de l'enregistrement: ${errorMessage}`;
        this.loading = false;
      }
    });
  }

  deleteAnswer(answer: PlacementAnswer) {
    if (!answer.id) {
      console.error('Cannot delete answer: no ID');
      this.error = 'Impossible de supprimer: ID manquant';
      return;
    }

    if (!confirm('Êtes-vous sûr de vouloir supprimer cette réponse ?')) {
      return;
    }

    this.loading = true;
    this.error = null;
    console.log('Deleting answer ID:', answer.id);
    
    this.apiService.deletePlacementAnswer(answer.id).subscribe({
      next: () => {
        console.log('Answer deleted successfully');
        this.loadTests();
        this.successMessage = 'Réponse supprimée avec succès';
        setTimeout(() => this.successMessage = null, 3000);
        this.loading = false;
      },
      error: (err) => {
        console.error('Error deleting answer:', err);
        this.error = `Erreur lors de la suppression: ${err.message || 'Erreur inconnue'}`;
        this.loading = false;
      }
    });
  }

  closeTestModal() {
    this.showTestModal = false;
    this.editingTest = null;
    this.testForm = {
      title: '',
      description: '',
      passingScore: 70,
      timeLimit: 30,
      isActive: true,
      questions: []
    };
  }

  closeQuestionModal() {
    this.showQuestionModal = false;
    this.editingQuestion = null;
    this.selectedTest = null;
    this.questionForm = {
      question: '',
      explanation: '',
      orderIndex: 0,
      answers: []
    };
  }

  closeAnswerModal() {
    this.showAnswerModal = false;
    this.editingAnswer = null;
    // Ne pas réinitialiser editingQuestion car on peut vouloir ajouter plusieurs réponses
    this.answerForm = {
      text: '',
      score: 0,
      isCorrect: false,
      orderIndex: 0
    };
    // Réinitialiser les erreurs et messages
    this.error = null;
    this.successMessage = null;
  }

  getAnswerLabel(index: number): string {
    return String.fromCharCode(65 + index);
  }

  getQuestionAnswers(question: PlacementQuestion): PlacementAnswer[] {
    if (!question.id || !question.answers || question.answers.length === 0) {
      return [];
    }
    
    // Vérifier le cache
    if (this.answersCache.has(question.id)) {
      return this.answersCache.get(question.id)!;
    }
    
    // Filtrer pour ne retourner que les objets complets (pas les IRI)
    const completeAnswers = question.answers.filter(a => 
      typeof a === 'object' && (a as any).id && (a as any).text !== undefined
    ) as PlacementAnswer[];
    
    // Si on a des objets complets, les mettre en cache et retourner
    if (completeAnswers.length > 0) {
      const sorted = completeAnswers.sort((a, b) => (a.orderIndex || 0) - (b.orderIndex || 0));
      this.answersCache.set(question.id, sorted);
      return sorted;
    }
    
    // Si on a des IRI et qu'on n'est pas déjà en train de charger
    const iriAnswers = question.answers.filter(a => 
      typeof a === 'string' && (a as string).startsWith('/api/')
    );
    
    if (iriAnswers.length > 0 && !this.loadingAnswers.has(question.id)) {
      // Charger les réponses si elles ne sont pas encore chargées
      this.loadingAnswers.add(question.id);
      this.loadAnswersForQuestion(question);
      // Retourner un tableau vide temporairement
      return [];
    }
    
    return [];
  }

  trackByAnswerId(index: number, answer: PlacementAnswer): any {
    return answer.id || index;
  }
}
