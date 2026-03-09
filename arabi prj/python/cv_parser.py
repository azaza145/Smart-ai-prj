#!/usr/bin/env python3
"""
Parse CV text (from direct PDF extraction) into a structured JSON schema.
Section-based parsing: split by section headers only when header is at line start.
Skills: keyword/whitelist extraction only (no naive word-token).
Output: structured schema + flat 'parsed' dict for PHP.
"""
import re
from datetime import datetime
from typing import Any

# Section detection: (canonical_key, line_start_prefixes). Line must start with one of these (after strip).
_SECTION_PREFIXES: list[tuple[str, list[str]]] = [
    ("experience", ["experience professionnelle", "expérience professionnelle", "experiences professionnelles"]),
    ("education", ["formation", "formations", "education", "études", "etudes"]),
    ("skills", ["compétences", "competences", "skills", "compétence"]),
    ("languages", ["langues", "languages"]),
    ("hobbies", ["loisirs", "hobbies", "centres d'intérêt", "interets"]),
    ("certifications", ["certifications"]),
    ("projets", ["projets", "projects"]),
    ("disponibilite", ["disponibilité", "disponibilite"]),
    ("summary", ["summary", "résumé", "resume", "profil", "objectif", "à propos", "a propos"]),
]


def _normalize_line_for_header(s: str) -> str:
    """Lowercase, collapse spaces, strip. For header matching."""
    return re.sub(r"\s+", " ", (s or "").strip().lower())


def _section_key_for_line(line: str) -> str | None:
    """If line is a section header (at line start), return canonical key else None."""
    norm = _normalize_line_for_header(line)
    if not norm:
        return None
    for key, prefixes in _SECTION_PREFIXES:
        for p in prefixes:
            if norm == p or norm.startswith(p + " ") or norm.startswith(p + ":") or norm.startswith(p + "-"):
                return key
    return None

# Headers that start another section (stop education/skills bleed)
_OTHER_SECTION_NAMES = [
    "experience professionnelle", "expérience professionnelle",
    "compétences", "competences", "langues", "loisirs", "hobbies",
    "certifications", "projets", "formation", "formations", "education",
]


def _line_is_other_section_start(line: str) -> bool:
    norm = _normalize_line_for_header(line)
    if not norm:
        return False
    for name in _OTHER_SECTION_NAMES:
        if norm == name or norm.startswith(name + " ") or norm.startswith(name + ":") or norm.startswith(name + "-"):
            return True
    return False

# Known tech/skill keywords (whitelist) — only these or regex matches go into skills
SKILL_WHITELIST = {
    "python", "java", "javascript", "php", "c++", "c#", "ruby", "go", "golang",
    "react", "angular", "vue", "node", "nodejs", "html", "css", "typescript", "dart", "flutter",
    "kotlin", "swift", "mysql", "postgresql", "mongodb", "redis", "sql", "nosql", "oracle",
    "linux", "windows", "docker", "kubernetes", "git", "jenkins", "aws", "azure", "gcp",
    "réseaux", "networks", "api", "rest", "sécurité", "security", "spring", "boot", "spring boot",
    "full-stack", "full stack", "développement", "development", "conception", "analyse",
    "travail d'équipe", "teamwork", "communication", "leadership", "gestion de projet",
    "autonome", "créatif", "agile", "scrum", "jira", "figma", "postman",
}
# Stopwords: never include as skills
SKILL_STOPWORDS = {
    "formation", "experience", "expérience", "compétences", "competences", "langues", "loisirs",
    "stage", "fin", "souhaite", "participer", "développer", "cycle", "diplôme", "diplome",
    "licence", "master", "bac", "sup", "mti", "oan", "ans", "projet", "projets",
    "technologies", "utilisées", "utilisees", "solution", "besoins", "fonctionnels", "techniques",
    "implémentation", "implementation", "plateforme", "gestion", "événements", "evenements",
    "—", "-", "et", "de", "du", "la", "le", "les", "en", "au", "aux", "par", "pour",
}

