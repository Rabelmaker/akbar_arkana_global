# Phase 0 Audit

## Project

- Laravel skeleton: Laravel 13.20.0, PHP 8.3.30, Pest 4.7.5, Vite 8.1.5.
- Kondisi awal hanya memiliki route welcome dan `resources/js/app.js` kosong.
- Breeze, Inertia, Sanctum, Spatie Permission, dan Activitylog belum terpasang pada awal audit.
- `.env` mengarah ke MySQL `wa_finance_report`, tetapi kredensial root lokal belum dapat membuat atau membuka database tersebut.
- Git repository berada di `D:\project`, bukan di root aplikasi. Folder proyek ini masih untracked dari sudut repository induk.

## Materialize Template

Sumber template:

`D:\template\themeforest-materialize-material-design-admin-template-v13.2.0\materialize-material-design-admin-template-13.2.0\vue-laravel-version\javascript-version\full-version`

File utama yang teridentifikasi:

- Entry: `resources/js/main.js`, `resources/js/App.vue`, `resources/views/application.blade.php`.
- Main layout/sidebar/navbar: `resources/js/layouts/components/DefaultLayoutWithVerticalNav.vue` dan `resources/js/@layouts/components/VerticalNav.vue`.
- Navbar components: `resources/js/layouts/components/NavSearchBar.vue`, `NavbarThemeSwitcher.vue`, `NavBarNotifications.vue`, `UserProfile.vue`.
- Footer: `resources/js/layouts/components/Footer.vue`.
- Theme: `themeConfig.js`, `resources/js/plugins/vuetify/theme.js`, `resources/styles/variables/`.
- Icons: `resources/js/plugins/iconify/`, `resources/images/svg/`, `resources/images/logo.svg`.
- Dashboard: `resources/js/pages/dashboards/analytics.vue`, `crm.vue`, `ecommerce.vue`.
- Forms: `resources/js/pages/forms/form-layouts.vue` dan `resources/js/views/demos/forms/form-layout/`.
- Tables: `resources/js/pages/tables/simple-table.vue`, `data-table.vue`.
- Charts: `resources/js/pages/charts/apex-chart.vue`, `chartjs.vue`.
- Authentication: `resources/js/pages/login.vue`, `register.vue`, `resources/js/layouts/blank.vue`.

## Integration Decision

Template asli menggunakan Laravel 11, Vite 5, Vue Router, Vuetify 3.5, auto-routing, dan fake API. Aplikasi target menggunakan Laravel 13, Vite 8, dan Inertia. Integrasi dilakukan dengan mempertahankan stack Laravel 13 dan mengambil theme tokens, generated icon set, logo/aset, struktur card, navigation drawer, app bar, table, dan pola auth Materialize. Vue Router dan fake API tidak disalin.

SCSS internal penuh tidak diaktifkan karena bergantung pada alias layout template dan variabel Vuetify 3.5 yang tidak kompatibel dengan Vuetify terbaru. Memaksanya menghasilkan error Sass unit dan akan mengunci aplikasi ke Vite 5. Source styles tetap tersedia untuk migrasi selektif berikutnya.

## Version Risks

- Materialize: Laravel 11 / Vite 5 versus aplikasi Laravel 13 / Vite 8.
- Activitylog 5.0 membutuhkan PHP 8.4; aplikasi PHP 8.3 menggunakan Activitylog 4.12.3.
- Template menggunakan Vue Router; aplikasi menggunakan Inertia, sehingga navigation harus memanggil Inertia router.
- Generated icon CSS cukup besar dan perlu dipangkas pada tahap optimasi.
