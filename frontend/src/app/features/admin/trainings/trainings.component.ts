import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService, Course, SyllabusModule, Lab } from '../../../core/services/api.service';

@Component({
  selector: 'app-admin-trainings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './trainings.component.html',
  styleUrls: ['./trainings.component.scss']
})
export class TrainingsComponent implements OnInit {
  courses: Course[] = [];
  filteredCourses: Course[] = [];
  paginatedCourses: Course[] = [];
  showModal = false;
  showDetailsModal = false;
  selectedCourse: Course | null = null;
  editingCourse: Course | null = null;
  activeTab: 'basic' | 'details' | 'syllabus' = 'basic';
  loading = false;
  error: string | null = null;
  successMessage: string | null = null;
  searchQuery = '';
  filterLevel = '';
  filterFormat = '';
  
  // Pagination
  currentPage = 1;
  itemsPerPage = 6;
  totalPages = 1;
  
  formData: Course = {
    title: '',
    code: '',
    level: 'Débutant',
    duration: '',
    format: 'Hybride',
    accessDelay: '',
    price: 0,
    role: 'administrator',
    product: 'azure-administrator',
    language: 'fr',
    nextDate: '',
    description: '',
    certification: '',
    popular: false,
    objectives: [],
    outcomes: [],
    prerequisites: [],
    targetRoles: [],
    syllabus: []
  };

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
        this.applyFilters();
        this.updatePagination();
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading courses:', err);
        this.error = 'Erreur lors du chargement des formations';
        this.loading = false;
        // Fallback sur localStorage si l'API n'est pas disponible
        const saved = localStorage.getItem('courses');
        if (saved) {
          this.courses = JSON.parse(saved);
          this.applyFilters();
          this.updatePagination();
        }
      }
    });
  }

  applyFilters() {
    let filtered = [...this.courses];

    // Filtre par recherche
    if (this.searchQuery.trim()) {
      const query = this.searchQuery.toLowerCase().trim();
      filtered = filtered.filter(course =>
        course.title.toLowerCase().includes(query) ||
        course.code.toLowerCase().includes(query) ||
        (course.description && course.description.toLowerCase().includes(query))
      );
    }

    // Filtre par niveau
    if (this.filterLevel) {
      filtered = filtered.filter(course => course.level === this.filterLevel);
    }

    // Filtre par format
    if (this.filterFormat) {
      filtered = filtered.filter(course => course.format === this.filterFormat);
    }

    this.filteredCourses = filtered;
    this.currentPage = 1; // Reset à la page 1 lors d'un nouveau filtre
    this.updatePagination();
  }

  updatePagination() {
    this.totalPages = Math.ceil(this.filteredCourses.length / this.itemsPerPage);
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;
    this.paginatedCourses = this.filteredCourses.slice(startIndex, endIndex);
  }

  goToPage(page: number) {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
      this.updatePagination();
      // Scroll vers le haut de la liste
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  getPageNumbers(): number[] {
    const pages: number[] = [];
    const maxVisible = 5;
    let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
    let end = Math.min(this.totalPages, start + maxVisible - 1);
    
    if (end - start < maxVisible - 1) {
      start = Math.max(1, end - maxVisible + 1);
    }
    
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    return pages;
  }

  openAddModal() {
    this.editingCourse = null;
    this.activeTab = 'basic';
    this.formData = {
      title: '',
      code: '',
      level: 'Débutant',
      duration: '',
      format: 'Hybride',
      accessDelay: '',
      price: 0,
      role: 'administrator',
      product: 'azure-administrator',
      language: 'fr',
      nextDate: '',
      description: '',
      certification: '',
      popular: false,
      objectives: [],
      outcomes: [],
      prerequisites: [],
      targetRoles: [],
      syllabus: []
    };
    this.showModal = true;
  }

  viewCourseDetails(course: Course) {
    this.selectedCourse = course;
    this.showDetailsModal = true;
  }

  closeDetailsModal() {
    this.showDetailsModal = false;
    this.selectedCourse = null;
  }

  getTotalLabs(course: Course): number {
    if (!course.syllabus || course.syllabus.length === 0) return 0;
    return course.syllabus.reduce((total, module) => total + (module.labs?.length || 0), 0);
  }

  editCourse(index: number) {
    this.editingCourse = this.courses[index];
    this.activeTab = 'basic';
    this.formData = JSON.parse(JSON.stringify(this.courses[index]));
    this.showModal = true;
  }

  deleteCourse(index: number) {
    const course = this.courses[index];
    if (!course.id) {
      alert('Impossible de supprimer cette formation');
      return;
    }

    if (confirm('Êtes-vous sûr de vouloir supprimer cette formation ?')) {
      this.loading = true;
      this.apiService.deleteCourse(course.id).subscribe({
      next: () => {
        this.courses.splice(index, 1);
        this.applyFilters();
        this.updatePagination();
        this.loading = false;
      },
        error: (err) => {
          console.error('Error deleting course:', err);
          alert('Erreur lors de la suppression');
          this.loading = false;
        }
      });
    }
  }

  saveCourse() {
    this.loading = true;
    this.error = null;
    this.successMessage = null;

    // Convertir le prix en nombre si c'est une string
    const courseData = {
      ...this.formData,
      price: typeof this.formData.price === 'string' ? parseFloat(this.formData.price) : this.formData.price
    };

    const operation = this.editingCourse?.id
      ? this.apiService.updateCourse(this.editingCourse.id, courseData)
      : this.apiService.createCourse(courseData);

    operation.subscribe({
      next: (saved) => {
        if (this.editingCourse?.id) {
          const index = this.courses.findIndex(c => c.id === this.editingCourse?.id);
          if (index !== -1) {
            this.courses[index] = saved;
          }
        } else {
          this.courses.push(saved);
        }
        this.applyFilters();
        this.updatePagination();
        this.successMessage = this.editingCourse?.id ? 'Formation modifiée avec succès' : 'Formation créée avec succès';
        this.loading = false;
        setTimeout(() => {
          this.closeModal();
        }, 1500);
      },
      error: (err) => {
        console.error('Error saving course:', err);
        this.error = err.error?.message || err.message || 'Erreur lors de l\'enregistrement';
        this.loading = false;
      }
    });
  }

  closeModal() {
    this.showModal = false;
    this.editingCourse = null;
    this.activeTab = 'basic';
    this.error = null;
    this.successMessage = null;
  }

  addItem(type: 'objectives' | 'outcomes' | 'prerequisites' | 'targetRoles') {
    this.formData[type].push('');
  }

  removeItem(type: 'objectives' | 'outcomes' | 'prerequisites' | 'targetRoles', index: number) {
    this.formData[type].splice(index, 1);
  }

  addModule() {
    this.formData.syllabus.push({
      title: '',
      description: '',
      labs: []
    });
  }

  removeModule(index: number) {
    this.formData.syllabus.splice(index, 1);
  }

  addLab(moduleIndex: number) {
    this.formData.syllabus[moduleIndex].labs.push({
      name: '',
      duration: ''
    });
  }

  removeLab(moduleIndex: number, labIndex: number) {
    this.formData.syllabus[moduleIndex].labs.splice(labIndex, 1);
  }
}
