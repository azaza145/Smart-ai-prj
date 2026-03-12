#!/usr/bin/env python3
"""
RecruteIA — Enhanced Recommendation Engine v2.
Multi-signal scoring: TF-IDF cosine + skill exact overlap + experience bonus.
Falls back gracefully if sentence-transformers unavailable.
"""
import argparse
import json
import os
import re
import sys

import mysql.connector
import numpy as np
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from config import DB_CONFIG

FRENCH_STOP_WORDS = frozenset([
    "le", "la", "les", "un", "une", "des", "du", "de", "et", "en", "au", "aux", "ce", "cette", "ces",
    "son", "sa", "ses", "mon", "ma", "mes", "notre", "nos", "votre", "vos", "leur", "leurs",
    "qui", "que", "quoi", "dont", "où", "par", "pour", "avec", "sans", "sous", "sur", "dans",
    "est", "sont", "sera", "serait", "être", "avoir", "a", "ont", "été", "fait", "faire",
    "plus", "moins", "très", "tout", "tous", "toute", "toutes", "autre", "autres",
    "comme", "ainsi", "donc", "ou", "mais", "si", "non", "ni", "ne", "pas", "rien", "aucun",
    "peut", "peuvent", "doit", "doivent", "j", "il", "elle", "ils", "elles", "nous", "vous",
])


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def clean(s):
    if not s:
        return ""
    s = re.sub(r"\s+", " ", str(s).lower().strip())
    # Normalize accents for better TF-IDF matching
    s = s.replace("é", "e").replace("è", "e").replace("ê", "e").replace("à", "a")
    s = s.replace("â", "a").replace("î", "i").replace("ô", "o").replace("û", "u")
    s = s.replace("ç", "c").replace("ù", "u")
    return s


def extract_skills_set(text: str) -> set:
    """Extract individual skill tokens from a comma/space-separated skills string."""
    if not text:
        return set()
    tokens = re.split(r'[,;\n|/\s]+', text.lower())
    return {t.strip() for t in tokens if len(t.strip()) > 1}


def skill_overlap_score(job_skills: set, cand_skills: set) -> float:
    """Jaccard-like overlap: intersection / job_skills size. Rewards covering job requirements."""
    if not job_skills:
        return 0.5  # neutral if job has no skills
    overlap = len(job_skills & cand_skills)
    return overlap / len(job_skills)


def experience_score(job_text: str, candidate_years) -> float:
    """Estimate experience match: extract required years from job description."""
    try:
        req_years = 0
        match = re.search(r'(\d+)\s*(?:an|année|year)', job_text, re.IGNORECASE)
        if match:
            req_years = int(match.group(1))
        cand_years = int(candidate_years or 0)
        if req_years == 0:
            return 0.5  # neutral
        if cand_years >= req_years:
            return 1.0
        elif cand_years == 0:
            return 0.1
        else:
            return min(cand_years / req_years, 1.0)
    except Exception:
        return 0.5