# City/address noise: not real places
CITY_ADDRESS_NOISE = {"lecture", "reading", "lire", "sport", "music", "cinema", "voyage", "—", "-", ""}


def _rest_after_header(line: str, key: str) -> str:
    """Return line content after the section header (e.g. 'COMPÉTENCES : Python' -> 'Python')."""
    rest = line.strip()
    for k, prefixes in _SECTION_PREFIXES:
        if k != key:
            continue
        norm = rest.lower()
        for p in prefixes:
            if norm.startswith(p):
                rest = rest[len(p):].lstrip(" :\t-")
                return rest.strip()
    return ""


def _split_into_sections(text: str) -> dict[str, str]:
    """Split by section headers. Headers must be at line start (prefix match)."""
    text = (text or "").strip()
    if not text:
        return {}
    lines = text.split("\n")
    sections = {}
    current_key = None
    current_lines = []
    for line in lines:
        key = _section_key_for_line(line)
        if key is not None:
            if current_key and current_lines:
                content = "\n".join(current_lines).strip()
                if content:
                    sections[current_key] = sections.get(current_key, "") + ("\n" + content if current_key in sections else content)
            current_key = key
            current_lines = []
            rest = _rest_after_header(line, key)
            if rest and _section_key_for_line(rest) is None:
                current_lines.append(rest)
            continue
        if current_key:
            current_lines.append(line)
        else:
            sections["personal"] = (sections.get("personal") or "") + (("\n" + line) if sections.get("personal") else line)
    if current_key and current_lines:
        content = "\n".join(current_lines).strip()
        if content:
            sections[current_key] = sections.get(current_key, "") + ("\n" + content if current_key in sections else content)
    return sections


def _regex_extract(text: str) -> dict[str, Any]:
    out = {}
    # Match email only; strip labels concatenated without space (e.g. ...@domain.comLinkedin)
    m = re.search(r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}", text)
    if m:
        raw = m.group(0).strip()
        # Remove trailing letters appended to TLD (e.g. ...@domain.comLinkedin -> .com)
        raw = re.sub(r"(\.[a-zA-Z]{2,})[a-zA-Z]+$", r"\1", raw)
        out["email"] = raw
    m = re.search(r"(?:\+212|\+33|0033|0)\s*[1-9](?:[\s.\-]*\d{2}){4,}", text)
    if m:
        phone = re.sub(r"[\s.\-\+]", "", m.group(0))
        if phone.startswith("33") and len(phone) > 10:
            phone = "0" + phone[2:]
        if 9 <= len(phone) <= 15:
            out["phone"] = phone
    m = re.search(r"https?://(?:www\.)?linkedin\.com/[^\s\)\]\"]+", text, re.IGNORECASE)
    if m:
        out["linkedin"] = m.group(0).strip()
    m = re.search(r"(?:Adresse|Address)\s*[:\-]\s*([^\n]+)", text, re.IGNORECASE | re.UNICODE)
    if m:
        out["address"] = re.sub(r"\s+", " ", m.group(1).strip())
    return out


def _parse_personal_and_title(personal_block: str, regex_info: dict) -> dict[str, Any]:
    info = {
        "full_name": "",
        "title": "",
        "email": regex_info.get("email") or "",
        "phone": regex_info.get("phone") or "",
        "address": regex_info.get("address") or "",
        "linkedin": regex_info.get("linkedin") or "",
    }
    if not personal_block:
        return info
    lines = [ln.strip() for ln in personal_block.split("\n") if ln.strip()]
    for line in lines:
        if "@" in line or re.match(r"^[\d+\s\-\.]+$", line) or "linkedin" in line.lower():
            continue
        if not info["full_name"] and len(line) > 1 and len(line) < 120:
            info["full_name"] = line
            continue
        if not info["title"] and len(line) > 3 and len(line) < 200:
            info["title"] = line
            break
    return info


