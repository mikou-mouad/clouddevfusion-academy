import { Component, OnInit } from '@angular/core';

import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { forkJoin } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';

@Component({
    selector: 'app-admin-dashboard',
    imports: [RouterLink, RouterLinkActive, RouterOutlet],
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit {
  stats = {
    courses: 0,
    trainings: 0,
    contacts: 0,
    blogPosts: 0,
    testimonials: 0,
    faqs: 0
  };
  
  loading = false;
  error: string | null = null;

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadStats();
  }

  loadStats() {
    this.loading = true;
    this.error = null;

    // Charger toutes les statistiques en parallèle
    forkJoin({
      courses: this.apiService.getCourses(),
      contacts: this.apiService.getContacts(),
      blogPosts: this.apiService.getBlogPosts(),
      testimonials: this.apiService.getTestimonials(),
      faqs: this.apiService.getFaqs()
    }).subscribe({
      next: (data) => {
        this.stats.courses = Array.isArray(data.courses) ? data.courses.length : 0;
        this.stats.trainings = this.stats.courses; // Les formations sont les mêmes que les cours pour l'instant
        this.stats.contacts = Array.isArray(data.contacts) ? data.contacts.length : 0;
        this.stats.blogPosts = Array.isArray(data.blogPosts) ? data.blogPosts.length : 0;
        this.stats.testimonials = Array.isArray(data.testimonials) ? data.testimonials.length : 0;
        this.stats.faqs = Array.isArray(data.faqs) ? data.faqs.length : 0;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading dashboard stats:', err);
        this.error = 'Erreur lors du chargement des statistiques';
        this.loading = false;
      }
    });
  }
}

