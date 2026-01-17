import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive, RouterOutlet, Router } from '@angular/router';
import { AuthService, User } from '../../core/services/auth.service';
import { Subscription } from 'rxjs';

@Component({
    selector: 'app-admin-layout',
    imports: [CommonModule, RouterLink, RouterLinkActive, RouterOutlet],
    templateUrl: './admin-layout.component.html',
    styleUrls: ['./admin-layout.component.scss']
})
export class AdminLayoutComponent implements OnInit, OnDestroy {
  currentUser: User | null = null;
  private userSubscription?: Subscription;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit() {
    // S'abonner aux changements d'utilisateur
    this.userSubscription = this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
      if (!user) {
        // Vérifier l'auth de manière asynchrone pour éviter les erreurs de connexion
        this.authService.checkAuthStatus();
        // Attendre un peu avant de rediriger pour laisser le temps à la vérification
        setTimeout(() => {
          if (!this.authService.isAuthenticated()) {
            this.router.navigate(['/admin/login']);
          }
        }, 500);
      }
    });

    // Vérifier l'auth initiale
    const currentUser = this.authService.getCurrentUser();
    if (!currentUser) {
      this.authService.checkAuthStatus();
    } else {
      this.currentUser = currentUser;
    }
  }

  ngOnDestroy() {
    if (this.userSubscription) {
      this.userSubscription.unsubscribe();
    }
  }

  logout() {
    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/admin/login']);
      },
      error: () => {
        // Même en cas d'erreur, rediriger vers login
        this.router.navigate(['/admin/login']);
      }
    });
  }

  isSuperAdmin(): boolean {
    return this.authService.isSuperAdmin();
  }
}

