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

  constructor(
    private route: ActivatedRoute,
    private apiService: ApiService
  ) {}

  ngOnInit() {
    const courseId = this.route.snapshot.paramMap.get('id');
    if (courseId) {
      this.loadCourse(parseInt(courseId, 10));
    } else {
      this.error = 'ID de formation non trouvé';
    }
  }

  loadCourse(id: number) {
    this.loading = true;
    this.error = null;
    this.apiService.getCourse(id).subscribe({
      next: (course) => {
        this.course = course;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading course:', err);
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
}

