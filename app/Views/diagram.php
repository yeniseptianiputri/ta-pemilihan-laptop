<?php

declare(strict_types=1);

$classDiagram = <<<'MERMAID'
classDiagram
    direction LR

    class HomeController {
        -laptops: LaptopRepository
        -recommendationService: RecommendationService
        +index(): void
    }
    class CatalogController {
        -laptops: LaptopRepository
        +index(): void
    }
    class RecommendationController {
        -laptops: LaptopRepository
        -recommendationService: RecommendationService
        +index(): void
    }
    class ConsultationController {
        -auth: AuthService
        -chat: ChatService
        -laptops: LaptopRepository
        +index(): void
    }
    class CashierController {
        -laptops: LaptopRepository
        -sales: SalesTransactionRepository
        -auth: AuthService
        +index(): void
    }
    class AdminController {
        -laptops: LaptopRepository
        -auth: AuthService
        -users: UserRepository
        -sales: SalesTransactionRepository
        +index(): void
    }
    class DiagramController {
        +index(): void
    }

    class AuthService {
        -users: UserRepository
        -authConfig: array
        +loginAdmin(email, password): array
        +loginCashier(email, password): array
        +loginUser(email, password): array
        +registerUser(name, email, password, confirmPassword): array
        +logoutAdmin(): void
        +logoutCashier(): void
        +logoutUser(): void
    }
    class RecommendationService {
        -defaultWeights: array
        +recommend(catalog, filters): array
        +defaultWeights(): array
    }
    class ChatService {
        -apiKey: string
        -model: string
        +ask(message, budget, useCase, catalog): string
    }

    class LaptopRepository {
        -pdo: PDO
        -defaultLaptops: array
        +all(): array
        +allForRanking(): array
        +find(id): array
        +create(payload): void
        +update(id, payload): void
        +delete(id): void
    }
    class UserRepository {
        -pdo: PDO
        +findById(id): array
        +findByEmail(email): array
        +create(email, passwordHash, role, name): int
        +updateManagedUser(...): void
        +validateCredentials(email, password, role): array
    }
    class SalesTransactionRepository {
        -pdo: PDO
        +create(laptopId, cashierId, quantity, unitPrice, customerName): string
        +all(): array
        +allByCashier(cashierId): array
        +delete(id): void
        +deleteByCashier(id, cashierId): bool
    }

    class Database {
        <<static>>
        -connection: PDO
        +init(config): void
        +connection(): PDO
    }
    class Session {
        <<static>>
        +start(): void
        +regenerate(): void
        +get(key, default): mixed
        +set(key, value): void
        +forget(key): void
    }
    class Csrf {
        <<static>>
        +token(): string
        +verify(token): bool
    }
    class View {
        <<static>>
        +render(view, data): void
    }
    class Env {
        <<static>>
        +load(path): void
    }
    class PDO {
        <<external>>
    }
    class ViewTemplates["app/Views/*.php"]
    class OpenAIAPI["OpenAI Responses API"]

    HomeController --> LaptopRepository : uses
    HomeController --> RecommendationService : uses
    HomeController ..> Csrf : verify_csrf()
    HomeController ..> View : render()
    CatalogController --> LaptopRepository : uses
    CatalogController ..> View : render()
    RecommendationController --> LaptopRepository : uses
    RecommendationController --> RecommendationService : uses
    RecommendationController ..> Csrf : verify_csrf()
    RecommendationController ..> View : render()
    ConsultationController --> AuthService : uses
    ConsultationController --> ChatService : uses
    ConsultationController --> LaptopRepository : uses
    ConsultationController ..> Csrf : verify_csrf()
    ConsultationController ..> Session : chat session
    ConsultationController ..> View : render()
    CashierController --> LaptopRepository : uses
    CashierController --> SalesTransactionRepository : uses
    CashierController --> AuthService : uses
    CashierController ..> Csrf : verify_csrf()
    CashierController ..> View : render()
    AdminController --> LaptopRepository : uses
    AdminController --> UserRepository : uses
    AdminController --> SalesTransactionRepository : uses
    AdminController --> AuthService : uses
    AdminController ..> Csrf : verify_csrf()
    AdminController ..> View : render()
    DiagramController ..> View : render()

    AuthService --> UserRepository : validate user
    AuthService ..> Session : role session
    ChatService ..> OpenAIAPI : POST /v1/responses
    LaptopRepository --> PDO : query
    UserRepository --> PDO : query
    SalesTransactionRepository --> PDO : query
    Database --> PDO : create connection
    View ..> ViewTemplates : include view file
