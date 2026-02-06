import { Laptop } from "@/domain/weightedProduct";

export function jelaskanHasil(laptop: Laptop): string {
  return `Laptop ${laptop.name} direkomendasikan karena memiliki RAM ${laptop.ram} GB dan harga yang sesuai.`;
}
