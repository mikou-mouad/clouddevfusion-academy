# Backend Symfony - CloudDev Fusion API

API REST pour la plateforme de formation CloudDev Fusion.

## Prérequis

- PHP >= 8.1
- Composer
- MySQL/MariaDB ou PostgreSQL
- Symfony CLI (optionnel)

## Installation

1. Installer les dépendances :
```bash
cd backend
composer install
```

2. Configurer la base de données dans `.env` :
```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/clouddev?serverVersion=8.0&charset=utf8mb4"
```

3. Créer la base de données :
```bash
php bin/console doctrine:database:create
```

4. Créer les migrations :
```bash
php bin/console make:migration
```

5. Exécuter les migrations :
```bash
php bin/console doctrine:migrations:migrate
```

## Démarrage du serveur

```bash
symfony server:start
# ou
php -S localhost:8000 -t public
```

L'API sera accessible sur `http://localhost:8000/api`

## Endpoints API

### Témoignages
- `GET /api/testimonials` - Liste des témoignages
- `GET /api/testimonials/{id}` - Détails d'un témoignage
- `POST /api/testimonials` - Créer un témoignage
- `PUT /api/testimonials/{id}` - Modifier un témoignage
- `DELETE /api/testimonials/{id}` - Supprimer un témoignage

### Formations
- `GET /api/courses` - Liste des formations
- `GET /api/courses/{id}` - Détails d'une formation
- `POST /api/courses` - Créer une formation
- `PUT /api/courses/{id}` - Modifier une formation
- `DELETE /api/courses/{id}` - Supprimer une formation

## Structure des entités

### Testimonial
- id (int)
- quote (text)
- author (string)
- role (string)
- company (string)
- rating (int, 1-5)
- createdAt (datetime)
- updatedAt (datetime)

### Course
- id (int)
- title (string)
- code (string)
- level (string)
- duration (string)
- format (string)
- price (decimal)
- role (string)
- product (string)
- language (string)
- nextDate (date)
- description (text)
- certification (string)
- popular (boolean)
- objectives (json)
- outcomes (json)
- prerequisites (json)
- targetRoles (json)
- syllabus (relation avec SyllabusModule)
- createdAt (datetime)
- updatedAt (datetime)

## CORS

Le CORS est configuré pour autoriser les requêtes depuis `http://localhost:4200` (Angular dev server).

Pour la production, modifier `config/packages/nelmio_cors.yaml`.

