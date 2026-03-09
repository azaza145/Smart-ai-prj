#!/usr/bin/env python3
"""
RecruteIA — Moteur de recommandation candidats / offres.

Construit un document texte par offre (titre, compétences, description, exigences) et un
document par candidat (profil, compétences, expérience, CV). Utilise TF-IDF + similarité
cosinus (scikit-learn) pour scorer et classer les candidats. Échange avec PHP via JSON sur stdout.
"""
import argparse
import json
import os
import re
import sys

import mysql.connector
import pandas as pd
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from config import DB_CONFIG

# Stop words français courants (pour améliorer le matching TF-IDF sur contenus FR)
FRENCH_STOP_WORDS = frozenset([
    "le", "la", "les", "un", "une", "des", "du", "de", "et", "en", "au", "aux", "ce", "cette", "ces",
    "son", "sa", "ses", "mon", "ma", "mes", "notre", "nos", "votre", "vos", "leur", "leurs",
    "qui", "que", "quoi", "dont", "où", "par", "pour", "avec", "sans", "sous", "sur", "dans",
    "est", "sont", "sera", "serait", "être", "avoir", "a", "ont", "été", "fait", "faire",
    "plus", "moins", "très", "tout", "tous", "toute", "toutes", "autre", "autres", "autre",
    "comme", "ainsi", "donc", "ou", "mais", "si", "non", "ni", "ne", "pas", "rien", "aucun",
    "peut", "peuvent", "doit", "doivent", "autre", "autres",
])


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def clean(s):
    if not s:
        return ""
    s = re.sub(r"\s+", " ", str(s).lower().strip())
    return s


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
    skills_norm = row.get("skills_norm")
    if skills_norm:
        if isinstance(skills_norm, str):
            try:
                skills_norm = json.loads(skills_norm)
            except Exception:
                skills_norm = []
        if isinstance(skills_norm, list):
            parts.append(" ".join(skills_norm))
    education_norm = row.get("education_norm")
    if education_norm:
        if isinstance(education_norm, str) and education_norm.startswith("["):
            try:
                education_norm = json.loads(education_norm)
            except Exception:
                pass
        if isinstance(education_norm, list):
            parts.append(" ".join(education_norm))
        else:
            parts.append(str(education_norm))
    experience_norm = row.get("experience_norm")
    if experience_norm:
        if isinstance(experience_norm, str) and experience_norm.startswith("["):
            try:
                experience_norm = json.loads(experience_norm)
            except Exception:
                pass
        if isinstance(experience_norm, list):
            parts.append(" ".join(experience_norm))
        else:
            parts.append(str(experience_norm))
    languages_norm = row.get("languages_norm")
    if languages_norm:
        if isinstance(languages_norm, str):
            try:
                languages_norm = json.loads(languages_norm)
            except Exception:
                languages_norm = []
        if isinstance(languages_norm, list):
            parts.append(" ".join(languages_norm))
    if cv_text:
        parts.append(clean(cv_text))
    return clean(" ".join(parts))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--job_id", type=int, required=True)
    parser.add_argument("--top_k", type=int, default=200)
    args = parser.parse_args()
    job_id = args.job_id
    top_k = max(1, min(5000, args.top_k))

    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    # Offre : titre, département, description, exigences, compétences (skills_raw), type de contrat
    # SELECT * pour rester compatible si skills_raw/type_contrat absents (migration non appliquée)
    cursor.execute("SELECT * FROM jobs WHERE id = %s", (job_id,))
    job = cursor.fetchone()
    if not job:
        out = {"error": "Job not found", "recommendations": []}
        print(json.dumps(out))
        sys.exit(1)

    job_doc = clean(" ".join([
        str(job.get("title") or ""),
        str(job.get("department") or ""),
        str(job.get("description") or ""),
        str(job.get("requirements") or ""),
        str(job.get("skills_raw") or ""),
        str(job.get("type_contrat") or ""),
    ]))
    if not job_doc.strip():
        job_doc = clean(str(job.get("title") or ""))

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
        SELECT c.id, cv.extracted_text
        FROM candidates c
        JOIN cvs cv ON cv.candidate_id = c.id
        WHERE cv.extracted_text IS NOT NULL AND cv.extracted_text != ''
    """)
    cv_texts = {row["id"]: row["extracted_text"] for row in cursor.fetchall()}

    # Construction des documents candidats (avec pandas pour cohérence stack)
    doc_by_id = {}
    meta_by_id = {}
    for c in candidates:
        cid = c["id"]
        cv_text = cv_texts.get(cid)
        doc = build_candidate_document(c, cv_text)
        doc_by_id[cid] = doc
        meta_by_id[cid] = {
            "candidate_id": cid,
            "full_name": clean((c.get("prenom") or "") + " " + (c.get("nom") or "")),
            "email": c.get("email") or "",
            "ville": c.get("ville") or "",
            "poste_actuel": c.get("poste_actuel") or "",
        }

    cids_ordered = sorted(doc_by_id.keys())
    all_docs = [job_doc] + [doc_by_id[cid] for cid in cids_ordered]
    # DataFrame pour exploitation / debug éventuel (pandas dans la stack)
    df_docs = pd.DataFrame({"candidate_id": [None] + list(cids_ordered), "document": all_docs})

    vectorizer = TfidfVectorizer(
        max_features=10000,
        stop_words=list(FRENCH_STOP_WORDS),
        ngram_range=(1, 2),
        min_df=1,
    )
    try:
        X = vectorizer.fit_transform(df_docs["document"].fillna(" ").astype(str))
    except Exception:
        X = vectorizer.fit_transform([d or " " for d in all_docs])
    job_vec = X[0:1]
    cand_vecs = X[1:]
    sims = cosine_similarity(job_vec, cand_vecs).flatten()
    scores = [(cids_ordered[i], float(sims[i])) for i in range(len(cids_ordered))]
    scores.sort(key=lambda x: -x[1])
    scores = scores[:top_k]

    recommendations = []
    for rank, (cid, score) in enumerate(scores, start=1):
        m = meta_by_id[cid].copy()
        m["score"] = round(score, 5)
        m["rank"] = rank
        recommendations.append(m)

    out = {"job_id": job_id, "recommendations": recommendations, "status": "completed"}
    print(json.dumps(out))
    cursor.close()
    conn.close()


if __name__ == "__main__":
    main()
