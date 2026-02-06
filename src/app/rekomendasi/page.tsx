"use client";

import { useState } from "react";
import LaptopForm from "@/components/LaptopForm";
import LaptopTable from "@/components/LaptopTable";
import {
  LaptopFilter,
  prosesRekomendasi,
} from "@/application/rekomendasiService";
import { Bobot, WeightedProductResult } from "@/domain/weightedProduct";

export default function RekomendasiPage() {
  const [hasil, setHasil] = useState<WeightedProductResult[]>([]);

  const handleSubmit = (bobot: Bobot, filter: LaptopFilter) => {
    const rekomendasi = prosesRekomendasi(bobot, filter);
    setHasil(rekomendasi);
  };

  return (
    <section className="space-y-8">
      <div className="fade-up rounded-3xl border border-slate-200/70 bg-white/80 p-6 shadow-sm backdrop-blur">
        <div className="space-y-3">
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
            Rekomendasi
          </p>
          <h2 className="font-display text-2xl font-semibold text-slate-900">
            Halaman Rekomendasi Laptop
          </h2>
          <p className="text-sm leading-relaxed text-slate-600">
            Alur sederhana: tekan tombol, proses Weighted Product, lalu lihat
            tabel hasil. Data diambil dari katalog laptop lokal.
          </p>
        </div>
      </div>

      <LaptopForm onSubmit={handleSubmit} />
      <LaptopTable items={hasil} />
    </section>
  );
}
