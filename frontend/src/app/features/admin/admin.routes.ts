import { Routes } from '@angular/router';
import { authGuard, superAdminGuard } from '../../core/guards/auth.guard';

export const ADMIN_ROUTES: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./login/login.component').then(m => m.AdminLoginComponent)
  },
  {
    path: '',
    loadComponent: () => import('./admin-layout.component').then(m => m.AdminLayoutComponent),
    canActivate: [authGuard],
    children: [
      {
        path: '',
        redirectTo: 'dashboard',
        pathMatch: 'full'
      },
      {
        path: 'dashboard',
        loadComponent: () => import('./dashboard/dashboard.component').then(m => m.DashboardComponent)
      },
      {
        path: 'trainings',
        loadComponent: () => import('./trainings/trainings.component').then(m => m.TrainingsComponent)
      },
      {
        path: 'courses',
        loadComponent: () => import('./courses/courses.component').then(m => m.CoursesComponent)
      },
      {
        path: 'testimonials',
        loadComponent: () => import('./testimonials/testimonials.component').then(m => m.AdminTestimonialsComponent)
      },
      {
        path: 'blog',
        loadComponent: () => import('./blog/blog.component').then(m => m.AdminBlogComponent)
      },
      {
        path: 'contacts',
        loadComponent: () => import('./contacts/contacts.component').then(m => m.ContactsComponent)
      },
      {
        path: 'faq',
        loadComponent: () => import('./faq/faq.component').then(m => m.AdminFaqComponent)
      },
      {
        path: 'exam-vouchers',
        loadComponent: () => import('./exam-vouchers/exam-vouchers.component').then(m => m.AdminExamVouchersComponent)
      },
      {
        path: 'home-banner',
        loadComponent: () => import('./home-banner/home-banner.component').then(m => m.AdminHomeBannerComponent)
      },
    {
        path: 'audit-logs',
        loadComponent: () => import('./audit-logs/audit-logs.component').then(m => m.AdminAuditLogsComponent),
        canActivate: [superAdminGuard]
    },
    {
        path: 'placement-tests',
        loadComponent: () => import('./placement-tests/placement-tests.component').then(m => m.PlacementTestsComponent),
        canActivate: [authGuard]
    }
    ]
  }
];

