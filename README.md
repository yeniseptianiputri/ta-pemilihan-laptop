# Sistem Pendukung Keputusan Pemilihan Spek Laptop

Dokumen ini disusun untuk kebutuhan skripsi dan menjelaskan aplikasi secara end-to-end: latar belakang, requirement, user flow, use case, sequence diagram, class diagram, activity diagram, ERD, implementasi, dan pengujian.

## 1. Ringkasan Penelitian

### 1.1 Latar Belakang
Pemilihan laptop sering dilakukan berdasarkan persepsi subjektif dan promosi produk, bukan analisis kriteria terukur. Hal ini dapat menyebabkan perangkat yang dipilih tidak sesuai kebutuhan komputasi maupun batas anggaran pengguna.

### 1.2 Rumusan Masalah
1. Bagaimana menyediakan sistem rekomendasi laptop yang objektif berdasarkan multi-kriteria?
2. Bagaimana menggabungkan proses perhitungan SPK dengan antarmuka yang mudah dipahami pengguna awam?
3. Bagaimana merancang basis data yang kompleks, ter-normalisasi, dan relevan untuk mendukung kebutuhan skripsi?

### 1.3 Tujuan
1. Membangun aplikasi SPK pemilihan laptop berbasis web menggunakan metode Weighted Product.
2. Menyediakan proses evaluasi alternatif laptop berdasarkan RAM, Storage, Prosesor, dan Harga.
3. Menyediakan modul admin, kasir, user, serta histori perhitungan rekomendasi untuk kebutuhan analitik.

### 1.4 Batasan Masalah
1. Kriteria utama: `ram`, `storage`, `processor`, `price`.
2. Bobot default awal: RAM 30%, Storage 20%, Prosesor 30%, Harga 20%.
3. Metode SPK: Weighted Product (WP).
4. Histori chat AI masih disimpan di session.
5. Histori proses rekomendasi WP disimpan ke database.

## 2. Gambaran Sistem

### 2.1 Aktor Sistem
1. Admin
2. Kasir
3. User
4. Visitor (belum login)
5. OpenAI API (sistem eksternal untuk konsultasi AI)

### 2.2 Fitur Utama
1. Landing page bertema marketplace.
2. Katalog laptop + pencarian.
3. Form rekomendasi WP.
4. Halaman rekomendasi (ranking + penjelasan).
5. Konsultasi AI (hanya role `user`).
6. Panel admin (CRUD laptop, manajemen user role, monitoring transaksi).
7. Panel kasir (input transaksi, riwayat, hapus transaksi sendiri).
8. Penyimpanan histori sesi rekomendasi ke database (`recommendation_sessions`, `recommendation_results`).

### 2.3 Teknologi
1. Backend: PHP 8 (native)
2. Database: MySQL / MariaDB (InnoDB)
3. Frontend: HTML + CSS native
4. API AI: OpenAI Responses API via cURL
5. Server lokal: Laragon (Apache + MySQL)

## 3. Arsitektur dan Struktur Proyek

### 3.1 Arsitektur
Aplikasi menerapkan pola berlapis (layered architecture):
1. `Controller`: menangani request/response.
2. `Service`: logika bisnis (auth, rekomendasi WP, chat AI).
3. `Repository`: akses data SQL.
4. `View`: rendering HTML.
5. `Core`: environment, session, CSRF, DB connection.

### 3.2 Struktur Direktori
```text
app/
  Config/
  Controllers/
  Core/
  Helpers/
  Repositories/
  Services/
  Views/
database/
  schema.sql
public/
  assets/css/style.css
  index.php
README.md
```

### 3.3 Sitemap / Routing
| Halaman | URL | Keterangan |
|---|---|---|
| Landing | `index.php?page=home` | Ringkasan sistem + quick access |
| Katalog | `index.php?page=katalog` | Daftar laptop + pencarian |
| Form Rekomendasi | `index.php?page=form-rekomendasi` | Input filter dan proses WP |
| Rekomendasi | `index.php?page=rekomendasi` | Ranking dan penjelasan |
| Diagram Mermaid | `index.php?page=diagram` | Class + activity diagram |
| Konsultasi | `index.php?page=konsultasi` | Login user + konsultasi AI |
| Kasir | `index.php?page=cashier` | Login kasir + transaksi |
| Admin | `index.php?page=admin` | Login admin + manajemen data |

## 4. Requirement Sistem

