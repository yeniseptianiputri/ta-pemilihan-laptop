"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import FormCard from "../components/FormCard";
import Badge from "../components/Badge";
import { savePreference } from "../lib/storage";
import { Preference } from "../lib/types";

export default function HomePage() {
  const router = useRouter();
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = (preference: Preference) => {
    setSubmitting(true);
    savePreference(preference);
    router.push("/recommendation");
  };

  return (
    <section className="space-y-10">
      <div className="space-y-4 text-center">
        <Badge>SPK Pembelian Laptop</Badge>
        <h1 className="text-3xl font-semibold text-slate-900 sm:text-4xl">
          Temukan laptop ideal sesuai kebutuhan Anda
        </h1>
        <p className="mx-auto max-w-2xl text-base text-slate-600">
          Sistem ini membantu pelanggan menentukan pilihan melalui metode SAW
          sederhana. Masukkan kebutuhan, lalu kami tampilkan 3 rekomendasi utama
          yang siap dibeli.
        </p>
      </div>

      <FormCard onSubmit={handleSubmit} isSubmitting={submitting} />
    </section>
  );
}
