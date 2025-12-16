import { Laptop, Preference } from "./types";

type CriteriaKey = "cpuScore" | "ramGB" | "storageGB" | "gpuScore" | "price";

interface ExplanationArgs {
  preference: Preference;
  laptop: Laptop;
  score: number;
  dominantCriteria: CriteriaKey;
  weights: Record<CriteriaKey, number>;
}

const criteriaLabel: Record<CriteriaKey, string> = {
  cpuScore: "CPU",
  ramGB: "RAM",
  storageGB: "penyimpanan",
  gpuScore: "grafis",
  price: "harga",
};

export const generateExplanation = ({
  preference,
  laptop,
  score,
  dominantCriteria,
  weights,
}: ExplanationArgs): string => {
  const focus = preference.priority.toLowerCase();
  const dominantLabel = criteriaLabel[dominantCriteria];
  const highlight = Object.entries(weights)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 2)
    .map(([key]) => criteriaLabel[key as CriteriaKey])
    .join(" dan ");

  return `Untuk tujuan ${preference.purpose.toLowerCase()}, ${laptop.name} menawarkan keseimbangan ${highlight}. ` +
    `Skor akhir ${score.toFixed(2)} menunjukkan ia memenuhi fokus ${focus} dengan ${dominantLabel} yang dominan serta harga yang masih relevan dengan anggaran Anda.`;
};
