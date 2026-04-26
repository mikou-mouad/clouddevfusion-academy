# Tests API – Tests de positionnement & Bons d'examen

## Authentification

- **Login** : `POST /api/login` (form: email, password) → retourne `sessionId`.
- Les requêtes protégées doivent envoyer soit le **cookie** `PHPSESSID`, soit l’en-tête **`X-Session-Id`** (comme le frontend).
- Sans auth : **302** (redirection vers login).

## Récapitulatif des méthodes testées (curl + X-Session-Id)

### Tests de positionnement (ApiPlatform)

| Méthode | URL | Attendu | Testé |
|--------|-----|---------|--------|
| GET | `/api/placement_tests` | 200 | OK |
| GET | `/api/placement_tests/{id}` (ex. id=1) | 200 ou 404 | OK (404 si absent) |
| GET | `/api/placement_questions` | 200 | OK |
| GET | `/api/placement_answers` | 200 | OK |
| GET | `/api/placement_test_results` | 200 | OK |
| POST | `/api/placement_tests` | 201 | (nécessite `course` existant) |
| PUT | `/api/placement_tests/{id}` | 200 | (id existant) |
| DELETE | `/api/placement_tests/{id}` | 204 | (id existant) |
| POST | `/api/placement_questions` | 201 | (nécessite `placementTest`) |
| PUT/DELETE | `/api/placement_questions/{id}` | 200/204 | id existant |
| POST/PUT/DELETE | `/api/placement_answers` | 201/200/204 | id existant |
| POST | `/api/placement_test_results` | 201 | (soumission résultat) |

### Bons d'examen (Controller)

| Méthode | URL | Attendu | Testé |
|--------|-----|---------|--------|
| GET | `/api/exam_vouchers` | 200 | OK |
| GET | `/api/exam_vouchers/{id}` | 200 ou 404 | OK |
| POST | `/api/exam_vouchers` | 201 | OK |
| PUT | `/api/exam_vouchers/{id}` | 200 | OK |
| DELETE | `/api/exam_vouchers/{id}` | 200 | OK |

## Lancer les tests automatisés

```bash
cd backend
./test-placement-and-vouchers.sh http://localhost:8000
```

Le script :
1. Se connecte et récupère le `sessionId`.
2. Envoie **X-Session-Id** sur toutes les requêtes (comme le frontend).
3. Vérifie : GET list/collection, POST (voucher avec code unique), GET one, PUT, DELETE, et 404 sur ID inexistant.

## Si le frontend affiche encore une erreur

1. **Vérifier que le `sessionId` est bien stocké après login**  
   Dans AuthService, après succès du login : `sessionStorage.setItem('apiSessionId', response.sessionId)`.

2. **Vérifier que les requêtes envoient l’en-tête**  
   ApiService utilise `getHeaders()` qui ajoute `X-Session-Id` si présent dans `sessionStorage`.

3. **Tester en direct**  
   - Ouvrir l’app en `http://localhost:4200` (avec proxy vers 8000).  
   - Se connecter en admin.  
   - Ouvrir les outils dev (Network) et vérifier que les requêtes vers `/api/placement_tests` et `/api/exam_vouchers` ont bien l’en-tête `X-Session-Id` et retournent 200.

4. **Backend**  
   - Un seul firewall `main` pour tout `/api/` (login + session).  
   - `SessionIdHeaderListener` lit `X-Session-Id` et l’injecte en cookie pour la session.
