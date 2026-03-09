#!/usr/bin/env python3
"""
RecruteIA — Normalisation des profils candidats pour le matching IA.

Lit tous les candidats en base, calcule les champs normalisés (compétences, langues,
niveaux, formation, expérience) sous forme de listes de tokens ou chaînes, puis
upsert dans candidate_profiles. Utilisé avant le pipeline de recommandation (TF-IDF).
Sortie : JSON sur stdout { rows_updated, rows_affected, status } pour PHP.
"""
import json
import os
import re
import sys

import mysql.connector

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from config import DB_CONFIG


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def normalize_text(s):
    if not s or not isinstance(s, str):
        return ""
    s = s.lower().strip()
    s = re.sub(r"\s+", " ", s)
    s = re.sub(r"[,;|/\n]+", " ", s)
    return s.strip()


def split_tokens(s, separators=None):
    if not s:
        return []
    if separators is None:
        separators = r"[,;|/\n]"
    parts = re.split(separators, str(s))
    out = []
    for p in parts:
        p = normalize_text(p)
        if p and p not in out:
            out.append(p)
    return out


def normalize_skills(raw):
    tokens = split_tokens(raw)
    return tokens


def normalize_languages(raw):
    return split_tokens(raw)


def normalize_language_level(raw):
    if not raw:
        return []
    tokens = split_tokens(raw)
    level_map = {"natif": "native", "bilingue": "bilingual", "courant": "fluent", "intermédiaire": "intermediate", "intermediaire": "intermediate", "débutant": "beginner", "debutant": "beginner"}
    out = []
    for t in tokens:
        out.append(level_map.get(t.lower(), t))
    return out


def normalize_education(row):
    """Retourne une liste de tokens (formation, diplôme, établissement, année) pour stockage JSON."""
    tokens = []
    for k in ["education_niveau", "diplome", "universite", "annee_diplome"]:
        v = (row.get(k) or "").strip()
        if v:
            for t in split_tokens(str(v)):
                if t and t not in tokens:
                    tokens.append(t)
    return tokens


def normalize_experience(row):
    """Retourne une liste de tokens (expérience, poste, entreprise, projets, certifs) pour stockage JSON."""
    tokens = []
    for k in ["experience_annees", "poste_actuel", "entreprise_actuelle", "experience_detail_raw", "projets_raw", "certifications_raw"]:
        v = (row.get(k) or "").strip()
        if v:
            for t in split_tokens(str(v)):
                if t and t not in tokens:
                    tokens.append(t)
    return tokens


def main():
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT id, education_niveau, diplome, universite, annee_diplome,
               competences_techniques_raw, competences_langues_raw, langues_niveau_raw,
               experience_annees, poste_actuel, entreprise_actuelle,
               experience_detail_raw, projets_raw, certifications_raw
        FROM candidates
    """)
    rows = cursor.fetchall()
    updated = 0
    upsert_sql = """
    INSERT INTO candidate_profiles (candidate_id, skills_norm, languages_norm, languages_level_norm, education_norm, experience_norm)
    VALUES (%s, %s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
        skills_norm = VALUES(skills_norm), languages_norm = VALUES(languages_norm),
        languages_level_norm = VALUES(languages_level_norm), education_norm = VALUES(education_norm),
        experience_norm = VALUES(experience_norm), updated_at = CURRENT_TIMESTAMP
    """
    for row in rows:
        cid = row["id"]
        skills = normalize_skills(row.get("competences_techniques_raw") or "")
        languages = normalize_languages(row.get("competences_langues_raw") or "")
        levels = normalize_language_level(row.get("langues_niveau_raw") or "")
        education = normalize_education(row)
        experience = normalize_experience(row)
        cursor.execute(upsert_sql, (
            cid,
            json.dumps(skills) if skills else None,
            json.dumps(languages) if languages else None,
            json.dumps(levels) if levels else None,
            json.dumps(education) if education else None,
            json.dumps(experience) if experience else None,
        ))
        updated += 1
    conn.commit()
    cursor.close()
    conn.close()
    out = {"rows_updated": updated, "rows_affected": updated, "status": "completed"}
    print(json.dumps(out))


if __name__ == "__main__":
    main()