def _trim_at_next_section(content: str) -> str:
    """Remove content from first line that looks like another section header."""
    out = []
    for line in content.split("\n"):
        if _line_is_other_section_start(line):
            break
        out.append(line)
    return "\n".join(out).strip()


# Degree labels that start an education entry (French CVs). Order: lowest academic level first for sorting.
EDUCATION_DEGREE_LABELS = [
    "baccalauréat",
    "baccalaureat",
    "bac ",
    "diplôme",
    "diplome",
    "technicien spécialisé",
    "technicien specialise",
    "bts",
    "dut",
    "licence",
    "master",
    "cycle ingénieur",
    "cycle ingenieur",
    "ingénieur",
    "ingenieur",
    "doctorat",
    "phd",
]

# Academic level for sorting: 1 = bac, 2 = diplôme/bts/dut, 3 = licence, 4 = master/cycle ingénieur, 5 = doctorat
def _education_sort_key(entry: dict) -> tuple[int, int]:
    degree = (entry.get("degree") or "").lower()
    for i, label in enumerate(EDUCATION_DEGREE_LABELS):
        if label in degree:
            if "bac" in label or "baccalauréat" in label or "baccalaureat" in label:
                return (1, -i)
            if "diplôme" in label or "diplome" in label or "technicien" in label or "bts" in label or "dut" in label:
                return (2, -i)
            if "licence" in label:
                return (3, -i)
            if "master" in label or "cycle" in label or "ingénieur" in label or "ingenieur" in label:
                return (4, -i)
            if "doctorat" in label or "phd" in label:
                return (5, -i)
    return (0, 0)


def _is_degree_line(line: str) -> bool:
    """True if line looks like the start of an education entry (degree label)."""
    lower = line.lower().strip()
    if _line_is_other_section_start(line) or len(lower) < 3:
        return False
    for label in EDUCATION_DEGREE_LABELS:
        if lower.startswith(label) or lower.startswith(label.rstrip() + " "):
            return True
    return False


def _split_degree_school(line: str) -> tuple[str, str]:
    """Split 'Degree : Name – School' or 'Degree - School' into (degree_part, school_part). Uses – or -."""
    # Prefer em/en dash then hyphen, max one split (first occurrence)
    for sep in [" – ", " - ", " — ", "-"]:
        if sep in line:
            parts = line.split(sep, 1)
            return (parts[0].strip()[:255], parts[1].strip()[:255]) if len(parts) == 2 else (line[:255], "")
    return (line.strip()[:255], "")


def _is_standalone_date_line(line: str) -> bool:
    """True if line is only a date range (e.g. '2025 – en cours', '2020-2023')."""
    stripped = line.strip()
    if re.match(r"^\d{4}\s*[-–—]\s*(?:en cours|présent|\d{4})\s*$", stripped, re.IGNORECASE):
        return True
    if re.match(r"^\d{4}\s*[-–—]\s*\d{4}\s*$", stripped):
        return True
    return False


