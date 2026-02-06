"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import {
  createLaptop,
  deleteLaptop,
  getLaptopCatalog,
  restoreDefaultLaptops,
  updateLaptop,
} from "@/application/laptopService";
import { Laptop } from "@/domain/weightedProduct";

const currency = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});

const ADMIN_EMAIL =
  process.env.NEXT_PUBLIC_ADMIN_EMAIL ?? "admin@laptop.local";
const ADMIN_PASSWORD =
  process.env.NEXT_PUBLIC_ADMIN_PASSWORD ?? "admin123";

interface LaptopFormState {
  name: string;
  ram: string;
  storage: string;
  processor: string;
  price: string;
}

const emptyForm: LaptopFormState = {
  name: "",
  ram: "",
  storage: "",
  processor: "",
  price: "",
};

export default function AdminPage() {
  const [isAuthed, setIsAuthed] = useState(false);
  const [loginEmail, setLoginEmail] = useState("");
  const [loginPassword, setLoginPassword] = useState("");
  const [loginError, setLoginError] = useState("");

  const [items, setItems] = useState<Laptop[]>([]);
  const [form, setForm] = useState<LaptopFormState>(emptyForm);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formError, setFormError] = useState("");

  const inputClass =
    "mt-2 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200";
  const primaryButton =
    "rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800";
  const secondaryButton =
    "rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100";
  const ghostButton =
    "rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-100";
  const dangerButton =
    "rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50";

  const handleLogin = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (loginEmail === ADMIN_EMAIL && loginPassword === ADMIN_PASSWORD) {
      setIsAuthed(true);
      setItems(getLaptopCatalog());
      setLoginError("");
    } else {
      setLoginError("Email atau password admin salah.");
    }
  };

  const handleLogout = () => {
    setIsAuthed(false);
    setItems([]);
    resetForm();
  };

  const handleFormChange = (
    field: keyof LaptopFormState,
    value: string
  ) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const resetForm = () => {
    setForm(emptyForm);
    setEditingId(null);
    setFormError("");
  };

  const handleEdit = (item: Laptop) => {
    setEditingId(item.id);
    setForm({
      name: item.name,
      ram: String(item.ram),
      storage: String(item.storage),
      processor: String(item.processor),
      price: String(item.price),
    });
  };

  const handleDelete = (id: number) => {
    const next = deleteLaptop(id);
    setItems(next);
    if (editingId === id) {
      resetForm();
    }
  };

  const handleReset = () => {
    const next = restoreDefaultLaptops();
    setItems(next);
    resetForm();
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const name = form.name.trim();
    const ram = Number(form.ram);
    const storage = Number(form.storage);
    const processor = Number(form.processor);
    const price = Number(form.price);

    if (!name) {
      setFormError("Nama laptop wajib diisi.");
      return;
    }
    if (![ram, storage, processor, price].every((value) => value > 0)) {
      setFormError("Semua angka harus lebih dari 0.");
      return;
    }

    setFormError("");
    const payload = { name, ram, storage, processor, price };

    if (editingId) {
      setItems(updateLaptop(editingId, payload));
    } else {
      setItems(createLaptop(payload));
    }

    resetForm();
  };

  if (!isAuthed) {
    return (
      <section className="space-y-6">
        <div className="fade-up rounded-3xl border border-slate-200/70 bg-white/80 p-6 shadow-sm backdrop-blur">
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
            Area Admin
          </p>
          <h1 className="font-display text-2xl font-semibold text-slate-900">
            Admin Panel
          </h1>
          <p className="mt-2 text-sm text-slate-600">
            Login untuk mengelola spesifikasi laptop.
          </p>
        </div>

        <form
          onSubmit={handleLogin}
          className="space-y-4 rounded-3xl border border-slate-200/70 bg-white/85 p-6 shadow-sm backdrop-blur"
        >
          <label className="text-sm font-medium text-slate-700">
            Email Admin
            <input
              type="email"
              value={loginEmail}
              onChange={(event) => setLoginEmail(event.target.value)}
              className={inputClass}
              placeholder="admin@laptop.local"
              required
            />
          </label>
          <label className="text-sm font-medium text-slate-700">
            Password Admin
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
            <button
              type="submit"
              className={primaryButton}
            >
              Masuk Admin
            </button>
            <Link
              href="/"
              className={secondaryButton}
            >
              Kembali ke Landing
            </Link>
          </div>
        </form>
      </section>
    );
  }

  return (
    <section className="space-y-8">
      <div className="fade-up flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-200/70 bg-white/80 p-6 shadow-sm backdrop-blur">
        <div>
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
            Area Admin
          </p>
          <h1 className="font-display text-2xl font-semibold text-slate-900">
            Admin Panel
          </h1>
          <p className="mt-2 text-sm text-slate-600">
            Kelola data spesifikasi laptop di katalog.
          </p>
        </div>
        <div className="flex flex-wrap gap-3">
          <Link href="/" className={secondaryButton}>
            Landing Page
          </Link>
          <button type="button" onClick={handleLogout} className={primaryButton}>
            Logout
          </button>
        </div>
      </div>

      <form
        onSubmit={handleSubmit}
        className="grid gap-4 rounded-3xl border border-slate-200/70 bg-white/85 p-6 shadow-sm backdrop-blur sm:grid-cols-2"
      >
        <div className="sm:col-span-2">
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
            Form
          </p>
          <h2 className="font-display text-lg font-semibold text-slate-900">
            {editingId ? "Edit Laptop" : "Tambah Laptop"}
          </h2>
          <p className="text-sm text-slate-600">
            Isi seluruh spesifikasi agar data konsisten.
          </p>
        </div>
        <label className="text-sm font-medium text-slate-700">
          Nama Laptop
          <input
            value={form.name}
            onChange={(event) => handleFormChange("name", event.target.value)}
            className={inputClass}
            placeholder="cth. Laptop X"
            required
          />
        </label>
        <label className="text-sm font-medium text-slate-700">
          RAM (GB)
          <input
            type="number"
            min={1}
            value={form.ram}
            onChange={(event) => handleFormChange("ram", event.target.value)}
            className={inputClass}
            required
          />
        </label>
        <label className="text-sm font-medium text-slate-700">
          Storage (GB)
          <input
            type="number"
            min={1}
            value={form.storage}
            onChange={(event) => handleFormChange("storage", event.target.value)}
            className={inputClass}
            required
          />
        </label>
        <label className="text-sm font-medium text-slate-700">
          Prosesor (skor)
          <input
            type="number"
            min={1}
            value={form.processor}
            onChange={(event) =>
              handleFormChange("processor", event.target.value)
            }
            className={inputClass}
            required
          />
        </label>
        <label className="text-sm font-medium text-slate-700">
          Harga (Rp)
          <input
            type="number"
            min={1}
            step={500000}
            value={form.price}
            onChange={(event) => handleFormChange("price", event.target.value)}
            className={inputClass}
            required
          />
        </label>
        {formError && (
          <p className="sm:col-span-2 text-sm text-red-600">{formError}</p>
        )}
        <div className="flex flex-wrap gap-3 sm:col-span-2">
          <button
            type="submit"
            className={primaryButton}
          >
            {editingId ? "Simpan Perubahan" : "Tambah Laptop"}
          </button>
          {editingId && (
            <button
              type="button"
              onClick={resetForm}
              className={secondaryButton}
            >
              Batal Edit
            </button>
          )}
          <button
            type="button"
            onClick={handleReset}
            className={secondaryButton}
          >
            Reset ke Default
          </button>
        </div>
      </form>

      <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-100/80 text-xs uppercase tracking-wider text-slate-600">
            <tr>
              <th className="px-4 py-3">Nama</th>
              <th className="px-4 py-3">RAM</th>
              <th className="px-4 py-3">Storage</th>
              <th className="px-4 py-3">Prosesor</th>
              <th className="px-4 py-3">Harga</th>
              <th className="px-4 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {items.map((item) => (
              <tr key={item.id} className="transition hover:bg-slate-50">
                <td className="px-4 py-3 font-medium text-slate-900">
                  {item.name}
                </td>
                <td className="px-4 py-3">{item.ram} GB</td>
                <td className="px-4 py-3">{item.storage} GB</td>
                <td className="px-4 py-3">{item.processor}</td>
                <td className="px-4 py-3 tabular-nums">
                  {currency.format(item.price)}
                </td>
                <td className="px-4 py-3">
                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() => handleEdit(item)}
                      className={ghostButton}
                    >
                      Edit
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(item.id)}
                      className={dangerButton}
                    >
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {items.length === 0 && (
              <tr>
                <td
                  colSpan={6}
                  className="px-4 py-6 text-center text-slate-500"
                >
                  Belum ada data laptop.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </section>
  );
}
