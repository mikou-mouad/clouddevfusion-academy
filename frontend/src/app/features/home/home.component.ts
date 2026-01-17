import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { trigger, transition, style, animate, keyframes } from '@angular/animations';
import { ApiService, Testimonial, Faq } from '../../core/services/api.service';
import { SafeUrlPipe } from '../../core/pipes/safe-url.pipe';

interface Partner {
  name: string;
  logoPath: string | null;
  imageError: boolean;
}

@Component({
    selector: 'app-home',
    imports: [CommonModule, RouterLink, FormsModule, SafeUrlPipe],
    templateUrl: './home.component.html',
    styleUrls: ['./home.component.scss'],
    animations: [
        trigger('fadeInUp', [
            transition(':enter', [
                style({ opacity: 0, transform: 'translateY(30px)' }),
                animate('0.8s ease-out', style({ opacity: 1, transform: 'translateY(0)' }))
            ])
        ]),
        trigger('countUp', [
            transition(':enter', [
                animate('2s ease-out', keyframes([
                    style({ opacity: 0, offset: 0 }),
                    style({ opacity: 1, offset: 0.2 }),
                    style({ opacity: 1, offset: 1 })
                ]))
            ])
        ])
    ]
})
export class HomeComponent implements OnInit, OnDestroy {
  cloudDevLogoPath = 'assets/cdfL.png';
  private apiService: ApiService;

  // Home Banner Data
  bannerData = {
    logoPath: 'assets/cdfL.png',
    kpi1: { number: '100+', label: 'Professionnels formés' },
    kpi2: { number: '98%', label: 'Taux de réussite' },
    kpi3: { number: '50+', label: 'Certifications disponibles' }
  };

  constructor(apiService: ApiService) {
    this.apiService = apiService;
  }

  // ============================================
  // CERTIFICATIONS MICROSOFT
  // ============================================
  microsoftCertifications = [
    {
      code: 'AZ-104',
      title: 'Azure Administrator Associate',
      level: 'Intermédiaire',
      description: 'Administration complète de l\'infrastructure Azure',
      delay: 0
    },
    {
      code: 'AZ-900',
      title: 'Azure Fundamentals',
      level: 'Débutant',
      description: 'Fondamentaux du cloud Azure et des services Microsoft',
      delay: 1
    },
    {
      code: 'AZ-204',
      title: 'Azure Developer Associate',
      level: 'Intermédiaire',
      description: 'Développement d\'applications cloud sur Azure',
      delay: 2
    },
    {
      code: 'AZ-305',
      title: 'Azure Solutions Architect Expert',
      level: 'Avancé',
      description: 'Architecture de solutions cloud scalables sur Azure',
      delay: 3
    },
    {
      code: 'AZ-500',
      title: 'Azure Security Engineer Associate',
      level: 'Intermédiaire',
      description: 'Sécurisation des environnements Azure',
      delay: 4
    },
    {
      code: 'AI-102',
      title: 'Azure AI Engineer Associate',
      level: 'Avancé',
      description: 'Ingénierie de solutions IA sur Azure',
      delay: 5
    },
    {
      code: 'DP-203',
      title: 'Azure Data Engineer Associate',
      level: 'Avancé',
      description: 'Ingénierie de données sur Azure',
      delay: 6
    },
    {
      code: 'AZ-400',
      title: 'Azure DevOps Engineer Expert',
      level: 'Avancé',
      description: 'DevOps et CI/CD sur Azure',
      delay: 7
    }
  ];

  // Carrousel Certifications
  currentCertificationSlide = 0;
  certificationsSlidesToShow = 3;
  private certificationsAutoPlayInterval?: any;

  get maxCertificationSlide(): number {
    return Math.max(0, this.microsoftCertifications.length - this.certificationsSlidesToShow);
  }

