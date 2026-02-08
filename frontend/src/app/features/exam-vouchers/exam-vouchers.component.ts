import { Component, OnInit } from '@angular/core';

import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService, ExamVoucher } from '../../core/services/api.service';

@Component({
    selector: 'app-exam-vouchers',
    imports: [RouterLink, FormsModule],
    templateUrl: './exam-vouchers.component.html',
    styleUrls: ['./exam-vouchers.component.scss']
})
export class ExamVouchersComponent implements OnInit {
  vouchers: ExamVoucher[] = [];
  loading = false;
  error: string | null = null;
  
  // Filtres
  filterExamCode = '';
  filterType = '';
  
  // Modal pour détails
  selectedVoucher: ExamVoucher | null = null;
  showDetailsModal = false;

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadVouchers();
  }

  loadVouchers() {
    this.loading = true;
    this.error = null;
    this.apiService.getExamVouchers().subscribe({
      next: (data) => {
        // Filtrer uniquement les vouchers actifs
        this.vouchers = Array.isArray(data) 
          ? data.filter(v => v && v.isActive) 
          : [];
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading vouchers:', err);
        this.error = 'Erreur lors du chargement des bons d\'examen';
        this.loading = false;
      }
    });
  }

  get filteredVouchers(): ExamVoucher[] {
    return this.vouchers.filter(v => {
      if (this.filterExamCode && !v.examCode.toLowerCase().includes(this.filterExamCode.toLowerCase())) {
        return false;
      }
      if (this.filterType && v.type !== this.filterType) {
        return false;
      }
      return true;
    });
  }

  get vouchersByType() {
    const grouped: { [key: string]: ExamVoucher[] } = {
      'voucher-only': [],
      'training-voucher': [],
      'retake': []
    };
    
    this.filteredVouchers.forEach(v => {
      if (grouped[v.type]) {
        grouped[v.type].push(v);
      }
    });
    
    return grouped;
  }

  openDetailsModal(voucher: ExamVoucher) {
    this.selectedVoucher = voucher;
    this.showDetailsModal = true;
  }

  closeDetailsModal() {
    this.showDetailsModal = false;
    this.selectedVoucher = null;
  }

  getTypeLabel(type: string): string {
    const labels: { [key: string]: string } = {
      'voucher-only': 'Voucher uniquement',
      'training-voucher': 'Formation + Voucher',
      'retake': 'Retake'
    };
    return labels[type] || type;
  }

  getTypeDescription(type: string): string {
    const descriptions: { [key: string]: string } = {
      'voucher-only': 'Accès uniquement à l\'examen de certification Microsoft',
      'training-voucher': 'Formation complète + bon d\'examen inclus',
      'retake': 'Possibilité de repasser l\'examen en cas d\'échec'
    };
    return descriptions[type] || '';
  }

  getTypeIcon(type: string): string {
    const icons: { [key: string]: string } = {
      'voucher-only': 'ticket',
      'training-voucher': 'book-ticket',
      'retake': 'refresh'
    };
    return icons[type] || 'ticket';
  }

  getUniqueExamCodes(): string[] {
    const codes = new Set(this.vouchers.map(v => v.examCode));
    return Array.from(codes).sort();
  }
}
