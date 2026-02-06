import { laptops as defaultLaptops } from "@/data/laptopData";
import { Laptop } from "@/domain/weightedProduct";

const STORAGE_KEY = "laptop-catalog-v1";

const isBrowser = () => typeof window !== "undefined";

export const loadLaptopCatalog = (): Laptop[] => {
  if (!isBrowser()) {
    return defaultLaptops;
  }

  const stored = window.localStorage.getItem(STORAGE_KEY);
  if (!stored) {
    return defaultLaptops;
  }

  try {
    const parsed = JSON.parse(stored) as Laptop[];
    if (!Array.isArray(parsed)) {
      return defaultLaptops;
    }
    return parsed;
  } catch {
    return defaultLaptops;
  }
};

export const saveLaptopCatalog = (items: Laptop[]): void => {
  if (!isBrowser()) {
    return;
  }
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
};

export const resetLaptopCatalog = (): Laptop[] => {
  const next = [...defaultLaptops];
  saveLaptopCatalog(next);
  return next;
};
