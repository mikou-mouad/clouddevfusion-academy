import { Component, OnInit } from '@angular/core';
import { CommonModule, JsonPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService, AuditLog } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';

@Component({
    selector: 'app-admin-audit-logs',
    imports: [CommonModule, FormsModule, JsonPipe],
    templateUrl: './audit-logs.component.html',
    styleUrls: ['./audit-logs.component.scss']
})
export class AdminAuditLogsComponent implements OnInit {
  auditLogs: AuditLog[] = [];
  filteredLogs: AuditLog[] = [];
  loading = false;
  error: string | null = null;

  // Filtres
  filterUserEmail = '';
  filterEntityType = '';
  filterAction = '';
  limit = 100;

  // Options de filtres
  entityTypes: string[] = ['Course', 'BlogPost', 'Testimonial', 'Faq', 'Contact', 'ExamVoucher', 'HomeBanner', 'User'];
  actions: string[] = ['create', 'update', 'delete', 'login', 'logout'];

  constructor(
    private apiService: ApiService,
    private authService: AuthService
  ) {}

  ngOnInit() {
    // Vérifier que c'est un super admin
    if (!this.authService.isSuperAdmin()) {
      this.error = 'Accès refusé. Seuls les super administrateurs peuvent consulter les logs.';
      return;
    }

    this.loadAuditLogs();
  }

  loadAuditLogs() {
    this.loading = true;
    this.error = null;

    const filters: any = { limit: this.limit };
    if (this.filterUserEmail) filters.userEmail = this.filterUserEmail;
    if (this.filterEntityType) filters.entityType = this.filterEntityType;
    if (this.filterAction) filters.action = this.filterAction;

    this.apiService.getAuditLogs(filters).subscribe({
      next: (logs) => {
        this.auditLogs = logs;
        this.filteredLogs = logs;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading audit logs:', err);
        this.error = 'Erreur lors du chargement des logs';
        this.loading = false;
      }
    });
  }

  applyFilters() {
    this.loadAuditLogs();
  }

  resetFilters() {
    this.filterUserEmail = '';
    this.filterEntityType = '';
    this.filterAction = '';
    this.limit = 100;
    this.loadAuditLogs();
  }

  formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  getActionLabel(action: string): string {
    const labels: { [key: string]: string } = {
      'create': 'Création',
      'update': 'Modification',
      'delete': 'Suppression',
      'login': 'Connexion',
      'logout': 'Déconnexion'
    };
    return labels[action] || action;
  }

  getActionIcon(action: string): string {
    const icons: { [key: string]: string } = {
      'create': 'plus',
      'update': 'edit',
      'delete': 'trash',
      'login': 'log-in',
      'logout': 'log-out'
    };
    return icons[action] || 'activity';
  }

  getActionColor(action: string): string {
    const colors: { [key: string]: string } = {
      'create': 'success',
      'update': 'warning',
      'delete': 'danger',
      'login': 'info',
      'logout': 'secondary'
    };
    return colors[action] || 'default';
  }

  hasChanges(changes: any): boolean {
    if (!changes) return false;
    if (typeof changes !== 'object') return false;
    return Object.keys(changes).length > 0;
  }
}
