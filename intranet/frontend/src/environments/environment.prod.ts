export const environment = {
  production: true,
  // O2Switch rewrite for /intranet/api is unreliable; Symfony front controller path is stable.
  apiBaseUrl: '/intranet/backend/public/index.php/intranet/api',
};
