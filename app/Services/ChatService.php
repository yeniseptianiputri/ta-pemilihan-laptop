<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ChatService
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-4.1-mini'
    ) {
    }

    public function ask(string $message, ?float $budget, string $useCase, array $catalog): string
    {
        if (trim($this->apiKey) === '') {
            return 'OPENAI_API_KEY belum diset di .env. Silakan isi key dulu untuk memakai bot.';
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi cURL belum aktif di PHP.');
        }

        $prompt = $this->buildPrompt($message, $budget, $useCase, $catalog);

        $payload = json_encode([
            'model' => $this->model,
            'input' => $prompt,
            'temperature' => 0.4,
            'max_output_tokens' => 400,
        ], JSON_THROW_ON_ERROR);

        $curl = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new RuntimeException('Gagal terhubung ke OpenAI API: ' . $error);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Respons OpenAI tidak valid.');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $apiError = is_array($decoded) ? ($decoded['error']['message'] ?? null) : null;
            throw new RuntimeException(
                'OpenAI API error: ' . ($apiError ?: 'status ' . $statusCode)
            );
        }

        $text = $this->extractText($decoded);

        return $text !== '' ? $text : 'Maaf, belum ada jawaban yang bisa ditampilkan.';
    }

    private function buildPrompt(string $message, ?float $budget, string $useCase, array $catalog): string
    {
        $lines = [
            'Anda adalah asisten rekomendasi laptop yang ringkas dan jelas.',
            'Tugas: beri saran laptop sesuai budget dan kebutuhan.',
            'Gunakan katalog di bawah jika memungkinkan. Jika katalog tidak cocok, beri saran umum yang realistis.',
            'Balas dalam bahasa Indonesia, maksimal 6 bullet poin.',
            '',
            'Budget: ' . ($budget !== null ? format_rupiah($budget) : 'tidak disebutkan'),
        ];

        if (trim($useCase) !== '') {
            $lines[] = 'Kebutuhan: ' . trim($useCase);
        }

        $lines[] = 'Pertanyaan pengguna: ' . trim($message);
        $lines[] = '';
        $lines[] = 'Katalog laptop:';
        $lines[] = $this->catalogText($catalog);

        return implode("\n", $lines);
    }

    private function catalogText(array $catalog): string
    {
        if ($catalog === []) {
            return 'Tidak ada data katalog yang dikirim.';
        }

        $rows = [];
        foreach (array_slice($catalog, 0, 30) as $index => $item) {
            $rows[] = sprintf(
                '%d. %s | RAM %dGB | Storage %dGB | Prosesor %d | Harga %d',
                $index + 1,
                (string)($item['name'] ?? '-'),
                (int)($item['ram'] ?? 0),
                (int)($item['storage'] ?? 0),
                (int)($item['processor'] ?? 0),
                (int)($item['price'] ?? 0)
            );
        }

        return implode("\n", $rows);
    }

    private function extractText(array $payload): string
    {
        if (!isset($payload['output']) || !is_array($payload['output'])) {
            return '';
        }

        $parts = [];
        foreach ($payload['output'] as $item) {
            if (($item['type'] ?? null) !== 'message' || !isset($item['content']) || !is_array($item['content'])) {
                continue;
            }

            foreach ($item['content'] as $content) {
                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    $parts[] = $content['text'];
                }
            }
        }

        return trim(implode('', $parts));
    }
}

