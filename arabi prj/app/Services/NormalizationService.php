<?php

namespace App\Services;

use App\Models\PipelineLog;

class NormalizationService
{
    public function runNormalization(): array
    {
        $logId = PipelineLog::start('normalization');
        try {
            $runner = new PythonRunner();
            $result = $runner->runNormalize();
            $rows = $result['rows_updated'] ?? $result['rows_affected'] ?? 0;
            PipelineLog::complete($logId, (int) $rows);
            return array_merge($result, ['log_id' => $logId]);
        } catch (\Throwable $e) {
            PipelineLog::fail($logId, $e->getMessage());
            throw $e;
        }
    }
}
