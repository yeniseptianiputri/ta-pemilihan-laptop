"use client";

import { FormEvent, useState } from "react";
import { getLaptopCatalog } from "@/application/laptopService";
import { LaptopFilter } from "@/application/rekomendasiService";
import { Bobot } from "@/domain/weightedProduct";

interface LaptopFormProps {
  onSubmit: (bobot: Bobot, filter: LaptopFilter) => void;
}

const defaultBobot: Bobot = {
  ram: 0.3,
  storage: 0.2,
  processor: 0.3,
  price: 0.2,
};

const emptyFilter: Required<LaptopFilter> = {
  name: "",
  minRam: 0,
  minStorage: 0,
  minProcessor: 0,
  maxPrice: 0,
};

export default function LaptopForm({ onSubmit }: LaptopFormProps) {
  const [filter, setFilter] = useState<Required<LaptopFilter>>(emptyFilter);
  const inputClass =
    "mt-2 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200";
  const items = getLaptopCatalog();
  const names = [...new Set(items.map((item) => item.name))].sort((a, b) =>
    a.localeCompare(b, "id")
  );
  const ramOptions = [...new Set(items.map((item) => item.ram))].sort(
    (a, b) => a - b
  );
  const storageOptions = [...new Set(items.map((item) => item.storage))].sort(
    (a, b) => a - b
  );
  const processorOptions = [
    ...new Set(items.map((item) => item.processor)),
  ].sort((a, b) => a - b);
  const priceOptions = [...new Set(items.map((item) => item.price))].sort(
    (a, b) => a - b
  );
  const currency = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  });

  const handleChange = (field: keyof LaptopFilter, value: string) => {
    if (field === "name") {
      setFilter((prev) => ({ ...prev, [field]: value }));
      return;
    }

    const numeric = Number(value);
    setFilter((prev) => ({
      ...prev,
      [field]: Number.isNaN(numeric) ? 0 : numeric,
    }));
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    onSubmit(defaultBobot, filter);
  };

  const handleReset = () => {
    setFilter(emptyFilter);
  };

  return (
    <form
      onSubmit={handleSubmit}
      className="fade-up space-y-4 rounded-2xl border border-slate-200/70 bg-white/85 p-5 shadow-sm backdrop-blur"
    >
      <div className="space-y-1">
        <p className="text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-slate-500">
          Langkah 1
        </p>
        <p className="font-semibold text-slate-900">
          Isi kriteria yang diinginkan.
        </p>
        <p className="text-sm text-slate-600">
          Bobot masih standar: RAM 30%, Storage 20%, Prosesor 30%, Harga 20%.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <label className="text-sm font-medium text-slate-700 sm:col-span-2">
          Nama Laptop
          <select
            value={filter.name}
            onChange={(event) => handleChange("name", event.target.value)}
            className={inputClass}
          >
            <option value="">Semua laptop</option>
            {names.map((name) => (
              <option key={name} value={name}>
                {name}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm font-medium text-slate-700">
          RAM Minimal (GB)
          <select
            value={filter.minRam || ""}
            onChange={(event) => handleChange("minRam", event.target.value)}
            className={inputClass}
          >
            <option value="">Semua RAM</option>
            {ramOptions.map((value) => (
              <option key={value} value={value}>
                {value} GB
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm font-medium text-slate-700">
          Storage Minimal (GB)
          <select
            value={filter.minStorage || ""}
            onChange={(event) => handleChange("minStorage", event.target.value)}
            className={inputClass}
          >
            <option value="">Semua storage</option>
            {storageOptions.map((value) => (
              <option key={value} value={value}>
                {value} GB
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm font-medium text-slate-700">
          Prosesor Minimal (skor)
          <select
            value={filter.minProcessor || ""}
            onChange={(event) => handleChange("minProcessor", event.target.value)}
            className={inputClass}
          >
            <option value="">Semua prosesor</option>
            {processorOptions.map((value) => (
              <option key={value} value={value}>
                {value}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm font-medium text-slate-700">
          Budget Maksimal (Rp)
          <select
            value={filter.maxPrice || ""}
            onChange={(event) => handleChange("maxPrice", event.target.value)}
            className={inputClass}
          >
            <option value="">Semua harga</option>
            {priceOptions.map((value) => (
              <option key={value} value={value}>
                {currency.format(value)}
              </option>
            ))}
          </select>
        </label>
      </div>

      <div className="flex flex-wrap gap-3">
        <button
          type="submit"
          className="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
        >
          Cari Rekomendasi
        </button>
        <button
          type="button"
          onClick={handleReset}
          className="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
        >
          Reset Kriteria
        </button>
      </div>
    </form>
  );
}
