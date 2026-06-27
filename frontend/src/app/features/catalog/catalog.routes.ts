import { Routes } from '@angular/router';

export const CATALOG_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./catalog-list/catalog-list.component').then(m => m.CatalogListComponent)
  },
  {
    path: ':code',
    loadComponent: () => import('./course-detail/course-detail.component').then(m => m.CourseDetailComponent)
  }
];

