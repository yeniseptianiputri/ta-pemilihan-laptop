"use client";

import { WeightedProductResult } from "@/domain/weightedProduct";
import { jelaskanHasil } from "@/lib/llmHelper";

const currency = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});

interface LaptopTableProps {
  items: WeightedProductResult[];
}

export default function LaptopTable({ items }: LaptopTableProps) {
  if (items.length === 0) {
    return (
      <p className="text-sm text-slate-600">
        Belum ada hasil. Isi kriteria lalu tekan tombol untuk melihat rekomendasi.
      </p>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
            Hasil
          </p>
          <h3 className="font-display text-xl font-semibold text-slate-900">
            Rekomendasi Teratas
          </h3>
        </div>
        <p className="text-xs text-slate-500">
          Total {items.length} laptop dinilai
        </p>
      </div>

      <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-100/80 text-xs uppercase tracking-wider text-slate-600">
            <tr>
              <th className="px-4 py-3">Rank</th>
              <th className="px-4 py-3">Nama</th>
              <th className="px-4 py-3">RAM</th>
              <th className="px-4 py-3">Storage</th>
              <th className="px-4 py-3">Prosesor</th>
              <th className="px-4 py-3">Harga</th>
              <th className="px-4 py-3">Skor WP</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {items.map((item, index) => {
              const isTop = index === 0;
              return (
                <tr
                  key={item.id}
                  className={`transition ${
                    isTop ? "bg-emerald-50/60" : "hover:bg-slate-50"
                  }`}
                >
                  <td className="px-4 py-3 text-sm font-semibold text-slate-700">
                    {index + 1}
                  </td>
                  <td className="px-4 py-3 font-medium text-slate-900">
                    {item.name}
                  </td>
                  <td className="px-4 py-3">{item.ram} GB</td>
                  <td className="px-4 py-3">{item.storage} GB</td>
                  <td className="px-4 py-3">{item.processor}</td>
                  <td className="px-4 py-3 tabular-nums">
                    {currency.format(item.price)}
                  </td>
                  <td className="px-4 py-3 tabular-nums">
                    {item.skor.toFixed(4)}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <div className="space-y-3 rounded-2xl border border-slate-200 bg-white/90 p-5 text-sm text-slate-600">
        <p className="font-semibold text-slate-700">Penjelasan singkat</p>
        <div className="space-y-2">
          {items.map((item) => (
            <p key={`${item.id}-reason`}>{jelaskanHasil(item)}</p>
          ))}
        </div>
      </div>
    </div>
  );
}
