import { Routes } from '@angular/router';

export const TRAINING_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () => import('./training-description/training-description.component').then(m => m.TrainingDescriptionComponent)
  }
];

