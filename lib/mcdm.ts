import { generateExplanation } from "./llm";
import { Laptop, Preference, PriorityOption, RecommendationItem } from "./types";

type Criteria = "cpuScore" | "ramGB" | "storageGB" | "gpuScore" | "price";

type WeightMap = Record<Criteria, number>;

const weightByPriority: Record<PriorityOption, WeightMap> = {
  Performa: {
    cpuScore: 0.3,
    ramGB: 0.25,
    gpuScore: 0.2,
    storageGB: 0.15,
    price: 0.1,
  },
  Hemat: {
    price: 0.35,
    cpuScore: 0.2,
    ramGB: 0.2,
    storageGB: 0.15,
    gpuScore: 0.1,
  },
  Seimbang: {
    cpuScore: 0.25,
    ramGB: 0.2,
    gpuScore: 0.15,
    storageGB: 0.15,
    price: 0.25,
  },
};

interface NormalizedValues {
  cpuScore: number;
  ramGB: number;
  storageGB: number;
  gpuScore: number;
  price: number;
}

const getDominantCriteria = (weights: WeightMap): Criteria => {
  return (Object.entries(weights).sort((a, b) => b[1] - a[1])[0]?.[0] ?? "cpuScore") as Criteria;
};

export interface RankingResult {
  results: RecommendationItem[];
  warning?: string;
}

export const rankLaptops = (
  preference: Preference,
  data: Laptop[]
): RankingResult => {
  const weights = weightByPriority[preference.priority];
  let candidates = data.filter((item) => item.price <= preference.budget);
  let warning: string | undefined;

  if (candidates.length === 0) {
    warning = "budget terlalu rendah";
    candidates = [...data].sort((a, b) => a.price - b.price).slice(0, 3);
  }

  const maxValues: NormalizedValues = candidates.reduce(
    (acc, laptop) => ({
      cpuScore: Math.max(acc.cpuScore, laptop.cpuScore),
      ramGB: Math.max(acc.ramGB, laptop.ramGB),
      storageGB: Math.max(acc.storageGB, laptop.storageGB),
      gpuScore: Math.max(acc.gpuScore, laptop.gpuScore),
      price: acc.price === 0 ? laptop.price : Math.min(acc.price, laptop.price),
    }),
    {
      cpuScore: 0,
      ramGB: 0,
      storageGB: 0,
      gpuScore: 0,
      price: 0,
    }
  );

  if (maxValues.price === 0) {
    maxValues.price = Math.min(...candidates.map((item) => item.price));
  }

  const dominantCriteria = getDominantCriteria(weights);

  const ranked = candidates
    .map((laptop) => {
      const normalized: NormalizedValues = {
        cpuScore: laptop.cpuScore / (maxValues.cpuScore || 1),
        ramGB: laptop.ramGB / (maxValues.ramGB || 1),
        storageGB: laptop.storageGB / (maxValues.storageGB || 1),
        gpuScore: laptop.gpuScore / (maxValues.gpuScore || 1),
        price: maxValues.price / laptop.price,
      };

      const score =
        normalized.cpuScore * weights.cpuScore +
        normalized.ramGB * weights.ramGB +
        normalized.storageGB * weights.storageGB +
        normalized.gpuScore * weights.gpuScore +
        normalized.price * weights.price;

      const scaledScore = Math.round(score * 100 * 100) / 100;

      return {
        laptop,
        score: scaledScore,
        explanation: generateExplanation({
          preference,
          laptop,
          score: scaledScore,
          dominantCriteria,
          weights,
        }),
      } satisfies RecommendationItem;
    })
    .sort((a, b) => b.score - a.score)
    .slice(0, 3);

  return { results: ranked, warning };
};
