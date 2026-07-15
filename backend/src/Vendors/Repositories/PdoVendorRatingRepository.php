<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\VendorRatingRepositoryContract;
use SkyFi\Vendors\DomainModels\VendorRating;
use SkyFi\Vendors\DTOs\VendorRatingData;

final class PdoVendorRatingRepository implements VendorRatingRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listByVendor(?int $vendorId = null): array
    {
        if ($vendorId !== null && $vendorId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT vr.*, u.name AS evaluator_name, v.name AS vendor_name
                 FROM vendor_ratings vr
                 JOIN vendors v ON v.id = vr.vendor_id
                 JOIN users u ON u.id = vr.evaluator_user_id
                 WHERE vr.vendor_id = ?
                 ORDER BY vr.evaluation_date DESC, vr.id DESC'
            );
            $stmt->execute([$vendorId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT vr.*, u.name AS evaluator_name, v.name AS vendor_name
                 FROM vendor_ratings vr
                 JOIN vendors v ON v.id = vr.vendor_id
                 JOIN users u ON u.id = vr.evaluator_user_id
                 ORDER BY vr.evaluation_date DESC, vr.id DESC'
            );
            $stmt->execute();
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn(array $row) => VendorRating::fromRow($row), $rows);
    }

    public function find(int $id): ?VendorRating
    {
        $stmt = $this->pdo->prepare(
            'SELECT vr.*, u.name AS evaluator_name, v.name AS vendor_name
             FROM vendor_ratings vr
             JOIN vendors v ON v.id = vr.vendor_id
             JOIN users u ON u.id = vr.evaluator_user_id
             WHERE vr.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? VendorRating::fromRow($row) : null;
    }

    public function create(VendorRatingData $data, int $actorId): VendorRating
    {
        $now = date('Y-m-d H:i:s');
        // Calculate score out of 5 based on performance averages
        // Formula: average of delivery, completion, quality minus return rate penalty
        $avgPct = ($data->deliveryPerformance + $data->orderCompletion + $data->productQuality) / 3.0;
        $score = max(0.0, min(5.0, ($avgPct - ($data->returnRate * 2.0)) / 20.0));
        $score = round($score, 2);

        $stmt = $this->pdo->prepare(
            'INSERT INTO vendor_ratings (vendor_id, evaluation_date, delivery_performance, order_completion, product_quality, return_rate, average_lead_time_days, overall_score, evaluator_user_id, comments, created_at, updated_at)
             VALUES (:vid, :edate, :dp, :oc, :pq, :rr, :lt, :score, :eval, :comments, :cat, :uat)'
        );
        $stmt->execute([
            'vid' => $data->vendorId,
            'edate' => $data->evaluationDate,
            'dp' => $data->deliveryPerformance,
            'oc' => $data->orderCompletion,
            'pq' => $data->productQuality,
            'rr' => $data->returnRate,
            'lt' => $data->averageLeadTimeDays,
            'score' => $score,
            'eval' => $actorId,
            'comments' => $data->comments,
            'cat' => $now,
            'uat' => $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();

        $this->recalculateOverallRating($data->vendorId);

        return $this->find($id) ?? throw new \RuntimeException('Failed to load created rating.');
    }

    public function recalculateOverallRating(int $vendorId): float
    {
        $stmt = $this->pdo->prepare('SELECT AVG(overall_score) FROM vendor_ratings WHERE vendor_id = ?');
        $stmt->execute([$vendorId]);
        $avgScore = round((float) ($stmt->fetchColumn() ?? 0.0), 2);

        if ($avgScore > 0.0) {
            $upd = $this->pdo->prepare('UPDATE vendors SET overall_rating = ? WHERE id = ?');
            $upd->execute([$avgScore, $vendorId]);
        }

        return $avgScore;
    }
}
