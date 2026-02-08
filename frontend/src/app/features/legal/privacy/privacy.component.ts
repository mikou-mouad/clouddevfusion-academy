import { Component } from '@angular/core';


@Component({
    selector: 'app-privacy',
    imports: [],
    template: `
    <div class="legal-page">
      <div class="container">
        <h1>Politique de confidentialité</h1>
        <div class="legal-content">
          <section>
            <h2>1. Collecte des données</h2>
            <p>Nous collectons les données personnelles que vous nous fournissez directement...</p>
          </section>
          <section>
            <h2>2. Utilisation des données</h2>
            <p>Vos données personnelles sont utilisées pour...</p>
          </section>
          <section>
            <h2>3. Vos droits RGPD</h2>
            <p>Conformément au RGPD, vous disposez des droits suivants...</p>
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
export class PrivacyComponent {}

