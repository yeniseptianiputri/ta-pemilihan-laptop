export interface Laptop {
  id: number;
  name: string;
  ram: number;
  storage: number;
  processor: number;
  price: number;
}

export interface Bobot {
  ram: number;
  storage: number;
  processor: number;
  price: number;
}

export interface WeightedProductResult extends Laptop {
  skor: number;
}

export function hitungWeightedProduct(
  laptops: Laptop[],
  bobot: Bobot
): WeightedProductResult[] {
  const hasil = laptops.map((laptop) => {
    const skor =
      Math.pow(laptop.ram, bobot.ram) *
      Math.pow(laptop.storage, bobot.storage) *
      Math.pow(laptop.processor, bobot.processor) *
      Math.pow(laptop.price, -bobot.price);

    return {
      ...laptop,
      skor,
    };
  });

  return hasil.sort((a, b) => b.skor - a.skor);
}
