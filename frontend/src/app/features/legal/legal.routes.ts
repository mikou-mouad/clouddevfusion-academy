import { Routes } from '@angular/router';

export const LEGAL_ROUTES: Routes = [
  {
    path: 'privacy',
    loadComponent: () => import('./privacy/privacy.component').then(m => m.PrivacyComponent)
  },
  {
    path: 'terms',
    loadComponent: () => import('./terms/terms.component').then(m => m.TermsComponent)
  },
  {
    path: 'cookies',
    loadComponent: () => import('./cookies/cookies.component').then(m => m.CookiesComponent)
  },
  {
    path: 'accessibility',
    loadComponent: () => import('./accessibility/accessibility.component').then(m => m.AccessibilityComponent)
  },
  {
    path: '',
    redirectTo: 'privacy',
    pathMatch: 'full'
  }
];

