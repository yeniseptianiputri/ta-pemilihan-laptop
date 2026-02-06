import { NextResponse } from "next/server";

type CatalogItem = {
  name: string;
  ram: number;
  storage: number;
  processor: number;
  price: number;
};

type ChatRequest = {
  message?: string;
  budget?: number;
  useCase?: string;
  catalog?: CatalogItem[];
};

const MODEL = process.env.OPENAI_MODEL ?? "gpt-4.1-mini";

const buildCatalogText = (catalog: CatalogItem[]) => {
  if (catalog.length === 0) {
    return "Tidak ada data katalog yang dikirim.";
  }
  return catalog
    .map(
      (item, index) =>
        `${index + 1}. ${item.name} | RAM ${item.ram}GB | Storage ${item.storage}GB | Prosesor ${item.processor} | Harga ${item.price}`
    )
    .join("\n");
};

const extractOutputText = (payload: unknown): string => {
  const data = payload as {
    output?: Array<{
      type?: string;
      content?: Array<{ type?: string; text?: string }>;
    }>;
  };

  if (!Array.isArray(data.output)) {
    return "";
  }

  const parts: string[] = [];
  for (const item of data.output) {
    if (item?.type !== "message" || !Array.isArray(item.content)) {
      continue;
    }
    for (const content of item.content) {
      if (content?.type === "output_text" && typeof content.text === "string") {
        parts.push(content.text);
      }
    }
  }

  return parts.join("").trim();
};

export async function POST(request: Request) {
  const apiKey = process.env.OPENAI_API_KEY;
  if (!apiKey) {
    return NextResponse.json(
      { error: "OPENAI_API_KEY belum diset di server." },
      { status: 500 }
    );
  }

  let body: ChatRequest;
  try {
    body = (await request.json()) as ChatRequest;
  } catch {
    return NextResponse.json({ error: "Format JSON tidak valid." }, { status: 400 });
  }

  const message = body.message?.trim();
  if (!message) {
    return NextResponse.json({ error: "Pesan wajib diisi." }, { status: 400 });
  }

  const budget =
    typeof body.budget === "number" && Number.isFinite(body.budget)
      ? body.budget
      : undefined;
  const useCase = body.useCase?.trim() ?? "";
  const catalog = Array.isArray(body.catalog) ? body.catalog.slice(0, 30) : [];

  const promptLines = [
    "Anda adalah asisten rekomendasi laptop yang ringkas dan jelas.",
    "Tugas: beri saran laptop sesuai budget dan kebutuhan.",
    "Gunakan katalog di bawah jika memungkinkan. Jika katalog tidak cocok, berikan saran umum yang masuk akal.",
    "Balas dalam bahasa Indonesia, maksimal 6 bullet poin.",
    "",
    `Budget: ${budget ? `Rp ${budget}` : "tidak disebutkan"}`,
  ];
  if (useCase) {
    promptLines.push(`Kebutuhan: ${useCase}`);
  }
  promptLines.push(`Pertanyaan pengguna: ${message}`);
  promptLines.push("");
  promptLines.push("Katalog laptop:");
  promptLines.push(buildCatalogText(catalog));

  const prompt = promptLines.join("\n");

  const response = await fetch("https://api.openai.com/v1/responses", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${apiKey}`,
    },
    body: JSON.stringify({
      model: MODEL,
      input: prompt,
      temperature: 0.4,
      max_output_tokens: 400,
    }),
  });

  if (!response.ok) {
    const errorText = await response.text();
    return NextResponse.json(
      { error: `OpenAI API error: ${errorText}` },
      { status: response.status }
    );
  }

  const data = await response.json();
  const text = extractOutputText(data);

  return NextResponse.json({
    text: text || "Maaf, belum ada jawaban yang bisa ditampilkan.",
  });
}
