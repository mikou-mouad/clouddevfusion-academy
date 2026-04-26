import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, BehaviorSubject, tap } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';

export interface User {
  id: number;
  email: string;
  username: string;
  roles: string[];
}

const SESSION_ID_KEY = 'apiSessionId';

export interface LoginResponse {
  success: boolean;
  message: string;
  user?: User;
  sessionId?: string;
  error?: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl || 'http://localhost:8000/api';
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    this.checkAuthStatus();
  }

  private getHeaders(): HttpHeaders {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    const sid = this.getSessionId();
    if (sid) headers['X-Session-Id'] = sid;
    return new HttpHeaders(headers);
  }

  checkAuthStatus(): void {
    // Ne pas vérifier l'auth si on est sur la page de login
    if (typeof window !== 'undefined' && window.location.pathname === '/admin/login') {
      return;
    }

    this.http.get<{ success: boolean; user?: User }>(`${this.apiUrl}/user`, {
      headers: this.getHeaders(),
      withCredentials: true
    }).subscribe({
      next: (response) => {
        if (response.success && response.user) {
          this.currentUserSubject.next(response.user);
        } else {
          this.currentUserSubject.next(null);
        }
      },
      error: () => {
        this.currentUserSubject.next(null);
      }
    });
  }

  login(email: string, password: string): Observable<LoginResponse> {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    return this.http.post<LoginResponse>(`${this.apiUrl}/login`, formData, {
      withCredentials: true,
      headers: {
        'Accept': 'application/json'
      }
    }).pipe(
      tap((response) => {
        if (response.success && response.user) {
          this.currentUserSubject.next(response.user);
          if (response.sessionId) {
            sessionStorage.setItem(SESSION_ID_KEY, response.sessionId);
          }
        }
      })
    );
  }

  logout(): Observable<any> {
    return this.http.post(`${this.apiUrl}/logout`, {}, {
      headers: this.getHeaders(),
      withCredentials: true
    }).pipe(
      tap(() => {
        sessionStorage.removeItem(SESSION_ID_KEY);
        this.currentUserSubject.next(null);
        this.router.navigate(['/admin/login']);
      })
    );
  }

  getSessionId(): string | null {
    return typeof sessionStorage !== 'undefined' ? sessionStorage.getItem(SESSION_ID_KEY) : null;
  }

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  isAuthenticated(): boolean {
    return this.currentUserSubject.value !== null;
  }

  hasRole(role: string): boolean {
    const user = this.currentUserSubject.value;
    if (!user) return false;
    return user.roles.includes(role);
  }

  isAdmin(): boolean {
    return this.hasRole('ROLE_ADMIN') || this.hasRole('ROLE_SUPER_ADMIN');
  }

  isSuperAdmin(): boolean {
    return this.hasRole('ROLE_SUPER_ADMIN');
  }
}
