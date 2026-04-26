import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService, BlogPost } from '../../../core/services/api.service';
import { QuillModule } from 'ngx-quill';
import Delta, { type Op } from 'quill-delta';

@Component({
    selector: 'app-admin-blog',
    imports: [CommonModule, FormsModule, QuillModule],
    templateUrl: './blog.component.html',
    styleUrls: ['./blog.component.scss']
})
export class AdminBlogComponent implements OnInit {
  blogPosts: BlogPost[] = [];
  showModal = false;
  editingPost: BlogPost | null = null;
  formData: BlogPost = {
    title: '',
    slug: '',
    excerpt: '',
    content: '',
    image: '',
    category: 'Azure',
    author: '',
    readTime: 5,
    published: false
  };
  loading = false;
  error: string | null = null;
  successMessage: string | null = null;
  uploadingImage = false;
  imagePreview: string | null = null;
  selectedFile: File | null = null;

  categories = ['Azure', 'Certification tips', 'Case studies', 'Labs', 'Updates'];

  // Configuration Quill Editor : préserver mise en page au collage (espaces, alignement)
  quillModules = this.buildQuillModules();

  private buildQuillModules(): Record<string, unknown> {
    const alignMatcher = (node: Node, delta: Delta): Delta => {
      const el = node as HTMLElement;
      const align = (el.style?.textAlign || (el.getAttribute && el.getAttribute('align')) || '').toLowerCase();
      if (!align || !['left', 'center', 'right', 'justify'].includes(align)) return delta;
      const ops: Op[] = delta.ops.map((op: Op) => {
        if (typeof op.insert === 'string' && op.insert.endsWith('\n')) {
          return { insert: op.insert, attributes: { ...(op.attributes || {}), align } } as Op;
        }
        return op;
      });
      return new Delta(ops);
    };
    return {
      toolbar: [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ 'header': 1 }, { 'header': 2 }, { 'header': 3 }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'script': 'sub'}, { 'script': 'super' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'font': [] }],
        [{ 'align': [] }],
        ['clean'],
        ['link', 'image', 'video']
      ],
      clipboard: {
        matchers: [
          ['P', alignMatcher],
          ['DIV', alignMatcher],
          ['H1', alignMatcher],
          ['H2', alignMatcher],
          ['H3', alignMatcher],
          ['H4', alignMatcher],
          ['H5', alignMatcher],
          ['H6', alignMatcher]
        ]
      }
    };
  }

  get blogPostsList(): BlogPost[] {
    return Array.isArray(this.blogPosts) 
      ? this.blogPosts.filter(p => p && p.category) 
      : [];
  }

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadBlogPosts();
  }

  loadBlogPosts() {
    this.loading = true;
    this.error = null;
    this.apiService.getBlogPosts().subscribe({
      next: (data) => {
        this.blogPosts = Array.isArray(data) ? data.filter(p => p && p.category) : [];
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading blog posts:', err);
        this.error = 'Erreur lors du chargement des articles';
        this.loading = false;
        this.blogPosts = [];
      }
    });
  }

  openAddModal() {
    this.editingPost = null;
    this.error = null;
    this.successMessage = null;
    this.imagePreview = null;
    this.selectedFile = null;
    this.formData = {
      title: '',
      slug: '',
      excerpt: '',
      content: '',
      image: '',
      category: 'Azure',
      author: '',
      readTime: 5,
      published: true
    };
    this.showModal = true;
  }

  editPost(index: number) {
    this.editingPost = this.blogPosts[index];
    this.formData = { ...this.blogPosts[index] };
    this.imagePreview = this.formData.image || null;
    this.selectedFile = null;
    this.error = null;
    this.successMessage = null;
    this.showModal = true;
  }

  deletePost(index: number) {
    const post = this.blogPosts[index];
    if (!post?.id || Number(post.id) < 1) {
      alert('Impossible de supprimer cet article');
      return;
    }

    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
      this.loading = true;
      this.apiService.deleteBlogPost(post.id!).subscribe({
        next: () => {
          this.blogPosts.splice(index, 1);
          this.loading = false;
        },
        error: (err) => {
          console.error('Error deleting blog post:', err);
          alert('Erreur lors de la suppression');
          this.loading = false;
        }
      });
    }
  }

  onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      this.selectedFile = input.files[0];
      
      // Afficher un aperçu de l'image
      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.imagePreview = e.target.result;
      };
      reader.readAsDataURL(this.selectedFile);
    }
  }

  async uploadImage(): Promise<string | null> {
    if (!this.selectedFile) {
      return this.formData.image || null;
    }

    this.uploadingImage = true;
    try {
      // Pour l'instant, on convertit l'image en base64
      // Dans un vrai projet, vous devriez uploader l'image sur un serveur
      const reader = new FileReader();
      return new Promise((resolve, reject) => {
        reader.onload = (e: any) => {
          const base64Image = e.target.result;
          this.uploadingImage = false;
          resolve(base64Image);
        };
        reader.onerror = () => {
          this.uploadingImage = false;
          reject('Erreur lors de la lecture de l\'image');
        };
        reader.readAsDataURL(this.selectedFile!);
      });
    } catch (error) {
      this.uploadingImage = false;
      console.error('Error uploading image:', error);
      return null;
    }
  }

  generateSlug(title: string): string {
    if (!title) {
      return '';
    }
    return title
      .toLowerCase()
      .trim()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '') // Supprimer les accents
      .replace(/[^a-z0-9-]+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }

  /**
   * Convertit les URLs blob: du contenu HTML en base64 pour que les images
   * collées depuis Word (ou insérées) persistent après enregistrement.
   */
  private async convertBlobUrlsToBase64InContent(html: string): Promise<string> {
    if (!html || !html.includes('blob:')) {
      return html;
    }
    // src="blob:..." ou src='blob:...'
    const imgRegex = /<img[^>]+src=["'](blob:[^"']+)["'][^>]*>/gi;
    const matches = [...html.matchAll(imgRegex)];
    let result = html;
    for (const match of matches) {
      const blobUrl = match[1];
      try {
        const base64 = await this.fetchBlobAsBase64(blobUrl);
        if (base64) {
          result = result.replace(match[0], match[0].replace(blobUrl, base64));
        }
      } catch (e) {
        console.warn('Impossible de convertir l\'image blob en base64:', e);
      }
    }
    return result;
  }

  private fetchBlobAsBase64(blobUrl: string): Promise<string> {
    return fetch(blobUrl)
      .then((r) => r.blob())
      .then(
        (blob) =>
          new Promise<string>((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result as string);
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(blob);
          })
      );
  }

  async savePost() {
    // Validation
    if (!this.formData.title || this.formData.title.trim().length < 3) {
      this.error = 'Le titre doit contenir au moins 3 caractères';
      return;
    }
    if (!this.formData.content || this.formData.content.trim().length < 50) {
      this.error = 'Le contenu doit contenir au moins 50 caractères';
      return;
    }
    if (!this.formData.author) {
      this.error = 'L\'auteur est requis';
      return;
    }

    this.loading = true;
    this.error = null;
    this.successMessage = null;

    try {
      // Upload de l'image si une nouvelle image est sélectionnée
      if (this.selectedFile) {
        const imageUrl = await this.uploadImage();
        if (imageUrl) {
          this.formData.image = imageUrl;
        }
      }

      // Générer le slug si nécessaire
      let slug = this.formData.slug?.trim();
      if (!slug || slug === '') {
        slug = this.generateSlug(this.formData.title);
      }
      
      // S'assurer que le slug n'est pas vide
      if (!slug || slug === '') {
        this.error = 'Impossible de générer un slug valide à partir du titre';
        this.loading = false;
        return;
      }

      // Convertir les images blob: (collées depuis Word) en base64 pour qu'elles persistent
      const contentToSave = await this.convertBlobUrlsToBase64InContent(this.formData.content.trim());

      const postData: BlogPost = {
        title: this.formData.title.trim(),
        slug: slug,
        excerpt: this.formData.excerpt?.trim() || undefined,
        content: contentToSave,
        image: this.formData.image || undefined,
        category: this.formData.category,
        author: this.formData.author.trim(),
        readTime: this.formData.readTime || 5,
        published: this.formData.published || false
      };

      const operation = this.editingPost?.id
        ? this.apiService.updateBlogPost(this.editingPost.id, postData)
        : this.apiService.createBlogPost(postData);

      operation.subscribe({
        next: (saved) => {
          if (saved && saved.id != null && Number(saved.id) > 0) {
            if (this.editingPost?.id) {
              const idx = this.blogPosts.findIndex(p => p.id === this.editingPost?.id);
              if (idx !== -1) this.blogPosts[idx] = saved;
            } else {
              this.blogPosts.push(saved);
            }
          } else {
            this.loadBlogPosts();
          }
          this.loading = false;
          this.successMessage = this.editingPost?.id ? 'Article modifié avec succès !' : 'Article ajouté avec succès !';
          setTimeout(() => this.closeModal(), 1000);
        },
        error: (err) => {
          if (err.status === 200 && err.error) {
            try {
              const body = typeof err.error === 'string' ? err.error : JSON.stringify(err.error);
              const saved = JSON.parse(body) as BlogPost;
              if (saved?.id != null && Number(saved.id) > 0) {
                if (this.editingPost?.id) {
                  const idx = this.blogPosts.findIndex(p => p.id === this.editingPost?.id);
                  if (idx !== -1) this.blogPosts[idx] = saved;
                } else {
                  this.blogPosts.push(saved);
                }
              } else {
                this.loadBlogPosts();
              }
            } catch {
              this.loadBlogPosts();
            }
            this.loading = false;
            this.successMessage = 'Article enregistré.';
            setTimeout(() => this.closeModal(), 1000);
            return;
          }
          console.error('Error saving blog post:', err);
          console.error('Error status:', err.status);
          console.error('Error URL:', err.url);
          console.error('Error details:', err.error);
          let errorMessage = 'Erreur lors de l\'enregistrement';
          if (err.status === 404) {
            errorMessage = 'Endpoint API non trouvé. Vérifiez que le serveur backend est démarré et que l\'endpoint /api/blog_posts existe.';
          } else if (err.error) {
            if (err.error.violations && Array.isArray(err.error.violations)) {
              const violations = err.error.violations.map((v: any) => v.message).join(', ');
              errorMessage = `Erreurs de validation : ${violations}`;
            } else if (err.error['hydra:description']) {
              errorMessage = err.error['hydra:description'];
            } else if (err.error.message) {
              errorMessage = err.error.message;
            } else if (err.error.detail) {
              errorMessage = err.error.detail;
            } else if (typeof err.error === 'string') {
              errorMessage = err.error;
            }
          } else if (err.message) {
            errorMessage = err.message;
          }
          
          this.error = errorMessage;
          this.loading = false;
        }
      });
    } catch (error) {
      this.error = 'Erreur lors de l\'upload de l\'image';
      this.loading = false;
    }
  }

  closeModal() {
    this.showModal = false;
    this.editingPost = null;
    this.error = null;
    this.successMessage = null;
    this.imagePreview = null;
    this.selectedFile = null;
  }

  // Les méthodes d'insertion sont maintenant gérées par Quill Editor
  // L'éditeur Quill permet d'insérer des images directement via la barre d'outils
}
