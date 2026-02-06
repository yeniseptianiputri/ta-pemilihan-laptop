import Link from "next/link";
import LaptopCatalog from "@/components/LaptopCatalog";

export default function HomePage() {
  return (
    <section className="space-y-12">
      <div className="fade-up rounded-3xl border border-slate-200/70 bg-white/80 p-8 shadow-sm backdrop-blur">
        <div className="space-y-6">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">
              Sistem Pendukung Keputusan
            </p>
            <h1 className="font-display text-3xl font-semibold text-slate-900 sm:text-4xl">
              Katalog &amp; Rekomendasi Laptop
            </h1>
            <p className="text-base leading-relaxed text-slate-600">
              Landing page berisi pencarian dan daftar laptop. Admin dapat
              mengelola spesifikasi secara sederhana tanpa backend.
            </p>
          </div>
          <div className="flex flex-wrap gap-3">
            <Link
              href="/rekomendasi"
              className="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
            >
              Lihat Rekomendasi
            </Link>
            <Link
              href="/konsultasi"
              className="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-100"
            >
              Konsultasi AI
            </Link>
            <Link
              href="/admin"
              className="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
            >
              Masuk Admin
            </Link>
          </div>
          <div className="stagger grid gap-3 sm:grid-cols-3">
            <div className="rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-3">
              <p className="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-slate-500">
                Metode
              </p>
              <p className="text-sm font-semibold text-slate-900">
                Weighted Product
              </p>
            </div>
            <div className="rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-3">
              <p className="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-slate-500">
                Data
              </p>
              <p className="text-sm font-semibold text-slate-900">
                Lokal &amp; Mudah Diedit
              </p>
            </div>
            <div className="rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-3">
              <p className="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-slate-500">
                Output
              </p>
              <p className="text-sm font-semibold text-slate-900">
                Ranking Laptop
              </p>
            </div>
          </div>
        </div>
      </div>

      <LaptopCatalog />
    </section>
  );
}
