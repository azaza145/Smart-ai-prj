<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\PipelineLog;

class RecommendationService
{
    public function runForJob(int $jobId, int $topK = 200): array
    {
        $logId = PipelineLog::start('recommendation', $jobId);
        try {
            $runner = new PythonRunner();
            // Step 1: Always normalize profiles first (fast, idempotent)
            try {
                $runner->runNormalize();
            } catch (\Throwable $e) {
                error_log('Normalization warning: ' . $e->getMessage());
            }
            // Step 2: Run recommendations
            $result = $runner->runRecommend($jobId, $topK);
            $count = count($result['recommendations'] ?? []);
            Recommendation::saveBatch($jobId, $result['recommendations'] ?? []);
            PipelineLog::complete($logId, $count);
            return array_merge($result, ['log_id' => $logId, 'count' => $count]);
        } catch (\Throwable $e) {
            PipelineLog::fail($logId, $e->getMessage());
            throw $e;
        }
    }
}
