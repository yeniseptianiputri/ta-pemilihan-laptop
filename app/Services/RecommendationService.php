<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RecommendationRepository;

final class RecommendationService
{
    private array $defaultWeights = [
        'ram' => 0.3,
        'storage' => 0.2,
        'processor' => 0.3,
        'price' => 0.2,
    ];

    public function __construct(private ?RecommendationRepository $recommendations = null)
    {
    }

    public function recommend(
        array $catalog,
        array $filters,
        ?int $userId = null,
        string $sourcePage = 'rekomendasi'
    ): array {
        $weights = $this->resolvedWeights();

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
                pow((float)$item['ram'], $weights['ram']) *
                pow((float)$item['storage'], $weights['storage']) *
                pow((float)$item['processor'], $weights['processor']) *
                pow((float)$item['price'], -$weights['price']);

            $item['skor'] = $score;
            $scored[] = $item;
        }

        usort($scored, static fn (array $a, array $b): int => $b['skor'] <=> $a['skor']);

        if ($this->recommendations !== null) {
            $this->recommendations->saveSession(
                $userId,
                $filters,
                $weights,
                $scored,
                $sourcePage
            );
        }

        return $scored;
    }

    public function defaultWeights(): array
    {
        return $this->resolvedWeights();
    }

    private function resolvedWeights(): array
    {
        $weights = $this->defaultWeights;
        if ($this->recommendations !== null) {
            $weights = $this->recommendations->weightsFromCriteria($weights);
        }

        $sum = array_sum($weights);
        if ($sum <= 0) {
            return $this->defaultWeights;
        }

        return [
            'ram' => $weights['ram'] / $sum,
            'storage' => $weights['storage'] / $sum,
            'processor' => $weights['processor'] / $sum,
            'price' => $weights['price'] / $sum,
        ];
    }
}
