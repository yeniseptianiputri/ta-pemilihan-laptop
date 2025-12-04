"use client";

import { useState } from "react";
import { laptops } from "../data/laptops";
import LaptopCard from "../components/LaptopCard";
import FilterPanel from "../components/FilterPanel";

export default function Home() {
  const [minRam, setMinRam] = useState(0);
  const [maxPrice, setMaxPrice] = useState(Infinity);

  const filtered = laptops.filter((l) => 
    l.ram >= minRam && l.price <= maxPrice
  );

  return (
    <div className="p-6 max-w-4xl mx-auto">
      <h1 className="text-3xl font-bold mb-4">Aplikasi Pemilihan Laptop</h1>

      <FilterPanel setMinRam={setMinRam} setMaxPrice={setMaxPrice} />

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {filtered.map((l) => (
          <LaptopCard key={l.id} laptop={l} />
        ))}
      </div>
    </div>
  );
}
