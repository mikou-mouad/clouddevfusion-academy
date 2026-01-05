import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Testimonial {
  id?: number;
  quote: string;
  author: string;
  role: string;
  company: string;
  rating: number;
  videoUrl?: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface ExamVoucher {
  id?: number;
  code: string;
  examCode: string;
  type: 'voucher-only' | 'training-voucher' | 'retake';
  price: string;
  validityPeriod: number;
  description?: string;
  bookingSteps?: string[];
  rescheduleRules?: string;
  redemptionInfo?: string;
  scheduleLocation?: string;
  idRequirements?: string;
  isActive: boolean;
  createdAt?: string;
  updatedAt?: string;
}

export interface Lab {
  id?: number;
  name: string;
  duration?: string;
}

export interface SyllabusModule {
  id?: number;
  title: string;
  description?: string;
  orderIndex?: number;
  labs: Lab[];
}

export interface Course {
  id?: number;
  title: string;
  code: string;
  level: string;
  duration: string;
  format: string;
  accessDelay?: string;
  price: number;
  role: string;
  product?: string;
  language: string;
  nextDate?: string;
  description?: string;
  certification?: string;
  popular?: boolean;
  objectives: string[];
  outcomes: string[];
  prerequisites: string[];
  targetRoles: string[];
  syllabus: SyllabusModule[];
  createdAt?: string;
  updatedAt?: string;
}

export interface HomeBanner {
  id?: number;
  logoPath?: string;
  kpi1Number?: string;
  kpi1Label?: string;
  kpi2Number?: string;
  kpi2Label?: string;
  kpi3Number?: string;
  kpi3Label?: string;
  active?: boolean;
  createdAt?: string;
  updatedAt?: string;
}

export interface AuditLog {
  id?: number;
  action: string;
  entityType: string;
  entityId?: number;
  entityTitle?: string;
  userEmail: string;
  username: string;
  changes?: any;
  description?: string;
  ipAddress?: string;
  createdAt: string;
}

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl = environment.apiUrl || 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    });
  }

  // Helper pour extraire les données d'une réponse API Platform (JSON-LD ou JSON)
  private extractCollection<T>(response: any): T[] {
    // Si c'est déjà un tableau, le retourner tel quel
    if (Array.isArray(response)) {
      return response;
    }
    // Si c'est un objet JSON-LD avec hydra:member
    if (response && response['hydra:member']) {
      return response['hydra:member'];
    }
    // Si c'est un objet avec une propriété membre
    if (response && response.member) {
      return response.member;
    }
    // Sinon retourner un tableau vide
    return [];
  }

  // ============================================
  // TESTIMONIALS
  // ============================================
  getTestimonials(): Observable<Testimonial[]> {
    return this.http.get<any>(`${this.apiUrl}/testimonials`, { headers: this.getHeaders() }).pipe(
      map(response => this.extractCollection<Testimonial>(response))
    );
  }

  getTestimonial(id: number): Observable<Testimonial> {
    return this.http.get<Testimonial>(`${this.apiUrl}/testimonials/${id}`, { headers: this.getHeaders() });
  }

  createTestimonial(testimonial: Testimonial): Observable<Testimonial> {
    return this.http.post<Testimonial>(`${this.apiUrl}/testimonials`, testimonial, { headers: this.getHeaders() });
  }

  updateTestimonial(id: number, testimonial: Testimonial): Observable<Testimonial> {
    return this.http.put<Testimonial>(`${this.apiUrl}/testimonials/${id}`, testimonial, { headers: this.getHeaders() });
  }

  deleteTestimonial(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/testimonials/${id}`, { headers: this.getHeaders() });
  }

  // ============================================
  // COURSES
  // ============================================
  getCourses(): Observable<Course[]> {
    return this.http.get<any>(`${this.apiUrl}/courses`, { headers: this.getHeaders() }).pipe(
      map(response => this.extractCollection<Course>(response))
    );
  }

  getCourse(id: number): Observable<Course> {
    return this.http.get<Course>(`${this.apiUrl}/courses/${id}`, { headers: this.getHeaders() });
  }

  createCourse(course: Course): Observable<Course> {
    return this.http.post<Course>(`${this.apiUrl}/courses`, course, { headers: this.getHeaders() });
  }

  updateCourse(id: number, course: Course): Observable<Course> {
    return this.http.put<Course>(`${this.apiUrl}/courses/${id}`, course, { 
      headers: this.getHeaders(),
      observe: 'body',
      responseType: 'json'
    });
  }

  deleteCourse(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/courses/${id}`, { headers: this.getHeaders() });
  }

  // ============================================
  // BLOG POSTS
  // ============================================
  getBlogPosts(): Observable<BlogPost[]> {
    return this.http.get<any>(`${this.apiUrl}/blog_posts`, { headers: this.getHeaders() }).pipe(
      map(response => this.extractCollection<BlogPost>(response))
    );
  }

  getBlogPost(id: number): Observable<BlogPost> {
    return this.http.get<BlogPost>(`${this.apiUrl}/blog_posts/${id}`, { headers: this.getHeaders() });
  }

  createBlogPost(blogPost: BlogPost): Observable<BlogPost> {
    return this.http.post<BlogPost>(`${this.apiUrl}/blog_posts`, blogPost, { headers: this.getHeaders() });
  }

  updateBlogPost(id: number, blogPost: BlogPost): Observable<BlogPost> {
    return this.http.put<BlogPost>(`${this.apiUrl}/blog_posts/${id}`, blogPost, { 
      headers: this.getHeaders(),
      observe: 'body',
      responseType: 'json'
    });
  }

  deleteBlogPost(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/blog_posts/${id}`, { headers: this.getHeaders() });
  }

  uploadImage(file: File): Observable<{ url: string }> {
    const formData = new FormData();
    formData.append('file', file);
    
    return this.http.post<{ url: string }>(`${this.apiUrl}/upload`, formData);
  }

  // ============================================
  // FAQ
  // ============================================
  getFaqs(): Observable<Faq[]> {
    return this.http.get<any>(`${this.apiUrl}/faqs`, { headers: this.getHeaders() }).pipe(
      map(response => this.extractCollection<Faq>(response))
    );
  }

  getFaq(id: number): Observable<Faq> {
    return this.http.get<Faq>(`${this.apiUrl}/faqs/${id}`, { headers: this.getHeaders() });
  }

  createFaq(faq: Faq): Observable<Faq> {
    return this.http.post<Faq>(`${this.apiUrl}/faqs`, faq, { headers: this.getHeaders() });
  }

  updateFaq(id: number, faq: Faq): Observable<Faq> {
    return this.http.put<Faq>(`${this.apiUrl}/faqs/${id}`, faq, { 
      headers: this.getHeaders(),
      observe: 'body',
      responseType: 'json'
    });
  }

  deleteFaq(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/faqs/${id}`, { headers: this.getHeaders() });
  }

  // ============================================
  // HOME BANNER
  // ============================================
  getHomeBanners(): Observable<HomeBanner[]> {
    return this.http.get<any>(`${this.apiUrl}/home_banners`, { headers: this.getHeaders() }).pipe(
      map(response => this.extractCollection<HomeBanner>(response))
    );
  }

  getHomeBanner(id: number): Observable<HomeBanner> {
    return this.http.get<HomeBanner>(`${this.apiUrl}/home_banners/${id}`, { headers: this.getHeaders() });
  }

  getActiveHomeBanner(): Observable<HomeBanner | null> {
    return this.getHomeBanners().pipe(
      map(banners => banners.find(b => b.active !== false) || banners[0] || null)
    );
  }

  createHomeBanner(banner: HomeBanner): Observable<HomeBanner> {
    return this.http.post<HomeBanner>(`${this.apiUrl}/home_banners`, banner, { headers: this.getHeaders() });
  }

  updateHomeBanner(id: number, banner: HomeBanner): Observable<HomeBanner> {
    return this.http.put<HomeBanner>(`${this.apiUrl}/home_banners/${id}`, banner, { headers: this.getHeaders() });
  }

  deleteHomeBanner(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/home_banners/${id}`, { headers: this.getHeaders() });
  }

  // ============================================
  // AUDIT LOGS (Super Admin uniquement)
  // ============================================
  getAuditLogs(filters?: { userEmail?: string; entityType?: string; action?: string; limit?: number }): Observable<AuditLog[]> {
    let url = `${this.apiUrl}/audit_logs`;
    const params: string[] = [];
    
    if (filters) {
      if (filters.userEmail) params.push(`userEmail=${encodeURIComponent(filters.userEmail)}`);
      if (filters.entityType) params.push(`entityType=${encodeURIComponent(filters.entityType)}`);
      if (filters.action) params.push(`action=${encodeURIComponent(filters.action)}`);
      if (filters.limit) params.push(`limit=${filters.limit}`);
      
      if (params.length > 0) {
        url += '?' + params.join('&');
      }
    }
    
    return this.http.get<any>(url, { headers: this.getHeaders() }).pipe(
      map(response => this.extractCollection<AuditLog>(response))
    );
  }

  getAuditLog(id: number): Observable<AuditLog> {
    return this.http.get<AuditLog>(`${this.apiUrl}/audit_logs/${id}`, { headers: this.getHeaders() });
  }

  // ============================================
  // CONTACTS
  // ============================================
  getContacts(): Observable<Contact[]> {
    return this.http.get<any>(`${this.apiUrl}/contacts`, { headers: this.getHeaders() }).pipe(
      map(response => this.extractCollection<Contact>(response))
    );
  }

  getContact(id: number): Observable<Contact> {
    return this.http.get<Contact>(`${this.apiUrl}/contacts/${id}`, { headers: this.getHeaders() });
  }

  createContact(contact: Contact): Observable<Contact> {
    return this.http.post<Contact>(`${this.apiUrl}/contacts`, contact, { headers: this.getHeaders() });
  }

  updateContact(id: number, contact: Contact): Observable<Contact> {
    return this.http.put<Contact>(`${this.apiUrl}/contacts/${id}`, contact, { 
      headers: this.getHeaders(),
      observe: 'body',
      responseType: 'json'
    });
  }

  deleteContact(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/contacts/${id}`, { headers: this.getHeaders() });
  }

  // ============================================
  // FILE UPLOAD
  // ============================================
  uploadVideo(file: File): Observable<{ success: boolean; url: string; filename: string; size: number; mimeType: string }> {
    const formData = new FormData();
    formData.append('video', file);
    
    return this.http.post<{ success: boolean; url: string; filename: string; size: number; mimeType: string }>(
      `${this.apiUrl}/upload/video`,
      formData
      // Pas besoin de Content-Type header, le navigateur le définit automatiquement pour FormData
    );
  }

  deleteVideo(filename: string): Observable<{ success: boolean }> {
    return this.http.delete<{ success: boolean }>(`${this.apiUrl}/upload/video/${filename}`, { headers: this.getHeaders() });
  }

  // ============================================
  // EXAM VOUCHERS
  // ============================================
  getExamVouchers(): Observable<ExamVoucher[]> {
    return this.http.get<any>(`${this.apiUrl}/exam_vouchers`, { headers: this.getHeaders() }).pipe(
      map(response => this.extractCollection<ExamVoucher>(response))
    );
  }

  getExamVoucher(id: number): Observable<ExamVoucher> {
    return this.http.get<ExamVoucher>(`${this.apiUrl}/exam_vouchers/${id}`, { headers: this.getHeaders() });
  }

  createExamVoucher(voucher: ExamVoucher): Observable<ExamVoucher> {
    return this.http.post<ExamVoucher>(`${this.apiUrl}/exam_vouchers`, voucher, { headers: this.getHeaders() });
  }

  updateExamVoucher(id: number, voucher: ExamVoucher): Observable<ExamVoucher> {
    return this.http.put<ExamVoucher>(`${this.apiUrl}/exam_vouchers/${id}`, voucher, { 
      headers: this.getHeaders(),
      observe: 'body',
      responseType: 'json'
    });
  }

  deleteExamVoucher(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/exam_vouchers/${id}`, { headers: this.getHeaders() });
  }
}

export interface BlogPost {
  id?: number;
  title: string;
  slug?: string;
  excerpt?: string;
  content: string;
  image?: string;
  category: string;
  author: string;
  readTime?: number;
  published?: boolean;
  createdAt?: string;
  updatedAt?: string;
}

export interface Faq {
  id?: number;
  question: string;
  answer: string;
  category: string;
  orderIndex?: number;
  published?: boolean;
  createdAt?: string;
  updatedAt?: string;
}

export interface Contact {
  id?: number;
  name: string;
  email: string;
  phone?: string;
  subject: string;
  message: string;
  read?: boolean;
  createdAt?: string;
  updatedAt?: string;
}

