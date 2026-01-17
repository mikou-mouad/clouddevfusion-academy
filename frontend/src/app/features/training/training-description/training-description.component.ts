import { Component } from '@angular/core';

import { RouterLink } from '@angular/router';

@Component({
    selector: 'app-training-description',
    imports: [RouterLink],
    templateUrl: './training-description.component.html',
    styleUrls: ['./training-description.component.scss']
})
export class TrainingDescriptionComponent {
  trainerCertifications = [
    {
      code: 'AZ-104',
      name: 'Azure Administrator Associate',
      description: 'Expertise en administration et gestion d\'infrastructures Azure'
    },
    {
      code: 'AZ-204',
      name: 'Azure Developer Associate',
      description: 'Maîtrise du développement d\'applications cloud sur Azure'
    },
    {
      code: 'AZ-305',
      name: 'Azure Solutions Architect Expert',
      description: 'Architecture de solutions cloud complexes et scalables'
    },
    {
      code: 'AZ-500',
      name: 'Azure Security Engineer',
      description: 'Sécurité et conformité des solutions Azure'
    },
    {
      code: 'AZ-400',
      name: 'Azure DevOps Engineer Expert',
      description: 'Intégration continue et déploiement sur Azure'
    }
  ];

  deliveryFormats = [
    {
      icon: '💻',
      name: 'En ligne (Distanciel)',
      description: 'Formation interactive en ligne avec accès aux labs Azure',
      features: [
        'Sessions en direct avec formateur',
        'Accès aux laboratoires Azure 24/7',
        'Enregistrements disponibles',
        'Support technique dédié'
      ]
    },
    {
      icon: '🏢',
      name: 'Présentiel',
      description: 'Formation en salle avec équipements fournis',
      features: [
        'Salle équipée avec matériel',
        'Accès direct aux labs Azure',
        'Interaction directe avec formateur',
        'Réseautage avec autres participants'
      ]
    },
    {
      icon: '🔄',
      name: 'Hybride (Mixte)',
      description: 'Combinaison présentiel et distanciel pour flexibilité maximale',
      features: [
        'Sessions en présentiel et en ligne',
        'Flexibilité dans l\'apprentissage',
        'Meilleur des deux formats',
        'Adaptation à vos contraintes'
      ]
    }
  ];

  qualityKPIs = [
    {
      value: '95%',
      label: 'Taux de réussite aux certifications',
      description: '95% de nos étudiants réussissent leur certification Microsoft du premier coup'
    },
    {
      value: '4.8/5',
      label: 'Satisfaction moyenne',
      description: 'Note moyenne de satisfaction de nos formations'
    },
    {
      value: '500+',
      label: 'Étudiants formés',
      description: 'Plus de 500 professionnels formés avec succès'
    },
    {
      value: '98%',
      label: 'Taux de recommandation',
      description: '98% de nos étudiants recommandent nos formations'
    }
  ];

  partners = [
    { name: 'Microsoft', logo: 'assets/Microsoft-logo.png' },
    { name: 'Azure', logo: null },
    { name: 'GitHub', logo: null },
    { name: 'Docker', logo: null }
  ];

  // Purpose section data
  microsoftPartnershipLevel = 'Gold Partner';
  azureFocus = 'Microsoft Azure & Cloud Solutions';

  // Targets section
  targets = [
    {
      icon: '🏢',
      title: 'Entreprises',
      description: 'Formations sur-mesure pour vos équipes IT, transformation digitale, montée en compétences Azure à l\'échelle.'
    },
    {
      icon: '👥',
      title: 'Groupes',
      description: 'Sessions de groupe pour professionnels, promotion de compétences collectives, échanges et networking.'
    },
    {
      icon: '👤',
      title: 'Particuliers (Private)',
      description: 'Coaching personnalisé, formation one-to-one adaptée à vos objectifs et à votre rythme d\'apprentissage.'
    }
  ];
}