### 4.1 Kebutuhan Fungsional
1. Sistem menampilkan daftar laptop dari database.
2. Sistem menghitung ranking laptop dengan metode Weighted Product.
3. Admin dapat login/logout dan kelola data laptop.
4. Admin dapat kelola user + role.
5. Admin dapat memantau dan menghapus transaksi kasir.
6. Kasir dapat login/logout, tambah transaksi, lihat/hapus transaksi miliknya.
7. User dapat register/login.
8. User dapat memakai konsultasi AI.
9. Sistem menyimpan histori rekomendasi WP ke database.

### 4.2 Kebutuhan Non-Fungsional
1. CSRF token pada semua form mutasi.
2. Password disimpan dengan `password_hash`.
3. Query memakai prepared statement PDO.
4. Session ID diregenerasi saat login.
5. Integritas relasi dijaga FK InnoDB.

### 4.3 Matriks Hak Akses
| Fitur | Visitor | User | Kasir | Admin |
|---|---|---|---|---|
| Lihat katalog laptop | Ya | Ya | Ya | Ya |
| Hitung rekomendasi WP | Ya | Ya | Ya | Ya |
| Konsultasi AI | Tidak | Ya | Tidak | Tidak |
| Input transaksi kasir | Tidak | Tidak | Ya | Tidak |
| Kelola master laptop | Tidak | Tidak | Tidak | Ya |
| Kelola user role | Tidak | Tidak | Tidak | Ya |
| Monitor/hapus transaksi | Tidak | Tidak | Terbatas | Ya |

## 5. User Flow

### 5.1 User Flow Visitor/User
1. Visitor membuka landing page.
2. Visitor melihat katalog atau form rekomendasi.
3. Visitor register/login sebagai role `user` jika ingin konsultasi AI.
4. User mengirim budget + kebutuhan.
5. Sistem menampilkan rekomendasi AI berbasis katalog.

### 5.2 User Flow Kasir
1. Kasir login pada halaman kasir.
2. Kasir memilih laptop, quantity, dan opsional nama pembeli.
3. Sistem membuat transaksi (`sales_orders` + `sales_order_items`).
4. Kasir melihat riwayat transaksi miliknya.

### 5.3 User Flow Admin
1. Admin login pada halaman admin.
2. Admin mengelola master laptop dan user.
3. Admin memantau semua transaksi kasir.
4. Admin logout.

### 5.4 User Flow Rekomendasi
1. Pengguna membuka form rekomendasi.
2. Pengguna mengisi filter kriteria.
3. Sistem melakukan perhitungan WP.
4. Sistem menyimpan histori sesi dan hasil ranking ke database.
5. Sistem menampilkan ranking laptop.

## 6. Use Case

### 6.1 Diagram Use Case
```mermaid
flowchart LR
    V[Visitor] --> UC1[Lihat Landing]
    V --> UC2[Lihat Katalog Laptop]
    V --> UC3[Isi Form Rekomendasi]
    V --> UC4[Lihat Hasil Ranking WP]
    V --> UC5[Register User]

    U[User] --> UC6[Login User]
    U --> UC7[Konsultasi AI]
    U --> UC8[Logout User]

    K[Kasir] --> UC9[Login Kasir]
    K --> UC10[Input Transaksi Penjualan]
    K --> UC11[Lihat Riwayat Transaksi Sendiri]
    K --> UC12[Hapus Transaksi Sendiri]
    K --> UC13[Logout Kasir]

    A[Admin] --> UC14[Login Admin]
    A --> UC15[Kelola Data Laptop]
    A --> UC16[Kelola User dan Role]
    A --> UC17[Monitor/Hapus Transaksi Kasir]
    A --> UC18[Logout Admin]

    V --> UC19[Proses Rekomendasi Tersimpan]
    U --> UC19
    K --> UC19
    A --> UC19

    UC7 --> EXT[(OpenAI API)]
```

