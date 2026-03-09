#!/usr/bin/env python3
"""
AI/NLP step for CV parsing: use an LLM (OpenAI-compatible API or Ollama) to extract
job title, summary, education entries, experience entries, and skills from raw CV text.
Configure via env: CV_LLM_API_URL, CV_LLM_API_KEY (optional), CV_LLM_MODEL.
"""
import json
import os
from typing import Any

# -----------------------------------------------------------------------------
# Config from env (no .env file required; app can set env)
# -----------------------------------------------------------------------------
CV_LLM_API_URL = os.environ.get("CV_LLM_API_URL", "").rstrip("/")  # e.g. https://api.openai.com/v1 or http://localhost:11434/v1
CV_LLM_API_KEY = os.environ.get("CV_LLM_API_KEY", "")
CV_LLM_MODEL = os.environ.get("CV_LLM_MODEL", "gpt-4o-mini")  # or llama3.2, mistral, etc.
CV_AI_ENABLED = os.environ.get("CV_AI_ENABLED", "0").strip().lower() in ("1", "true", "yes")

# Truncate CV text sent to LLM to avoid token limits
CV_AI_MAX_CHARS = int(os.environ.get("CV_AI_MAX_CHARS", "12000"))


EXTRACT_SYSTEM = """Tu es un expert en extraction de données structurées à partir de CV.
Tu dois renvoyer UNIQUEMENT un objet JSON valide, sans texte avant ou après.
Schéma attendu (tous les champs peuvent être vides ou listes vides) :
{
  "job_title": "string (intitulé du poste ou titre professionnel)",
  "summary": "string (résumé / objectif / profil en une phrase ou deux)",
  "education": [
    { "degree": "string", "school": "string", "year": "string", "details": "string" }
  ],
  "experience": [
    { "title": "string", "company": "string", "duration": "string", "description": "string" }
  ],
  "skills": ["string (compétences techniques et soft skills)"]
}
Règles : ordre formation = du plus ancien au plus récent (Bac puis Diplôme puis Licence puis Master/Ingénieur). 
Compétences = uniquement des noms (technologies, langages, outils), pas de phrases. 
Pas de commentaire, pas de markdown, uniquement le JSON."""

EXTRACT_USER_TEMPLATE = """Extrais les champs suivants à partir de ce CV (texte brut extrait d'un PDF).
Renvoie uniquement le JSON décrit dans les instructions.

--- DÉBUT DU CV ---
{text}
--- FIN DU CV ---"""


def _call_openai_compatible(
    api_url: str,
    api_key: str,
    model: str,
    messages: list[dict],
    timeout: int = 90,
) -> str:
    """Call OpenAI-compatible chat API (OpenAI, Ollama, Azure, etc.). Returns content of first choice."""
    try:
        import urllib.request
    except ImportError:
        import urllib.request  # noqa: F401
    url = api_url + "/chat/completions"
    body = {
        "model": model,
        "messages": messages,
        "temperature": 0.1,
        "max_tokens": 4000,
    }
    data = json.dumps(body).encode("utf-8")
    headers = {"Content-Type": "application/json"}
    if api_key:
        headers["Authorization"] = f"Bearer {api_key}"
    req = urllib.request.Request(url, data=data, headers=headers, method="POST")
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        out = json.loads(resp.read().decode("utf-8"))
    content = (out.get("choices") or [{}])[0].get("message", {}).get("content", "")
    return content.strip()


def _parse_json_from_response(raw: str) -> dict[str, Any]:
    """Extract a single JSON object from model output (may be wrapped in markdown)."""
    raw = (raw or "").strip()
    # Remove markdown code block if present
    if "```json" in raw:
        raw = raw.split("```json", 1)[-1].split("```", 1)[0].strip()
    elif "```" in raw:
        raw = raw.split("```", 1)[-1].split("```", 1)[0].strip()
    return json.loads(raw)


