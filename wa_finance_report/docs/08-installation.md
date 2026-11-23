# Installation

Persyaratan: PHP 8.3+, Composer, Node.js, npm, dan MySQL 8+.

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Buat database MySQL `wa_finance_report`, lalu isi `DB_USERNAME` dan `DB_PASSWORD` yang valid di `.env`.

Set `SUPER_ADMIN_PASSWORD` dan opsional `DEMO_USER_PASSWORD`, kemudian:

```bash
php artisan migrate --seed
npm run build
composer run dev
```

Password development tidak memiliki default di source code.

Production harus menjalankan Laravel scheduler agar status subscription diperbarui:

```cron
* * * * * cd /path/to/wa_finance_report && php artisan schedule:run >> /dev/null 2>&1
```

Command expiry dapat dijalankan manual dengan:

```bash
php artisan subscriptions:expire
```
