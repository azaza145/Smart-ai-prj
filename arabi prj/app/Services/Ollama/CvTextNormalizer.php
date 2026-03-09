<?php

namespace App\Services\Ollama;

/**
 * Normalizes raw extracted CV text before sending to Ollama.
 * - Collapse excessive whitespace, fix common PDF artifacts
 * - Preserve UTF-8 / French characters
 */
final class CvTextNormalizer
{
    /**
     * Normalize raw CV text for better LLM parsing.
     */
    public static function normalize(string $rawText): string
    {
        if (trim($rawText) === '') {
            return '';
        }
        $s = $rawText;
        // UTF-8, strip BOM
        if (substr($s, 0, 3) === "\xEF\xBB\xBF") {
            $s = substr($s, 3);
        }
        // Replace various line breaks with single newline
        $s = preg_replace("/\r\n|\r/", "\n", $s);
        // Collapse multiple spaces/tabs to one space (but keep single newlines for structure)
        $s = preg_replace("/[ \t]+/u", " ", $s);
        // Collapse 3+ newlines to double newline (paragraph break)
        $s = preg_replace("/\n{3,}/", "\n\n", $s);
        // Trim each line
        $lines = array_map('trim', explode("\n", $s));
        $lines = array_filter($lines, static function ($l) {
            return $l !== '';
        });
        return implode("\n", $lines);
    }
}