MERMAID;

$activityDiagram = <<<'MERMAID'
flowchart TD
    A([Mulai]) --> B[Pengguna membuka aplikasi]
    B --> C{Pilih modul}

    C --> D[Home atau Rekomendasi]
    D --> D1[Isi filter: nama, RAM, storage, prosesor, budget]
    D1 --> D2{Token CSRF valid?}
    D2 -- Tidak --> D3[Set flash error dan redirect]
    D3 --> C
    D2 -- Ya --> D4[LaptopRepository::allForRanking()]
    D4 --> D5[RecommendationService::recommend()]
    D5 --> D6[Tampilkan ranking laptop]
    D6 --> C

    C --> E[Konsultasi AI]
    E --> E1{Sudah login role user?}
    E1 -- Tidak --> E2[Login atau register user]
    E2 --> E1
    E1 -- Ya --> E3[Isi budget dan kebutuhan]
    E3 --> E4{Budget valid?}
    E4 -- Tidak --> E5[Set flash error]
    E5 --> E
    E4 -- Ya --> E6[ChatService::ask() bangun prompt dari katalog]
    E6 --> O[(OpenAI Responses API)]
    O --> E7[Terima jawaban AI]
    E7 --> E8[Simpan chat ke session]
    E8 --> E9[Tampilkan riwayat chat]
    E9 --> C

    C --> F[Panel Kasir]
    F --> F1{Login cashier valid?}
    F1 -- Tidak --> F2[Set flash error]
    F2 --> F
    F1 -- Ya --> F3[Input laptop, quantity, customer]
    F3 --> F4{Data valid dan laptop ditemukan?}
    F4 -- Tidak --> F5[Set flash error]
    F5 --> F
    F4 -- Ya --> F6[SalesTransactionRepository::create()]
    F6 --> F7[Tampilkan invoice dan riwayat kasir]
    F7 --> C

    C --> G[Panel Admin]
    G --> G1{Login admin valid?}
    G1 -- Tidak --> G2[Set flash error]
    G2 --> G
    G1 -- Ya --> G3[Kelola laptop, user, transaksi]
    G3 --> G4{Token CSRF valid?}
    G4 -- Tidak --> G5[Set flash error]
    G5 --> G
    G4 -- Ya --> G6[Repository CRUD sesuai action]
    G6 --> G7[Tampilkan dashboard admin]
    G7 --> C

    C --> H([Selesai])
MERMAID;
?>
<section class="card">
    <p class="eyebrow">Dokumentasi Mermaid</p>
    <h2>Class Diagram dan Activity Diagram Sistem</h2>
    <p class="lead">
        Diagram berikut disusun dari implementasi aktual pada layer controller, service, repository, dan core.
        Jika diagram tidak ter-render otomatis, gunakan kode Mermaid pada bagian bawah setiap diagram.
    </p>
</section>

<section class="diagram-stack">
    <article class="card">
        <p class="eyebrow">Diagram UML</p>
        <h2>Class Diagram Program</h2>
        <p class="muted">
            Menunjukkan struktur class, dependency antar-layer, dan integrasi eksternal OpenAI API.
        </p>

        <div class="diagram-scroll">
            <div class="diagram-view">
                <div class="mermaid">
<?= $classDiagram ?>
                </div>
            </div>
        </div>

        <details class="diagram-raw">
            <summary>Lihat kode Mermaid class diagram</summary>
            <pre class="code-block"><code><?= e($classDiagram) ?></code></pre>
        </details>
    </article>

    <article class="card">
        <p class="eyebrow">Diagram Aktivitas</p>
        <h2>Activity Diagram Sistem</h2>
        <p class="muted">
            Menunjukkan alur utama berdasarkan modul rekomendasi, konsultasi AI, kasir, dan admin.
        </p>

        <div class="diagram-scroll">
            <div class="diagram-view">
                <div class="mermaid">
<?= $activityDiagram ?>
                </div>
            </div>
        </div>

        <details class="diagram-raw">
            <summary>Lihat kode Mermaid activity diagram</summary>
            <pre class="code-block"><code><?= e($activityDiagram) ?></code></pre>
        </details>
    </article>
</section>

<script type="module">
import mermaid from "https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs";

mermaid.initialize({
    startOnLoad: true,
    securityLevel: "loose",
    theme: "default"
});
</script>

