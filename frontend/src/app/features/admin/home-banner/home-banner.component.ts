import { Component, OnInit } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { ApiService, HomeBanner } from '../../../core/services/api.service';

@Component({
    selector: 'app-admin-home-banner',
    imports: [FormsModule],
    templateUrl: './home-banner.component.html',
    styleUrls: ['./home-banner.component.scss']
})
export class AdminHomeBannerComponent implements OnInit {
  banner: HomeBanner | null = null;
  loading = false;
  error: string | null = null;
  successMessage: string | null = null;
  uploadingLogo = false;
  logoPreview: string | null = null;
  selectedLogoFile: File | null = null;

  formData: HomeBanner = {
    logoPath: 'assets/cdfL.png',
    kpi1Number: '100+',
    kpi1Label: 'Professionnels formés',
    kpi2Number: '98%',
    kpi2Label: 'Taux de réussite',
    kpi3Number: '50+',
    kpi3Label: 'Certifications disponibles',
    active: true
  };

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadBanner();
  }

  loadBanner() {
    this.loading = true;
    this.error = null;
    this.apiService.getActiveHomeBanner().subscribe({
      next: (banner) => {
        if (banner) {
          this.banner = banner;
          this.formData = { ...banner };
          this.logoPreview = banner.logoPath || null;
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading banner:', err);
        this.error = 'Erreur lors du chargement de la bannière';
        this.loading = false;
      }
    });
  }

  onLogoSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      const file = input.files[0];
      
      // Vérifier le type de fichier
      if (!file.type.startsWith('image/')) {
        this.error = 'Veuillez sélectionner un fichier image';
        return;
      }
      
      // Vérifier la taille (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        this.error = 'L\'image est trop volumineuse (max 5MB)';
        return;
      }
      
      this.selectedLogoFile = file;
      
      // Aperçu de l'image
      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.logoPreview = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  }

  uploadLogo(): Promise<string | null> {
    if (!this.selectedLogoFile) {
      return Promise.resolve(this.formData.logoPath || null);
    }

    this.uploadingLogo = true;
    this.error = null;

    return new Promise((resolve, reject) => {
      this.apiService.uploadImage(this.selectedLogoFile!).subscribe({
        next: (result) => {
          this.uploadingLogo = false;
          if (result && result.url) {
            resolve(result.url);
          } else {
            this.error = 'Erreur lors de l\'upload du logo';
            resolve(null);
          }
        },
        error: (error) => {
          this.uploadingLogo = false;
          console.error('Error uploading logo:', error);
          this.error = 'Erreur lors de l\'upload du logo';
          resolve(null);
        }
      });
    });
  }

  removeLogoPreview() {
    this.logoPreview = null;
    this.selectedLogoFile = null;
    this.formData.logoPath = 'assets/cdfL.png';
  }

  async saveBanner() {
    this.loading = true;
    this.error = null;
    this.successMessage = null;

    // Upload du logo si un nouveau fichier est sélectionné
    if (this.selectedLogoFile) {
      const logoUrl = await this.uploadLogo();
      if (logoUrl) {
        this.formData.logoPath = logoUrl;
        this.logoPreview = logoUrl;
      } else {
        this.loading = false;
        return;
      }
    }

    const operation = this.banner?.id
      ? this.apiService.updateHomeBanner(this.banner.id, this.formData)
      : this.apiService.createHomeBanner(this.formData);

    operation.subscribe({
      next: (saved) => {
        this.banner = saved;
        this.selectedLogoFile = null;
        this.successMessage = 'Bannière mise à jour avec succès';
        this.loading = false;
        setTimeout(() => {
          this.successMessage = null;
        }, 3000);
      },
      error: (err) => {
        console.error('Error saving banner:', err);
        this.error = err.error?.message || err.message || 'Erreur lors de l\'enregistrement';
        this.loading = false;
      }
    });
  }

  onImageError(event: Event) {
    const img = event.target as HTMLImageElement;
    if (img) {
      img.style.display = 'none';
    }
  }
}
