# CloudAcademy by CloudDevFusion

Plateforme de formation Azure et Kubernetes - Projet Angular + Symfony

## Structure du projet

```
CloudDev/
├── frontend/          # Application Angular
└── backend/           # API Symfony (à créer)
```

## Phase 1 - Modules implémentés

### Frontend (Angular)

#### Modules Publics
- **Home** - Page d'accueil avec présentation
- **Training Description** - Description CloudDev Fusion Training
  - Qui sommes-nous
  - Publics cibles (Entreprise, Groups, Private)
  - Mission
  - Certifications formateurs
  - Modèle d'enseignement (labs-first)
  - Formats de livraison
  - KPIs qualité
  - Partenariats et logos
- **Catalog** - Catalogue de formations
  - Filtres (Rôle, Produit, Niveau, Format, Langue, Prix, Date)
  - Page de détail de cours
  - Objectifs, résultats, rôles ciblés, prérequis
  - Syllabus détaillé par module avec liste de labs
  - Liens vers références Microsoft
  - Éligibilité financement (CPF/OPCO)
- **Testimonials** - Témoignages
  - Vidéos d'étudiants et clients entreprises
- **Registration** - Processus d'inscription
  - Guide étape par étape
  - Instructions CPF
  - À quoi s'attendre après inscription
- **Blog** - Blog
  - Catégories (Azure, Certification tips, Case studies, Labs, Updates)
  - Articles 600-1200 mots
  - Diagrams, blocs de code, liens vers cours
  - Bio auteur, date de publication, temps de lecture estimé
  - Lead magnets (checklists, guides)
  - Newsletter hebdomadaire
- **Contact** - Formulaire de contact
  - Champs complets (nom, email, téléphone, entreprise, rôle, sujet, etc.)
  - Routage (sales, support, partnerships)
  - Upload de fichiers (RFP)
  - Checkbox consentement, notice de confidentialité
  - Autres canaux (téléphone, email, adresse, réseaux sociaux)
- **FAQ** - Questions fréquentes
  - Prérequis, formats, accès labs, enregistrements, langue, financement, factures, annulations, accessibilité, planification examens, certificats
- **Legal** - Pages légales
  - Conditions générales et conditions de contrat de formation
  - Politique de confidentialité et droits RGPD
  - Politique des cookies et préférences
  - Déclaration d'accessibilité
  - Informations légales entreprise (nom, SIREN/SIRET, TVA, adresse)
  - Notices de marque et badges partenaires Microsoft

#### Back Office (Admin)
- **Dashboard** - Tableau de bord avec statistiques
- **Trainings Management** - Gestion des formations
- **Courses Management** - Gestion du catalogue
- **Testimonials Management** - Gestion des témoignages
- **Blog Management** - Gestion du blog
- **Contacts Management** - Gestion des contacts
- **FAQ Management** - Gestion de la FAQ

## Démarrage rapide

### Frontend

```bash
cd frontend
npm install
npm start
```

L'application sera accessible sur `http://localhost:4200`

### Backend (à créer)

```bash
cd backend
# Installation Symfony et configuration
```

## Technologies

- **Frontend**: Angular 17+ (Standalone Components), TypeScript, SCSS
- **Backend**: Symfony (à implémenter)

