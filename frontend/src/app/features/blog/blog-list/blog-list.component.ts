import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService, BlogPost } from '../../../core/services/api.service';

@Component({
    selector: 'app-blog-list',
    imports: [CommonModule, RouterLink, FormsModule],
    template: `
    <div class="blog-page">
      <section class="blog-header">
        <div class="blog-header-content">
          <h1>Blog</h1>
          <p class="blog-subtitle">Actualités, conseils et guides Azure</p>
        </div>
      </section>

      <section class="categories-section">
        <div class="container">
          <div class="categories">
            <button 
              *ngFor="let category of categories" 
              [class.active]="selectedCategory === category"
              (click)="selectedCategory = category"
              class="category-btn">
              {{ category }}
            </button>
          </div>
        </div>
      </section>

      <section class="posts-section">
        <div class="container">
          <div class="loading-state" *ngIf="loading">
            <p>Chargement des articles...</p>
          </div>
          
          <div class="error-state" *ngIf="error">
            <p>{{ error }}</p>
          </div>

          <div class="posts-grid" *ngIf="!loading && !error">
            <article class="post-card" *ngFor="let post of filteredPosts">
              <div *ngIf="post && post.category">
                <div class="post-image" *ngIf="post.image">
                  <img [src]="post.image" [alt]="post.title">
                </div>
                <div class="post-content">
                  <div class="post-meta">
                    <span class="category">{{ post?.category }}</span>
                    <span class="date" *ngIf="post?.createdAt">{{ post.createdAt | date:'dd MMM yyyy' }}</span>
                    <span class="read-time" *ngIf="post?.readTime">{{ post.readTime }} min de lecture</span>
                  </div>
                  <h2>
                    <a [routerLink]="['/blog', post.slug]" *ngIf="post?.slug">{{ post?.title }}</a>
                    <span *ngIf="!post?.slug">{{ post?.title }}</span>
                  </h2>
                  <p class="excerpt" *ngIf="post?.excerpt">{{ post.excerpt }}</p>
                  <div class="post-author">
                    <strong>{{ post?.author }}</strong>
                  </div>
                  <a [routerLink]="['/blog', post.slug]" class="read-more" *ngIf="post?.slug">Lire la suite →</a>
                </div>
              </div>
            </article>
            
            <div class="empty-state" *ngIf="filteredPosts.length === 0">
              <p>Aucun article disponible pour le moment.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="newsletter-section">
        <div class="container">
          <h2>Newsletter</h2>
          <p>Recevez nos derniers articles et guides directement dans votre boîte mail</p>
          <form class="newsletter-form">
            <input type="email" placeholder="Votre email" [(ngModel)]="email" name="email">
            <button type="submit" class="btn btn-primary">S'abonner</button>
          </form>
        </div>
      </section>
    </div>
  `,
    styles: [`
    .blog-page {
      padding: 0;
      margin-top: 0;
    }

    .blog-header {
      position: relative;
      background: var(--primary-blue);
      color: var(--white);
      padding: 60px 0 50px;
      text-align: center;
      margin-top: 0;
      padding-top: calc(82px + 60px);
      border-top: none;
    }

    .blog-header-content {
      max-width: 600px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
    }

    .blog-header h1 {
      color: var(--white);
      font-size: clamp(2rem, 5vw, 3rem);
      font-weight: 600;
      margin: 0 0 10px;
      letter-spacing: 0;
      line-height: 1.3;
    }

    .blog-subtitle {
      font-size: clamp(0.95rem, 1.8vw, 1.15rem);
      color: rgba(255, 255, 255, 0.9);
      margin: 0;
      font-weight: 400;
      line-height: 1.6;
    }

    .categories-section {
      padding: 30px 0;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
      border-bottom: 1px solid rgba(0, 102, 204, 0.1);
      position: sticky;
      top: 70px;
      z-index: 100;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      transition: box-shadow 0.3s ease;
      
      &:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      }
    }

    .categories {
      display: flex;
      gap: var(--spacing-sm);
      flex-wrap: wrap;
      justify-content: center;
    }

    .category-btn {
      padding: 12px 24px;
      border: 2px solid var(--primary-blue);
      background: var(--white);
      color: var(--primary-blue);
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 600;
      font-size: 0.95rem;
      letter-spacing: 0.3px;
      position: relative;
      overflow: hidden;
      
      &::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(0, 102, 204, 0.1);
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
      }
      
      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 102, 204, 0.2);
        
        &::before {
          width: 300px;
          height: 300px;
        }
      }
      
      &.active {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: var(--white);
        border-color: var(--primary-blue);
        box-shadow: 0 4px 15px rgba(0, 102, 204, 0.3);
        transform: translateY(-2px);
      }
    }

    .posts-section {
      padding: var(--spacing-xl) 0;
    }

    .posts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: var(--spacing-lg);
    }

    .post-card {
      background: var(--white);
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      
      &:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        
        .read-more {
          color: var(--dark-blue);
          transform: translateX(5px);
        }
        
        h2 a {
          color: var(--primary-blue);
        }
      }
    }

    .post-image {
      width: 100%;
      height: 200px;
      background: var(--light-gray);
      overflow: hidden;
      
      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    }

    .post-content {
      padding: var(--spacing-lg);
    }

    .post-meta {
      display: flex;
      gap: var(--spacing-sm);
      font-size: 0.85rem;
      color: var(--text-gray);
      margin-bottom: var(--spacing-sm);
      flex-wrap: wrap;
      
      .category {
        color: var(--primary-blue);
        font-weight: 600;
      }
    }

    .post-content h2 {
      margin-bottom: var(--spacing-sm);
      
      a {
        color: var(--dark-blue);
        text-decoration: none;
        
        &:hover {
          color: var(--primary-blue);
        }
      }
    }

    .excerpt {
      color: var(--text-gray);
      margin-bottom: var(--spacing-sm);
    }

    .post-author {
      margin-bottom: var(--spacing-sm);
      color: var(--text-gray);
      font-size: 0.9rem;
    }

    .read-more {
      color: var(--primary-blue);
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      margin-top: var(--spacing-sm);
      transition: all 0.3s ease;
      cursor: pointer;
      
      &:hover {
        color: var(--dark-blue);
        text-decoration: underline;
        transform: translateX(5px);
      }
    }
    
    .post-card {
      h2 a {
        transition: color 0.3s ease;
        cursor: pointer;
      }
      
      &:hover h2 a {
        color: var(--primary-blue);
      }
    }

    .newsletter-section {
      padding: var(--spacing-xl) 0;
      background-color: var(--light-gray);
      text-align: center;
    }

    .newsletter-form {
      display: flex;
      gap: var(--spacing-sm);
      max-width: 500px;
      margin: var(--spacing-lg) auto 0;
      
      input {
        flex: 1;
        padding: var(--spacing-sm);
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
      }
    }
  `]
})
export class BlogListComponent implements OnInit {
  selectedCategory = 'Tous';
  email = '';
  posts: BlogPost[] = [];
  loading = false;
  error: string | null = null;

  categories = ['Tous', 'Azure', 'Certification tips', 'Case studies', 'Labs', 'Updates'];

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadBlogPosts();
  }

  loadBlogPosts() {
    this.loading = true;
    this.error = null;
    this.apiService.getBlogPosts().subscribe({
      next: (data) => {
        // Filtrer uniquement les articles publiés et valides (non null et avec category)
        this.posts = Array.isArray(data) 
          ? data.filter(post => post && post.category && post.published === true)
          : [];
        this.loading = false;
        
        // Extraire les catégories uniques des articles
        const uniqueCategories = new Set(this.posts.map(post => post.category).filter(Boolean));
        const allCategories = ['Tous', ...Array.from(uniqueCategories).sort()];
        this.categories = allCategories;
      },
      error: (err) => {
        console.error('Error loading blog posts:', err);
        this.error = 'Erreur lors du chargement des articles';
        this.loading = false;
        this.posts = [];
      }
    });
  }

  get filteredPosts() {
    // Filtrer les posts valides
    const validPosts = this.posts.filter(post => post && post.category);
    if (this.selectedCategory === 'Tous') {
      return validPosts;
    }
    return validPosts.filter(post => post.category === this.selectedCategory);
  }
}

