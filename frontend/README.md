#  Frontend

Frontend Angular pour KubeLaunch by CloudDevFusion - Plateforme de formation Azure et Kubernetes.

## Structure du projet

```
frontend/
├── src/
│   ├── app/
│   │   ├── core/               # Composants et services partagés
│   │   │   └── components/
│   │   │       ├── header/
│   │   │       └── footer/
│   │   ├── features/           # Modules fonctionnels
│   │   │   ├── home/           # Page d'accueil
│   │   │   ├── training/       # Description des formations
│   │   │   ├── catalog/        # Catalogue de formations
│   │   │   ├── testimonials/   # Témoignages
│   │   │   ├── registration/   # Processus d'inscription
│   │   │   ├── blog/           # Blog et articles
│   │   │   ├── contact/        # Formulaire de contact
│   │   │   ├── faq/            # Questions fréquentes
│   │   │   ├── legal/          # Pages légales
│   │   │   └── admin/          # Back office
│   │   ├── app.component.ts    # Composant racine
│   │   └── app.routes.ts       # Routes principales
│   ├── assets/                 # Images, logos, etc.
│   ├── styles.scss             # Styles globaux
│   ├── index.html
│   └── main.ts
├── angular.json
├── package.json
└── tsconfig.json
```

## Modules implémentés (Phase 1)

### Frontend Public
-  Home - Page d'accueil
-  Training Description - Description CloudDev Fusion Training
-  Catalog - Catalogue de formations avec filtres
-  Course Detail - Page détaillée d'un cours
-  Testimonials - Témoignages et vidéos
-  Registration - Processus d'inscription
-  Blog - Articles avec catégories
-  Contact - Formulaire de contact complet
-  FAQ - Questions fréquentes
-  Legal - Pages légales (Privacy, Terms, Cookies, Accessibility)

### Back Office (Admin)
-  Dashboard - Tableau de bord
-  Trainings Management - Gestion des formations
-  Courses Management - Gestion du catalogue
-  Testimonials Management - Gestion des témoignages
-  Blog Management - Gestion du blog
-  Contacts Management - Gestion des contacts
-  FAQ Management - Gestion de la FAQ

## Installation

```bash
cd frontend
npm install
```

## Développement

```bash
npm start
# ou
ng serve
```

L'application sera accessible sur `http://localhost:4200`

## Build

```bash
npm run build
```

## Technologies utilisées

- Angular 17+ (Standalone Components)
- TypeScript
- SCSS
- RxJS

