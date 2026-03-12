<?php

namespace App\Services\Ollama;

/**
 * Builds the user prompt for strict JSON CV extraction.
 * Output must be valid JSON only, no explanations.
 */
final class CvExtractionPrompt
{
    /**
     * Build the full user prompt: instructions + raw CV text.
     */
    public static function build(string $normalizedCvText): string
    {
        $len = mb_strlen($normalizedCvText);
        $maxChars = 28000;
        if ($len > $maxChars) {
            $normalizedCvText = mb_substr($normalizedCvText, 0, $maxChars) . "\n[... tronqué ...]";
        }

        return <<<PROMPT
Tu es un assistant expert en extraction de données de CV. Réponds UNIQUEMENT par un objet JSON valide. Pas de markdown, pas de ```json, pas de texte avant ou après.

=== RÈGLES CRITIQUES ===

FORMATION (education) :
- Contient UNIQUEMENT les diplômes, licences, masters, ingéniorats, baccalauréats, BTS, DEUST.
- Un diplôme = UN objet séparé dans le tableau. Si le CV a 4 diplômes → tableau de 4 objets.
- JAMAIS de stages ou emplois dans education.
- "degree" = intitulé exact du diplôme. "school" = établissement (ex: Lycée X, ISGA, ONEE si c'est un centre de formation, pas une entreprise d'emploi).
- "year" = UNE SEULE période claire par diplôme (ex: "2021", "2021-2023", "2023 – en cours"). Ne jamais concaténer plusieurs dates (éviter "20232021-202320212024"). Si plusieurs dates apparaissent, choisir la période principale du diplôme.
- "details" = description courte si présente, sinon "".

EXPÉRIENCE (experience) :
- Contient UNIQUEMENT les stages, emplois, missions professionnelles.
- "title" = titre du poste ou stage RÉEL uniquement (ex: "Stage PFA", "Technicien Réseaux", "Développeur Full-Stack"). 
  INTERDIT : "EXPÉRIENCE PROFESSIONNELLE", "PROFESSIONNELLE", "PROFESSIONNEL", ou tout titre de section. Si une ligne ne contient qu'un tel mot, extraire le vrai titre du poste depuis la phrase ou la ligne suivante.
- "company" = nom de l'organisation (ex: ONEE, Capgemini, Bank Al-Maghrib). "duration" = UNE seule période lisible (ex: "2023-2025", "Mars 2023 – Juin 2023"). "description" = max 3 phrases, sans répétition.
- JAMAIS de formations dans experience. Chaque entrée = un poste différent ; ne pas dupliquer la même phrase pour chaque entrée.

COMPÉTENCES (skills) :
- Uniquement des technologies, langages, outils, frameworks concrets : "Java", "Spring Boot", "Docker"...
- INTERDITS : "développement", "programmation", "réseaux", "sécurité", "systèmes", "compétences",
  "formation", "expérience", "langues", "loisirs", "organisation", "communication", "supervision",
  "virtualisation", "soft skills", "résolution", ou tout titre de section.
- Multi-mots comme "Spring Boot" ou "Machine Learning" doivent rester en UN token, jamais splitté.

LANGUES (languages) :
- Tableau d'objets : [{"name": "Arabe", "level": "Maternel"}, {"name": "Français", "level": "Courant"}]
- "name" = NOM de la langue SEULEMENT. "level" = niveau SEULEMENT.
- Niveaux valides : "Maternel", "Courant", "Intermédiaire", "Débutant", "Bilingue".
- JAMAIS "Maternel" ou "Courant" comme valeur de "name".

CONTACT :
- email : extraire la partie avant "Linkedin" ou tout mot collé (ex: "abc@gmail.comLinkedin" → "abc@gmail.com").
- linkedin : URL complète dans contact.linkedin, séparée de l'email.
- city : uniquement si clairement identifiable, sinon "".

GÉNÉRAL : champ inconnu → "". Ne jamais inventer. description dans experience : max 3 phrases, sans répétitions.

=== EXEMPLE DE SORTIE ATTENDUE ===
{
  "full_name": "Anwar Barroug",
  "job_title": "Élève Ingénieur en Développement Web et Mobile",
  "summary": "Élève-ingénieur cherchant un stage PFA 2-3 mois en Full-Stack et IA.",
  "contact": {
    "email": "anwarcvbarroug@gmail.com",
    "phone": "+212659782662",
    "address": "AMAL 5 NR 1559 EL MASSIRA CYM, RABAT",
    "city": "Rabat",
    "linkedin": "https://www.linkedin.com/in/anwar-barroug-9b10712b9/"
  },
  "education": [
    {"degree": "Cycle Ingénieur — Ingénierie des Systèmes Informatiques", "school": "ISGA Rabat", "year": "2023 – en cours", "details": ""},
    {"degree": "Licence — Ingénierie des Systèmes Informatiques (BAC+3)", "school": "SUP MTI", "year": "2021 – 2023", "details": "Projet Full-Stack : Angular, Spring Boot, PostgreSQL"},
    {"degree": "Technicien Spécialisé Infrastructure Digitale, Réseaux et SI", "school": "OFPPT", "year": "2021 – 2024", "details": ""},
    {"degree": "Baccalauréat Sciences Physiques", "school": "Lycée ABI DARR ELGHAFFARI", "year": "2021", "details": ""}
  ],
  "experience": [
    {"title": "Stage PFA — Développement Full-Stack", "company": "Bank Al-Maghrib", "location": "Rabat", "duration": "27 Avr 2025 – 27 Juil 2025", "description": "Développement d'une application Full-Stack."},
    {"title": "Stage de fin de formation", "company": "ONEE", "location": "", "duration": "1 Mars 2023 – 31 Juin 2023", "description": "Support technique et administration réseau."},
    {"title": "Technicien Support — Administration Systèmes", "company": "OFPPT", "location": "", "duration": "2025 – en cours", "description": "Administration des systèmes, maintenance serveurs, sécurisation réseau."}
  ],
  "skills": ["Java", "Jakarta EE", "Spring Boot", "Angular", "Python", "Pandas", "Scikit-Learn", "MySQL", "PostgreSQL", "Linux", "Windows Server", "VMware", "Hyper-V", "Nagios", "Docker", "Git", "TCP/IP", "Cisco"],
  "languages": [
    {"name": "Arabe", "level": "Maternel"},
    {"name": "Français", "level": "Courant"},
    {"name": "Anglais", "level": "Courant"}
  ],
  "projects": [],
  "certifications": [],
  "hobbies": ["Technologies et développement", "Calisthenics", "Course à pied", "Lecture"],
  "availability": "Disponible dès Juin 2025 – Stage 2 à 3 mois",
  "salary_expectation": ""
}

=== TEXTE DU CV ===
---
{$normalizedCvText}
---
JSON uniquement :
PROMPT;
    }
}
