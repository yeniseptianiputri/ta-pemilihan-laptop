import { Laptop } from "@/domain/weightedProduct";
import {
  loadLaptopCatalog,
  resetLaptopCatalog,
  saveLaptopCatalog,
} from "@/lib/laptopStorage";

const getNextId = (items: Laptop[]): number => {
  if (items.length === 0) {
    return 1;
  }
  return Math.max(...items.map((item) => item.id)) + 1;
};

export const getLaptopCatalog = (): Laptop[] => loadLaptopCatalog();

export const createLaptop = (payload: Omit<Laptop, "id">): Laptop[] => {
  const items = loadLaptopCatalog();
  const nextItem: Laptop = {
    id: getNextId(items),
    ...payload,
  };
  const next = [nextItem, ...items];
  saveLaptopCatalog(next);
  return next;
};

export const updateLaptop = (
  id: number,
  payload: Omit<Laptop, "id">
): Laptop[] => {
  const items = loadLaptopCatalog();
  const next = items.map((item) =>
    item.id === id ? { id, ...payload } : item
  );
  saveLaptopCatalog(next);
  return next;
};

export const deleteLaptop = (id: number): Laptop[] => {
  const items = loadLaptopCatalog();
  const next = items.filter((item) => item.id !== id);
  saveLaptopCatalog(next);
  return next;
};

export const restoreDefaultLaptops = (): Laptop[] => resetLaptopCatalog();
