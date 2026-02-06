"use client";

import { useState } from "react";
import { getLaptopCatalog } from "@/application/laptopService";

const currency = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});

export default function LaptopCatalog() {
  const [query, setQuery] = useState("");
  const items = getLaptopCatalog();
  const keyword = query.trim().toLowerCase();
  const filtered = keyword
    ? items.filter((item) => item.name.toLowerCase().includes(keyword))
    : items;

  return (
    <section className="fade-up space-y-4 rounded-3xl border border-slate-200/70 bg-white/85 p-6 shadow-sm backdrop-blur">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div className="space-y-2">
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
            Katalog
          </p>
          <h2 className="font-display text-xl font-semibold text-slate-900">
            Daftar Laptop
          </h2>
          <p className="text-sm text-slate-600">
            Data laptop tersimpan lokal dan bisa dikelola lewat halaman admin.
          </p>
        </div>
        <label className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
          Cari laptop
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="cth. Laptop A"
            className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200 sm:w-64"
          />
        </label>
      </div>

      <div className="text-xs text-slate-500">
        Menampilkan {filtered.length} dari {items.length} laptop
      </div>

      <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-100/80 text-xs uppercase tracking-wider text-slate-600">
            <tr>
              <th className="px-4 py-3">Nama</th>
              <th className="px-4 py-3">RAM</th>
              <th className="px-4 py-3">Storage</th>
              <th className="px-4 py-3">Prosesor</th>
              <th className="px-4 py-3">Harga</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {filtered.map((item) => (
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
              </tr>
            ))}
            {filtered.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-slate-500">
                  Laptop tidak ditemukan.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </section>
  );
}
