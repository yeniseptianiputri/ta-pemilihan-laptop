"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import ResultCard from "../../components/ResultCard";
import Badge from "../../components/Badge";
import Loading from "../../components/Loading";
import { laptopCatalog } from "../../lib/mockData";
import { rankLaptops } from "../../lib/mcdm";
import { loadPreference } from "../../lib/storage";
import { Preference, RecommendationItem } from "../../lib/types";

const currency = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});

export default function RecommendationPage() {
  const router = useRouter();
  const [preference, setPreference] = useState<Preference | null>(null);
  const [results, setResults] = useState<RecommendationItem[]>([]);
  const [warning, setWarning] = useState<string | undefined>();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const stored = loadPreference();
    if (!stored) {
      setLoading(false);
      return;
    }

    const { results: ranked, warning: warn } = rankLaptops(stored, laptopCatalog);
    setPreference(stored);
    setResults(ranked);
    setWarning(warn);
    setLoading(false);
  }, []);

  if (loading) {
    return <Loading />;
  }

  if (!preference) {
    return (
      <div className="rounded-2xl border border-slate-200 bg-white p-8 text-center">
        <p className="text-base text-slate-600">
          Preferensi belum ditemukan. Silakan isi formulir terlebih dahulu.
        </p>
        <button
          onClick={() => router.push("/")}
          className="mt-6 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-500"
        >
          Kembali ke Form
        </button>
      </div>
    );
  }

  return (
    <section className="space-y-8">
      <div className="space-y-3">
        <p className="text-sm font-semibold uppercase tracking-wide text-blue-600">
          Rekomendasi Anda
        </p>
        <h1 className="text-3xl font-semibold text-slate-900">
          Top 3 laptop terbaik untuk preferensi ini
        </h1>
        <p className="text-base text-slate-600">
          Metode SAW menilai performa, kapasitas memori, grafis, dan harga lalu
          memberikan skor akhir berskala 0-100.
        </p>
      </div>

      <div className="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm">
        <div className="flex flex-wrap items-center gap-4">
          <Badge tone="default">{preference.purpose}</Badge>
          <Badge tone="default">Prioritas: {preference.priority}</Badge>
          <Badge tone="default">Budget: {currency.format(preference.budget)}</Badge>
          {warning && <Badge tone="warning">Catatan: {warning}</Badge>}
        </div>
      </div>

      <div className="grid gap-4">
        {results.map((item, index) => (
          <ResultCard key={item.laptop.id} item={item} index={index} />
        ))}
      </div>

      <button
        type="button"
        onClick={() => router.push("/")}
        className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
      >
        Ubah preferensi
      </button>
    </section>
  );
}
