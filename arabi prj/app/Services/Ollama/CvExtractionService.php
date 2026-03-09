<?php

namespace App\Services\Ollama;

use App\Services\CandidateProfileSchema;

/**
 * Ollama-powered CV extraction: raw text → normalize → prompt → Ollama → validate → canonical profile.
 * Single source of truth: returned profile is normalized and safe to save.
 */
class CvExtractionService
{
    private OllamaClient $client;
    private string $model;
    private string $fallbackModel;

    public function __construct(?OllamaClient $client = null)
    {
        $this->client = $client ?? new OllamaClient(
            $_ENV['OLLAMA_BASE_URL'] ?? null,
            (int) ($_ENV['OLLAMA_TIMEOUT'] ?? 90)
        );
        $this->model = $_ENV['OLLAMA_CV_MODEL'] ?? 'qwen2.5:7b';
        $this->fallbackModel = $_ENV['OLLAMA_CV_FALLBACK_MODEL'] ?? 'llama3.2:3b';
    }

    /**
     * Extract canonical candidate profile from raw CV text using Ollama.
     * Returns normalized profile array (CandidateProfileSchema shape). Throws on failure.
     */
    public function extractFromText(string $rawCvText): array
    {
        $normalizedText = CvTextNormalizer::normalize($rawCvText);
        if ($normalizedText === '') {
            return CandidateProfileSchema::normalizeCandidateProfile(CandidateProfileSchema::emptyProfile());
        }
        $prompt = CvExtractionPrompt::build($normalizedText);
        $response = $this->callOllama($prompt);
        $profile = self::parseAndValidateResponse($response);
        return CandidateProfileSchema::normalizeCandidateProfile($profile);
    }

    private function callOllama(string $prompt): string
    {
        try {
            return $this->client->chat($this->model, $prompt);
        } catch (\Throwable $e) {
            try {
                return $this->client->chat($this->fallbackModel, $prompt);
            } catch (\Throwable $e2) {
                throw new \RuntimeException('Ollama CV extraction failed: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Parse raw LLM response to JSON and map to canonical profile shape; validate and clean.
     */
    public static function parseAndValidateResponse(string $rawResponse): array
    {
        $json = self::extractJsonFromResponse($rawResponse);
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return CandidateProfileSchema::emptyProfile();
        }
        return self::mapOllamaJsonToCanonical($decoded);
    }

    /** Strip markdown code fence and any text before/after JSON. */
    public static function extractJsonFromResponse(string $raw): string
    {
        $s = trim($raw);
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $s, $m)) {
            return trim($m[1]);
        }
        $start = strpos($s, '{');
        if ($start === false) {
            return $s;
        }
        $depth = 0;
        $end = -1;
        for ($i = $start, $len = strlen($s); $i < $len; $i++) {
            $c = $s[$i];
            if ($c === '{') {
                $depth++;
            } elseif ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }
        if ($end >= 0) {
            return substr($s, $start, $end - $start + 1);
        }
        return $s;
    }

    /**
     * Map Ollama JSON (may have different keys) to canonical profile.
     */
    public static function mapOllamaJsonToCanonical(array $data): array
    {
        $empty = CandidateProfileSchema::emptyProfile();
        $contact = $data['contact'] ?? [];
        if (!is_array($contact)) {
            $contact = [];
        }
        $profile = [
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'job_title' => trim((string) ($data['job_title'] ?? '')),
            'summary' => trim((string) ($data['summary'] ?? '')),
            'contact' => [
                'email' => self::cleanEmailInput(trim((string) ($contact['email'] ?? ''))),
                'phone' => trim((string) ($contact['phone'] ?? '')),
                'address' => trim((string) ($contact['address'] ?? '')),
                'city' => trim((string) ($contact['city'] ?? '')),
                'linkedin' => self::cleanLinkedInUrl(trim((string) ($contact['linkedin'] ?? ''))),
            ],
            'education' => self::normalizeEducationList($data['education'] ?? []),
            'experience' => self::normalizeExperienceList($data['experience'] ?? []),
            'skills' => self::normalizeStringList($data['skills'] ?? []),
            'languages' => self::normalizeStringList($data['languages'] ?? []),
            'projects' => self::normalizeStringList($data['projects'] ?? []),
            'certifications' => self::normalizeStringList($data['certifications'] ?? []),
            'hobbies' => self::normalizeStringList($data['hobbies'] ?? []),
            'availability' => trim((string) ($data['availability'] ?? '')),
            'salary_expectation' => trim((string) ($data['salary_expectation'] ?? '')),
        ];
        $profile['contact'] = array_merge($empty['contact'], $profile['contact']);
        return array_merge($empty, $profile);
    }

    private static function cleanEmailInput(string $v): string
    {
        if ($v === '') {
            return '';
        }
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $v, $m)) {
            $c = $m[0];
            return filter_var($c, FILTER_VALIDATE_EMAIL) ? $c : '';
        }
        return '';
    }

    private static function cleanLinkedInUrl(string $v): string
    {
        if ($v === '') {
            return '';
        }
        if (preg_match('#https?://(?:www\.)?linkedin\.com/[^\s\)\]"\'<>]+#i', $v, $m)) {
            return trim($m[0]);
        }
        return '';
    }

    private static function normalizeEducationList($list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $e) {
            if (!is_array($e)) {
                continue;
            }
            $out[] = [
                'degree' => trim((string) ($e['degree'] ?? '')),
                'school' => trim((string) ($e['school'] ?? '')),
                'year' => trim((string) ($e['year'] ?? '')),
                'details' => trim((string) ($e['details'] ?? '')),
            ];
        }
        return $out;
    }

    private static function normalizeExperienceList($list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $e) {
            if (!is_array($e)) {
                continue;
            }
            $out[] = [
                'title' => trim((string) ($e['title'] ?? '')),
                'company' => trim((string) ($e['company'] ?? '')),
                'location' => trim((string) ($e['location'] ?? '')),
                'duration' => trim((string) ($e['duration'] ?? '')),
                'description' => trim((string) ($e['description'] ?? '')),
            ];
        }
        return $out;
    }

    private static function normalizeStringList($list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        $seen = [];
        foreach ($list as $s) {
            $v = trim(is_string($s) ? $s : (string) $s);
            if ($v === '' || mb_strlen($v) > 200) {
                continue;
            }
            $lower = mb_strtolower($v);
            if (isset($seen[$lower])) {
                continue;
            }
            $seen[$lower] = true;
            $out[] = $v;
        }
        return array_values($out);
    }
}
