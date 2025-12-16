"use client";

import { useState } from "react";
import { RecommendationItem } from "../lib/types";
import Badge from "./Badge";

const currency = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});

interface ResultCardProps {
  item: RecommendationItem;
  index: number;
}

const ResultCard = ({ item, index }: ResultCardProps) => {
  const [open, setOpen] = useState(false);

  return (
    <div className="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm">
      <div className="flex items-start justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <Badge>#{index + 1}</Badge>
            <p className="text-xs font-medium text-slate-500">
              Skor {item.score.toFixed(2)} / 100
            </p>
          </div>
          <h3 className="mt-2 text-lg font-semibold text-slate-900">
            {item.laptop.name}
          </h3>
          <p className="text-sm text-slate-500">{currency.format(item.laptop.price)}</p>
        </div>
        <div className="text-right text-sm text-slate-500">
          <p>
            {item.laptop.ramGB} GB RAM / {item.laptop.storageGB} GB SSD
          </p>
          <p>CPU {item.laptop.cpuScore} / GPU {item.laptop.gpuScore}</p>
        </div>
      </div>

      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="mt-4 inline-flex w-full items-center justify-between rounded-xl bg-slate-50 px-4 py-2 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
      >
        <span>Lihat alasan</span>
        <span>{open ? "-" : "+"}</span>
      </button>

      {open && (
        <p className="mt-3 text-sm leading-6 text-slate-600">{item.explanation}</p>
      )}
    </div>
  );
};

export default ResultCard;
