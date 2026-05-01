<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class RecommendationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS recommendation_sessions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                session_token VARCHAR(128) NULL,
                source_page VARCHAR(40) NULL,
                filters_json JSON NOT NULL,
                weights_json JSON NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_reco_sessions_user_id (user_id),
                INDEX idx_reco_sessions_created_at (created_at),
                CONSTRAINT fk_reco_sessions_user_runtime
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON UPDATE CASCADE
                    ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS recommendation_results (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                recommendation_session_id BIGINT UNSIGNED NOT NULL,
                laptop_id INT UNSIGNED NULL,
                rank_position SMALLINT UNSIGNED NOT NULL,
                wp_score DECIMAL(18,8) NOT NULL,
                snapshot_json JSON NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_reco_results_session_id (recommendation_session_id),
                INDEX idx_reco_results_laptop_id (laptop_id),
                CONSTRAINT fk_reco_results_session_runtime
                    FOREIGN KEY (recommendation_session_id) REFERENCES recommendation_sessions(id)
                    ON UPDATE CASCADE
                    ON DELETE CASCADE,
                CONSTRAINT fk_reco_results_laptop_runtime
                    FOREIGN KEY (laptop_id) REFERENCES laptops(id)
                    ON UPDATE CASCADE
                    ON DELETE SET NULL
            ) ENGINE=InnoDB'
        );
    }

    public function weightsFromCriteria(array $fallback): array
    {
        $statement = $this->pdo->query(
            "SELECT code, weight
            FROM criteria
            WHERE code IN ('ram', 'storage', 'processor', 'price')"
        );
        $rows = $statement->fetchAll();

        if ($rows === []) {
            return $fallback;
        }

        $weights = $fallback;
        foreach ($rows as $row) {
            $code = (string)($row['code'] ?? '');
            $value = (float)($row['weight'] ?? 0);
            if (array_key_exists($code, $weights) && $value > 0) {
                $weights[$code] = $value;
            }
        }

        return $weights;
    }

    public function saveSession(
        ?int $userId,
        array $filters,
        array $weights,
        array $results,
        string $sourcePage
    ): void {
        $sessionToken = session_id() !== '' ? session_id() : null;

        try {
            $this->pdo->beginTransaction();

            $sessionStatement = $this->pdo->prepare(
                'INSERT INTO recommendation_sessions
                (user_id, session_token, source_page, filters_json, weights_json)
                VALUES
                (:user_id, :session_token, :source_page, :filters_json, :weights_json)'
            );
            $sessionStatement->execute([
                'user_id' => $userId,
                'session_token' => $sessionToken,
                'source_page' => $sourcePage,
                'filters_json' => json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'weights_json' => json_encode($weights, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);

            $recommendationSessionId = (int)$this->pdo->lastInsertId();
            if ($recommendationSessionId <= 0) {
                $this->pdo->rollBack();
                return;
            }

            $resultStatement = $this->pdo->prepare(
                'INSERT INTO recommendation_results
                (recommendation_session_id, laptop_id, rank_position, wp_score, snapshot_json)
                VALUES
                (:recommendation_session_id, :laptop_id, :rank_position, :wp_score, :snapshot_json)'
            );

            foreach (array_slice($results, 0, 50) as $index => $row) {
                $resultStatement->execute([
                    'recommendation_session_id' => $recommendationSessionId,
                    'laptop_id' => (int)($row['id'] ?? 0) > 0 ? (int)$row['id'] : null,
                    'rank_position' => $index + 1,
                    'wp_score' => (float)($row['skor'] ?? 0),
                    'snapshot_json' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }
    }
}
