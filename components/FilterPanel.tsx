export default function FilterPanel({ setMinRam, setMaxPrice }: any) {
  return (
    <div className="flex gap-4 mb-6">
      <input
        type="number"
        placeholder="Minimal RAM (GB)"
        onChange={(e) => setMinRam(Number(e.target.value))}
        className="border p-2 rounded"
      />

      <input
        type="number"
        placeholder="Harga Maks (Rp)"
        onChange={(e) => setMaxPrice(Number(e.target.value))}
        className="border p-2 rounded"
      />
    </div>
  );
}
