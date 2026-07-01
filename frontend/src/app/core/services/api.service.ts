import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpResponse } from '@angular/common/http';
import { Observable, map, catchError, of, throwError, switchMap } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Testimonial {
  id?: number;
  quote?: string;
  author?: string;
  role?: string;
  company?: string;
  rating?: number;
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

export interface PlacementAnswer {
  id?: number;
  text: string;
  score: number;
  isCorrect: boolean;
  orderIndex: number;
}

export interface PlacementQuestion {
  id?: number;
  question: string;
  explanation?: string;
  orderIndex: number;
  answers: PlacementAnswer[];
}

export interface PlacementTest {
  id?: number;
  course?: Course | string;
  title: string;
  description?: string;
  passingScore: number;
  timeLimit: number; // en minutes
  isActive: boolean;
  questions: PlacementQuestion[];
  createdAt?: string;
  updatedAt?: string;
}

export interface PlacementTestResult {
  id?: number;
  placementTest?: PlacementTest;
  userEmail?: string;
  userName?: string;
  userPhone?: string;
  score: number;
  totalQuestions: number;
  correctAnswers: number;
  answeredQuestions?: number;
  passed: boolean;
  answers?: { [questionId: number]: number }; // questionId -> answerId
  completedAt?: string;
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
  placementTest?: PlacementTest;
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
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    const sessionId = typeof sessionStorage !== 'undefined' ? sessionStorage.getItem('apiSessionId') : null;
    if (sessionId) {
      headers['X-Session-Id'] = sessionId;
    }
    return new HttpHeaders(headers);
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
    return this.http.get(`${this.apiUrl}/blog_posts`, {
      headers: this.getHeaders(),
      responseType: 'text',
      withCredentials: true
    }).pipe(
      map(body => {
        if (!body?.trim()) return [];
        try {
          const response = JSON.parse(body);
          const rawList = this.extractCollection<any>(response);
          const normalized: BlogPost[] = [];
          for (const raw of rawList) {
            const post = this.normalizeBlogPostResponse(raw);
            if (post) normalized.push(post);
          }
          return normalized;
        } catch {
          return [];
        }
      }),
      catchError(() => of([]))
    );
  }

  getBlogPost(id: number): Observable<BlogPost> {
    return this.http.get<BlogPost>(`${this.apiUrl}/blog_posts/${id}`, { headers: this.getHeaders(), withCredentials: true });
  }

  private buildBlogPostCreatePayload(blogPost: BlogPost): Record<string, unknown> {
    const p: Record<string, unknown> = {
      title: blogPost.title ?? '',
      slug: blogPost.slug ?? (blogPost.title ? blogPost.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') : ''),
      content: blogPost.content ?? '',
      category: blogPost.category ?? 'Azure',
      author: blogPost.author ?? '',
      published: !!blogPost.published
    };
    if (blogPost['excerpt'] != null && blogPost['excerpt'] !== '') p['excerpt'] = blogPost['excerpt'];
    if (blogPost['image'] != null && blogPost['image'] !== '') p['image'] = blogPost['image'];
    const rt = blogPost['readTime'];
    p['readTime'] = typeof rt === 'number' && !isNaN(rt) ? rt : 5;
    return p;
  }

  createBlogPost(blogPost: BlogPost): Observable<BlogPost | null> {
    const payload = this.buildBlogPostCreatePayload(blogPost);
    return this.http.post(`${this.apiUrl}/blog_posts`, payload, {
      headers: this.getHeaders(),
      observe: 'response',
      responseType: 'text',
      withCredentials: true
    }).pipe(
      switchMap((res: HttpResponse<string>) => {
        const saved = this.parseBlogPostBody(res);
        if (saved?.id != null && Number(saved.id) > 0) return of(saved);
        if (res.status >= 200 && res.status < 300) {
          const location = res.headers.get('Location') || res.headers.get('Content-Location');
          const id = location ? location.replace(/.*\//, '').replace(/\?.*$/, '').trim() : null;
          if (id && /^\d+$/.test(id)) {
            const numId = parseInt(id, 10);
            return this.http.get(`${this.apiUrl}/blog_posts/${id}`, {
              headers: this.getHeaders(),
              responseType: 'text'
            }).pipe(
              map(body => {
                try {
                  const raw = JSON.parse(body?.trim() || '{}');
                  const normalized = this.normalizeBlogPostResponse(raw);
                  if (normalized) return normalized;
                  return { id: numId, title: 'Article créé', slug: '', category: 'Azure', author: '', published: false } as BlogPost;
                } catch {
                  return { id: numId, title: 'Article créé', slug: '', category: 'Azure', author: '', published: false } as BlogPost;
                }
              }),
              catchError(() => of({ id: numId, title: 'Article créé', slug: '', category: 'Azure', author: '', published: false } as BlogPost))
            );
          }
        }
        return of(null);
      }),
      catchError((err) => {
        if (err.status >= 200 && err.status < 300 && err.error != null) {
          try {
            const body = typeof err.error === 'string' ? err.error : JSON.stringify(err.error);
            const parsed = this.normalizeBlogPostResponse(JSON.parse(body));
            return of(parsed);
          } catch { }
        }
        return throwError(() => err);
      })
    );
  }

  updateBlogPost(id: number, blogPost: BlogPost): Observable<BlogPost | null> {
    return this.http.put(`${this.apiUrl}/blog_posts/${id}`, blogPost, {
      headers: this.getHeaders(),
      observe: 'response',
      responseType: 'text'
    }).pipe(
      map((res: HttpResponse<string>) => this.parseBlogPostBody(res)),
      catchError((err) => {
        if (err.status >= 200 && err.status < 300 && err.error != null) {
          try {
            const body = typeof err.error === 'string' ? err.error : JSON.stringify(err.error);
            const parsed = this.normalizeBlogPostResponse(JSON.parse(body));
            return of(parsed);
          } catch { }
        }
        return throwError(() => err);
      })
    );
  }

  private parseBlogPostBody(res: HttpResponse<string>): BlogPost | null {
    if (res.status < 200 || res.status >= 300) return null;
    const body = res.body?.trim();
    if (!body) return null;
    try {
      const parsed = JSON.parse(body);
      const raw = Array.isArray(parsed) && parsed.length > 0
        ? parsed[0]
        : parsed?.data ?? parsed?.result ?? parsed;
      return this.normalizeBlogPostResponse(raw);
    } catch {
      return null;
    }
  }

  private normalizeBlogPostResponse(raw: any): BlogPost | null {
    if (!raw || typeof raw !== 'object') return null;
    let id: number | undefined;
    if (typeof raw.id === 'number') id = raw.id;
    else if (typeof raw.id === 'string') id = parseInt(raw.id, 10);
    else if (typeof raw['@id'] === 'string') {
      const match = raw['@id'].match(/\/(\d+)$/);
      if (match) id = parseInt(match[1], 10);
    }
    const title = (raw.title ?? raw['title'] ?? '').toString().trim() || (raw.slug ?? raw['slug'] ?? '').toString().trim() || 'Sans titre';
    return {
      id: isNaN(id as number) ? undefined : id,
      title: String(title),
      slug: raw.slug ?? raw['slug'] ?? raw.title ?? '',
      excerpt: raw.excerpt ?? raw['excerpt'],
      content: raw.content ?? raw['content'] ?? '',
      image: raw.image ?? raw['image'],
      category: raw.category ?? raw['category'] ?? 'Azure',
      author: raw.author ?? raw['author'] ?? '',
      readTime: raw.readTime ?? raw['readTime'],
      published: raw.published ?? raw['published'] ?? false,
      createdAt: raw.createdAt ?? raw['createdAt'],
      updatedAt: raw.updatedAt ?? raw['updatedAt']
    };
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
    return this.http.get<any>(`${this.apiUrl}/contacts`, { headers: this.getHeaders(), withCredentials: true }).pipe(
      map(response => this.extractCollection<Contact>(response))
    );
  }

  getContact(id: number): Observable<Contact> {
    return this.http.get<Contact>(`${this.apiUrl}/contacts/${id}`, { headers: this.getHeaders(), withCredentials: true });
  }

  createContact(contact: Contact): Observable<Contact> {
    return this.http.post<Contact>(`${this.apiUrl}/contacts`, contact, { headers: this.getHeaders() });
  }

  updateContact(id: number, contact: Contact): Observable<Contact> {
    return this.http.put<Contact>(`${this.apiUrl}/contacts/${id}`, contact, { 
      headers: this.getHeaders(),
      withCredentials: true,
      observe: 'body',
      responseType: 'json'
    });
  }

  deleteContact(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/contacts/${id}`, { headers: this.getHeaders(), withCredentials: true });
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

  // ============================================
  // PLACEMENT TESTS
  // ============================================
  private normalizePlacementAnswer(raw: any): PlacementAnswer {
    return {
      ...raw,
      score: typeof raw?.score === 'string' ? parseFloat(raw.score) : (raw?.score ?? 0),
      isCorrect: raw?.isCorrect ?? raw?.correct ?? false,
      orderIndex: raw?.orderIndex ?? 0,
    };
  }

  private normalizePlacementQuestion(raw: any): PlacementQuestion {
    return {
      ...raw,
      orderIndex: raw?.orderIndex ?? 0,
      answers: Array.isArray(raw?.answers)
        ? raw.answers.map((answer: any) => this.normalizePlacementAnswer(answer))
        : [],
    };
  }

  private normalizePlacementTest(raw: any): PlacementTest {
    const questions = Array.isArray(raw?.questions)
      ? raw.questions
          .map((question: any) => typeof question === 'object' ? this.normalizePlacementQuestion(question) : question)
          .sort((a: PlacementQuestion, b: PlacementQuestion) => (a.orderIndex ?? 0) - (b.orderIndex ?? 0))
      : [];

    return {
      ...raw,
      isActive: raw?.isActive ?? raw?.active ?? false,
      questions,
    };
  }

  private extractPlacementCourseId(test: PlacementTest | any): number | null {
    const course = test?.course;
    if (!course) return null;
    if (typeof course === 'string') {
      const match = course.match(/\/(\d+)$/);
      return match ? parseInt(match[1], 10) : null;
    }
    return course.id ?? null;
  }

  getPlacementTests(): Observable<PlacementTest[]> {
    return this.http.get<any>(`${this.apiUrl}/placement_tests`, { 
      headers: this.getHeaders(),
      withCredentials: true
    }).pipe(
      map(response => this.extractCollection<any>(response).map(test => this.normalizePlacementTest(test)))
    );
  }

  getPlacementTest(id: number): Observable<PlacementTest> {
    return this.http.get<PlacementTest>(`${this.apiUrl}/placement_tests/${id}`, { 
      headers: this.getHeaders(),
      withCredentials: true
    }).pipe(
      map(test => this.normalizePlacementTest(test))
    );
  }

  getPlacementTestByCourse(courseId: number): Observable<PlacementTest | null> {
    const courseIri = `/api/courses/${courseId}`;
    return this.http.get<any>(`${this.apiUrl}/placement_tests?course=${encodeURIComponent(courseIri)}`, { headers: this.getHeaders() }).pipe(
      map(response => {
        const collection = this.extractCollection<any>(response)
          .map(test => this.normalizePlacementTest(test))
          .filter(test => this.extractPlacementCourseId(test) === courseId);
        return collection.length > 0 ? collection[0] : null;
      })
    );
  }

  createPlacementTest(test: Partial<PlacementTest>): Observable<PlacementTest> {
    return this.http.post<PlacementTest>(`${this.apiUrl}/placement_tests`, test, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  updatePlacementTest(id: number, test: Partial<PlacementTest>): Observable<PlacementTest> {
    return this.http.put<PlacementTest>(`${this.apiUrl}/placement_tests/${id}`, test, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  deletePlacementTest(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/placement_tests/${id}`, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  // Questions
  createPlacementQuestion(question: Partial<PlacementQuestion>): Observable<PlacementQuestion> {
    return this.http.post<PlacementQuestion>(`${this.apiUrl}/placement_questions`, question, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  updatePlacementQuestion(id: number, question: Partial<PlacementQuestion>): Observable<PlacementQuestion> {
    return this.http.put<PlacementQuestion>(`${this.apiUrl}/placement_questions/${id}`, question, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  deletePlacementQuestion(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/placement_questions/${id}`, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  // Answers
  getPlacementAnswer(id: number): Observable<PlacementAnswer> {
    return this.http.get<PlacementAnswer>(`${this.apiUrl}/placement_answers/${id}`, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  createPlacementAnswer(answer: PlacementAnswer): Observable<PlacementAnswer> {
    const answerData: any = {
      ...answer,
      score: answer.score?.toString() || '0.00'
    };
    return this.http.post<PlacementAnswer>(`${this.apiUrl}/placement_answers`, answerData, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  updatePlacementAnswer(id: number, answer: PlacementAnswer): Observable<PlacementAnswer> {
    const answerData: any = {
      ...answer,
      score: answer.score?.toString() || '0.00'
    };
    return this.http.put<PlacementAnswer>(`${this.apiUrl}/placement_answers/${id}`, answerData, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  deletePlacementAnswer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/placement_answers/${id}`, { 
      headers: this.getHeaders(),
      withCredentials: true
    });
  }

  // Submit test result
  submitPlacementTestResult(result: PlacementTestResult): Observable<PlacementTestResult> {
    return this.http.post<PlacementTestResult>(`${this.apiUrl}/placement_test_results`, result, { headers: this.getHeaders() });
  }

  getPlacementTestResults(): Observable<PlacementTestResult[]> {
    return this.http.get<any>(`${this.apiUrl}/placement_test_results`, {
      headers: this.getHeaders(),
      withCredentials: true
    }).pipe(
      map(response => this.extractCollection<PlacementTestResult>(response))
    );
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

