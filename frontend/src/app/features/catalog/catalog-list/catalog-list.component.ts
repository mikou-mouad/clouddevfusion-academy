import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService, Course } from '../../../core/services/api.service';

@Component({
  selector: 'app-catalog-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './catalog-list.component.html',
  styleUrls: ['./catalog-list.component.scss']
})
export class CatalogListComponent implements OnInit {
  searchQuery: string = '';
  sortBy: string = 'default';
  loading = false;
  error: string | null = null;
  
  filters = {
    role: '',
    product: '',
    level: '',
    format: '',
    language: '',
    priceMin: '',
    priceMax: '',
    dateFrom: '',
    dateTo: ''
  };

  courses: Course[] = [];

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadCourses();
  }

  loadCourses() {
    this.loading = true;
    this.error = null;
    this.apiService.getCourses().subscribe({
      next: (data) => {
        this.courses = data;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading courses:', err);
        this.error = 'Erreur lors du chargement des formations';
        this.loading = false;
      }
    });
  }

  get filteredCourses(): Course[] {
    let filtered = [...this.courses];

    // Recherche textuelle
    if (this.searchQuery.trim()) {
      const query = this.searchQuery.toLowerCase().trim();
      filtered = filtered.filter(c => 
        c.title.toLowerCase().includes(query) ||
        c.code.toLowerCase().includes(query) ||
        (c.description && c.description.toLowerCase().includes(query))
      );
    }

    // Filtres
    if (this.filters.role) {
      filtered = filtered.filter(c => c.role === this.filters.role);
    }

    if (this.filters.product) {
      filtered = filtered.filter(c => c.product === this.filters.product);
    }

    if (this.filters.level) {
      const levelMap: { [key: string]: string } = {
        'beginner': 'Débutant',
        'intermediate': 'Intermédiaire',
        'advanced': 'Avancé'
      };
      filtered = filtered.filter(c => c.level === levelMap[this.filters.level]);
    }

    if (this.filters.format) {
      const formatMap: { [key: string]: string } = {
        'online': 'En ligne',
        'onsite': 'Présentiel',
        'hybrid': 'Hybride'
      };
      filtered = filtered.filter(c => c.format === formatMap[this.filters.format]);
    }

    if (this.filters.language) {
      filtered = filtered.filter(c => c.language === this.filters.language);
    }

    if (this.filters.priceMin) {
      // Convertir le prix en nombre si c'est une chaîne
      filtered = filtered.filter(c => {
        const price = typeof c.price === 'string' ? parseFloat(c.price) : c.price;
        return price >= Number(this.filters.priceMin);
      });
    }

    if (this.filters.priceMax) {
      // Convertir le prix en nombre si c'est une chaîne
      filtered = filtered.filter(c => {
        const price = typeof c.price === 'string' ? parseFloat(c.price) : c.price;
        return price <= Number(this.filters.priceMax);
      });
    }

    if (this.filters.dateFrom) {
      filtered = filtered.filter(c => {
        if (!c.nextDate) return false;
        const courseDate = typeof c.nextDate === 'string' ? c.nextDate.split('T')[0] : c.nextDate;
        return courseDate >= this.filters.dateFrom;
      });
    }

    if (this.filters.dateTo) {
      filtered = filtered.filter(c => {
        if (!c.nextDate) return false;
        const courseDate = typeof c.nextDate === 'string' ? c.nextDate.split('T')[0] : c.nextDate;
        return courseDate <= this.filters.dateTo;
      });
    }

    // Tri
    filtered = this.sortCourses(filtered);

    return filtered;
  }

  sortCourses(courses: Course[]): Course[] {
    const sorted = [...courses];
    
    switch (this.sortBy) {
      case 'price-asc':
        return sorted.sort((a, b) => {
          const priceA = typeof a.price === 'string' ? parseFloat(a.price) : a.price;
          const priceB = typeof b.price === 'string' ? parseFloat(b.price) : b.price;
          return priceA - priceB;
        });
      case 'price-desc':
        return sorted.sort((a, b) => {
          const priceA = typeof a.price === 'string' ? parseFloat(a.price) : a.price;
          const priceB = typeof b.price === 'string' ? parseFloat(b.price) : b.price;
          return priceB - priceA;
        });
      case 'date-asc':
        return sorted.sort((a, b) => {
          const dateA = a.nextDate ? (typeof a.nextDate === 'string' ? a.nextDate.split('T')[0] : a.nextDate) : '';
          const dateB = b.nextDate ? (typeof b.nextDate === 'string' ? b.nextDate.split('T')[0] : b.nextDate) : '';
          return dateA.localeCompare(dateB);
        });
      case 'date-desc':
        return sorted.sort((a, b) => {
          const dateA = a.nextDate ? (typeof a.nextDate === 'string' ? a.nextDate.split('T')[0] : a.nextDate) : '';
          const dateB = b.nextDate ? (typeof b.nextDate === 'string' ? b.nextDate.split('T')[0] : b.nextDate) : '';
          return dateB.localeCompare(dateA);
        });
      case 'popular':
        return sorted.sort((a, b) => {
          if (a.popular && !b.popular) return -1;
          if (!a.popular && b.popular) return 1;
          return 0;
        });
      case 'title-asc':
        return sorted.sort((a, b) => a.title.localeCompare(b.title));
      default:
        return sorted;
    }
  }

  applyFilters() {
    // Les filtres sont appliqués automatiquement via le getter filteredCourses
  }

  resetFilters() {
    this.searchQuery = '';
    this.sortBy = 'default';
    this.filters = {
      role: '',
      product: '',
      level: '',
      format: '',
      language: '',
      priceMin: '',
      priceMax: '',
      dateFrom: '',
      dateTo: ''
    };
  }

  getActiveFiltersCount(): number {
    let count = 0;
    if (this.searchQuery.trim()) count++;
    if (this.filters.role) count++;
    if (this.filters.product) count++;
    if (this.filters.level) count++;
    if (this.filters.format) count++;
    if (this.filters.language) count++;
    if (this.filters.priceMin) count++;
    if (this.filters.priceMax) count++;
    if (this.filters.dateFrom) count++;
    if (this.filters.dateTo) count++;
    return count;
  }

  getCoursePrice(course: Course): number {
    return typeof course.price === 'string' ? parseFloat(course.price) : course.price;
  }
}

