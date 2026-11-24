# SPPG MART

SPPG MART is an Indonesian food-procurement marketplace connecting SPPG kitchens with suppliers. The Laravel application includes a responsive storefront, buyer-verified product ratings, delivery tracking, order and stock management, supplier verification, admin product moderation and account blocking, private NIB/payment documents, chat, and a versioned JSON API.

## Requirements

- PHP 8.2 or newer and Composer
- Node.js and npm
- SQLite (default) or another database supported by Laravel

## Local setup (Windows PowerShell)

```powershell
Copy-Item .env.example .env
New-Item -ItemType File -Force database\database.sqlite
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

In another terminal, run `npm run dev` while developing frontend assets. Configure database connection values in `.env` before migrating if you are not using SQLite.

The local/testing seeder creates one local administrator account:

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@sppgmart.test` | `password123` |

Sample buyers, suppliers, and products are not seeded, so the catalog starts clean. Categories are seeded as a starting taxonomy. Demo accounts are deliberately not seeded outside `local` and `testing`; change the local administrator password before using the database with real data.

## Configuration

- Set `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, and the correct `APP_URL` for deployment.
- `JWT_SECRET` is optional; when empty, the API signs tokens with `APP_KEY`. Keep both secrets private and stable.
- Configure `MIDTRANS_SERVER_KEY` and `MIDTRANS_IS_PRODUCTION` to enable dynamic QRIS payments.
- Cloudflare Turnstile is optional for login and registration. Set both `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` to enable it; leave both empty to use the built-in arithmetic CAPTCHA during local development.
- Password reset email uses Laravel's configured mailer. Set a production mail transport in `.env`.
- Region selection uses the public `wilayah.id` service. Responses are cached by the application.
- Private NIB, payment-proof, and delivery-proof files use the local private disk. Public product images and supplier logos use the public disk; run `php artisan storage:link` to serve those public uploads.

## API

The versioned API is under `/api/v1`. Registration and login are rate limited. Authenticated endpoints require `Authorization: Bearer <token>`.

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`
- `GET /api/v1/products`, `GET /api/v1/categories`, `GET /api/v1/suppliers`
- `GET /api/v1/orders`, `POST /api/v1/orders`

Supplier catalogs are public only after an administrator approves the supplier's NIB and document. API order items must come from one supplier per order; orders reserve stock within a database transaction.

## Validation

```powershell
php artisan test
vendor\bin\pint --test
npm run build
```