### 6.2 Daftar Use Case
| Kode | Use Case | Aktor | Deskripsi |
|---|---|---|---|
| UC1 | Lihat Landing | Visitor | Melihat ringkasan aplikasi |
| UC2 | Lihat Katalog Laptop | Semua role | Menampilkan data laptop + pencarian |
| UC3 | Isi Form Rekomendasi | Semua role | Mengisi filter WP |
| UC4 | Lihat Hasil Ranking WP | Semua role | Melihat ranking hasil |
| UC5 | Register User | Visitor | Membuat akun role `user` |
| UC6 | Login User | User | Akses konsultasi AI |
| UC7 | Konsultasi AI | User | Mengirim kebutuhan + budget |
| UC8 | Logout User | User | Mengakhiri sesi user |
| UC9 | Login Kasir | Kasir | Akses modul kasir |
| UC10 | Input Transaksi Penjualan | Kasir | Menyimpan transaksi |
| UC11 | Lihat Riwayat Transaksi Sendiri | Kasir | Monitoring transaksi sendiri |
| UC12 | Hapus Transaksi Sendiri | Kasir | Hapus transaksi milik sendiri |
| UC13 | Logout Kasir | Kasir | Mengakhiri sesi kasir |
| UC14 | Login Admin | Admin | Akses dashboard admin |
| UC15 | Kelola Data Laptop | Admin | CRUD laptop |
| UC16 | Kelola User dan Role | Admin | CRUD user + role |
| UC17 | Monitor/Hapus Transaksi Kasir | Admin | Monitoring semua transaksi |
| UC18 | Logout Admin | Admin | Mengakhiri sesi admin |
| UC19 | Simpan Histori Rekomendasi | Semua pengguna rekomendasi | Menyimpan input dan hasil ranking WP ke DB |

## 7. Sequence Diagram

### 7.1 Sequence Login Admin
```mermaid
sequenceDiagram
    actor Admin
    participant View as Admin Page
    participant C as AdminController
    participant S as AuthService
    participant R as UserRepository
    participant DB as MySQL

    Admin->>View: Submit email + password
    View->>C: POST action=admin_login
    C->>S: loginAdmin(email,password)
    S->>R: validateCredentials(role=admin)
    R->>DB: SELECT users + roles
    DB-->>R: user row
    R-->>S: valid/invalid
    S-->>C: result
    C-->>View: redirect + flash
```

### 7.2 Sequence Admin Kelola User
```mermaid
sequenceDiagram
    actor Admin
    participant View as Admin Form User
    participant C as AdminController
    participant R as UserRepository
    participant DB as MySQL

    Admin->>View: Submit create/update/delete user
    View->>C: POST action=create_user/update_user/delete_user
    C->>C: Validasi CSRF + payload + role rule
    C->>R: create/updateManagedUser/deleteById
    R->>DB: INSERT/UPDATE/DELETE users(role_id)
    DB-->>R: success
    R-->>C: success
    C-->>View: redirect + flash
```

### 7.3 Sequence Kasir Input Transaksi
```mermaid
sequenceDiagram
    actor Kasir
    participant View as Cashier Page
    participant C as CashierController
    participant A as AuthService
    participant L as LaptopRepository
    participant T as SalesTransactionRepository
    participant DB as MySQL

    Kasir->>View: Submit laptop + quantity
    View->>C: POST action=create_sale
    C->>A: isCashierLoggedIn()
    A-->>C: true
    C->>L: find(laptop_id)
    L->>DB: SELECT laptop by id
    DB-->>L: laptop row
    L-->>C: laptop
    C->>T: create(order,laptop,cashier,qty,price)
    T->>DB: INSERT sales_orders
    T->>DB: INSERT sales_order_items
    DB-->>T: success
    T-->>C: order_code
    C-->>View: redirect + flash
```

### 7.4 Sequence Rekomendasi Weighted Product
```mermaid
sequenceDiagram
    actor Pengguna
    participant View as Form Rekomendasi
    participant C as RecommendationController
    participant L as LaptopRepository
    participant S as RecommendationService
    participant RR as RecommendationRepository
    participant DB as MySQL

    Pengguna->>View: Submit filter
    View->>C: POST action=recommend
    C->>L: allForRanking()
    L-->>C: daftar laptop
    C->>S: recommend(catalog,filters)
    S->>RR: weightsFromCriteria()
    RR->>DB: SELECT criteria.weight
    DB-->>RR: bobot
    S->>S: hitung skor WP
    S->>RR: saveSession(filters,weights,results)
    RR->>DB: INSERT recommendation_sessions
    RR->>DB: INSERT recommendation_results
    DB-->>RR: success
    S-->>C: ranking hasil
    C-->>View: render tabel ranking
```

