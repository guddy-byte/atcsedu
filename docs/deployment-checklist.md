# Production Deployment Checklist

## Recommended Layout

- Frontend: `https://yourdomain.com`
- Backend API: `https://api.yourdomain.com`
- Frontend source: `C:\Users\USER\Herd\atcsedu`
- Backend source: `C:\Users\USER\Herd\atcsedu\atcsedu-api`

This layout matches the way the app is already configured:

- the Vue frontend reads `VITE_API_BASE_URL`
- the Laravel API reads `APP_URL`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, and `PAYSTACK_CALLBACK_URL`

## Frontend Deployment

1. Copy `C:\Users\USER\Herd\atcsedu\.env.production.example` to a real production env file and replace the API domain.
2. Build the frontend:

```powershell
npm run build
```

3. Upload the generated `dist` contents from `C:\Users\USER\Herd\atcsedu\dist` to the folder your main domain serves.
4. Make sure your hosting serves the frontend from the built files, not from the source project.

## Backend Deployment

1. Copy `C:\Users\USER\Herd\atcsedu\atcsedu-api\.env.production.example` to `.env` on the server and fill in:
   - database credentials
   - Paystack keys
   - mail credentials
   - exact frontend and API URLs
2. Point the API domain document root to:

```text
atcsedu-api/public
```

3. Install PHP dependencies on the server:

```powershell
composer install --no-dev --optimize-autoloader
```

4. Install backend asset dependencies and build assets:

```powershell
npm install
npm run build
```

5. Run the Laravel production setup:

```powershell
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Domain And DNS

1. Point `yourdomain.com` to the frontend hosting target.
2. Point `api.yourdomain.com` to the backend hosting target.
3. Enable SSL for both before opening the site to users.
4. Update `CORS_ALLOWED_ORIGINS` so it includes only your real frontend domains.

## Go-Live Safety Notes

- Keep `APP_DEBUG=false` in production.
- Keep `SEED_DEMO_STUDENT=false` in production so the demo account is not recreated.
- Do not expose the Laravel project root. Only the `public` directory should be web-accessible.
- If your hosting panel cannot point the API domain to `atcsedu-api/public`, stop there and adjust the hosting layout before going live.