  // ============================================
  // OFFRES PAR TYPE (Entreprise, Groupe, École)
  // ============================================
  offers = [
    {
      type: 'entreprise',
      title: 'Formation Entreprise',
      description: 'Solutions de formation sur mesure pour vos équipes. Programmes adaptés à vos besoins spécifiques et à votre secteur d\'activité.',
      features: [
        'Formations sur mesure',
        'Formateurs dédiés',
        'Horaires flexibles',
        'Suivi personnalisé',
        'Certifications incluses'
      ],
      gradient: 'linear-gradient(135deg, #0066CC 0%, #4d9eff 100%)',
      iconColor: '#0066CC'
    },
    {
      type: 'groupe',
      title: 'Formation Groupe',
      description: 'Formations collectives pour petits groupes. Bénéficiez de tarifs préférentiels et d\'une dynamique d\'apprentissage collaborative.',
      features: [
        'Tarifs préférentiels',
        'Groupes de 4 à 6 personnes',
        'Dates flexibles',
        'Ambiance collaborative',
        'Support post-formation'
      ],
      gradient: 'linear-gradient(135deg, #4d9eff 0%, #0066CC 100%)',
      iconColor: '#4d9eff'
    },
    {
      type: 'ecole',
      title: 'Formation École',
      description: 'Programmes de formation pour établissements scolaires et centres de formation. Contenus pédagogiques adaptés aux étudiants.',
      features: [
        'Programmes adaptés',
        'Contenus pédagogiques',
        'Tarifs institutionnels',
        'Support enseignant',
        'Certifications étudiantes'
      ],
      gradient: 'linear-gradient(135deg, #003d7a 0%, #0066CC 100%)',
      iconColor: '#003d7a'
    }
  ];

  // ============================================
  // FEATURES
  // ============================================
  features = [
    {
      icon: 'certification',
      title: 'Formations Certifiantes',
      description: 'Préparation complète aux certifications Microsoft Azure officielles avec un taux de réussite de 98%.'
    },
    {
      icon: 'labs',
      title: 'Certifié Microsoft',
      description: 'Membre du programme Microsoft Training Partners, formations alignées sur les standards officiels Microsoft et accès aux ressources MS Learn.'
    },
    {
      icon: 'flexible',
      title: 'Financement CPF',
      description: 'Éligible au financement CPF, OPCO et autres dispositifs, directement ou à travers nos partenaires, pour faciliter votre accès à la formation.'
    },
    {
      icon: 'expert',
      title: 'Formateurs Experts MCT',
      description: 'Des formateurs certifiés Microsoft (MCT) avec une expérience terrain approfondie dans le cloud Azure.'
    },
    {
      icon: 'support',
      title: 'Groupes Restreints & Support Continu',
      description: 'Groupes de 5 ou 6 Personnes avec accompagnement personnalisé avant, pendant et après la formation avec un support dédié.'
    },
    {
      icon: 'cpf',
      title: 'Formats Flexibles',
      description: 'Formations en présentiel, distanciel ou hybride, adaptées à vos contraintes et votre rythme.'
    }
  ];

  // Carrousel Features
  currentFeatureSlide = 0;
  featuresSlidesToShow = 3;
  private featuresAutoPlayInterval?: any;

  get maxFeatureSlide(): number {
    return Math.max(0, this.features.length - this.featuresSlidesToShow);
  }

  // ============================================
  // TÉMOIGNAGES
  // ============================================
  testimonials: Testimonial[] = [];

  // Carrousel Testimonials
  currentTestimonialSlide = 0;
  private testimonialsAutoPlayInterval?: any;

  get testimonialIndicators(): number[] {
    return Array(this.testimonials.length).fill(0).map((_, i) => i);
  }

  // ============================================
  // PARTENAIRES
  // ============================================
  partners = [
    { name: 'Qualiopi', logoPath: 'assets/qualiopi.png', imageError: false },
    { name: 'Microsoft', logoPath: 'assets/Microsoft-logo.png', imageError: false },
    { name: 'Nexa School', logoPath: 'assets/nexa.png', imageError: false },
    { name: 'Azure', logoPath: 'assets/Microsoft-Azure.png', imageError: false },
    { name: 'Global Knowledge', logoPath: 'assets/globalKnowledge.png', imageError: false },
    { name: 'Unlock Formation', logoPath: 'assets/unlock.png', imageError: false }
  ];

  // Dupliquer les partenaires pour l'effet de défilement infini
  get duplicatedPartners() {
    return [...this.partners, ...this.partners];
  }

  getFallbackClass(name: string): string {
    const slug = name.toLowerCase().replace(/\s+/g, '-');
    return `fallback-${slug}`;
  }

  onImageError(event: Event, partnerIndex: number): void {
    if (this.partners[partnerIndex]) {
      this.partners[partnerIndex].imageError = true;
    }
  }

  shouldShowFallback(partner: Partner): boolean {
    return !partner.logoPath || partner.imageError;
  }