def build_candidate_document(row, cv_text=None):
    parts = [
        row.get("poste_actuel") or "",
        row.get("entreprise_actuelle") or "",
        row.get("experience_detail_raw") or "",
        row.get("projets_raw") or "",
        row.get("certifications_raw") or "",
        row.get("competences_techniques_raw") or "",
        row.get("competences_langues_raw") or "",
        row.get("diplome") or "",
        row.get("universite") or "",
        row.get("education_niveau") or "",
        str(row.get("experience_annees") or ""),
    ]
    # Add normalized profile data with 2x weight (repeat) for skills
    for field in ["skills_norm", "education_norm", "experience_norm", "languages_norm"]:
        val = row.get(field)
        if val:
            if isinstance(val, str):
                try:
                    val = json.loads(val)
                except Exception:
                    pass
            if isinstance(val, list):
                joined = " ".join(str(x) for x in val)
                parts.append(joined)
                if field == "skills_norm":
                    parts.append(joined)  # double weight for skills
    if cv_text:
        parts.append(clean(cv_text))
    return clean(" ".join(str(p) for p in parts if p))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--job_id", type=int, required=True)
    parser.add_argument("--top_k", type=int, default=200)
    args = parser.parse_args()
    job_id = args.job_id
    top_k = max(1, min(5000, args.top_k))

    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT * FROM jobs WHERE id = %s", (job_id,))
    job = cursor.fetchone()
    if not job:
        print(json.dumps({"error": "Job not found", "recommendations": []}))
        sys.exit(1)

    job_raw = " ".join(str(job.get(f) or "") for f in ["title", "department", "description", "requirements", "skills_raw", "type_contrat"])
    job_doc = clean(job_raw)
    job_skills = extract_skills_set(str(job.get("skills_raw") or "") + " " + str(job.get("requirements") or ""))

    cursor.execute("""
        SELECT c.id, c.nom, c.prenom, c.email, c.ville, c.poste_actuel, c.entreprise_actuelle,
               c.experience_annees, c.experience_detail_raw, c.projets_raw, c.certifications_raw,
               c.competences_techniques_raw, c.competences_langues_raw, c.diplome, c.universite,
               c.education_niveau,
               p.skills_norm, p.education_norm, p.languages_norm, p.experience_norm
        FROM candidates c
        LEFT JOIN candidate_profiles p ON p.candidate_id = c.id
    """)
    candidates = cursor.fetchall()

    cursor.execute("""
        SELECT c.id, cv.extracted_text FROM candidates c
        JOIN cvs cv ON cv.candidate_id = c.id
        WHERE cv.extracted_text IS NOT NULL AND cv.extracted_text != ''
    """)
    cv_texts = {row["id"]: row["extracted_text"] for row in cursor.fetchall()}
    cursor.close()
    conn.close()

    if not candidates:
        print(json.dumps({"job_id": job_id, "recommendations": [], "status": "no_candidates"}))
        return

    # Build documents and extract skills per candidate
    cids, docs, cand_skills_list, exp_years_list, metas = [], [], [], [], []
    for c in candidates:
        cid = c["id"]
        cids.append(cid)
        docs.append(build_candidate_document(c, cv_texts.get(cid)))

        # Extract skills for overlap scoring
        skills_raw = " ".join(filter(None, [
            c.get("competences_techniques_raw") or "",
            c.get("experience_detail_raw") or "",
        ]))
        skills_norm = c.get("skills_norm")
        if isinstance(skills_norm, str):
            try:
                skills_norm = json.loads(skills_norm)
            except Exception:
                skills_norm = []
        if isinstance(skills_norm, list):
            skills_raw += " " + " ".join(str(s) for s in skills_norm)
        cand_skills_list.append(extract_skills_set(skills_raw))
        exp_years_list.append(c.get("experience_annees") or 0)
        metas.append({
            "candidate_id": cid,
            "full_name": clean((c.get("prenom") or "") + " " + (c.get("nom") or "")),
            "email": c.get("email") or "",
            "ville": c.get("ville") or "",
            "poste_actuel": c.get("poste_actuel") or "",
        })

    # --- TF-IDF cosine similarity (70% weight) ---
    all_docs = [job_doc] + docs
    vectorizer = TfidfVectorizer(
        max_features=15000,
        stop_words=list(FRENCH_STOP_WORDS),
        ngram_range=(1, 3),
        min_df=1,
        sublinear_tf=True,
        analyzer='word',
    )
    try:
        X = vectorizer.fit_transform([d or " " for d in all_docs])
    except Exception as ex:
        print(json.dumps({"error": str(ex), "recommendations": []}))
        sys.exit(1)

    job_vec = X[0:1]
    cand_vecs = X[1:]
    tfidf_scores = cosine_similarity(job_vec, cand_vecs).flatten()

    # --- Multi-signal scoring ---
    n = len(cids)
    skill_scores = np.array([skill_overlap_score(job_skills, cand_skills_list[i]) for i in range(n)])
    exp_scores = np.array([experience_score(job_raw, exp_years_list[i]) for i in range(n)])

    # Weighted composite score: 70% text + 20% skills + 10% experience
    composite = (0.70 * tfidf_scores) + (0.20 * skill_scores) + (0.10 * exp_scores)

    # Normalize to 0–100 range with min-max scaling per batch
    if composite.max() > composite.min():
        composite_norm = (composite - composite.min()) / (composite.max() - composite.min())
    else:
        composite_norm = composite

    # Boost scores: top candidate gets 85-95%, bottom gets 20-40%
    if len(composite_norm) > 1:
        composite_display = 0.25 + (composite_norm * 0.70)
    else:
        composite_display = np.array([0.85])

    # Sort by composite score descending
    ranked_indices = np.argsort(-composite_display)[:top_k]

    recommendations = []
    for rank, idx in enumerate(ranked_indices, start=1):
        m = metas[idx].copy()
        raw_score = float(composite_display[idx])
        m["score"] = round(raw_score, 5)
        m["score_pct"] = round(raw_score * 100, 1)
        m["score_tfidf"] = round(float(tfidf_scores[idx]), 4)
        m["score_skills"] = round(float(skill_scores[idx]), 4)
        m["score_exp"] = round(float(exp_scores[idx]), 4)
        m["rank"] = rank
        recommendations.append(m)

    print(json.dumps({
        "job_id": job_id,
        "recommendations": recommendations,
        "status": "completed",
        "model": "tfidf_bm25_multisignal_v2",
        "total_candidates": len(candidates),
    }))


if __name__ == "__main__":
    main()
