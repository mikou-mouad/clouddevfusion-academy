import { Component } from '@angular/core';

import { RouterLink } from '@angular/router';

@Component({
    selector: 'app-trainers',
    imports: [RouterLink],
    templateUrl: './trainers.component.html',
    styleUrls: ['./trainers.component.scss']
})
export class TrainersComponent {
  trainers = [
    {
      name: 'Jean Dupont',
      role: 'Senior Azure Architect',
      certifications: ['AZ-305', 'AZ-104', 'AZ-500'],
      experience: '15 ans',
      bio: 'Expert Azure avec plus de 15 ans d\'expérience dans l\'architecture de solutions cloud. Formateur certifié Microsoft depuis 8 ans.',
      specialties: ['Azure Infrastructure', 'Azure Security', 'Cloud Architecture'],
      image: null
    },
    {
      name: 'Marie Martin',
      role: 'Azure DevOps Expert',
      certifications: ['AZ-400', 'AZ-204', 'AZ-104'],
      experience: '12 ans',
      bio: 'Spécialiste DevOps et développement cloud. Passionnée par l\'automatisation et les bonnes pratiques CI/CD.',
      specialties: ['Azure DevOps', 'CI/CD', 'Infrastructure as Code'],
      image: null
    },
    {
      name: 'Pierre Durand',
      role: 'Azure Developer & Trainer',
      certifications: ['AZ-204', 'AZ-900', 'AZ-104'],
      experience: '10 ans',
      bio: 'Développeur cloud expérimenté et formateur passionné. Expert en développement d\'applications Azure natives.',
      specialties: ['Azure Development', 'API Management', 'Serverless'],
      image: null
    },
    {
      name: 'Sophie Bernard',
      role: 'Azure Security Specialist',
      certifications: ['AZ-500', 'AZ-104', 'SC-300'],
      experience: '13 ans',
      bio: 'Spécialiste en sécurité cloud Azure avec une expertise approfondie en conformité et gouvernance.',
      specialties: ['Azure Security', 'Compliance', 'Identity Management'],
      image: null
    }
  ];
}

