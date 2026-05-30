# Deploy production intranet under `/intranet`

This guide adds the intranet without touching the existing root website.

## Goal

- Frontend served at: `https://academy.clouddevfusion.com/intranet`
- Backend API served at: `https://academy.clouddevfusion.com/intranet/api`
- Existing production site at `/` remains unchanged.

## 1) Build frontend

On the server (or CI):

```bash
cd /path/to/CloudDev/intranet/front
npm ci
npm run build -- --configuration production
```

Angular output: `dist/front/browser`.

## 2) Run Symfony backend (separate PHP-FPM / vhost root)

Backend app root should be:

`/path/to/CloudDev/intranet/backend/public`

Keep it isolated from the main site backend/runtime.

## 3) Nginx safe routing (path-based)

Add ONLY these blocks inside the existing `server {}` for `academy.clouddevfusion.com`.

```nginx
# ---- Intranet frontend SPA under /intranet ----
location ^~ /intranet/ {
    alias /var/www/clouddev/intranet/front/dist/front/browser/;
    index index.html;
    try_files $uri $uri/ /intranet/index.html;
}

# Normalize /intranet (no trailing slash)
location = /intranet {
    return 301 /intranet/;
}

# ---- Intranet API under /intranet/api ----
# Option A: reverse proxy to dedicated intranet backend service
location ^~ /intranet/api/ {
    proxy_pass http://127.0.0.1:4100/api/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Notes:

- `^~ /intranet/` prevents overlap with `/`.
- `proxy_pass .../api/` rewrites `/intranet/api/*` to Symfony `/api/*`.
- Do not change existing root `location /` blocks.

## 4) Validate before reload

```bash
sudo nginx -t
```

If valid:

```bash
sudo systemctl reload nginx
```

## 5) Post-deploy smoke checks

- `https://academy.clouddevfusion.com/` (existing site still works)
- `https://academy.clouddevfusion.com/intranet/` (Angular app loads)
- `https://academy.clouddevfusion.com/intranet/api/health` returns `{"ok":true}`
- Login + dashboard + API actions (create/edit/archive formation)

## Rollback (safe)

- Remove only the `/intranet` Nginx blocks
- Reload Nginx
- Existing main site remains intact
