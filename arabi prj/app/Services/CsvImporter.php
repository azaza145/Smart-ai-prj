<?php

namespace App\Services;

use App\Core\DB;
use App\Models\Candidate;
use App\Models\ImportLog;

class CsvImporter
{
    private const REQUIRED_COLUMNS = [
        'id', 'nom', 'prenom', 'email', 'telephone', 'age', 'ville',
        'experience_annees', 'poste_actuel', 'entreprise_actuelle',
        'education_niveau', 'diplome', 'universite', 'annee_diplome',
        'competences_techniques', 'competences_langues', 'langues_niveau',
        'experience_detail', 'projets', 'certifications', 'disponibilite', 'pretention_salaire',
    ];

    /**
     * Prefer running Python import for large files; fallback to PHP streaming.
     */
    public function importViaPython(string $csvPath): array
    {
        $runner = new PythonRunner();
        return $runner->runImport($csvPath);
    }

    /**
     * PHP-only import: validate schema, stream read, upsert by email.
     */
    public function importFromPath(string $csvPath): array
    {
        if (!is_readable($csvPath)) {
            throw new \RuntimeException("CSV not readable: {$csvPath}");
        }
        $logId = ImportLog::create($csvPath);
        $inserted = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $processed = 0;

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            ImportLog::fail($logId, 'Could not open file');
            throw new \RuntimeException('Could not open CSV');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            ImportLog::fail($logId, 'Empty or invalid CSV header');
            throw new \RuntimeException('Invalid CSV header');
        }
        $header = array_map('trim', $header);
        foreach (self::REQUIRED_COLUMNS as $col) {
            if (!in_array($col, $header, true)) {
                fclose($handle);
                ImportLog::fail($logId, "Missing required column: {$col}");
                throw new \RuntimeException("Missing required column: {$col}");
            }
        }

        $keyMap = array_flip($header);
        while (($row = fgetcsv($handle)) !== false) {
            $processed++;
            $record = [];
            foreach (self::REQUIRED_COLUMNS as $col) {
                $idx = $keyMap[$col] ?? null;
                $record[$col] = $idx !== null ? trim($row[$idx] ?? '') : '';
            }
            if (empty($record['email'])) {
                $failed++;
                $errors[] = "Row {$processed}: missing email";
                continue;
            }
            try {
                $existing = Candidate::findByEmail($record['email']);
                Candidate::upsertByEmail($record);
                if ($existing) {
                    $updated++;
                } else {
                    $inserted++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Row {$processed}: " . $e->getMessage();
            }
        }
        fclose($handle);

        $errorLog = implode("\n", array_slice($errors, 0, 100));
        if (count($errors) > 100) {
            $errorLog .= "\n... and " . (count($errors) - 100) . " more.";
        }
        ImportLog::complete($logId, $processed, $inserted, $updated, $failed, $errorLog);

        return [
            'log_id' => $logId,
            'rows_processed' => $processed,
            'rows_inserted' => $inserted,
            'rows_updated' => $updated,
            'rows_failed' => $failed,
            'status' => 'completed',
        ];
    }
}
