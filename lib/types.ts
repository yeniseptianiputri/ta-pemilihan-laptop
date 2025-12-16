export type PurposeOption = "Kuliah/Office" | "Programming" | "Desain" | "Gaming";
export type PriorityOption = "Performa" | "Hemat" | "Seimbang";

export interface Laptop {
  id: string;
  name: string;
  cpuScore: number;
  ramGB: number;
  storageGB: number;
  gpuScore: number;
  price: number;
}

export interface Preference {
  purpose: PurposeOption;
  budget: number;
  priority: PriorityOption;
}

export interface RecommendationItem {
  laptop: Laptop;
  score: number;
  explanation: string;
}

export interface RecommendationResponse {
  preference: Preference;
  results: RecommendationItem[];
  warning?: string;
}
