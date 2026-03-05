<?php

declare(strict_types=1);

namespace App\Services;

final class RecommendationService
{
    private array $defaultWeights = [
        'ram' => 0.3,
        'storage' => 0.2,
        'processor' => 0.3,
        'price' => 0.2,
    ];

    public function recommend(array $catalog, array $filters): array
    {
        $filtered = array_values(array_filter($catalog, function (array $item) use ($filters): bool {
            $name = strtolower(trim((string)($filters['name'] ?? '')));
            if ($name !== '' && !str_contains(strtolower((string)$item['name']), $name)) {
                return false;
            }

            $minRam = (int)($filters['min_ram'] ?? 0);
            if ($minRam > 0 && (int)$item['ram'] < $minRam) {
                return false;
            }

            $minStorage = (int)($filters['min_storage'] ?? 0);
            if ($minStorage > 0 && (int)$item['storage'] < $minStorage) {
                return false;
            }

            $minProcessor = (int)($filters['min_processor'] ?? 0);
            if ($minProcessor > 0 && (int)$item['processor'] < $minProcessor) {
                return false;
            }

            $maxPrice = (int)($filters['max_price'] ?? 0);
            if ($maxPrice > 0 && (int)$item['price'] > $maxPrice) {
                return false;
            }

            return true;
        }));

        $scored = [];
        foreach ($filtered as $item) {
            $score =
                pow((float)$item['ram'], $this->defaultWeights['ram']) *
                pow((float)$item['storage'], $this->defaultWeights['storage']) *
                pow((float)$item['processor'], $this->defaultWeights['processor']) *
                pow((float)$item['price'], -$this->defaultWeights['price']);

            $item['skor'] = $score;
            $scored[] = $item;
        }

        usort($scored, static fn (array $a, array $b): int => $b['skor'] <=> $a['skor']);

        return $scored;
    }

    public function defaultWeights(): array
    {
        return $this->defaultWeights;
    }
}

