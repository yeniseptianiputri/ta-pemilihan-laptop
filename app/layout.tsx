import "./globals.css";

export const metadata = {
  title: "Aplikasi Pemilihan Laptop",
  description: "Dibuat dengan Next.js dan Tailwind CSS",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="id">
      <body className="bg-gray-100 text-gray-900">
        {/* NAVBAR */}
        <header className="bg-white shadow-md">
          <nav className="max-w-6xl mx-auto p-4 flex justify-between items-center">
            <h1 className="text-2xl font-bold text-blue-600">
              Laptop Selector
            </h1>
          </nav>
        </header>

        {/* CONTENT */}
        <main className="max-w-6xl mx-auto p-6 min-h-screen">
          {children}
        </main>

        {/* FOOTER */}
        <footer className="bg-white border-t mt-10">
          <div className="max-w-6xl mx-auto p-4 text-center text-sm text-gray-600">
            © 2025 Laptop Selector — Made with Next.js & Tailwind CSS
          </div>
        </footer>
      </body>
    </html>
  );
}
