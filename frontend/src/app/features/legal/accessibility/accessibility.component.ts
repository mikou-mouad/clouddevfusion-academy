import { Component } from '@angular/core';


@Component({
    selector: 'app-accessibility',
    imports: [],
    template: `
    <div class="accessibility-page">
      <div class="container">
        <div class="page-header">
          <h1>Documentation d'accessibilité</h1>
          <p class="subtitle">Organisme de formation : CloudDev Fusion</p>
        </div>

        <div class="accessibility-content">
          <!-- Section 1: Politique d'accessibilité -->
          <section class="accessibility-section">
            <div class="section-header">
              <div class="icon-wrapper">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
              </div>
              <h2>1. Politique d'accessibilité</h2>
            </div>
            <div class="section-body">
              <p class="lead">CloudDev Fusion s'engage à accueillir et accompagner toute personne en situation de handicap afin de lui permettre d'accéder à ses formations dans les meilleures conditions possibles.</p>
              <p>Un référent handicap est désigné au sein de l'organisme et demeure disponible pour étudier chaque demande spécifique.</p>
            </div>
          </section>

          <!-- Section 2: Informations communiquées aux stagiaires -->
          <section class="accessibility-section">
            <div class="section-header">
              <div class="icon-wrapper">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                  <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
              </div>
              <h2>2. Informations communiquées aux stagiaires</h2>
            </div>
            <div class="section-body">
              <p>Lors de l'inscription, les stagiaires peuvent signaler toute contrainte ou tout besoin particulier via la fiche de renseignement stagiaire.</p>
              <p class="adaptations-title"><strong>Les adaptations possibles incluent :</strong></p>
              <ul class="adaptations-list">
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  <span>Aménagements des supports (version contrastée, fichiers audio)</span>
                </li>
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  <span>Ajustement du rythme de la formation</span>
                </li>
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  <span>Assistance technique (outils numériques adaptés)</span>
                </li>
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  <span>Soutien pédagogique complémentaire si nécessaire</span>
                </li>
              </ul>
            </div>
          </section>

          <!-- Section 3: Partenaires et relais -->
          <section class="accessibility-section">
            <div class="section-header">
              <div class="icon-wrapper">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
              </div>
              <h2>3. Partenaires et relais</h2>
            </div>
            <div class="section-body">
              <p>Pour garantir une prise en charge adaptée, CloudDev Fusion travaille en lien avec les acteurs suivants :</p>
              <ul class="partners-list">
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                  </svg>
                  <div>
                    <strong>AGEFIPH</strong>
                    <a href="https://www.agefiph.fr" target="_blank" rel="noopener noreferrer">www.agefiph.fr</a>
                  </div>
                </li>
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                  </svg>
                  <div>
                    <strong>CAP EMPLOI</strong>
                    <span>Réseau national</span>
                  </div>
                </li>
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                  </svg>
                  <div>
                    <strong>MDPH</strong>
                    <span>Maison Départementale des Personnes Handicapées</span>
                  </div>
                </li>
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                  </svg>
                  <div>
                    <strong>Associations locales spécialisées</strong>
                  </div>
                </li>
              </ul>
            </div>
          </section>

          <!-- Section 4: Documents associés -->
          <section class="accessibility-section">
            <div class="section-header">
              <div class="icon-wrapper">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                  <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
              </div>
              <h2>4. Documents associés</h2>
            </div>
            <div class="section-body">
              <ul class="documents-list">
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                  </svg>
                  <span>Fiche de renseignement stagiaire (rubrique « contraintes particulières »)</span>
                </li>
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                  </svg>
                  <span>Procédure interne accessibilité</span>
                </li>
                <li>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                  </svg>
                  <span>Liste des partenaires référents</span>
                </li>
              </ul>
            </div>
          </section>

          <!-- Footer de la page -->
          <div class="page-footer">
            <p class="document-info">CloudDev Fusion – Documentation d'accessibilité – Document Qualiopi</p>
          </div>
        </div>
      </div>
    </div>
  `,
    styles: [`
    .accessibility-page {
      padding: 60px 0;
      min-height: calc(100vh - 200px);
      background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
    }

    .page-header {
      text-align: center;
      margin-bottom: 50px;
    }

    .page-header h1 {
      color: var(--dark-blue, #003d7a);
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 10px;
      line-height: 1.2;
    }

    .page-header .subtitle {
      color: var(--text-gray, #666);
      font-size: 1.1rem;
      font-weight: 500;
    }

    .accessibility-content {
      max-width: 900px;
      margin: 0 auto;
    }

    .accessibility-section {
      background: white;
      border-radius: 12px;
      padding: 35px;
      margin-bottom: 30px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .accessibility-section:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    .section-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
      padding-bottom: 20px;
      border-bottom: 2px solid #e8ecf1;
    }

    .icon-wrapper {
      width: 56px;
      height: 56px;
      background: linear-gradient(135deg, #0066CC 0%, #4d9eff 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      flex-shrink: 0;
    }

    .section-header h2 {
      color: var(--dark-blue, #003d7a);
      font-size: 1.75rem;
      font-weight: 600;
      margin: 0;
    }

    .section-body {
      color: var(--text-gray, #555);
      line-height: 1.8;
    }

    .section-body p {
      margin-bottom: 15px;
      font-size: 1.05rem;
    }

    .section-body .lead {
      font-size: 1.15rem;
      font-weight: 500;
      color: var(--dark-blue, #003d7a);
      margin-bottom: 20px;
    }

    .adaptations-title {
      margin-top: 25px;
      margin-bottom: 15px;
      color: var(--dark-blue, #003d7a);
    }

    .adaptations-list,
    .partners-list,
    .documents-list {
      list-style: none;
      padding: 0;
      margin: 20px 0;
    }

    .adaptations-list li,
    .documents-list li {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .adaptations-list li:last-child,
    .documents-list li:last-child {
      border-bottom: none;
    }

    .adaptations-list li svg,
    .documents-list li svg {
      color: #0066CC;
      flex-shrink: 0;
      margin-top: 2px;
    }

    .partners-list li {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px;
      margin-bottom: 12px;
      background: #f8f9fa;
      border-radius: 8px;
      border-left: 4px solid #0066CC;
      transition: background 0.2s ease;
    }

    .partners-list li:hover {
      background: #f0f4f8;
    }

    .partners-list li svg {
      color: #0066CC;
      flex-shrink: 0;
    }

    .partners-list li div {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .partners-list li strong {
      color: var(--dark-blue, #003d7a);
      font-size: 1.05rem;
    }

    .partners-list li a {
      color: #0066CC;
      text-decoration: none;
      font-size: 0.95rem;
      transition: color 0.2s ease;
    }

    .partners-list li a:hover {
      color: #004d99;
      text-decoration: underline;
    }

    .partners-list li span {
      color: var(--text-gray, #666);
      font-size: 0.95rem;
    }

    .documents-list li span {
      color: var(--text-gray, #555);
      font-size: 1rem;
    }

    .page-footer {
      margin-top: 40px;
      padding-top: 30px;
      border-top: 2px solid #e8ecf1;
      text-align: center;
    }

    .page-footer .document-info {
      color: var(--text-gray, #888);
      font-size: 0.9rem;
      font-style: italic;
      margin: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .accessibility-page {
        padding: 30px 0;
      }

      .page-header h1 {
        font-size: 2rem;
      }

      .accessibility-section {
        padding: 25px 20px;
      }

      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }

      .section-header h2 {
        font-size: 1.5rem;
      }

      .partners-list li {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  `]
})
export class AccessibilityComponent {}

