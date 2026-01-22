import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    redirectTo: 'home',
    pathMatch: 'full'
  },
  {
    path: 'home',
    loadComponent: () => import('./features/home/home.component').then(m => m.HomeComponent)
  },
  {
    path: 'training',
    loadChildren: () => import('./features/training/training.routes').then(m => m.TRAINING_ROUTES)
  },
  {
    path: 'catalog',
    loadChildren: () => import('./features/catalog/catalog.routes').then(m => m.CATALOG_ROUTES)
  },
  {
    path: 'testimonials',
    loadComponent: () => import('./features/testimonials/testimonials.component').then(m => m.TestimonialsComponent)
  },
  {
    path: 'trainers',
    loadComponent: () => import('./features/trainers/trainers.component').then(m => m.TrainersComponent)
  },
  {
    path: 'registration',
    loadComponent: () => import('./features/registration/registration.component').then(m => m.RegistrationComponent)
  },
  {
    path: 'blog',
    loadChildren: () => import('./features/blog/blog.routes').then(m => m.BLOG_ROUTES)
  },
  {
    path: 'contact',
    loadComponent: () => import('./features/contact/contact.component').then(m => m.ContactComponent)
  },
  {
    path: 'faq',
    loadComponent: () => import('./features/faq/faq.component').then(m => m.FaqComponent)
  },
  {
    path: 'exam-vouchers',
    loadComponent: () => import('./features/exam-vouchers/exam-vouchers.component').then(m => m.ExamVouchersComponent)
  },
  {
    path: 'placement-test/:courseId',
    loadComponent: () => import('./features/placement-test/placement-test.component').then(m => m.PlacementTestComponent)
  },
  {
    path: 'legal',
    loadChildren: () => import('./features/legal/legal.routes').then(m => m.LEGAL_ROUTES)
  },
  {
    path: 'admin',
    loadChildren: () => import('./features/admin/admin.routes').then(m => m.ADMIN_ROUTES)
  },
  {
    path: '**',
    redirectTo: 'home'
  }
];

