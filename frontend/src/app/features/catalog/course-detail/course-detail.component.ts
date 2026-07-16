import { Component, OnInit } from '@angular/core';

import { RouterLink, ActivatedRoute } from '@angular/router';
import { ApiService, Course } from '../../../core/services/api.service';

@Component({
    selector: 'app-course-detail',
    imports: [RouterLink],
    templateUrl: './course-detail.component.html',
    styleUrls: ['./course-detail.component.scss']
})
export class CourseDetailComponent implements OnInit {
  course: Course | null = null;
  loading = false;
  error: string | null = null;
  hasPlacementTest = false;

  constructor(
    private route: ActivatedRoute,
    private apiService: ApiService
  ) {}

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