### 7.5 Sequence Konsultasi AI (Role User)
```mermaid
sequenceDiagram
    actor User
    participant View as Consultation Page
    participant C as ConsultationController
    participant A as AuthService
    participant L as LaptopRepository
    participant Chat as ChatService
    participant OAI as OpenAI API

    User->>View: Submit budget + kebutuhan
    View->>C: POST action=send_chat
    C->>A: isUserLoggedIn()
    A-->>C: true
    C->>L: allForRanking()
    L-->>C: catalog
    C->>Chat: ask(question,budget,useCase,catalog)
    Chat->>OAI: POST /v1/responses
    OAI-->>Chat: recommendation text
    Chat-->>C: text
    C-->>View: simpan chat session + render
```

### 7.6 Class Diagram Program
```mermaid
classDiagram
    direction LR

    class HomeController {
        +index(): void
    }
    class CatalogController {
        +index(): void
    }
    class RecommendationController {
        +index(): void
    }
    class ConsultationController {
        +index(): void
    }
    class CashierController {
        +index(): void
    }
    class AdminController {
        +index(): void
    }

    class AuthService {
        +loginAdmin(email,password): array
        +loginCashier(email,password): array
        +loginUser(email,password): array
        +registerUser(name,email,password,confirm): array
        +logoutAdmin(): void
        +logoutCashier(): void
        +logoutUser(): void
    }
    class RecommendationService {
        +recommend(catalog,filters,userId,sourcePage): array
        +defaultWeights(): array
    }
    class ChatService {
        +ask(message,budget,useCase,catalog): string
    }

    class LaptopRepository {
        +ensureSchema(): void
        +all(): array
        +allForRanking(): array
        +searchByName(query): array
        +find(id): array
        +create(payload): void
        +update(id,payload): void
        +delete(id): void
    }
    class UserRepository {
        +ensureRoleSchema(): void
        +findById(id): array
        +findByEmail(email): array
        +create(email,passwordHash,role,name): int
        +updateManagedUser(...): void
        +validateCredentials(email,password,role): array
    }
    class SalesTransactionRepository {
        +ensureSchema(): void
        +create(laptopId,cashierId,qty,unitPrice,customer): string
        +all(): array
        +allByCashier(cashierId): array
        +delete(id): void
        +deleteByCashier(id,cashierId): bool
    }
    class RecommendationRepository {
        +ensureSchema(): void
        +weightsFromCriteria(fallback): array
        +saveSession(userId,filters,weights,results,sourcePage): void
    }

    class Database {
        <<static>>
        +init(config): void
        +connection(): PDO
    }
    class Session {
        <<static>>
        +start(): void
        +regenerate(): void
        +get(key,default): mixed
        +set(key,value): void
        +forget(key): void
    }
    class Csrf {
        <<static>>
        +token(): string
        +verify(token): bool
    }
    class View {
        <<static>>
        +render(view,data): void
    }
    class Env {
        <<static>>
        +load(path): void
    }

    HomeController --> LaptopRepository
    HomeController --> RecommendationService
    CatalogController --> LaptopRepository
    RecommendationController --> LaptopRepository
    RecommendationController --> RecommendationService
    ConsultationController --> AuthService
    ConsultationController --> ChatService
    ConsultationController --> LaptopRepository
    CashierController --> AuthService
    CashierController --> LaptopRepository
    CashierController --> SalesTransactionRepository
    AdminController --> AuthService
    AdminController --> LaptopRepository
    AdminController --> UserRepository
    AdminController --> SalesTransactionRepository

    AuthService --> UserRepository
    RecommendationService --> RecommendationRepository
```

