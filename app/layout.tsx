import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "SPK Pembelian Laptop",
  description: "Rekomendasi laptop berbasis preferensi pelanggan",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const year = new Date().getFullYear();

  return (
    <html lang="id">
      <body className="bg-gradient-to-b from-slate-50 to-white text-slate-900">
        <header className="border-b border-slate-200 bg-white/80 backdrop-blur">
          <div className="mx-auto flex max-w-5xl flex-col gap-1 px-6 py-5">
            <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
              Sistem Pendukung Keputusan
            </p>
            <h1 className="text-2xl font-semibold text-slate-900">
              Advisor Laptop Cerdas
            </h1>
            <p className="text-sm text-slate-500">
              Fokus pada pengalaman pelanggan tanpa modul admin.
            </p>
          </div>
        </header>

        <main className="mx-auto min-h-screen max-w-5xl px-6 py-10">{children}</main>

        <footer className="border-t border-slate-200 bg-white/80">
          <div className="mx-auto max-w-5xl px-6 py-4 text-xs text-slate-500">
            &copy; {year} SPK Pembelian Laptop - Dibangun dengan Next.js dan Tailwind CSS
          </div>
        </footer>
      </body>
    </html>
  );
}