def extract_with_ai(cv_text: str) -> dict[str, Any] | None:
    """
    Call LLM to extract job_title, summary, education[], experience[], skills[].
    Returns a dict compatible with our structured schema, or None if AI is disabled or fails.
    """
    if not CV_AI_ENABLED or not CV_LLM_API_URL:
        return None
    text = (cv_text or "")[:CV_AI_MAX_CHARS]
    if not text.strip():
        return None
    user_content = EXTRACT_USER_TEMPLATE.format(text=text)
    messages = [
        {"role": "system", "content": EXTRACT_SYSTEM},
        {"role": "user", "content": user_content},
    ]
    try:
        content = _call_openai_compatible(CV_LLM_API_URL, CV_LLM_API_KEY, CV_LLM_MODEL, messages, timeout=45)
        if not content:
            return None
        data = _parse_json_from_response(content)
    except Exception:
        return None  # Timeout or API error → fallback to rule-based only
    # Normalize to our schema
    out: dict[str, Any] = {
        "personal_info": {},
        "summary": "",
        "experience": [],
        "education": [],
        "skills": {"programming": [], "databases": [], "systems": [], "network": [], "security": [], "soft_skills": []},
        "skills_raw": "",
        "languages": [],
        "hobbies": [],
    }
    if isinstance(data.get("job_title"), str) and data["job_title"].strip():
        out["personal_info"]["title"] = data["job_title"].strip()[:255]
    if isinstance(data.get("summary"), str) and data["summary"].strip():
        out["summary"] = data["summary"].strip()[:500]
    if isinstance(data.get("education"), list):
        for ed in data["education"]:
            if not isinstance(ed, dict):
                continue
            out["education"].append({
                "degree": (ed.get("degree") or "").strip()[:255],
                "school": (ed.get("school") or "").strip()[:255],
                "year": (ed.get("year") or "").strip()[:50],
                "details": (ed.get("details") or "").strip()[:500],
            })
    if isinstance(data.get("experience"), list):
        for ex in data["experience"]:
            if not isinstance(ex, dict):
                continue
            out["experience"].append({
                "title": (ex.get("title") or "").strip()[:255],
                "company": (ex.get("company") or "").strip()[:255],
                "period": (ex.get("duration") or ex.get("period") or "").strip()[:100],
                "description": (ex.get("description") or "").strip()[:2000],
            })
    if isinstance(data.get("skills"), list):
        skills_list = []
        for s in data["skills"]:
            if isinstance(s, str) and s.strip() and len(s.strip()) <= 80:
                skills_list.append(s.strip())
        out["skills_raw"] = ", ".join(skills_list)
        # Put all in soft_skills for compatibility; PHP will flatten
        out["skills"]["soft_skills"] = skills_list
    return out


def merge_structured(rule_based: dict[str, Any], ai_result: dict[str, Any] | None) -> dict[str, Any]:
    """
    Merge AI result into rule-based structured output.
    - Regex/section fields (email, phone, etc.) keep rule_based.
    - job_title, summary: use AI if present and rule_based is empty.
    - education, experience, skills: use AI if it has more entries or rule_based is empty.
    """
    if not ai_result:
        return rule_based
    out = dict(rule_based)
    pi = out.get("personal_info") or {}
    ai_pi = ai_result.get("personal_info") or {}
    if (ai_pi.get("title") or "").strip() and not (pi.get("title") or "").strip():
        pi = dict(pi)
        pi["title"] = (ai_pi.get("title") or "").strip()[:255]
        out["personal_info"] = pi
    if (ai_result.get("summary") or "").strip() and not (out.get("summary") or "").strip():
        out["summary"] = (ai_result.get("summary") or "").strip()[:500]
    if not out.get("education") and ai_result.get("education"):
        out["education"] = list(ai_result["education"])
    elif ai_result.get("education") and len(ai_result["education"]) >= len(out.get("education") or []):
        out["education"] = list(ai_result["education"])
    if not out.get("experience") and ai_result.get("experience"):
        out["experience"] = list(ai_result["experience"])
    elif ai_result.get("experience") and len(ai_result["experience"]) >= len(out.get("experience") or []):
        out["experience"] = list(ai_result["experience"])
    ai_skills = ai_result.get("skills") or {}
    ai_flat = (ai_skills.get("soft_skills") or []) + (ai_skills.get("programming") or [])
    out_skills = out.get("skills") or {}
    out_flat = []
    for cat in ("programming", "databases", "systems", "network", "security", "soft_skills"):
        out_flat.extend(out_skills.get(cat) or [])
    if ai_flat and len(ai_flat) >= len(out_flat):
        out["skills"] = {"programming": [], "databases": [], "systems": [], "network": [], "security": [], "soft_skills": ai_flat}
    return out