  // ============================================
  // PROCESSUS "COMMENT ÇA MARCHE"
  // ============================================
  processSteps = [
    {
      step: 1,
      title: 'Consultation Gratuite',
      description: 'Réservez un rendez-vous avec notre conseiller pour discuter de vos objectifs et identifier la formation adaptée à votre profil.',
      icon: 'consultation',
      color: 'primary'
    },
    {
      step: 2,
      title: 'Inscription & Financement',
      description: 'Inscrivez-vous facilement en ligne. Nous vous accompagnons dans vos démarches de financement (CPF, OPCO, entreprise). Directement ou à travers nos partenaires.',
      icon: 'financement',
      color: 'secondary'
    },
    {
      step: 3,
      title: 'Formation',
      description: 'Suivez une formation immersive avec nos experts MCT, avec une session live de 2h30 chaque semaine, accédez aux labs pratiques et préparez-vous efficacement à la certification.',
      icon: 'formation',
      color: 'success'
    },
    {
      step: 4,
      title: 'Certification',
      description: 'Passez votre examen de certification Microsoft et obtenez votre badge officiel. Nous vous accompagnons jusqu\'à la réussite.',
      icon: 'certification',
      color: 'accent'
    }
  ];

  // ============================================
  // FAQ RAPIDE
  // ============================================
  faqItems: Faq[] = [];
  openFaqIds = new Set<number>();

  loadFaqs() {
    this.apiService.getFaqs().subscribe({
      next: (data: Faq[]) => {
        // Filtrer uniquement les FAQs publiées et prendre les 4 premières
        const publishedFaqs = (Array.isArray(data) ? data : [])
          .filter(faq => faq.published !== false)
          .slice(0, 4);
        
        // Trier par orderIndex
        this.faqItems = publishedFaqs.sort((a, b) => {
          const orderA = a.orderIndex ?? 999;
          const orderB = b.orderIndex ?? 999;
          if (orderA !== orderB) {
            return orderA - orderB;
          }
          return (a.id ?? 0) - (b.id ?? 0);
        });
        
        // Réinitialiser les FAQs ouvertes
        this.openFaqIds.clear();
      },
      error: (err: any) => {
        console.error('Error loading FAQs:', err);
        // Fallback sur données par défaut si l'API n'est pas disponible
        this.faqItems = [
          {
            id: 1,
            question: 'Comment financer ma formation ?',
            answer: 'Direct ou à travers un partenaire, nos formations sont éligibles au CPF, aux financements OPCO, aux plans de développement des compétences et aux budgets formation entreprise. Nous vous accompagnons dans toutes vos démarches.',
            category: 'Financement',
            published: true
          },
          {
            id: 2,
            question: 'Quel niveau prérequis pour suivre une formation ?',
            answer: 'Chaque formation indique son niveau requis. Nous proposons des parcours débutants (AZ-900) jusqu\'aux expertises avancées. Nos conseillers vous orientent vers la formation adaptée à votre profil. Vous pouvez aussi faire les tests de positionnement sur notre site.',
            category: 'Prérequis',
            published: true
          },
          {
            id: 3,
            question: 'Les formations sont-elles disponibles en distanciel ?',
            answer: 'Oui, toutes nos formations sont disponibles en présentiel, distanciel ou hybride. Vous choisissez le format qui correspond le mieux à vos contraintes et préférences.',
            category: 'Formats',
            published: true
          },
          {
            id: 4,
            question: 'Quelle est la garantie de réussite à l\'examen ?',
            answer: 'Avec un taux de réussite de 98%, nous vous préparons efficacement. Si vous échouez, vous bénéficiez d\'une session de rattrapage gratuite et d\'un accompagnement supplémentaire.',
            category: 'Certifications',
            published: true
          }
        ];
        this.openFaqIds.clear();
      }
    });
  }

  isFaqOpen(faq: Faq): boolean {
    return faq.id !== undefined && this.openFaqIds.has(faq.id);
  }

  toggleFaq(faq: Faq): void {
    if (faq.id !== undefined) {
      if (this.openFaqIds.has(faq.id)) {
        this.openFaqIds.delete(faq.id);
      } else {
        this.openFaqIds.add(faq.id);
      }
    }
  }

  private resizeListener?: () => void;
  private scrollListener?: () => void;
  showFloatingButton = false;

