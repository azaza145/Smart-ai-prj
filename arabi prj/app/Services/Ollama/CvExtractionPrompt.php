<?php

namespace App\Services\Ollama;

/**
 * Builds the user prompt for strict JSON CV extraction.
 * Output must be valid JSON only, no explanations.
 */
final class CvExtractionPrompt
{
    /** Example JSON structure (for model reference). */
    private const EXAMPLE_JSON = '{"full_name":"","job_title":"","summary":"","contact":{"email":"","phone":"","address":"","city":"","linkedin":""},"education":[{"degree":"","school":"","year":"","details":""}],"experience":[{"title":"","company":"","location":"","duration":"","description":""}],"skills":[],"languages":[],"projects":[],"certifications":[],"hobbies":[],"availability":"","salary_expectation":""}';

    /**
     * Build the full user prompt: instructions + raw CV text.
     */
    public static function build(string $normalizedCvText): string
    {
        $len = mb_strlen($normalizedCvText);
        $maxChars = 28000;
        if ($len > $maxChars) {
            $normalizedCvText = mb_substr($normalizedCvText, 0, $maxChars) . "\n[... texte tronqué ...]";
        }
        $example = self::EXAMPLE_JSON;
        return <<<PROMPT
Tu es un assistant qui extrait des données structurées d'un CV. Réponds UNIQUEMENT par un objet JSON valide, sans aucun texte avant ou après.

Règles:
- Répondre uniquement avec l'objet JSON. Pas d'explication, pas de markdown, pas de \`\`\`json.
- Email: une seule adresse email valide. Si le texte a "email@domain.comLinkedin", garde uniquement "email@domain.com".
- LinkedIn: URL complète dans contact.linkedin, séparée de l'email.
- education: uniquement FORMATION / études (diplômes, écoles). Pas d'expériences.
- experience: uniquement EXPÉRIENCE PROFESSIONNELLE (stages, emplois). Pas de formations.
- skills: compétences dédupliquées, pas de stopwords (formation, expérience, compétences, langues, etc.).
- city: remplir seulement si clairement identifiable, sinon vide.
- Champs incertains: chaîne vide.

Structure attendue (exemple): {$example}

Texte du CV:
---
{$normalizedCvText}
---
Réponds uniquement par l'objet JSON.
PROMPT;
    }
}
