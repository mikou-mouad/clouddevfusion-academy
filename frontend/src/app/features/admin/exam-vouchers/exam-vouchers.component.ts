import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService, ExamVoucher } from '../../../core/services/api.service';

@Component({
    selector: 'app-admin-exam-vouchers',
    imports: [CommonModule, FormsModule],
    templateUrl: './exam-vouchers.component.html',
    styleUrls: ['./exam-vouchers.component.scss']
})
export class AdminExamVouchersComponent implements OnInit {
  vouchers: ExamVoucher[] = [];
  showModal = false;
  editingVoucher: ExamVoucher | null = null;
  formData: ExamVoucher = {
    code: '',
    examCode: '',
    type: 'voucher-only',
    price: '0',
    validityPeriod: 365,
    description: '',
    bookingSteps: [],
    rescheduleRules: '',
    redemptionInfo: '',
    scheduleLocation: '',
    idRequirements: '',
    isActive: true
  };
  loading = false;
  error: string | null = null;
  
  // Filtres
  filterExamCode = '';
  filterType = '';
  filterActive: boolean | null = null;

  // Nouveau step pour bookingSteps
  newBookingStep = '';

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadVouchers();
  }

  loadVouchers() {
    this.loading = true;
    this.error = null;
    this.apiService.getExamVouchers().subscribe({
      next: (data) => {
        this.vouchers = Array.isArray(data) ? data : [];
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
      if (this.filterActive !== null && v.isActive !== this.filterActive) {
        return false;
      }
      return true;
    });
  }

  openAddModal() {
    this.editingVoucher = null;
    this.error = null;
    this.formData = {
      code: '',
      examCode: '',
      type: 'voucher-only',
      price: '0',
      validityPeriod: 365,
      description: '',
      bookingSteps: [],
      rescheduleRules: '',
      redemptionInfo: '',
      scheduleLocation: '',
      idRequirements: '',
      isActive: true
    };
    this.newBookingStep = '';
    this.showModal = true;
  }

  editVoucher(index: number) {
    this.editingVoucher = this.filteredVouchers[index];
    this.formData = { ...this.filteredVouchers[index] };
    this.newBookingStep = '';
    this.showModal = true;
  }

  addBookingStep() {
    if (this.newBookingStep.trim()) {
      if (!this.formData.bookingSteps) {
        this.formData.bookingSteps = [];
      }
      this.formData.bookingSteps.push(this.newBookingStep.trim());
      this.newBookingStep = '';
    }
  }

  removeBookingStep(index: number) {
    if (this.formData.bookingSteps) {
      this.formData.bookingSteps.splice(index, 1);
    }
  }

  saveVoucher() {
    // Validation
    if (!this.formData.code || !this.formData.examCode || !this.formData.price) {
      this.error = 'Le code, l\'examen et le prix sont requis';
      return;
    }

    if (parseFloat(this.formData.price) < 0) {
      this.error = 'Le prix doit être positif';
      return;
    }

    if (this.formData.validityPeriod < 1) {
      this.error = 'La période de validité doit être d\'au moins 1 jour';
      return;
    }

    this.loading = true;
    this.error = null;

    const voucherData: ExamVoucher = {
      code: this.formData.code.trim(),
      examCode: this.formData.examCode.trim(),
      type: this.formData.type,
      price: this.formData.price,
      validityPeriod: this.formData.validityPeriod,
      description: this.formData.description?.trim() || '',
      bookingSteps: this.formData.bookingSteps || [],
      rescheduleRules: this.formData.rescheduleRules?.trim() || '',
      redemptionInfo: this.formData.redemptionInfo?.trim() || '',
      scheduleLocation: this.formData.scheduleLocation?.trim() || '',
      idRequirements: this.formData.idRequirements?.trim() || '',
      isActive: this.formData.isActive
    };

    const operation = this.editingVoucher?.id
      ? this.apiService.updateExamVoucher(this.editingVoucher.id, voucherData)
      : this.apiService.createExamVoucher(voucherData);

    operation.subscribe({
      next: (saved) => {
        if (this.editingVoucher?.id) {
          const index = this.vouchers.findIndex(v => v.id === this.editingVoucher?.id);
          if (index !== -1) {
            this.vouchers[index] = saved;
          }
        } else {
          this.vouchers.push(saved);
        }
        this.closeModal();
        this.loading = false;
      },
      error: (err) => {
        console.error('Error saving voucher:', err);
        this.error = err.error?.detail || err.error?.message || 'Erreur lors de l\'enregistrement';
        this.loading = false;
      }
    });
  }

  deleteVoucher(index: number) {
    const voucher = this.filteredVouchers[index];
    if (!voucher.id) {
      alert('Impossible de supprimer ce bon d\'examen');
      return;
    }

    if (confirm('Êtes-vous sûr de vouloir supprimer ce bon d\'examen ?')) {
      this.loading = true;
      this.apiService.deleteExamVoucher(voucher.id).subscribe({
        next: () => {
          this.vouchers = this.vouchers.filter(v => v.id !== voucher.id);
          this.loading = false;
        },
        error: (err) => {
          console.error('Error deleting voucher:', err);
          alert('Erreur lors de la suppression');
          this.loading = false;
        }
      });
    }
  }

  closeModal() {
    this.showModal = false;
    this.editingVoucher = null;
    this.error = null;
    this.newBookingStep = '';
  }

  getTypeLabel(type: string): string {
    const labels: { [key: string]: string } = {
      'voucher-only': 'Voucher uniquement',
      'training-voucher': 'Formation + Voucher',
      'retake': 'Retake'
    };
    return labels[type] || type;
  }

  getExamCodes(): string[] {
    return ['AZ-104', 'AZ-900', 'AZ-204', 'AZ-305', 'AZ-400', 'AZ-500'];
  }
}