  ngOnInit() {
    this.loadTestimonials();
    this.loadFaqs();
    this.loadHomeBanner();
    this.resizeListener = () => {
      this.updateSlidesToShow();
      if (this.currentCertificationSlide > this.maxCertificationSlide) {
        this.currentCertificationSlide = this.maxCertificationSlide;
      }
      if (this.currentFeatureSlide > this.maxFeatureSlide) {
        this.currentFeatureSlide = this.maxFeatureSlide;
      }
    };
    window.addEventListener('resize', this.resizeListener);
    
    this.updateSlidesToShow();
    this.startCertificationsAutoPlay();
    this.startFeaturesAutoPlay();
    this.startTestimonialsAutoPlay();
    
    // Enable smooth scroll and modern scroll effects
    this.setupScrollEffects();
  }

  ngOnDestroy() {
    if (this.resizeListener) {
      window.removeEventListener('resize', this.resizeListener);
    }
    if (this.scrollListener) {
      window.removeEventListener('scroll', this.scrollListener);
    }
    this.stopCertificationsAutoPlay();
    this.stopFeaturesAutoPlay();
    this.stopTestimonialsAutoPlay();
  }

  // ============================================
  // SCROLL EFFECTS MODERNES
  // ============================================
  setupScrollEffects(): void {
    // Enable smooth scroll
    if (typeof document !== 'undefined') {
      document.documentElement.style.scrollBehavior = 'smooth';
    }

    // Parallax effect on scroll + Floating button
    this.scrollListener = () => {
      requestAnimationFrame(() => {
        const scrollY = window.pageYOffset || document.documentElement.scrollTop;
        
        // Afficher le bouton flottant après 300px de scroll
        this.showFloatingButton = scrollY > 300;
        
        // Parallax pour le hero background
        const heroBackground = document.querySelector('.hero-background');
        if (heroBackground) {
          const parallaxOffset = scrollY * 0.2;
          (heroBackground as HTMLElement).style.transform = `translateY(${parallaxOffset}px)`;
        }
      });
    };

    window.addEventListener('scroll', this.scrollListener, { passive: true } as any);
  }

  updateSlidesToShow() {
    if (typeof window === 'undefined') return;
    if (window.innerWidth >= 1024) {
      this.certificationsSlidesToShow = 3;
      this.featuresSlidesToShow = 3;
    } else if (window.innerWidth >= 768) {
      this.certificationsSlidesToShow = 2;
      this.featuresSlidesToShow = 2;
    } else {
      this.certificationsSlidesToShow = 1;
      this.featuresSlidesToShow = 1;
    }
  }

  // ============================================
  // CERTIFICATIONS CAROUSEL
  // ============================================
  startCertificationsAutoPlay() {
    this.certificationsAutoPlayInterval = setInterval(() => {
      if (this.currentCertificationSlide < this.maxCertificationSlide) {
        this.currentCertificationSlide++;
      } else {
        this.currentCertificationSlide = 0;
      }
    }, 5000);
  }

  stopCertificationsAutoPlay() {
    if (this.certificationsAutoPlayInterval) {
      clearInterval(this.certificationsAutoPlayInterval);
    }
  }

  nextCertification() {
    this.stopCertificationsAutoPlay();
    if (this.currentCertificationSlide < this.maxCertificationSlide) {
      this.currentCertificationSlide++;
    } else {
      this.currentCertificationSlide = 0;
    }
    setTimeout(() => this.startCertificationsAutoPlay(), 10000);
  }

  previousCertification() {
    this.stopCertificationsAutoPlay();
    if (this.currentCertificationSlide > 0) {
      this.currentCertificationSlide--;
    } else {
      this.currentCertificationSlide = this.maxCertificationSlide;
    }
    setTimeout(() => this.startCertificationsAutoPlay(), 10000);
  }


  // ============================================
  // FEATURES CAROUSEL
  // ============================================
  startFeaturesAutoPlay() {
    this.featuresAutoPlayInterval = setInterval(() => {
      if (this.currentFeatureSlide < this.maxFeatureSlide) {
        this.currentFeatureSlide++;
      } else {
        this.currentFeatureSlide = 0;
      }
    }, 5000);
  }

  stopFeaturesAutoPlay() {
    if (this.featuresAutoPlayInterval) {
      clearInterval(this.featuresAutoPlayInterval);
    }
  }

