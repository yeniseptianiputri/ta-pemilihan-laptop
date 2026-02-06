import {
  Bobot,
  hitungWeightedProduct,
  WeightedProductResult,
} from "@/domain/weightedProduct";
import { getLaptopCatalog } from "./laptopService";

export interface LaptopFilter {
  name?: string;
  minRam?: number;
  minStorage?: number;
  minProcessor?: number;
  maxPrice?: number;
}

export function prosesRekomendasi(
  bobot: Bobot,
  filter?: LaptopFilter
): WeightedProductResult[] {
  let katalog = getLaptopCatalog();

  if (filter) {
    const keyword = filter.name?.trim().toLowerCase() ?? "";
    katalog = katalog.filter((item) => {
      if (keyword && !item.name.toLowerCase().includes(keyword)) {
        return false;
      }
      if (filter.minRam && item.ram < filter.minRam) {
        return false;
      }
      if (filter.minStorage && item.storage < filter.minStorage) {
        return false;
      }
      if (filter.minProcessor && item.processor < filter.minProcessor) {
        return false;
      }
      if (filter.maxPrice && item.price > filter.maxPrice) {
        return false;
      }
      return true;
    });
  }

  return hitungWeightedProduct(katalog, bobot);
}
