import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService, Faq } from '../../../core/services/api.service';

@Component({
    selector: 'app-admin-faq',
    imports: [CommonModule, FormsModule],
    templateUrl: './faq.component.html',
    styleUrls: ['./faq.component.scss']
})
export class AdminFaqComponent implements OnInit {
  faqs: Faq[] = [];
  showModal = false;
  editingFaq: Faq | null = null;
  formData: Faq = {
    question: '',
    answer: '',
    category: 'Tous',
    orderIndex: 0,
    published: true
  };
  loading = false;
  error: string | null = null;
  successMessage: string | null = null;

  categories = ['Tous', 'Prérequis', 'Formats', 'Financement', 'Certifications', 'Inscription', 'Labs', 'Examens'];

  get faqsList(): Faq[] {
    return Array.isArray(this.faqs) ? this.faqs : [];
  }

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadFaqs();
  }

  loadFaqs() {
    this.loading = true;
    this.error = null;
    this.apiService.getFaqs().subscribe({
      next: (data) => {
        this.faqs = Array.isArray(data) ? data : [];
        // Trier par orderIndex puis par ID
        this.faqs.sort((a, b) => {
          const orderA = a.orderIndex ?? 999;
          const orderB = b.orderIndex ?? 999;
          if (orderA !== orderB) {
            return orderA - orderB;
          }
          return (a.id ?? 0) - (b.id ?? 0);
        });
        this.loading = false;
        this.error = null; // S'assurer que l'erreur est réinitialisée en cas de succès
      },
      error: (err) => {
        console.error('Error loading FAQs:', err);
        // Ne pas afficher d'erreur si c'est juste un tableau vide
        if (err.status === 0 || err.status >= 400) {
          this.error = 'Erreur lors du chargement des FAQs. Vérifiez que le serveur backend est démarré.';
        } else {
          this.error = null; // Pas d'erreur si c'est juste une réponse vide
        }
        this.loading = false;
        // Fallback sur localStorage si l'API n'est pas disponible
        const saved = localStorage.getItem('faqs');
        if (saved) {
          try {
            const parsed = JSON.parse(saved);
            this.faqs = Array.isArray(parsed) ? parsed : [];
          } catch (e) {
            this.faqs = [];
          }
        } else {
          this.faqs = [];
        }
      }
    });
  }

  openAddModal() {
    this.editingFaq = null;
    this.error = null;
    this.successMessage = null;
    this.formData = {
      question: '',
      answer: '',
      category: 'Tous',
      orderIndex: this.faqs.length,
      published: true
    };
    this.showModal = true;
  }

  editFaq(index: number) {
    this.editingFaq = this.faqs[index];
    this.formData = { ...this.faqs[index] };
    this.error = null;
    this.successMessage = null;
    this.showModal = true;
  }

  deleteFaq(index: number) {
    const faq = this.faqs[index];
    if (!faq.id) {
      alert('Impossible de supprimer cette FAQ');
      return;
    }

    if (confirm(`Êtes-vous sûr de vouloir supprimer cette FAQ : "${faq.question}" ?`)) {
      this.loading = true;
      this.apiService.deleteFaq(faq.id).subscribe({
        next: () => {
          this.faqs.splice(index, 1);
          this.loading = false;
        },
        error: (err) => {
          console.error('Error deleting FAQ:', err);
          alert('Erreur lors de la suppression');
          this.loading = false;
        }
      });
    }
  }

  saveFaq() {
    // Validation côté client
    if (!this.formData.question || this.formData.question.trim().length < 5) {
      this.error = 'La question doit contenir au moins 5 caractères';
      return;
    }
    if (!this.formData.answer || this.formData.answer.trim().length < 10) {
      this.error = 'La réponse doit contenir au moins 10 caractères';
      return;
    }
    if (!this.formData.category) {
      this.error = 'La catégorie est requise';
      return;
    }

    this.loading = true;
    this.error = null;
    this.successMessage = null;

    const faqData: Faq = {
      question: this.formData.question.trim(),
      answer: this.formData.answer.trim(),
      category: this.formData.category,
      orderIndex: this.formData.orderIndex ?? 0,
      published: this.formData.published ?? true
    };

    const operation = this.editingFaq?.id
      ? this.apiService.updateFaq(this.editingFaq.id, faqData)
      : this.apiService.createFaq(faqData);

    operation.subscribe({
      next: (saved) => {
        if (this.editingFaq?.id) {
          const index = this.faqs.findIndex(f => f.id === this.editingFaq?.id);
          if (index !== -1) {
            this.faqs[index] = saved;
          }
        } else {
          this.faqs.push(saved);
        }
        // Re-trier après ajout/modification
        this.faqs.sort((a, b) => {
          const orderA = a.orderIndex ?? 999;
          const orderB = b.orderIndex ?? 999;
          if (orderA !== orderB) {
            return orderA - orderB;
          }
          return (a.id ?? 0) - (b.id ?? 0);
        });
        this.loading = false;
        this.successMessage = this.editingFaq?.id ? 'FAQ modifiée avec succès' : 'FAQ créée avec succès';
        setTimeout(() => {
          this.closeModal();
          this.successMessage = null;
        }, 1000);
      },
      error: (err) => {
        console.error('Error saving FAQ:', err);
        
        let errorMessage = 'Erreur lors de l\'enregistrement';
        if (err.error) {
          if (err.error.violations && Array.isArray(err.error.violations)) {
            const violations = err.error.violations.map((v: any) => v.message).join(', ');
            errorMessage = `Erreurs de validation : ${violations}`;
          } else if (err.error['hydra:description']) {
            errorMessage = err.error['hydra:description'];
          } else if (err.error.message) {
            errorMessage = err.error.message;
          } else if (typeof err.error === 'string') {
            errorMessage = err.error;
          }
        }
        
        this.error = errorMessage;
        this.loading = false;
      }
    });
  }

  closeModal() {
    this.showModal = false;
    this.editingFaq = null;
    this.error = null;
    this.successMessage = null;
  }

  togglePublished(faq: Faq) {
    if (!faq.id) return;
    
    const updatedFaq = { ...faq, published: !faq.published };
    this.loading = true;
    this.apiService.updateFaq(faq.id, updatedFaq).subscribe({
      next: (saved) => {
        const index = this.faqs.findIndex(f => f.id === faq.id);
        if (index !== -1) {
          this.faqs[index] = saved;
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Error updating FAQ:', err);
        alert('Erreur lors de la modification');
        this.loading = false;
      }
    });
  }
}