### 7.7 Activity Diagram Sistem
```mermaid
flowchart TD
    A([Mulai]) --> B[User membuka aplikasi]
    B --> C{Pilih modul}

    C --> D[Rekomendasi WP]
    D --> D1[Submit filter]
    D1 --> D2{CSRF valid?}
    D2 -- Tidak --> D3[Flash error + redirect]
    D3 --> C
    D2 -- Ya --> D4[LaptopRepository ambil katalog]
    D4 --> D5[RecommendationService hitung skor WP]
    D5 --> D6[Simpan recommendation_sessions + recommendation_results]
    D6 --> D7[Tampilkan ranking]
    D7 --> C

    C --> E[Konsultasi AI]
    E --> E1{Login role user?}
    E1 -- Tidak --> E2[Login atau register user]
    E2 --> E1
    E1 -- Ya --> E3[Isi budget + kebutuhan]
    E3 --> E4{Budget valid?}
    E4 -- Tidak --> E5[Flash error]
    E5 --> E
    E4 -- Ya --> E6[ChatService kirim prompt ke OpenAI]
    E6 --> E7[Terima jawaban]
    E7 --> E8[Simpan chat ke session]
    E8 --> E9[Tampilkan riwayat]
    E9 --> C

    C --> F[Modul Kasir]
    F --> F1{Login kasir valid?}
    F1 -- Tidak --> F2[Flash error]
    F2 --> F
    F1 -- Ya --> F3[Input transaksi]
    F3 --> F4{Data valid?}
    F4 -- Tidak --> F5[Flash error]
    F5 --> F
    F4 -- Ya --> F6[Simpan sales_orders]
    F6 --> F7[Simpan sales_order_items]
    F7 --> F8[Tampilkan riwayat kasir]
    F8 --> C

    C --> G[Modul Admin]
    G --> G1{Login admin valid?}
    G1 -- Tidak --> G2[Flash error]
    G2 --> G
    G1 -- Ya --> G3[CRUD laptop/user/transaksi]
    G3 --> G4{CSRF + rule valid?}
    G4 -- Tidak --> G5[Flash error]
    G5 --> G
    G4 -- Ya --> G6[Repository execute action]
    G6 --> G7[Tampilkan dashboard admin]
    G7 --> C

    C --> H([Selesai])
```

## 8. Metode Weighted Product

### 8.1 Kriteria
1. `ram` (benefit)
2. `storage` (benefit)
3. `processor` (benefit)
4. `price` (cost)

### 8.2 Bobot
Bobot default tersimpan di tabel `criteria`:
- RAM = 0.3
- Storage = 0.2
- Prosesor = 0.3
- Harga = 0.2

### 8.3 Formula
```text
S_i = (ram^w_ram) * (storage^w_storage) * (processor^w_processor) * (price^-w_price)
```

## 9. Desain Basis Data (ERD Kompleks)

### 9.1 ERD
```mermaid
erDiagram
    ROLES {
        TINYINT id PK
        VARCHAR code UK
        VARCHAR label
        TIMESTAMP created_at
    }

    USERS {
        INT id PK
        TINYINT role_id FK
        VARCHAR name
        VARCHAR email UK
        VARCHAR password_hash
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    BRANDS {
        INT id PK
        VARCHAR name UK
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    LAPTOPS {
        INT id PK
        INT brand_id FK
        VARCHAR name
        INT price
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    CRITERIA {
        TINYINT id PK
        VARCHAR code UK
        VARCHAR name
        ENUM attribute_type
        DECIMAL weight
        VARCHAR unit
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    LAPTOP_CRITERIA_VALUES {
        BIGINT id PK
        INT laptop_id FK
        TINYINT criterion_id FK
        DECIMAL numeric_value
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    CUSTOMERS {
        INT id PK
        VARCHAR full_name UK
        VARCHAR phone
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    SALES_ORDERS {
        BIGINT id PK
        VARCHAR order_code UK
        INT cashier_id FK
        INT customer_id FK
        VARCHAR customer_note
        ENUM order_status
        BIGINT grand_total
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    SALES_ORDER_ITEMS {
        BIGINT id PK
        BIGINT order_id FK
        INT laptop_id FK
        SMALLINT quantity
        INT unit_price
        BIGINT line_total
        TIMESTAMP created_at
    }

    RECOMMENDATION_SESSIONS {
        BIGINT id PK
        INT user_id FK
        VARCHAR session_token
        VARCHAR source_page
        JSON filters_json
        JSON weights_json
        TIMESTAMP created_at
    }

    RECOMMENDATION_RESULTS {
        BIGINT id PK
        BIGINT recommendation_session_id FK
        INT laptop_id FK
        SMALLINT rank_position
        DECIMAL wp_score
        JSON snapshot_json
        TIMESTAMP created_at
    }

    ROLES ||--o{ USERS : role_id
    BRANDS ||--o{ LAPTOPS : brand_id
    LAPTOPS ||--o{ LAPTOP_CRITERIA_VALUES : laptop_id
    CRITERIA ||--o{ LAPTOP_CRITERIA_VALUES : criterion_id
    USERS ||--o{ SALES_ORDERS : cashier_id
    CUSTOMERS ||--o{ SALES_ORDERS : customer_id
    SALES_ORDERS ||--o{ SALES_ORDER_ITEMS : order_id
    LAPTOPS ||--o{ SALES_ORDER_ITEMS : laptop_id
    USERS ||--o{ RECOMMENDATION_SESSIONS : user_id
    RECOMMENDATION_SESSIONS ||--o{ RECOMMENDATION_RESULTS : recommendation_session_id
    LAPTOPS ||--o{ RECOMMENDATION_RESULTS : laptop_id
```