def _parse_education_block(block: str) -> list[dict]:
    """
    Parse FORMATION block into separate education entries.
    - Detects degree labels (Cycle Ingénieur, Licence, Diplôme, Baccalauréat, etc.)
    - Splits 'Degree – School' on dash to get degree and school
    - Standalone date lines attach to the previous entry
    - Returns entries sorted by academic level (Bac → Diplôme → Licence → Cycle Ingénieur)
    """
    entries: list[dict] = []
    if not block or not block.strip():
        return entries
    block = _trim_at_next_section(block)
    lines = [ln.strip() for ln in block.split("\n") if ln.strip()]

    i = 0
    while i < len(lines):
        line = lines[i]
        if _line_is_other_section_start(line):
            break
        # Standalone date line → attach to last entry
        if _is_standalone_date_line(line):
            if entries:
                entries[-1]["year"] = line[:50]
            i += 1
            continue
        # Degree line (with optional " – School" or " - School" on same line)
        if _is_degree_line(line):
            degree_part, school_part = _split_degree_school(line)
            if not degree_part:
                i += 1
                continue
            entry: dict = {
                "degree": degree_part,
                "school": school_part,
                "year": "",
                "details": "",
            }
            # Next line: if not a degree line and not a date line, treat as school (if we don't have one) or details
            j = i + 1
            while j < len(lines):
                next_line = lines[j]
                if _line_is_other_section_start(next_line):
                    break
                if _is_standalone_date_line(next_line):
                    entry["year"] = next_line[:50]
                    j += 1
                    break
                if _is_degree_line(next_line):
                    break
                if not entry.get("school") and next_line and len(next_line) > 1:
                    entry["school"] = next_line[:255]
                else:
                    entry["details"] = (entry.get("details") or "") + next_line + "\n"
                j += 1
            entries.append(entry)
            i = j
            continue
        # Orphan line (no degree label): treat as school for last entry if missing, else skip
        if entries and not entries[-1].get("school") and line and len(line) > 1:
            entries[-1]["school"] = line[:255]
        i += 1

    # Sort by academic progression: Baccalauréat first, then Diplôme, Licence, Cycle Ingénieur/Master
    entries.sort(key=_education_sort_key)
    # Heuristic: "en cours" / "présent" usually refers to the current (highest) study; if a high-level entry has no year and another has "en cours", move year to the high-level one
    year_en_cours = None
    idx_with_en_cours = None
    for i, e in enumerate(entries):
        y = (e.get("year") or "").lower()
        if "en cours" in y or "présent" in y or "present" in y:
            year_en_cours = e.get("year")
            idx_with_en_cours = i
            break
    if year_en_cours and idx_with_en_cours is not None:
        # Find highest-level entry (cycle ingénieur / master) without a year
        high_labels = ["cycle ingénieur", "cycle ingenieur", "master", "ingénieur", "ingenieur"]
        for i in range(len(entries) - 1, -1, -1):
            if i == idx_with_en_cours:
                continue
            deg = (entries[i].get("degree") or "").lower()
            if any(h in deg for h in high_labels) and not (entries[i].get("year") or "").strip():
                entries[i]["year"] = year_en_cours
                entries[idx_with_en_cours]["year"] = ""
                break
    # Normalize: ensure degree/school/year only (details optional for JSON)
    return [
        {"degree": e.get("degree") or "", "school": e.get("school") or "", "year": e.get("year") or "", "details": e.get("details") or ""}
        for e in entries
    ]


def _parse_experience_block(block: str) -> list[dict]:
    entries = []
    if not block or not block.strip():
        return entries
    lines = [ln.strip() for ln in block.split("\n") if ln.strip()]
    current = {}
    for line in lines:
        if re.match(r"^\d{4}\s*[-–—]", line) or re.match(r"^[\d\s\-–—]+$", line):
            if current and (current.get("title") or current.get("company") or current.get("description")):
                entries.append(current)
            current = {"title": "", "company": "", "period": line, "description": ""}
            continue
        if not current:
            current = {"title": "", "company": "", "period": "", "description": ""}
        if not current.get("title") and len(line) > 1 and not _line_is_other_section_start(line):
            current["title"] = line[:255]
        elif current.get("title") and not current.get("company") and len(line) > 1 and not _line_is_other_section_start(line):
            current["company"] = line[:255]
            entries.append(current)
            current = {"title": "", "company": "", "period": "", "description": ""}
        elif current.get("company") or current.get("title"):
            current["description"] = (current.get("description") or "") + line + "\n"
    if current and (current.get("title") or current.get("company") or current.get("description")):
        entries.append(current)
    return entries


