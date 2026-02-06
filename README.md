# SPK Pemilihan Laptop

Aplikasi Next.js (App Router) dengan TypeScript dan Tailwind CSS. Fokus pada contoh clean architecture yang sederhana: katalog laptop, admin CRUD, dan rekomendasi Weighted Product (WP) berbasis data lokal.

## Fitur Utama

- Landing page berisi title, pencarian, dan daftar laptop.
- Admin panel untuk CRUD spesifikasi laptop (tersimpan di localStorage).
- Halaman rekomendasi memakai metode Weighted Product dari data katalog.
- Halaman konsultasi untuk tanya bot (butuh login user dan API key).
- Login user mendukung register sederhana (disimpan di localStorage).

## Konfigurasi Environment

Salin `.env.example` ke `.env` lalu sesuaikan jika perlu:

```
NEXT_PUBLIC_APP_NAME="SPK Pemilihan Laptop"
NEXT_PUBLIC_ADMIN_EMAIL="admin@laptop.local"
NEXT_PUBLIC_ADMIN_PASSWORD="admin123"
NEXT_PUBLIC_USER_EMAIL="user@laptop.local"
NEXT_PUBLIC_USER_PASSWORD="user123"
OPENAI_API_KEY="isi_api_key_anda"
OPENAI_MODEL="gpt-4.1-mini"
```

## Struktur Direktori

```
src/
├── app/
│   ├── page.tsx
│   ├── rekomendasi/page.tsx
│   └── admin/page.tsx
├── components/
│   ├── LaptopCatalog.tsx
│   ├── LaptopForm.tsx
│   └── LaptopTable.tsx
├── domain/
│   └── weightedProduct.ts
├── application/
│   ├── laptopService.ts
│   └── rekomendasiService.ts
├── data/
│   └── laptopData.ts
└── lib/
    ├── llmHelper.ts
    └── laptopStorage.ts
```

## Menjalankan Secara Lokal

```bash
npm install
npm run dev
```

Buka http://localhost:3000 untuk melihat landing page. Admin ada di `/admin` dan rekomendasi ada di `/rekomendasi`.
Halaman konsultasi bot ada di `/konsultasi`.
