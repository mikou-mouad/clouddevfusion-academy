import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
    selector: 'app-registration',
    imports: [CommonModule, RouterLink],
    templateUrl: './registration.component.html',
    styleUrls: ['./registration.component.scss']
})
export class RegistrationComponent {
  steps = [
    {
      title: 'Choisissez votre formation',
      description: 'Parcourez notre catalogue et sélectionnez la formation qui correspond à vos besoins et à vos objectifs de certification.'
    },
    {
      title: 'Vérifiez les prérequis',
      description: 'Assurez-vous de remplir les prérequis nécessaires pour suivre la formation et réussir votre certification.'
    },
    {
      title: 'Remplissez le formulaire',
      description: 'Complétez le formulaire d\'inscription avec vos informations personnelles et professionnelles.'
    },
    {
      title: 'Validez votre inscription',
      description: 'Confirmez votre inscription et recevez toutes les informations nécessaires pour préparer votre formation.'
    }
  ];

  cpfSteps = [
    {
      title: 'Créez votre compte CPF',
      description: 'Connectez-vous sur moncompteformation.gouv.fr et vérifiez votre solde disponible.',
      link: 'https://www.moncompteformation.gouv.fr',
      linkText: 'Accéder à moncompteformation.gouv.fr'
    },
    {
      title: 'Recherchez la formation',
      description: 'Utilisez le code de formation ou le nom de la formation Azure que vous souhaitez suivre.',
      link: null,
      linkText: null
    },
    {
      title: 'Demandez un devis',
      description: 'Remplissez le formulaire de demande de devis directement sur la plateforme CPF.',
      link: null,
      linkText: null
    },
    {
      title: 'Attendez la validation',
      description: 'Nous vous recontacterons sous 48h pour finaliser votre inscription et planifier votre formation.',
      link: null,
      linkText: null
    }
  ];

  expectations = [
    {
      title: 'Email de confirmation',
      description: 'Vous recevrez un email de confirmation avec tous les détails de votre inscription et les prochaines étapes.'
    },
    {
      title: 'Accès à la plateforme',
      description: 'Accès à la plateforme d\'apprentissage 48h avant le début de la formation pour vous familiariser avec l\'environnement.'
    },
    {
      title: 'Matériel pédagogique',
      description: 'Tous les supports de cours, guides pratiques et ressources complémentaires seront mis à votre disposition.'
    },
    {
      title: 'Invitation à la session',
      description: 'Vous recevrez une invitation détaillée avec le lien de connexion et toutes les informations pratiques pour la session.'
    }
  ];
}

