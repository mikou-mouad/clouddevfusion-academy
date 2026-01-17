import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
    selector: 'app-cookies',
    imports: [CommonModule],
    template: `
    <div class="legal-page">
      <div class="container">
        <h1>Politique des cookies</h1>
        <div class="legal-content">
          <section>
            <h2>1. Qu'est-ce qu'un cookie ?</h2>
            <p>Les cookies sont de petits fichiers texte stockés sur votre appareil...</p>
          </section>
          <section>
            <h2>2. Types de cookies utilisés</h2>
            <p>Nous utilisons différents types de cookies...</p>
          </section>
          <section>
            <h2>3. Gestion des préférences</h2>
            <p>Vous pouvez gérer vos préférences de cookies...</p>
          </section>
        </div>
      </div>
    </div>
  `,
    styles: [`
    .legal-page {
      padding: var(--spacing-xl) 0;
      min-height: calc(100vh - 200px);
    }

    h1 {
      color: var(--dark-blue);
      margin-bottom: var(--spacing-lg);
      text-align: center;
    }

    .legal-content {
      max-width: 800px;
      margin: 0 auto;
      background: var(--white);
      padding: var(--spacing-xl);
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      
      section {
        margin-bottom: var(--spacing-xl);
        
        h2 {
          color: var(--primary-blue);
          margin-bottom: var(--spacing-md);
        }
        
        p {
          color: var(--text-gray);
          line-height: 1.8;
        }
      }
    }
  `]
})
export class CookiesComponent {}

