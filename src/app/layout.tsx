import type { Metadata } from "next";
import Link from "next/link";
import { Manrope, Sora } from "next/font/google";
import "./globals.css";

const bodyFont = Manrope({
  subsets: ["latin"],
  variable: "--font-body",
  display: "swap",
});

const displayFont = Sora({
  subsets: ["latin"],
  variable: "--font-display",
  display: "swap",
});

const appName = process.env.NEXT_PUBLIC_APP_NAME ?? "SPK Pemilihan Laptop";

export const metadata: Metadata = {
  title: appName,
  description: "Rekomendasi laptop sederhana dengan metode Weighted Product.",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="id">
      <body
        className={`${bodyFont.variable} ${displayFont.variable} flex min-h-screen flex-col text-slate-900`}
      >
        <header className="border-b border-slate-200/70 bg-white/70 backdrop-blur">
          <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <Link href="/" className="space-y-1">
              <p className="text-[0.65rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
                Sistem Pendukung Keputusan
              </p>
              <p className="font-display text-lg font-semibold text-slate-900">
                {appName}
              </p>
            </Link>
            <nav className="flex flex-wrap gap-3 text-sm font-semibold text-slate-600">
              <Link
                href="/"
                className="rounded-full px-3 py-1 transition hover:bg-slate-100"
              >
                Beranda
              </Link>
              <Link
                href="/rekomendasi"
                className="rounded-full px-3 py-1 transition hover:bg-slate-100"
              >
                Rekomendasi
              </Link>
              <Link
                href="/konsultasi"
                className="rounded-full px-3 py-1 transition hover:bg-slate-100"
              >
                Konsultasi
              </Link>
              <Link
                href="/admin"
                className="rounded-full px-3 py-1 transition hover:bg-slate-100"
              >
                Admin
              </Link>
            </nav>
          </div>
        </header>

        <main className="mx-auto w-full max-w-5xl flex-1 px-6 py-10">
          {children}
        </main>

        <footer className="border-t border-slate-200/70 bg-white/70">
          <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2 px-6 py-5 text-xs text-slate-500">
            <p>SPK Pemilihan Laptop · Metode Weighted Product</p>
            <p>Desain sederhana untuk pengguna awam.</p>
          </div>
        </footer>
      </body>
    </html>
  );
}
