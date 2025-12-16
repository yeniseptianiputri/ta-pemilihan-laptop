"use client";

import { FormEvent, useState } from "react";
import { Preference, PriorityOption, PurposeOption } from "../lib/types";
import Badge from "./Badge";

interface FormCardProps {
  onSubmit: (preference: Preference) => void;
  isSubmitting?: boolean;
}

const purposeOptions: PurposeOption[] = [
  "Kuliah/Office",
  "Programming",
  "Desain",
  "Gaming",
];

const priorityOptions: PriorityOption[] = [
  "Performa",
  "Hemat",
  "Seimbang",
];

const FormCard = ({ onSubmit, isSubmitting }: FormCardProps) => {
  const [purpose, setPurpose] = useState<PurposeOption>(purposeOptions[0]);
  const [priority, setPriority] = useState<PriorityOption>(priorityOptions[0]);
  const [budget, setBudget] = useState<string>("");
  const [error, setError] = useState<string>("");

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const parsedBudget = Number(budget);

    if (!parsedBudget || parsedBudget <= 0) {
      setError("Budget harus lebih dari 0");
      return;
    }

    setError("");
    onSubmit({
      purpose,
      priority,
      budget: parsedBudget,
    });
  };

  return (
    <form
      onSubmit={handleSubmit}
      className="space-y-6 rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur"
    >
      <div>
        <div className="flex items-center justify-between">
          <h2 className="text-xl font-semibold text-slate-900">
            Preferensi Pembelian
          </h2>
          <Badge>Role Pelanggan</Badge>
        </div>
        <p className="mt-1 text-sm text-slate-500">
          Lengkapi kebutuhan Anda lalu dapatkan rekomendasi terbaik.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <label className="text-sm font-medium text-slate-700">
          Tujuan Penggunaan
          <select
            className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none"
            value={purpose}
            onChange={(event) => setPurpose(event.target.value as PurposeOption)}
          >
            {purposeOptions.map((option) => (
              <option key={option}>{option}</option>
            ))}
          </select>
        </label>

        <label className="text-sm font-medium text-slate-700">
          Budget Maksimal (Rp)
          <input
            type="number"
            min={0}
            step={500000}
            placeholder="cth. 12000000"
            value={budget}
            onChange={(event) => setBudget(event.target.value)}
            className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none"
          />
        </label>
      </div>

      <label className="text-sm font-medium text-slate-700">
        Prioritas Utama
        <select
          className="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none"
          value={priority}
          onChange={(event) => setPriority(event.target.value as PriorityOption)}
        >
          {priorityOptions.map((option) => (
            <option key={option}>{option}</option>
          ))}
        </select>
      </label>

      {error && <p className="text-sm text-red-600">{error}</p>}

      <button
        type="submit"
        disabled={isSubmitting}
        className="w-full rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:bg-blue-300"
      >
        {isSubmitting ? "Mengolah..." : "Dapatkan Rekomendasi"}
      </button>
    </form>
  );
};

export default FormCard;