  nextFeature() {
    this.stopFeaturesAutoPlay();
    if (this.currentFeatureSlide < this.maxFeatureSlide) {
      this.currentFeatureSlide++;
    } else {
      this.currentFeatureSlide = 0;
    }
    setTimeout(() => this.startFeaturesAutoPlay(), 10000);
  }

  previousFeature() {
    this.stopFeaturesAutoPlay();
    if (this.currentFeatureSlide > 0) {
      this.currentFeatureSlide--;
    } else {
      this.currentFeatureSlide = this.maxFeatureSlide;
    }
    setTimeout(() => this.startFeaturesAutoPlay(), 10000);
  }

  // ============================================
  // TESTIMONIALS CAROUSEL
  // ============================================
  loadTestimonials() {
    this.apiService.getTestimonials().subscribe({
      next: (data: Testimonial[]) => {
        // Filtrer les témoignages valides (non null et avec quote)
        this.testimonials = Array.isArray(data) 
          ? data.filter(t => t && ((t.quote && t.author) || t.videoUrl)) 
          : [];
        // Réinitialiser le slide si nécessaire
        if (this.currentTestimonialSlide >= this.testimonials.length && this.testimonials.length > 0) {
          this.currentTestimonialSlide = 0;
        }
        // Redémarrer l'autoplay si nécessaire
        if (this.testimonials.length > 0) {
          this.startTestimonialsAutoPlay();
        }
      },
      error: (err: any) => {
        console.error('Error loading testimonials:', err);
        // Fallback sur données par défaut si l'API n'est pas disponible
        this.testimonials = [
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
    });
  }

  startTestimonialsAutoPlay() {
    this.testimonialsAutoPlayInterval = setInterval(() => {
      if (this.currentTestimonialSlide < this.testimonials.length - 1) {
        this.currentTestimonialSlide++;
      } else {
        this.currentTestimonialSlide = 0;
      }
    }, 4000);
  }

  stopTestimonialsAutoPlay() {
    if (this.testimonialsAutoPlayInterval) {
      clearInterval(this.testimonialsAutoPlayInterval);
    }
  }

  nextTestimonial() {
    this.stopTestimonialsAutoPlay();
    if (this.currentTestimonialSlide < this.testimonials.length - 1) {
      this.currentTestimonialSlide++;
    } else {
      this.currentTestimonialSlide = 0;
    }
    setTimeout(() => this.startTestimonialsAutoPlay(), 10000);
  }

  previousTestimonial() {
    this.stopTestimonialsAutoPlay();
    if (this.currentTestimonialSlide > 0) {
      this.currentTestimonialSlide--;
    } else {
      this.currentTestimonialSlide = this.testimonials.length - 1;
    }
    setTimeout(() => this.startTestimonialsAutoPlay(), 10000);
  }

  goToTestimonial(index: number) {
    this.stopTestimonialsAutoPlay();
    this.currentTestimonialSlide = index;
    setTimeout(() => this.startTestimonialsAutoPlay(), 10000);
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

  isVideoUrl(url: string | undefined): boolean {
    if (!url) return false;
    return url.includes('youtube.com') || url.includes('youtu.be') || url.includes('vimeo.com') || url.includes('/embed/') || url.startsWith('/uploads/');
  }

  isUploadedVideo(url: string | undefined): boolean {
    if (!url) return false;
    return url.startsWith('/uploads/');
  }

  // ============================================
  // HOME BANNER
  // ============================================
  loadHomeBanner() {
    this.apiService.getActiveHomeBanner().subscribe({
      next: (banner) => {
        if (banner) {
          if (banner.logoPath) {
            this.cloudDevLogoPath = banner.logoPath;
          }
          if (banner.kpi1Number && banner.kpi1Label) {
            this.bannerData.kpi1 = { number: banner.kpi1Number, label: banner.kpi1Label };
          }
          if (banner.kpi2Number && banner.kpi2Label) {
            this.bannerData.kpi2 = { number: banner.kpi2Number, label: banner.kpi2Label };
          }
          if (banner.kpi3Number && banner.kpi3Label) {
            this.bannerData.kpi3 = { number: banner.kpi3Number, label: banner.kpi3Label };
          }
        }
      },
      error: (err) => {
        console.error('Error loading home banner:', err);
        // Utiliser les valeurs par défaut en cas d'erreur
      }
    });
  }
}
