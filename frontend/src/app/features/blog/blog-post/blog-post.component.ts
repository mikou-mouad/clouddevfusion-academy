import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService, BlogPost, Course } from '../../../core/services/api.service';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import DOMPurify from 'dompurify';

@Component({
    selector: 'app-blog-post',
    imports: [CommonModule, RouterLink, FormsModule],
    templateUrl: './blog-post.component.html',
    styleUrls: ['./blog-post.component.scss']
})
export class BlogPostComponent implements OnInit {
  post: BlogPost | null = null;
  relatedPosts: BlogPost[] = [];
  popularCourses: Course[] = [];
  loading = false;
  error: string | null = null;
  newsletterEmail = '';

  constructor(
    private route: ActivatedRoute,
    private apiService: ApiService,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit() {
    const slug = this.route.snapshot.paramMap.get('slug');
    if (slug) {
      this.loadBlogPost(slug);
      this.loadPopularCourses();
    } else {
      this.error = 'Slug de l\'article non trouvé';
    }
  }

  loadBlogPost(slug: string) {
    this.loading = true;
    this.error = null;
    
    // Charger tous les articles et trouver celui avec le slug correspondant
    this.apiService.getBlogPosts().subscribe({
      next: (posts) => {
        // Filtrer les articles publiés et valides (non null et avec category)
        const allPosts = Array.isArray(posts) 
          ? posts.filter(p => p && p.category && p.published === true) 
          : [];
        const foundPost = allPosts.find(post => post.slug === slug);
        
        if (foundPost && foundPost.category) {
          this.post = foundPost;
          // Charger les articles similaires (même catégorie, exclure l'article actuel)
          this.relatedPosts = allPosts
            .filter(p => p.category === foundPost.category && p.id !== foundPost.id)
            .slice(0, 3);
        } else {
          this.error = 'Article non trouvé ou non publié';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading blog post:', err);
        this.error = 'Erreur lors du chargement de l\'article';
        this.loading = false;
      }
    });
  }

  loadPopularCourses() {
    this.apiService.getCourses().subscribe({
      next: (courses) => {
        this.popularCourses = Array.isArray(courses)
          ? courses.filter(c => c.popular === true).slice(0, 3)
          : [];
      },
      error: (err) => {
        console.error('Error loading popular courses:', err);
      }
    });
  }

  subscribeNewsletter() {
    if (this.newsletterEmail) {
      // TODO: Implémenter l'appel API pour la newsletter
      alert('Merci pour votre inscription à la newsletter !');
      this.newsletterEmail = '';
    }
  }

  formatContent(content?: string): SafeHtml {
    if (!content) return '';
    const clean = DOMPurify.sanitize(content, {
      USE_PROFILES: { html: true },
      ALLOWED_TAGS: ['p','br','h2','h3','h4','ul','ol','li','strong','em','a','img','blockquote','pre','code','table','thead','tbody','tr','th','td'],
      ALLOWED_ATTR: ['href','src','alt','title','target','rel','style']
    });
    return this.sanitizer.bypassSecurityTrustHtml(clean);
  }
}

