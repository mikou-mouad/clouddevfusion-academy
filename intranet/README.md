# Intranet Etudiant (Angular + Symfony)

Ce dossier contient un intranet separe du site principal:

- `intranet/front`: application Angular
- `intranet/backend`: API Symfony

## Demarrage local

### 1) Backend Symfony (port 4100)

```bash
cd /Users/oussama/Desktop/CloudDev/intranet/backend
symfony server:start --port=4100 -d
```

Si tu n'utilises pas Symfony CLI:

```bash
cd /Users/oussama/Desktop/CloudDev/intranet/backend
php -S 127.0.0.1:4100 -t public
```

### 2) Front Angular (port 4201)

```bash
cd /Users/oussama/Desktop/CloudDev/intranet/front
npm install
npm start -- --port 4201
```

Puis ouvre `http://127.0.0.1:4201`.

## Compte de test

- Email: `student@clouddev.local`
- Mot de passe: `student123`

## API exposee

- `GET /api/health`
- `POST /api/auth/login`
- `GET /api/me/dashboard` (Authorization: `Bearer <token>`)

## Notes techniques

- Les donnees sont centralisees dans `intranet/backend/src/Data/IntranetData.php`.
- Le CORS pour Angular est gere par `intranet/backend/src/EventSubscriber/CorsSubscriber.php`.
- Prochaine etape: brancher ces endpoints a la vraie BDD (students, enrollments, sessions, planning).
