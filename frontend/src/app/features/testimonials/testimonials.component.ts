import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ApiService, Testimonial } from '../../core/services/api.service';
import { SafeUrlPipe } from '../../core/pipes/safe-url.pipe';

@Component({
    selector: 'app-testimonials',
    imports: [CommonModule, RouterLink, SafeUrlPipe],
    templateUrl: './testimonials.component.html',
    styleUrls: ['./testimonials.component.scss']
})
export class TestimonialsComponent implements OnInit {
  testimonials: Testimonial[] = [];
  displayedTestimonials: Testimonial[] = [];
  loading = false;
  error: string | null = null;
  
  // Pagination
  currentPage = 1;
  itemsPerPage = 6;
  totalPages = 1;

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadTestimonials();
  }

  loadTestimonials() {
    this.loading = true;
    this.error = null;
    this.apiService.getTestimonials().subscribe({
      next: (data) => {
        // Filtrer les témoignages valides (non null et avec au moins quote+author OU videoUrl)
        this.testimonials = Array.isArray(data) 
          ? data.filter(t => t && ((t.quote && t.author) || t.videoUrl)) 
          : [];
        this.totalPages = Math.ceil(this.testimonials.length / this.itemsPerPage);
        this.updateDisplayedTestimonials();
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading testimonials:', err);
        this.error = 'Erreur lors du chargement des témoignages';
        this.loading = false;
        // Fallback sur données par défaut si l'API n'est pas disponible
        this.testimonials = this.getDefaultTestimonials();
        this.totalPages = Math.ceil(this.testimonials.length / this.itemsPerPage);
        this.updateDisplayedTestimonials();
      }
    });
  }

  updateDisplayedTestimonials() {
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;
    this.displayedTestimonials = this.testimonials.slice(startIndex, endIndex);
  }

  goToPage(page: number) {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
      this.updateDisplayedTestimonials();
      // Scroll vers le haut de la section des témoignages
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  previousPage() {
    if (this.currentPage > 1) {
      this.goToPage(this.currentPage - 1);
    }
  }

  nextPage() {
    if (this.currentPage < this.totalPages) {
      this.goToPage(this.currentPage + 1);
    }
  }

  getPageNumbers(): number[] {
    const pages: number[] = [];
    const maxPagesToShow = 5;
    let startPage = Math.max(1, this.currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(this.totalPages, startPage + maxPagesToShow - 1);
    
    if (endPage - startPage < maxPagesToShow - 1) {
      startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
      pages.push(i);
    }
    
    return pages;
  }

  private getDefaultTestimonials(): Testimonial[] {
    return [
      {
        quote: 'Excellente formation, très pratique avec de vrais cas d\'usage. J\'ai réussi ma certification AZ-104 du premier coup ! Les formateurs MCT sont vraiment experts et disponibles pour répondre à toutes nos questions.',
        author: 'Jean Dupont',
        role: 'Administrateur Système',
        company: 'TechCorp',
        rating: 5
      },
      {
        quote: 'Les formateurs sont vraiment experts et disponibles. L\'approche labs-first m\'a permis de comprendre rapidement les concepts Azure complexes. La qualité du contenu et le suivi personnalisé font toute la différence.',
        author: 'Marie Martin',
        role: 'DevOps Engineer',
        company: 'CloudSolutions',
        rating: 5
      },
      {
        quote: 'Formation de qualité, bien structurée. Le financement CPF a rendu l\'accès très simple. Je recommande vivement CloudDev Fusion pour toute personne souhaitant se certifier sur Azure.',
        author: 'Pierre Durand',
        role: 'IT Manager',
        company: 'InnovateTech',
        rating: 5
      }
    ];
  }

  get testimonialsWithVideos(): Testimonial[] {
    return this.testimonials.filter(t => t.videoUrl && t.videoUrl.trim() !== '');
  }

  get testimonialsWithoutVideos(): Testimonial[] {
    return this.testimonials.filter(t => !t.videoUrl || t.videoUrl.trim() === '');
  }

  getVideoEmbedUrl(videoUrl: string): string {
    if (!videoUrl) return '';
    
    // YouTube
    if (videoUrl.includes('youtube.com/watch?v=')) {
      const videoId = videoUrl.split('v=')[1]?.split('&')[0];
      return videoId ? `https://www.youtube.com/embed/${videoId}` : '';
    }
    if (videoUrl.includes('youtu.be/')) {
      const videoId = videoUrl.split('youtu.be/')[1]?.split('?')[0];
      return videoId ? `https://www.youtube.com/embed/${videoId}` : '';
    }
    
    // Vimeo
    if (videoUrl.includes('vimeo.com/')) {
      const videoId = videoUrl.split('vimeo.com/')[1]?.split('?')[0];
      return videoId ? `https://player.vimeo.com/video/${videoId}` : '';
    }
    
    // Si c'est déjà une URL embed, la retourner telle quelle
    if (videoUrl.includes('/embed/')) {
      return videoUrl;
    }
    
    return videoUrl;
  }

  isVideoUrl(url: string): boolean {
    if (!url) return false;
    return url.includes('youtube.com') || url.includes('youtu.be') || url.includes('vimeo.com') || url.includes('/embed/') || url.startsWith('/uploads/');
  }

  isUploadedVideo(url: string | undefined): boolean {
    if (!url) return false;
    return url.startsWith('/uploads/');
  }
}

