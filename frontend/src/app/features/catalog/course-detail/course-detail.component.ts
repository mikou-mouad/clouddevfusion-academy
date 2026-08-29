import { Component, OnInit } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { ApiService, Course } from '../../../core/services/api.service';

@Component({
    selector: 'app-course-detail',
    imports: [FormsModule, RouterLink],
    templateUrl: './course-detail.component.html',
    styleUrls: ['./course-detail.component.scss']
})
export class CourseDetailComponent implements OnInit {
  course: Course | null = null;
  loading = false;
  error: string | null = null;
  hasPlacementTest = false;
  leadAction: 'rdv' | 'cpf' | null = null;
  leadLoading = false;
  leadError: string | null = null;
  leadData = {
    firstName: '',
    lastName: '',
    phone: '',
    email: ''
  };

  constructor(
    private route: ActivatedRoute,
    private apiService: ApiService,
    private router: Router
  ) {}

  openLeadForm(action: 'rdv' | 'cpf'): void {
    if (action === 'rdv') {
      this.router.navigate(['/contact']);
      return;
    }

    this.leadAction = action;
    this.leadError = null;
  }

  closeLeadForm(): void {
    if (!this.leadLoading) {
      this.leadAction = null;
      this.leadError = null;
    }
  }

  submitLead(): void {
    if (!this.course || !this.leadAction) {
      this.leadError = 'Une erreur est survenue. Veuillez réessayer.';
      return;
    }

    const action = this.leadAction;

    if (action === 'rdv') {
      this.router.navigate(['/contact']);
      return;
    }

    this.leadLoading = true;
    this.leadError = null;
    const cpfUrl = this.course.cpfUrl || 'https://www.moncompteformation.gouv.fr';

    window.open(cpfUrl, '_blank', 'noopener,noreferrer');

    this.apiService.createContact({
      name: `${this.leadData.firstName.trim()} ${this.leadData.lastName.trim()}`,
      email: this.leadData.email.trim(),
      phone: this.leadData.phone.trim(),
      subject: 'cpf',
      message: `Inscription avec le CPF pour la formation ${this.course.title} (${this.course.code}).`
    }).subscribe({
      next: () => {
        this.leadLoading = false;
        this.leadAction = null;
      },
      error: (err) => {
        console.error('Error submitting course lead:', err);
        this.leadLoading = false;
        this.leadAction = null;
      }
    });
  }

  ngOnInit() {
    const courseCode = this.route.snapshot.paramMap.get('code');
    if (courseCode) {
      this.loadCourseByCode(courseCode);
    } else {
      this.error = 'Code de formation non trouvé';
    }
  }

  loadCourseByCode(code: string) {
    this.loading = true;
    this.error = null;
    this.hasPlacementTest = false;
    this.apiService.getCourses().subscribe({
      next: (courses) => {
        const normalizedCode = code.trim().toLowerCase();
        let course = courses.find((item) => item.code.trim().toLowerCase() === normalizedCode);

        if (!course && /^[0-9]+$/.test(normalizedCode)) {
          const numericId = Number(normalizedCode);
          course = courses.find((item) => item.id === numericId);
        }

        if (!course) {
          this.error = 'Formation introuvable';
          this.loading = false;
          return;
        }

        this.course = course;
        this.loading = false;
        this.resolvePlacementTestAvailability(course);
      },
      error: (err) => {
        console.error('Error loading courses:', err);
        this.error = 'Erreur lors du chargement de la formation';
        this.loading = false;
      }
    });
  }

  getCoursePrice(course: Course): number {
    return typeof course.price === 'string' ? parseFloat(course.price) : course.price;
  }

  isCpfEligible(course: Course | null | undefined): boolean {
    return !!course && course.cpfEligible === true;
  }

  getFormats(course: Course): string[] {
    // Si format est une chaîne unique, retourner un tableau avec cette valeur
    if (course.format) {
      return [course.format];
    }
    return [];
  }

  formatDate(dateString: string | null | undefined): { day: string, month: string, year: string } | null {
    if (!dateString) return null;
    const date = new Date(dateString);
    const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return {
      day: date.getDate().toString().padStart(2, '0'),
      month: months[date.getMonth()],
      year: date.getFullYear().toString()
    };
  }

  private resolvePlacementTestAvailability(course: Course): void {
    if (course.id == null) {
      this.hasPlacementTest = false;
      return;
    }

    const embedded = course.placementTest;
    const embeddedActive = embedded?.isActive ?? (embedded as { active?: boolean } | undefined)?.active;
    if (embedded?.id && embeddedActive === true) {
      this.hasPlacementTest = true;
      return;
    }
    if (embedded?.id && embeddedActive === false) {
      this.hasPlacementTest = false;
      return;
    }

    this.apiService.getPlacementTestByCourse(course.id).subscribe({
      next: (test) => {
        this.hasPlacementTest = test != null && test.isActive !== false;
      },
      error: () => {
        this.hasPlacementTest = false;
      }
    });
  }
}