def _extract_skills_keywords_only(text: str) -> dict[str, list[str]]:
    """Extract skills only via whitelist and category regex. No naive tokenization."""
    categories = {
        "programming": [],
        "databases": [],
        "systems": [],
        "network": [],
        "security": [],
        "soft_skills": [],
    }
    cat_patterns = {
        "programming": re.compile(
            r"\b(python|java|javascript|php|c\+\+|c#|ruby|go|golang|react|angular|vue|node\.?js|html|css|typescript|dart|flutter|kotlin|swift)\b",
            re.IGNORECASE,
        ),
        "databases": re.compile(
            r"\b(mysql|postgresql|mongodb|redis|sql|nosql|oracle)\b",
            re.IGNORECASE,
        ),
        "systems": re.compile(
            r"\b(linux|windows|docker|kubernetes|git|ci/cd|jenkins|aws|azure|gcp)\b",
            re.IGNORECASE,
        ),
        "network": re.compile(
            r"\b(réseaux|networks|tcp/ip|dns|vpn|api|rest)\b",
            re.IGNORECASE,
        ),
        "security": re.compile(
            r"\b(sécurité|security|cybersécurité|ssl|oauth)\b",
            re.IGNORECASE,
        ),
        "soft_skills": re.compile(
            r"\b(travail d'équipe|teamwork|communication|leadership|gestion de projet|autonome|créatif|agile|scrum)\b",
            re.IGNORECASE | re.UNICODE,
        ),
    }
    seen = set()
    for cat, pat in cat_patterns.items():
        for m in pat.finditer(text):
            token = m.group(0).strip()
            key = token.lower()
            if key in SKILL_STOPWORDS or len(token) > 60:
                continue
            if key not in seen:
                seen.add(key)
                categories[cat].append(token)
    # Also allow comma-separated tokens that are in whitelist
    for part in re.split(r"[\n,;|/]", text):
        token = part.strip()
        if len(token) < 2 or len(token) > 50:
            continue
        key = token.lower().replace(" ", "")
        if key in SKILL_STOPWORDS:
            continue
        for w in SKILL_WHITELIST:
            if w in key or key in w.replace(" ", ""):
                if token.lower() not in seen:
                    seen.add(token.lower())
                    categories["programming"].append(token)
                break
    return categories


def _parse_languages_block(block: str) -> list[str]:
    if not block or not block.strip():
        return []
    lines = [ln.strip() for ln in block.split("\n") if ln.strip()]
    out = []
    for line in lines:
        if re.match(r"^(LOISIRS|HOBBIES)", line, re.IGNORECASE):
            break
        out.append(line[:200])
    return out


def parse(text: str) -> dict[str, Any]:
    text = (text or "").strip()
    if not text:
        return {
            "personal_info": {},
            "summary": "",
            "experience": [],
            "education": [],
            "skills": {"programming": [], "databases": [], "systems": [], "network": [], "security": [], "soft_skills": []},
            "skills_raw": "",
            "languages": [],
            "hobbies": [],
        }

    sections = _split_into_sections(text)
    regex_info = _regex_extract(text)

    personal_block = sections.get("personal", "")
    personal_info = _parse_personal_and_title(personal_block, regex_info)

    summary = sections.get("summary", "").strip()[:500]

    education_raw = sections.get("education", "")
    education = _parse_education_block(education_raw)

    experience_raw = sections.get("experience", "")
    experience = _parse_experience_block(experience_raw)

    skills_raw = sections.get("skills", "")
    skills = _extract_skills_keywords_only(skills_raw) if skills_raw else {"programming": [], "databases": [], "systems": [], "network": [], "security": [], "soft_skills": []}
    if skills_raw and not any(skills.get(c) for c in ("programming", "databases", "systems", "network", "security", "soft_skills")):
        skills["programming"] = []

    languages_raw = sections.get("languages", "")
    languages = _parse_languages_block(languages_raw) if languages_raw else []

    hobbies_raw = sections.get("hobbies", "")
    hobbies = [ln.strip() for ln in (hobbies_raw or "").split("\n") if ln.strip()] if hobbies_raw else []

    return {
        "personal_info": personal_info,
        "summary": summary,
        "experience": experience,
        "education": education,
        "skills": skills,
        "skills_raw": skills_raw,
        "languages": languages,
        "hobbies": hobbies,
    }


