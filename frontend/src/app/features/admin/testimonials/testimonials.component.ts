import { Component, OnInit } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { ApiService, Testimonial } from '../../../core/services/api.service';

@Component({
    selector: 'app-admin-testimonials',
    imports: [FormsModule],
    templateUrl: './testimonials.component.html',
    styleUrls: ['./testimonials.component.scss']
})
export class AdminTestimonialsComponent implements OnInit {
  testimonials: Testimonial[] = [];
  showModal = false;
  editingTestimonial: Testimonial | null = null;
  formData: Testimonial = {
    quote: '',
    author: '',
    role: '',
    company: '',
    rating: 5,
    videoUrl: ''
  };
  loading = false;
  error: string | null = null;
  uploadingVideo = false;
  selectedVideoFile: File | null = null;
  videoPreviewUrl: string | null = null;
  testimonialMode: 'video' | 'text' = 'text'; // Mode par défaut : témoignage écrit

  // Getter pour s'assurer que testimonials est toujours un tableau et filtrer les éléments null
  get testimonialsList(): Testimonial[] {
    return Array.isArray(this.testimonials) 
      ? this.testimonials.filter(t => t && ((t.quote && t.author) || t.videoUrl)) 
      : [];
  }

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadTestimonials();
  }

  loadTestimonials() {
    this.loading = true;
    this.error = null;
    this.apiService.getTestimonials().subscribe({
      next: (data) => {
        // Filtrer les témoignages valides (non null et avec quote)
        this.testimonials = Array.isArray(data) 
          ? data.filter(t => t && t.quote && t.author) 
          : [];
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading testimonials:', err);
        this.error = 'Erreur lors du chargement des témoignages';
        this.loading = false;
        // Fallback sur localStorage si l'API n'est pas disponible
        const saved = localStorage.getItem('testimonials');
        if (saved) {
          try {
            const parsed = JSON.parse(saved);
            this.testimonials = Array.isArray(parsed) ? parsed : [];
          } catch (e) {
            this.testimonials = [];
          }
        } else {
          this.testimonials = [];
        }
      }
    });
  }

  openAddModal(mode: 'video' | 'text' = 'text') {
    this.editingTestimonial = null;
    this.error = null;
    this.testimonialMode = mode;
    this.formData = {
      quote: '',
      author: '',
      role: '',
      company: '',
      rating: 5,
      videoUrl: ''
    };
    this.selectedVideoFile = null;
    this.videoPreviewUrl = null;
    this.showModal = true;
  }

  editTestimonial(index: number) {
    this.editingTestimonial = this.testimonials[index];
    this.formData = { ...this.testimonials[index] };
    this.selectedVideoFile = null;
    this.videoPreviewUrl = this.formData.videoUrl && !this.isExternalVideoUrl(this.formData.videoUrl) ? this.formData.videoUrl : null;
    // Déterminer le mode selon le contenu
    this.testimonialMode = (this.formData.videoUrl && !this.formData.quote) ? 'video' : 'text';
    this.showModal = true;
  }

  onVideoFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      const file = input.files[0];
      
      // Vérifier le type de fichier
      if (!file.type.startsWith('video/')) {
        this.error = 'Veuillez sélectionner un fichier vidéo valide';
        return;
      }
      
      // Vérifier la taille (max 100MB)
      const maxSize = 100 * 1024 * 1024; // 100MB
      if (file.size > maxSize) {
        this.error = 'Le fichier est trop volumineux. Taille maximale: 100MB';
        return;
      }
      
      this.selectedVideoFile = file;
      this.uploadingVideo = true;
      this.error = null;
      
      // Upload du fichier
      this.apiService.uploadVideo(file).subscribe({
        next: (response) => {
          this.formData.videoUrl = response.url;
          this.videoPreviewUrl = response.url;
          this.uploadingVideo = false;
          this.error = null;
        },
        error: (err) => {
          console.error('Error uploading video:', err);
          this.error = err.error?.error || 'Erreur lors de l\'upload de la vidéo';
          this.uploadingVideo = false;
          this.selectedVideoFile = null;
        }
      });
    }
  }

  removeVideo() {
    // Si c'est un fichier uploadé (commence par /uploads/), on peut le supprimer
    if (this.formData.videoUrl && this.formData.videoUrl.startsWith('/uploads/')) {
      const filename = this.formData.videoUrl.split('/').pop();
      if (filename) {
        this.apiService.deleteVideo(filename).subscribe({
          next: () => {
            this.formData.videoUrl = '';
            this.videoPreviewUrl = null;
            this.selectedVideoFile = null;
          },
          error: (err) => {
            console.error('Error deleting video:', err);
            // On supprime quand même l'URL même si la suppression échoue
            this.formData.videoUrl = '';
            this.videoPreviewUrl = null;
            this.selectedVideoFile = null;
          }
        });
      }
    } else {
      // C'est une URL externe, on la supprime simplement
      this.formData.videoUrl = '';
      this.videoPreviewUrl = null;
      this.selectedVideoFile = null;
    }
  }

  isExternalVideoUrl(url: string | undefined): boolean {
    if (!url) return false;
    return url.includes('youtube.com') || url.includes('youtu.be') || url.includes('vimeo.com') || url.includes('http://') || url.includes('https://');
  }

  isUploadedVideo(url: string | undefined): boolean {
    if (!url) return false;
    return url.startsWith('/uploads/');
  }

  deleteTestimonial(index: number) {
    const testimonial = this.testimonials[index];
    if (!testimonial.id) {
      alert('Impossible de supprimer ce témoignage');
      return;
    }

    if (confirm('Êtes-vous sûr de vouloir supprimer ce témoignage ?')) {
      this.loading = true;
      this.apiService.deleteTestimonial(testimonial.id).subscribe({
        next: () => {
          this.testimonials.splice(index, 1);
          this.loading = false;
        },
        error: (err) => {
          console.error('Error deleting testimonial:', err);
          alert('Erreur lors de la suppression');
          this.loading = false;
        }
      });
    }
  }

  saveTestimonial() {
    // Validation selon le mode sélectionné
    if (this.testimonialMode === 'video') {
      // Mode vidéo : seule la vidéo est requise
      if (!this.formData.videoUrl || this.formData.videoUrl.trim() === '') {
        this.error = 'Veuillez uploader une vidéo ou fournir une URL vidéo';
        return;
      }
      // Nettoyer les champs texte si mode vidéo
      this.formData.quote = '';
      this.formData.author = '';
      this.formData.role = '';
      this.formData.company = '';
      this.formData.rating = 5;
    } else {
      // Mode texte : tous les champs texte sont requis
      if (!this.formData.quote || this.formData.quote.trim().length < 10) {
        this.error = 'La citation doit contenir au moins 10 caractères';
        return;
      }
      if (!this.formData.author || !this.formData.role || !this.formData.company) {
        this.error = 'Tous les champs sont requis pour un témoignage écrit';
        return;
      }
      // Nettoyer la vidéo si mode texte (optionnel, on peut garder les deux)
    }

    this.loading = true;
    this.error = null;

    // Préparer les données avec le bon format
    // Convertir le rating en nombre si nécessaire
    let ratingValue = this.formData.rating;
    if (typeof ratingValue === 'string') {
      ratingValue = parseInt(ratingValue, 10);
    }
    if (isNaN(ratingValue) || ratingValue < 1 || ratingValue > 5) {
      ratingValue = 5; // Valeur par défaut
    }

    const testimonialData: Testimonial = {
      quote: this.formData.quote?.trim() || '',
      author: this.formData.author?.trim() || '',
      role: this.formData.role?.trim() || '',
      company: this.formData.company?.trim() || '',
      rating: ratingValue,
      videoUrl: this.formData.videoUrl?.trim() || undefined
    };

    const operation = this.editingTestimonial?.id
      ? this.apiService.updateTestimonial(this.editingTestimonial.id, testimonialData)
      : this.apiService.createTestimonial(testimonialData);

    operation.subscribe({
      next: (saved) => {
        if (this.editingTestimonial?.id) {
          const index = this.testimonials.findIndex(t => t.id === this.editingTestimonial?.id);
          if (index !== -1) {
            this.testimonials[index] = saved;
          }
        } else {
          this.testimonials.push(saved);
        }
        this.loading = false;
        this.closeModal();
      },
      error: (err) => {
        console.error('Error saving testimonial:', err);
        
        // Extraire les messages d'erreur de validation
        let errorMessage = 'Erreur lors de l\'enregistrement';
        if (err.error) {
          if (err.error.violations && Array.isArray(err.error.violations)) {
            const violations = err.error.violations.map((v: any) => v.message).join(', ');
            errorMessage = `Erreurs de validation : ${violations}`;
          } else if (err.error['hydra:description']) {
            errorMessage = err.error['hydra:description'];
          } else if (err.error.message) {
            errorMessage = err.error.message;
          } else if (typeof err.error === 'string') {
            errorMessage = err.error;
          }
        }
        
        this.error = errorMessage;
        this.loading = false;
      }
    });
  }

  closeModal() {
    this.showModal = false;
    this.editingTestimonial = null;
    this.error = null;
    this.selectedVideoFile = null;
    this.videoPreviewUrl = null;
    this.uploadingVideo = false;
  }
}
