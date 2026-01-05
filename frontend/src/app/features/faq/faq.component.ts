import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService, Faq } from '../../core/services/api.service';

@Component({
  selector: 'app-faq',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './faq.component.html',
  styleUrls: ['./faq.component.scss']
})
export class FaqComponent implements OnInit {
  selectedCategory = 'Tous';
  loading = false;
  error: string | null = null;

  categories: string[] = ['Tous'];
  faqs: (Faq & { open?: boolean })[] = [];

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadFaqs();
  }

  loadFaqs() {
    this.loading = true;
    this.error = null;
    this.apiService.getFaqs().subscribe({
      next: (data) => {
        // Filtrer uniquement les FAQs publiées
        const publishedFaqs = (Array.isArray(data) ? data : [])
          .filter(faq => faq.published !== false);
        
        // Extraire les catégories uniques depuis les FAQs
        const uniqueCategories = [...new Set(publishedFaqs.map(faq => faq.category).filter(Boolean))];
        this.categories = ['Tous', ...uniqueCategories.sort()];
        
        // Mapper les FAQs avec l'état open
        this.faqs = publishedFaqs.map(faq => ({ ...faq, open: false }));
        
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
        this.error = null;
      },
      error: (err) => {
        console.error('Error loading FAQs:', err);
        this.error = 'Erreur lors du chargement des FAQs';
        this.loading = false;
        // Fallback sur données statiques si l'API n'est pas disponible
        this.faqs = [
          {
            id: 1,
            category: 'Prérequis',
            question: 'Quels sont les prérequis pour suivre une formation ?',
            answer: 'Les prérequis varient selon la formation. Consultez la page détaillée de chaque formation pour connaître les prérequis spécifiques.',
            open: false,
            published: true
          },
          {
            id: 2,
            category: 'Financement',
            question: 'Puis-je financer ma formation avec le CPF ?',
            answer: 'Oui, la plupart de nos formations sont éligibles au financement CPF. Vous pouvez également utiliser d\'autres dispositifs comme OPCO.',
            open: false,
            published: true
          },
          {
            id: 3,
            category: 'Formats',
            question: 'Quels formats de formation proposez-vous ?',
            answer: 'Nous proposons des formations en présentiel, en distanciel (en ligne) et en format hybride (mixte).',
            open: false,
            published: true
          }
        ];
        this.categories = ['Tous', 'Prérequis', 'Formats', 'Financement'];
      }
    });
  }

  get filteredFaqs() {
    if (this.selectedCategory === 'Tous') {
      return this.faqs;
    }
    return this.faqs.filter(faq => faq.category === this.selectedCategory);
  }

  toggleFaq(faq: any) {
    faq.open = !faq.open;
  }
}

