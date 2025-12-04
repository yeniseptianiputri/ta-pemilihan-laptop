export default function LaptopCard({ laptop }: any) {
  return (
    <div className="border rounded-lg p-4 shadow hover:shadow-lg transition">
      <h2 className="text-xl font-semibold">{laptop.name}</h2>
      <p className="text-sm text-gray-600">CPU: {laptop.cpu}</p>
      <p className="text-sm text-gray-600">RAM: {laptop.ram} GB</p>
      <p className="font-bold text-blue-600 mt-2">
        Rp {laptop.price.toLocaleString()}
      </p>
    </div>
  );
}
