"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { getLaptopCatalog } from "@/application/laptopService";
import { registerUser, validateLogin } from "@/lib/userAuth";

type ChatMessage = {
  role: "user" | "assistant";
  text: string;
};

const currency = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});

export default function KonsultasiPage() {
  const [isAuthed, setIsAuthed] = useState(false);
  const [mode, setMode] = useState<"login" | "register">("login");
  const [loginEmail, setLoginEmail] = useState("");
  const [loginPassword, setLoginPassword] = useState("");
  const [loginError, setLoginError] = useState("");
  const [registerName, setRegisterName] = useState("");
  const [registerEmail, setRegisterEmail] = useState("");
  const [registerPassword, setRegisterPassword] = useState("");
  const [registerConfirm, setRegisterConfirm] = useState("");
  const [registerError, setRegisterError] = useState("");
  const [registerSuccess, setRegisterSuccess] = useState("");

  const [budget, setBudget] = useState("");
  const [needs, setNeeds] = useState("");
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const inputClass =
    "mt-2 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200";
  const primaryButton =
    "rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800";
  const secondaryButton =
    "rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100";
  const tabButton = (active: boolean) =>
    active
      ? "rounded-full bg-slate-900 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-white"
      : "rounded-full border border-slate-200 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 hover:bg-slate-100";

  const handleLogin = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const result = validateLogin(loginEmail, loginPassword);
    if (!result.ok) {
      setLoginError(result.error || "Email atau password user salah.");
      return;
    }
    setIsAuthed(true);
    setLoginError("");
    setRegisterError("");
    setRegisterSuccess("");
  };

  const handleLogout = () => {
    setIsAuthed(false);
    setLoginEmail("");
    setLoginPassword("");
    setLoginError("");
    setMode("login");
    setRegisterSuccess("");
    setRegisterError("");
    setRegisterName("");
    setRegisterEmail("");
    setRegisterPassword("");
    setRegisterConfirm("");
  };

  const handleRegister = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setRegisterError("");
    setRegisterSuccess("");

    if (!registerEmail || !registerPassword) {
      setRegisterError("Email dan password wajib diisi.");
      return;
    }
    if (registerPassword.length < 6) {
      setRegisterError("Password minimal 6 karakter.");
      return;
    }
    if (registerPassword !== registerConfirm) {
      setRegisterError("Konfirmasi password tidak sama.");
      return;
    }

    const result = registerUser({
      email: registerEmail,
      password: registerPassword,
      name: registerName,
    });
    if (!result.ok) {
      setRegisterError(result.error || "Registrasi gagal.");
      return;
    }

    setRegisterSuccess("Registrasi berhasil. Anda sudah login.");
    setIsAuthed(true);
    setLoginEmail(registerEmail);
    setLoginPassword(registerPassword);
    setLoginError("");
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const budgetValue = Number(budget);
    if (!budgetValue || budgetValue <= 0) {
      setError("Budget wajib diisi dan lebih dari 0.");
      return;
    }

    const trimmedNeeds = needs.trim();
    const question =
      trimmedNeeds ||
      "Tolong rekomendasikan laptop yang cocok dengan budget saya.";
    const catalog = getLaptopCatalog();
    const userText = `Budget: ${currency.format(
      budgetValue
    )}\nKebutuhan: ${question}`;

    setError("");
    setLoading(true);
    setMessages((prev) => [...prev, { role: "user", text: userText }]);

    try {
      const response = await fetch("/api/chat", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          message: question,
          budget: budgetValue,
          useCase: trimmedNeeds,
          catalog,
        }),
      });

      const data = (await response.json()) as { text?: string; error?: string };
      if (!response.ok) {
        throw new Error(data.error || "Gagal memproses permintaan.");
      }

      setMessages((prev) => [
        ...prev,
        { role: "assistant", text: data.text || "Tidak ada jawaban." },
      ]);
      setNeeds("");
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Terjadi kesalahan tak terduga.";
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  const handleResetChat = () => {
    setMessages([]);
    setNeeds("");
    setBudget("");
    setError("");
  };

  if (!isAuthed) {
    return (
      <section className="space-y-6">
        <div className="fade-up rounded-3xl border border-slate-200/70 bg-white/80 p-6 shadow-sm backdrop-blur">
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
            Konsultasi
          </p>
          <h1 className="font-display text-2xl font-semibold text-slate-900">
            Akses Konsultasi
          </h1>
          <p className="mt-2 text-sm text-slate-600">
            Login atau daftar dulu untuk menggunakan bot rekomendasi laptop.
          </p>
          <div className="mt-4 flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => {
                setMode("login");
                setLoginError("");
              }}
              className={tabButton(mode === "login")}
            >
              Login
            </button>
            <button
              type="button"
              onClick={() => {
                setMode("register");
                setRegisterError("");
                setRegisterSuccess("");
              }}
              className={tabButton(mode === "register")}
            >
              Register
            </button>
          </div>
        </div>

        {mode === "login" ? (
          <form
            onSubmit={handleLogin}
            className="space-y-4 rounded-3xl border border-slate-200/70 bg-white/85 p-6 shadow-sm backdrop-blur"
          >
            <label className="text-sm font-medium text-slate-700">
              Email User
              <input
                type="email"
                value={loginEmail}
                onChange={(event) => setLoginEmail(event.target.value)}
                className={inputClass}
                placeholder="user@laptop.local"
                required
              />
            </label>
            <label className="text-sm font-medium text-slate-700">
              Password User
              <input
                type="password"
                value={loginPassword}
                onChange={(event) => setLoginPassword(event.target.value)}
                className={inputClass}
                placeholder="password"
                required
              />
            </label>
            {loginError && <p className="text-sm text-red-600">{loginError}</p>}
            <div className="flex flex-wrap gap-3">
              <button type="submit" className={primaryButton}>
                Masuk
              </button>
              <Link href="/" className={secondaryButton}>
                Kembali ke Beranda
              </Link>
            </div>
          </form>
        ) : (
          <form
            onSubmit={handleRegister}
            className="space-y-4 rounded-3xl border border-slate-200/70 bg-white/85 p-6 shadow-sm backdrop-blur"
          >
            <label className="text-sm font-medium text-slate-700">
              Nama (opsional)
              <input
                type="text"
                value={registerName}
                onChange={(event) => setRegisterName(event.target.value)}
                className={inputClass}
                placeholder="cth. Budi"
              />
            </label>
            <label className="text-sm font-medium text-slate-700">
              Email User
              <input
                type="email"
                value={registerEmail}
                onChange={(event) => setRegisterEmail(event.target.value)}
                className={inputClass}
                placeholder="nama@email.com"
                required
              />
            </label>
            <label className="text-sm font-medium text-slate-700">
              Password
              <input
                type="password"
                value={registerPassword}
                onChange={(event) => setRegisterPassword(event.target.value)}
                className={inputClass}
                placeholder="minimal 6 karakter"
                required
              />
            </label>
            <label className="text-sm font-medium text-slate-700">
              Konfirmasi Password
              <input
                type="password"
                value={registerConfirm}
                onChange={(event) => setRegisterConfirm(event.target.value)}
                className={inputClass}
                placeholder="ulang password"
                required
              />
            </label>
            {registerError && (
              <p className="text-sm text-red-600">{registerError}</p>
            )}
            {registerSuccess && (
              <p className="text-sm text-emerald-600">{registerSuccess}</p>
            )}
            <div className="flex flex-wrap gap-3">
              <button type="submit" className={primaryButton}>
                Daftar
              </button>
              <Link href="/" className={secondaryButton}>
                Kembali ke Beranda
              </Link>
            </div>
          </form>
        )}
      </section>
    );
  }

  return (
    <section className="space-y-6">
      <div className="fade-up flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-200/70 bg-white/80 p-6 shadow-sm backdrop-blur">
        <div>
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
            Konsultasi
          </p>
          <h1 className="font-display text-2xl font-semibold text-slate-900">
            Bot Rekomendasi Laptop
          </h1>
          <p className="mt-2 text-sm text-slate-600">
            Masukkan budget dan kebutuhan, lalu tanya bot.
          </p>
        </div>
        <button type="button" onClick={handleLogout} className={secondaryButton}>
          Logout
        </button>
      </div>

      <form
        onSubmit={handleSubmit}
        className="space-y-4 rounded-3xl border border-slate-200/70 bg-white/85 p-6 shadow-sm backdrop-blur"
      >
        <div className="grid gap-4 sm:grid-cols-2">
          <label className="text-sm font-medium text-slate-700">
            Budget Maksimal (Rp)
            <input
              type="number"
              min={1}
              step={500000}
              value={budget}
              onChange={(event) => setBudget(event.target.value)}
              className={inputClass}
              placeholder="cth. 9000000"
              required
            />
          </label>
          <label className="text-sm font-medium text-slate-700 sm:col-span-2">
            Kebutuhan / Catatan
            <textarea
              value={needs}
              onChange={(event) => setNeeds(event.target.value)}
              className={`${inputClass} min-h-[120px]`}
              placeholder="cth. untuk kuliah, desain ringan, baterai awet"
            />
          </label>
        </div>
        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex flex-wrap gap-3">
          <button type="submit" className={primaryButton} disabled={loading}>
            {loading ? "Meminta saran..." : "Minta Saran"}
          </button>
          <button
            type="button"
            onClick={handleResetChat}
            className={secondaryButton}
          >
            Reset Form
          </button>
        </div>
      </form>

      <div className="space-y-3 rounded-3xl border border-slate-200/70 bg-white/85 p-6 shadow-sm backdrop-blur">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <p className="text-sm font-semibold text-slate-900">Riwayat Chat</p>
          <p className="text-xs text-slate-500">
            Data katalog terpakai: {getLaptopCatalog().length} laptop
          </p>
        </div>
        {messages.length === 0 ? (
          <p className="text-sm text-slate-600">
            Belum ada percakapan. Isi form di atas untuk mulai bertanya.
          </p>
        ) : (
          <div className="space-y-3">
            {messages.map((item, index) => (
              <div
                key={`${item.role}-${index}`}
                className={`rounded-2xl border px-4 py-3 text-sm ${
                  item.role === "user"
                    ? "border-slate-200 bg-slate-50 text-slate-800"
                    : "border-emerald-200 bg-emerald-50 text-emerald-900"
                }`}
              >
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                  {item.role === "user" ? "User" : "Bot"}
                </p>
                <p className="mt-2 whitespace-pre-line">{item.text}</p>
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
