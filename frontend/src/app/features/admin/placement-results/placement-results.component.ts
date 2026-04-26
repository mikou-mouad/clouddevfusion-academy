import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService, PlacementTestResult, PlacementTest } from '../../../core/services/api.service';

@Component({
  selector: 'app-admin-placement-results',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './placement-results.component.html',
  styleUrls: ['./placement-results.component.scss']
})
export class PlacementResultsComponent implements OnInit {
  results: PlacementTestResult[] = [];
  loading = false;
  error: string | null = null;

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadResults();
  }

  loadResults() {
    this.loading = true;
    this.error = null;
    this.apiService.getPlacementTestResults().subscribe({
      next: (data) => {
        this.results = Array.isArray(data) ? data : [];
        this.results.sort((a, b) => {
          const dateA = a.completedAt ? new Date(a.completedAt).getTime() : 0;
          const dateB = b.completedAt ? new Date(b.completedAt).getTime() : 0;
          return dateB - dateA;
        });
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading results:', err);
        const status = err?.status;
        if (status === 403 || status === 401) {
          this.error = 'Accès refusé. Vérifiez que vous êtes bien connecté en tant qu\'admin, puis actualisez la page.';
        } else {
          this.error = 'Erreur lors du chargement des résultats';
        }
        this.loading = false;
      }
    });
  }

  getTestTitle(result: PlacementTestResult): string {
    const test = result.placementTest as PlacementTest | string | undefined;
    if (!test) return '–';
    if (typeof test === 'object' && 'title' in test && test.title) return test.title;
    if (typeof test === 'string' && test.includes('/')) {
      const id = test.split('/').pop();
      return id ? `Test #${id}` : '–';
    }
    return `Test #${(test as { id?: number })?.id ?? '?'}`;
  }

  formatDate(dateStr: string | undefined): string {
    if (!dateStr) return '–';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  getScoreDisplay(result: PlacementTestResult): string {
    const score = typeof result.score === 'number' ? result.score : parseFloat(String(result.score));
    return isNaN(score) ? '–' : score.toFixed(1) + '%';
  }
}
