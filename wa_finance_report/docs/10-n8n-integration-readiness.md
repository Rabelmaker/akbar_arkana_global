# n8n Integration Readiness

Backend contract untuk workflow n8n, WhatsApp ingestion, AI extraction, dan
sinkronisasi laporan sudah tersedia. Workflow n8n, provider WhatsApp, model AI,
dan Google Sheets belum dihubungkan pada fase ini.

## Transaction ingestion

`POST /api/v1/integrations/transactions`

Gunakan Sanctum token yang hanya memiliki ability `integrations:write`, serta
selalu kirim `Idempotency-Key`.

```json
{
  "external_id": "wamid.HBgM...",
  "idempotency_key": "wa-message-HBgM...",
  "source": "whatsapp",
  "input_type": "text",
  "transaction": {
    "type": "expense",
    "transaction_date": "2026-07-19 12:30:00",
    "amount": "75000.00",
    "fee_amount": "0.00",
    "category": "Makan",
    "financial_account": "Kas Utama",
    "destination_financial_account": null,
    "description": "Makan siang tim",
    "merchant": "Warung Nusantara",
    "reference_number": null,
    "payment_method": "cash",
    "notes": null,
    "status": "posted"
  },
  "sender": {
    "phone": "+6281234567890",
    "name": "Akbar"
  },
  "ai": {
    "model": "transaction-extractor-v1",
    "confidence_score": 0.96,
    "raw_result": {
      "intent": "expense"
    }
  }
}
```

Nama rekening dan kategori dicocokkan secara case-insensitive di dalam tenant.
Sistem tidak membuat master data baru dari hasil AI. Jika mapping tidak
ditemukan, response HTTP 202 berisi `status: needs_review`, `missing_fields`,
dan `review_id`.

## Operational records

- `webhook_logs` menyimpan payload yang sudah disanitasi, hasil response,
  idempotency key, status, dan waktu proses.
- `ai_processing_logs` menyimpan input, hasil ekstraksi, raw AI result,
  confidence score, model, dan status review.
- Header authorization, cookie, CSRF, dan nilai token tidak disimpan.
- Nomor pengirim dimasking pada webhook log.
- `integration_connections.credential` memakai encrypted cast dan tidak pernah
  diserialisasi oleh model.

## Suggested n8n flow

1. Terima event WhatsApp dan tetapkan idempotency key dari message ID.
2. Normalisasi media menjadi text/audio/image/document.
3. Jalankan AI extractor dengan schema JSON yang tetap.
4. Kirim payload ke endpoint integration dengan scoped bearer token.
5. Jika 201/200, simpan external reference dan lanjutkan notifikasi sukses.
6. Jika 202, arahkan item ke antrean review tanpa membuat kategori/rekening.
7. Jika 422, simpan validation errors untuk perbaikan mapping atau prompt.
8. Ambil report/export atau balances hanya ketika sinkronisasi dibutuhkan.

Retry aman dilakukan dengan idempotency key yang sama. Jangan membuat key baru
untuk retry event yang sama.