def to_flat_parsed(structured: dict[str, Any]) -> dict[str, Any]:
    out = {}
    pi = structured.get("personal_info") or {}
    if pi.get("email"):
        out["email"] = (pi["email"] or "")[:255]
    if pi.get("phone"):
        out["telephone"] = (pi["phone"] or "")[:50]
    if pi.get("address"):
        addr = (pi["address"] or "").strip()
        if len(addr) > 255:
            addr = addr[:255]
        ville = ""
        for part in re.split(r"[,;]", addr):
            part = part.strip()
            if 2 <= len(part) <= 100 and (part.isupper() or re.match(r"^[A-Za-zÀ-ÿ\-]+$", part)):
                if part.lower() not in CITY_ADDRESS_NOISE:
                    ville = part
        if ville:
            out["ville"] = ville[:255]
        else:
            clean_addr = " ".join(p for p in re.split(r"[,;]", addr) if p.strip().lower() not in CITY_ADDRESS_NOISE and len(p.strip()) > 1)
            if clean_addr:
                out["ville"] = clean_addr[:255]
    if pi.get("title"):
        out["poste_actuel"] = (pi["title"] or "")[:255]

    education = structured.get("education") or []
    if education:
        # Use highest degree (last after academic sort) for flat DB fields
        highest = education[-1]
        if highest.get("degree"):
            out["education_niveau"] = (highest["degree"] or "")[:255]
        if highest.get("school"):
            out["universite"] = (highest["school"] or "")[:255]
        if highest.get("year"):
            out["annee_diplome"] = (highest["year"] or "")[:50]
        out["diplome"] = (highest.get("degree") or "")[:255]

    experience = structured.get("experience") or []
    if experience:
        first = experience[0]
        if not out.get("poste_actuel") and first.get("title"):
            out["poste_actuel"] = (first["title"] or "")[:255]
        if first.get("company"):
            out["entreprise_actuelle"] = (first["company"] or "")[:255]
        detail_lines = []
        for e in experience:
            t, c, p = (e.get("title") or ""), (e.get("company") or ""), (e.get("period") or "")
            if t or c or p:
                detail_lines.append(f"{t} @ {c} {p}".strip())
        if detail_lines:
            out["experience_detail_raw"] = "\n".join(detail_lines)[:8000]
        years = 0
        for e in experience:
            per = (e.get("period") or "")
            for m in re.finditer(r"(\d{4})\s*[-–—]\s*(\d{4}|présent)", per, re.IGNORECASE):
                y1 = int(m.group(1))
                y2_s = m.group(2)
                if y2_s.isdigit():
                    years += int(y2_s) - y1
                else:
                    years += datetime.now().year - y1
        if years and 0 < years <= 50:
            out["experience_annees"] = years

    skills = structured.get("skills") or {}
    all_skills = []
    for cat in ("programming", "databases", "systems", "network", "security", "soft_skills"):
        all_skills.extend(skills.get(cat) or [])
    if all_skills:
        out["competences_techniques_raw"] = ", ".join(all_skills)[:2000]

    languages = structured.get("languages") or []
    if languages:
        out["langues_niveau_raw"] = "\n".join(languages)[:1000]
        lang_names = []
        for line in languages:
            name = re.split(r"[:\-]", line, 1)[0].strip()
            if name and len(name) < 50 and name.lower() not in CITY_ADDRESS_NOISE:
                lang_names.append(name)
        if lang_names:
            out["competences_langues_raw"] = ", ".join(lang_names)[:500]

    if structured.get("summary"):
        out["disponibilite"] = (structured["summary"] or "")[:255]

    return out
