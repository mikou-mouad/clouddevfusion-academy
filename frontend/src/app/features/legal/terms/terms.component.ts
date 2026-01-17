import { Component } from '@angular/core';


@Component({
    selector: 'app-terms',
    imports: [],
    template: `
    <div class="legal-page">
      <div class="container">
        <h1>Conditions générales</h1>
        <div class="legal-content">
          <section>
            <h2>1. Conditions de service</h2>
            <p>En utilisant nos services, vous acceptez les conditions suivantes...</p>
          </section>
          <section>
            <h2>2. Conditions de formation</h2>
            <p>Les conditions spécifiques aux formations incluent...</p>
          </section>
          <section>
            <h2>3. Annulation et remboursement</h2>
            <p>Politique d'annulation et de remboursement...</p>
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
export class TermsComponent {}

