# Roles and Permissions

Role aktif:

- `super-admin`: seluruh permission.
- `user`: permission dashboard dan pengelolaan finance milik account.

Role membership account:

- `account-owner`: dapat mengubah account settings dan berpindah account.
- `account-admin`: dapat mengubah account settings dan berpindah account.
- `account-member`: dapat berpindah ke account aktif yang menjadi membership-nya.
- `account-viewer`: dapat berpindah ke account aktif yang menjadi membership-nya.

Permission dibuat oleh `database/seeders/RolePermissionSeeder.php`. Route admin dilindungi
middleware `role:super-admin`; route user menggunakan `active.account`. `AccountPolicy`
menggabungkan permission `manage account settings` dengan role/owner flag pada pivot
`account_user`, sehingga permission global saja tidak cukup untuk mengubah tenant.

Route plan admin membutuhkan `super-admin` dan permission `manage plans`. Route subscription
admin membutuhkan `super-admin` dan permission `manage subscriptions`. Route mutasi finansial
mulai Phase 4 wajib menggunakan `active.account` dan `active.subscription`.