### 9.2 Data Dictionary Singkat
1. `roles`: master role (`admin`, `cashier`, `user`).
2. `users`: akun user, relasi ke `roles`.
3. `brands`: master merek laptop.
4. `laptops`: master laptop (brand + nama + harga).
5. `criteria`: master kriteria dan bobot WP.
6. `laptop_criteria_values`: nilai kriteria per laptop (relasi M:N).
7. `customers`: master pembeli.
8. `sales_orders`: header transaksi kasir.
9. `sales_order_items`: detail item transaksi.
10. `recommendation_sessions`: histori input rekomendasi.
11. `recommendation_results`: histori hasil ranking per sesi.

## 10. Normalisasi dan Kompleksitas DB

### 10.1 Hasil Normalisasi
1. `users.role` dipisah ke tabel referensi `roles`.
2. Kriteria laptop dipisah ke tabel `criteria` dan pivot `laptop_criteria_values`.
3. Transaksi dipisah menjadi header-detail.
4. Histori rekomendasi dipisah dari master data.

### 10.2 Nilai Tambah Akademik
1. Memenuhi relasi 1:N dan M:N secara nyata.
2. Menunjukkan integritas referensial (FK) untuk skripsi.
3. Mendukung analitik keputusan melalui histori rekomendasi.

## 11. Keamanan Aplikasi

1. CSRF token pada form mutasi.
2. Password hashing (`password_hash`, `password_verify`).
3. Prepared statement PDO.
4. Session regeneration saat login.
5. Validasi role antar modul.

## 12. Setup dan Menjalankan Aplikasi

### 12.1 Prasyarat
1. Laragon (Apache + MySQL)
2. PHP 8.x
3. MySQL 8.x / MariaDB kompatibel

### 12.2 Langkah Instalasi
1. Letakkan proyek di `C:\laragon\www\pemilihan-laptop`.
2. Import `database/schema.sql`.
3. Isi file `.env`.
4. Jalankan Apache + MySQL.
5. Akses `http://localhost/pemilihan-laptop/`.

Catatan: saat bootstrap, aplikasi akan memastikan tabel relasi baru tersedia dan melakukan migrasi data lama yang masih kompatibel.

### 12.3 Konfigurasi `.env` minimal
```env
APP_NAME="SPK Pemilihan Laptop"
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=spk_laptop
DB_USER=root
DB_PASS=

ADMIN_EMAIL=admin@laptop.local
ADMIN_PASSWORD=admin123
CASHIER_EMAIL=cashier@laptop.local
CASHIER_PASSWORD=cashier123
USER_EMAIL=user@laptop.local
USER_PASSWORD=user123

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
```

## 13. Akun Default

1. Admin: mengikuti `ADMIN_EMAIL` dan `ADMIN_PASSWORD` pada `.env`.
2. Kasir: mengikuti `CASHIER_EMAIL` dan `CASHIER_PASSWORD` pada `.env`.
3. User: mengikuti `USER_EMAIL` dan `USER_PASSWORD` pada `.env`.

## 14. Skenario Uji Fungsional (Ringkas)

1. Login admin/kasir/user valid dan invalid.
2. Admin tambah/edit/hapus data laptop.
3. Admin tambah/edit/hapus user dan role.
4. Kasir membuat transaksi dan lihat riwayat transaksi sendiri.
5. Admin monitor/hapus transaksi kasir.
6. Rekomendasi WP menghasilkan ranking.
7. Histori rekomendasi tersimpan di DB.
8. Konsultasi AI hanya untuk role user.
9. Validasi CSRF dan session role.

## 15. Pengembangan Lanjutan

1. Modul UI untuk manajemen bobot kriteria di tabel `criteria`.
2. Ekspor laporan transaksi dan ranking ke PDF/Excel.
3. Penyimpanan histori konsultasi AI ke database.
4. Audit trail perubahan data admin/kasir.

---

Jika dokumen ini dipakai untuk skripsi, bagian pada Bab Analisis dan Perancangan dapat langsung mengacu ke: **Bagian 5 (User Flow), 6 (Use Case), 7 (Sequence/Class/Activity Diagram), 9 (ERD), dan 10 (Normalisasi)**.
