import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService, BlogPost, Course } from '../../../core/services/api.service';

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
    private apiService: ApiService
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

  formatContent(content: string | undefined): string {
    if (!content) return '';
    const normalizedSpaces = this.normalizeHardSpaces(content);
    // Normalise les pseudo-listes "1. ..." / "- ..." collées depuis éditeurs
    // pour préserver l'indentation correcte côté affichage public.
    return this.normalizePseudoLists(normalizedSpaces);
  }

  private normalizeHardSpaces(html: string): string {
    return html
      // HTML entities
      .replace(/&nbsp;|&#160;/gi, ' ')
      // Unicode NBSP
      .replace(/\u00A0/g, ' ');
  }

  private normalizePseudoLists(html: string): string {
    if (typeof window === 'undefined' || !('DOMParser' in window)) {
      return html;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
    const root = doc.body.firstElementChild as HTMLElement | null;
    if (!root) {
      return html;
    }

    this.splitParagraphsContainingListLines(doc, root);

    const children = Array.from(root.children);
    let i = 0;

    const isNumbered = (text: string): boolean => /^\s*\d+\.\s+/.test(text);
    const isBulleted = (text: string): boolean => /^\s*[-–•]\s+/.test(text);
    const stripMarker = (text: string): string =>
      text.replace(/^\s*(\d+\.\s+|[-–•]\s+)/, '').trim();

    while (i < children.length) {
      const node = children[i] as HTMLElement;
      const tag = node.tagName.toLowerCase();
      const text = node.textContent?.trim() ?? '';

      if (tag !== 'p' || !text) {
        i++;
        continue;
      }

      const makeOrdered = isNumbered(text);
      const makeUnordered = !makeOrdered && isBulleted(text);
      if (!makeOrdered && !makeUnordered) {
        i++;
        continue;
      }

      const list = doc.createElement(makeOrdered ? 'ol' : 'ul');
      let cursor = i;

      while (cursor < children.length) {
        const current = children[cursor] as HTMLElement;
        const currentTag = current.tagName.toLowerCase();
        const currentText = current.textContent?.trim() ?? '';
        const matches = makeOrdered ? isNumbered(currentText) : isBulleted(currentText);

        if (currentTag !== 'p' || !currentText || !matches) {
          break;
        }

        const li = doc.createElement('li');
        li.innerHTML = stripMarker(current.innerHTML);
        list.appendChild(li);
        current.remove();
        cursor++;
      }

      const insertBefore = root.children[i] ?? null;
      root.insertBefore(list, insertBefore);

      // Recalcule après mutation
      i++;
      while (i < root.children.length && ['ol', 'ul'].includes(root.children[i].tagName.toLowerCase())) {
        i++;
      }
    }

    return root.innerHTML;
  }

  private splitParagraphsContainingListLines(doc: Document, root: HTMLElement): void {
    const paragraphs = Array.from(root.querySelectorAll(':scope > p'));

    const startsLikeList = (text: string): boolean => /^\s*(\d+\.\s+|[-–•]\s+)/.test(text);

    for (const p of paragraphs) {
      const html = p.innerHTML;
      if (!/<br\s*\/?>/i.test(html)) {
        continue;
      }

      const parts = html
        .split(/<br\s*\/?>/i)
        .map(part => part.trim())
        .filter(Boolean);

      // On ne découpe que si on détecte des lignes de type liste.
      if (parts.length < 2 || !parts.some(part => startsLikeList(part))) {
        continue;
      }

      const fragment = doc.createDocumentFragment();
      for (const part of parts) {
        const np = doc.createElement('p');
        np.innerHTML = part;
        fragment.appendChild(np);
      }

      p.replaceWith(fragment);
    }
  }
}

