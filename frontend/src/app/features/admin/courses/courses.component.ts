import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService, Course, SyllabusModule, Lab } from '../../../core/services/api.service';

@Component({
  selector: 'app-admin-courses',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './courses.component.html',
  styleUrls: ['./courses.component.scss']
})
export class CoursesComponent implements OnInit {
  courses: Course[] = [];
  filteredCourses: Course[] = [];
  paginatedCourses: Course[] = [];
  showModal = false;
  showDetailsModal = false;
  selectedCourse: Course | null = null;
  editingCourse: Course | null = null;
  activeTab: 'basic' | 'details' | 'syllabus' | 'placement' = 'basic';
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
    }
  }

  getPageNumbers(): number[] {
    const maxVisible = 5;
    if (this.totalPages <= maxVisible) {
      return Array.from({ length: this.totalPages }, (_, i) => i + 1);
    }
    let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
    let end = Math.min(this.totalPages, start + maxVisible - 1);
    if (end - start < maxVisible - 1) {
      start = Math.max(1, end - maxVisible + 1);
    }
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
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

  editCourse(index: number, event?: Event) {
    if (event) {
      event.stopPropagation();
      event.preventDefault();
    }
    console.log('Edit course clicked, index:', index, 'course:', this.paginatedCourses[index]);
    const course = this.paginatedCourses[index];
    if (!course) {
      console.error('Course not found at index:', index);
      alert('Formation introuvable');
      return;
    }
    this.editingCourse = course;
    this.activeTab = 'basic';
    // Créer une copie profonde pour éviter les modifications directes
    this.formData = JSON.parse(JSON.stringify(course));
    // S'assurer que les tableaux existent
    this.formData.objectives = this.formData.objectives || [];
    this.formData.outcomes = this.formData.outcomes || [];
    this.formData.prerequisites = this.formData.prerequisites || [];
    this.formData.targetRoles = this.formData.targetRoles || [];
    this.formData.syllabus = this.formData.syllabus || [];
    this.showModal = true;
  }

  deleteCourse(index: number, event?: Event) {
    if (event) {
      event.stopPropagation();
      event.preventDefault();
    }
    console.log('Delete course clicked, index:', index, 'course:', this.paginatedCourses[index]);
    const course = this.paginatedCourses[index];
    if (!course) {
      console.error('Course not found at index:', index);
      alert('Formation introuvable');
      return;
    }
    if (!course.id) {
      alert('Impossible de supprimer cette formation (ID manquant)');
      return;
    }

    if (confirm(`Êtes-vous sûr de vouloir supprimer la formation "${course.title}" ?`)) {
      this.loading = true;
      this.apiService.deleteCourse(course.id).subscribe({
        next: () => {
          console.log('Course deleted successfully');
          const originalIndex = this.courses.findIndex(c => c.id === course.id);
          if (originalIndex !== -1) {
            this.courses.splice(originalIndex, 1);
          }
          this.applyFilters();
          this.updatePagination();
          this.loading = false;
        },
        error: (err) => {
          console.error('Error deleting course:', err);
          alert('Erreur lors de la suppression: ' + (err.error?.message || err.message || 'Erreur inconnue'));
          this.loading = false;
        }
      });
    }
  }

  saveCourse() {
    this.loading = true;
    this.error = null;

    // Préparer les données pour l'API
    const courseData: any = {
      ...this.formData
    };

    // Convertir le prix en nombre si c'est une chaîne
    if (typeof courseData.price === 'string') {
      courseData.price = parseFloat(courseData.price) || 0;
    } else {
      courseData.price = courseData.price || 0;
    }

    // Pour la mise à jour, ne pas inclure l'ID dans les données (il est dans l'URL)
    if (this.editingCourse?.id) {
      delete courseData.id;
      // Ne pas inclure les champs créés automatiquement
      delete courseData.createdAt;
      delete courseData.updatedAt;
    }

    // Formater la date si elle existe
    if (courseData.nextDate) {
      // Si c'est déjà une date ISO, la garder telle quelle
      // Sinon, convertir en format ISO
      if (typeof courseData.nextDate === 'string' && !courseData.nextDate.includes('T')) {
        // Format YYYY-MM-DD -> ISO
        courseData.nextDate = courseData.nextDate + 'T00:00:00+00:00';
      }
    }

    // S'assurer que les tableaux ne sont pas undefined
    courseData.objectives = courseData.objectives || [];
    courseData.outcomes = courseData.outcomes || [];
    courseData.prerequisites = courseData.prerequisites || [];
    courseData.targetRoles = courseData.targetRoles || [];
    courseData.syllabus = courseData.syllabus || [];

    // Nettoyer les modules du syllabus (enlever les IDs pour éviter les conflits)
    courseData.syllabus = courseData.syllabus.map((module: any) => {
      const cleanModule: any = {
        title: module.title,
        description: module.description || null,
        labs: (module.labs || []).map((lab: any) => ({
          name: lab.name,
          duration: lab.duration || null
        }))
      };
      return cleanModule;
    });

    console.log('Sending course data:', JSON.stringify(courseData, null, 2));

    const operation = this.editingCourse?.id
      ? this.apiService.updateCourse(this.editingCourse.id, courseData)
      : this.apiService.createCourse(courseData);

    operation.subscribe({
      next: (saved) => {
        console.log('Course saved successfully:', saved);
        this.loading = false;
        this.error = null;
        this.successMessage = this.editingCourse?.id ? 'Formation modifiée avec succès' : 'Formation créée avec succès';
        
        // Recharger la liste pour avoir les données à jour
        this.loadCourses();
        this.applyFilters();
        this.updatePagination();
        
        // Fermer le modal après un court délai pour voir le succès
        setTimeout(() => {
          this.closeModal();
          this.successMessage = null;
        }, 1000);
      },
      error: (err) => {
        console.error('Error saving course:', err);
        console.error('Error status:', err.status);
        console.error('Error details:', err.error);
        
        // Ne traiter comme erreur que si le statut HTTP indique une erreur
        if (err.status && err.status >= 400) {
          // Afficher un message d'erreur plus détaillé
          let errorMessage = 'Erreur lors de l\'enregistrement';
          if (err.error) {
            if (err.error['hydra:description']) {
              errorMessage = err.error['hydra:description'];
            } else if (err.error['violations']) {
              const violations = err.error['violations'].map((v: any) => `${v.propertyPath}: ${v.message}`).join(', ');
              errorMessage = `Erreurs de validation: ${violations}`;
            } else if (err.error.message) {
              errorMessage = err.error.message;
            } else if (typeof err.error === 'string') {
              errorMessage = err.error;
            } else if (err.error.detail) {
              errorMessage = err.error.detail;
            }
          } else if (err.message) {
            errorMessage = err.message;
          }
          
          this.error = errorMessage;
        } else {
          // Si ce n'est pas une vraie erreur HTTP, considérer comme succès
          console.log('Response received, treating as success');
          this.loading = false;
          this.error = null;
          this.loadCourses();
          setTimeout(() => {
            this.closeModal();
          }, 300);
        }
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

  viewCourseDetails(course: Course, event?: Event) {
    if (event) {
      event.stopPropagation();
    }
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

  private saveToLocalStorage() {
    localStorage.setItem('courses', JSON.stringify(this.courses));
  }
}
