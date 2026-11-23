# API v1

Base path: `/api/v1`. Seluruh response menggunakan JSON envelope:

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": {},
  "meta": {}
}
```

Error memakai `success: false`, `message`, dan `errors` tanpa stack trace. Nilai uang
dikirim sebagai string desimal dua digit agar tidak kehilangan presisi.

## Authentication

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/auth/login` | Membuat Sanctum token untuk user, account, dan paket yang aktif |
| POST | `/auth/logout` | Mencabut token yang sedang digunakan |
| GET | `/auth/user` | Profil user, active account, dan abilities token |
| GET | `/account` | Detail active account |
| GET | `/account/subscription` | Subscription dan entitlement paket aktif |

Contoh login:

```json
{
  "email": "owner@example.com",
  "password": "secret",
  "device_name": "n8n production",
  "abilities": ["reports:read", "integrations:write"],
  "expires_at": "2026-12-31"
}
```

Token dikirim pada request berikutnya dengan `Authorization: Bearer <token>`.
Token juga dapat dibuat dan dicabut dari menu **API Tokens**. Nilai token hanya
ditampilkan satu kali ketika dibuat.

## Abilities

- `transactions:read`, `transactions:write`
- `reports:read`
- `financial-accounts:read`, `financial-accounts:write`
- `categories:read`, `categories:write`
- `budgets:read`, `budgets:write`
- `integrations:write`

Semua endpoint memeriksa tenant active account. Endpoint mutasi juga memerlukan
subscription aktif. Endpoint bisnis memerlukan paket dengan `api_access`.

## Finance resources

| Resource | Endpoints |
| --- | --- |
| Financial accounts | `GET/POST /financial-accounts`, `GET/PUT/DELETE /financial-accounts/{id}` |
| Categories | `GET/POST /categories`, `GET/PUT/DELETE /categories/{id}` |
| Transactions | `GET/POST /transactions`, `GET/PUT/DELETE /transactions/{id}` |
| Transaction lifecycle | `POST /transactions/{id}/post`, `/cancel`, `/reverse` |
| Budgets | `GET/POST /budgets`, `GET/PUT/DELETE /budgets/{id}` |

Endpoint list menggunakan pagination dan mengembalikan metadata `current_page`,
`last_page`, `per_page`, dan `total`. Batas `per_page` maksimum adalah 50.

## Reports

Seluruh endpoint berikut memerlukan ability `reports:read`:

- `GET /reports/summary`
- `GET /reports/cash-flow`
- `GET /reports/income`
- `GET /reports/expenses`
- `GET /reports/categories`
- `GET /reports/financial-accounts`
- `GET /reports/budgets`
- `GET /reports/export`

Filter utama: `date_from`, `date_to`, `status`, `type`, `source`,
`financial_account_id`, `category_id`, `created_by`, `amount_min`, dan
`amount_max`. `/reports/export` menghasilkan data terstruktur yang siap
ditulis ke Google Sheets oleh workflow eksternal.

## Integration endpoints

Endpoint ini menggunakan ability `integrations:write` dan rate limit khusus:

- `POST /integrations/transactions`
- `GET /integrations/reports/summary`
- `GET /integrations/reports/transactions`
- `GET /integrations/budgets/summary`
- `GET /integrations/financial-accounts/balances`

`POST /integrations/transactions` menerima idempotency key pada body atau header
`Idempotency-Key`. Request yang sama tidak membuat transaksi atau efek saldo
kedua. HTTP 201 berarti transaksi dibuat, 200 berarti request duplikat berhasil
dipulihkan, dan 202 berarti mapping perlu ditinjau.

Status HTTP umum: 200/201 sukses, 202 perlu review, 401 token tidak valid, 402
subscription tidak aktif, 403 ability/entitlement/tenant ditolak, 422 validasi,
dan 429 rate limit.
