#!/usr/bin/env python3
"""
RecruteIA — Import candidats CSV vers MySQL.

Import massif de candidats depuis un fichier CSV. Idempotent : upsert par email.
Colonnes attendues (noms exacts) : id, nom, prenom, email, telephone, age, ville,
experience_annees, poste_actuel, entreprise_actuelle, education_niveau, diplome,
universite, annee_diplome, competences_techniques, competences_langues, langues_niveau,
experience_detail, projets, certifications, disponibilite, pretention_salaire.
Mapping vers la table candidates : competences_techniques → competences_techniques_raw, etc.
Sortie : JSON sur stdout pour PHP (rows_processed, rows_inserted, rows_updated, rows_failed, error_log).
"""
import argparse
import json
import os
import sys

import pandas as pd
import mysql.connector
from mysql.connector import Error

# Add parent to path for config
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from config import DB_CONFIG, DEFAULT_CSV_PATH

REQUIRED_COLUMNS = [
    "id", "nom", "prenom", "email", "telephone", "age", "ville",
    "experience_annees", "poste_actuel", "entreprise_actuelle",
    "education_niveau", "diplome", "universite", "annee_diplome",
    "competences_techniques", "competences_langues", "langues_niveau",
    "experience_detail", "projets", "certifications", "disponibilite", "pretention_salaire",
]


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--path", default=DEFAULT_CSV_PATH, help="Path to CSV file")
    args = parser.parse_args()
    path = args.path.strip().strip("'\"")
    if not os.path.isfile(path):
        out = {"error": f"File not found: {path}", "rows_processed": 0, "rows_inserted": 0, "rows_updated": 0, "rows_failed": 0, "error_log": f"File not found: {path}"}
        print(json.dumps(out))
        sys.exit(1)

    try:
        df = pd.read_csv(path, encoding="utf-8", dtype=str, on_bad_lines="skip")
    except Exception as e:
        out = {"error": str(e), "rows_processed": 0, "rows_inserted": 0, "rows_updated": 0, "rows_failed": 0, "error_log": str(e)}
        print(json.dumps(out))
        sys.exit(1)

    for col in REQUIRED_COLUMNS:
        if col not in df.columns:
            out = {"error": f"Missing column: {col}", "rows_processed": 0, "rows_inserted": 0, "rows_updated": 0, "rows_failed": 0, "error_log": f"Missing column: {col}"}
            print(json.dumps(out))
            sys.exit(1)

    df = df.fillna("")
    conn = get_connection()
    cursor = conn.cursor()

    insert_sql = """
    INSERT INTO candidates (
        csv_source_id, nom, prenom, email, telephone, age, ville,
        experience_annees, poste_actuel, entreprise_actuelle,
        education_niveau, diplome, universite, annee_diplome,
        competences_techniques_raw, competences_langues_raw, langues_niveau_raw,
        experience_detail_raw, projets_raw, certifications_raw,
        disponibilite, pretention_salaire
    ) VALUES (
        %(csv_source_id)s, %(nom)s, %(prenom)s, %(email)s, %(telephone)s, %(age)s, %(ville)s,
        %(experience_annees)s, %(poste_actuel)s, %(entreprise_actuelle)s,
        %(education_niveau)s, %(diplome)s, %(universite)s, %(annee_diplome)s,
        %(competences_techniques_raw)s, %(competences_langues_raw)s, %(langues_niveau_raw)s,
        %(experience_detail_raw)s, %(projets_raw)s, %(certifications_raw)s,
        %(disponibilite)s, %(pretention_salaire)s
    )
    ON DUPLICATE KEY UPDATE
        csv_source_id = VALUES(csv_source_id), nom = VALUES(nom), prenom = VALUES(prenom),
        telephone = VALUES(telephone), age = VALUES(age), ville = VALUES(ville),
        experience_annees = VALUES(experience_annees), poste_actuel = VALUES(poste_actuel),
        entreprise_actuelle = VALUES(entreprise_actuelle), education_niveau = VALUES(education_niveau),
        diplome = VALUES(diplome), universite = VALUES(universite), annee_diplome = VALUES(annee_diplome),
        competences_techniques_raw = VALUES(competences_techniques_raw),
        competences_langues_raw = VALUES(competences_langues_raw),
        langues_niveau_raw = VALUES(langues_niveau_raw),
        experience_detail_raw = VALUES(experience_detail_raw),
        projets_raw = VALUES(projets_raw), certifications_raw = VALUES(certifications_raw),
        disponibilite = VALUES(disponibilite), pretention_salaire = VALUES(pretention_salaire),
        updated_at = CURRENT_TIMESTAMP
    """

    rows_processed = 0
    rows_inserted = 0
    rows_updated = 0
    rows_failed = 0
    errors = []

    for _, row in df.iterrows():
        rows_processed += 1
        email = (row.get("email") or "").strip()
        if not email:
            rows_failed += 1
            errors.append(f"Row {rows_processed}: missing email")
            continue
        try:
            age_val = row.get("age") or ""
            if age_val and str(age_val).isdigit():
                age_val = int(age_val)
            else:
                age_val = None
            exp_val = row.get("experience_annees") or ""
            if exp_val and str(exp_val).replace(".", "").isdigit():
                try:
                    exp_val = int(float(exp_val))
                except ValueError:
                    exp_val = None
            else:
                exp_val = None
            data = {
                "csv_source_id": int(row["id"]) if str(row.get("id", "")).isdigit() else None,
                "nom": (row.get("nom") or "").strip() or "",
                "prenom": (row.get("prenom") or "").strip() or "",
                "email": email,
                "telephone": (row.get("telephone") or "").strip() or None,
                "age": age_val,
                "ville": (row.get("ville") or "").strip() or None,
                "experience_annees": exp_val,
                "poste_actuel": (row.get("poste_actuel") or "").strip() or None,
                "entreprise_actuelle": (row.get("entreprise_actuelle") or "").strip() or None,
                "education_niveau": (row.get("education_niveau") or "").strip() or None,
                "diplome": (row.get("diplome") or "").strip() or None,
                "universite": (row.get("universite") or "").strip() or None,
                "annee_diplome": (row.get("annee_diplome") or "").strip() or None,
                "competences_techniques_raw": (row.get("competences_techniques") or "").strip() or None,
                "competences_langues_raw": (row.get("competences_langues") or "").strip() or None,
                "langues_niveau_raw": (row.get("langues_niveau") or "").strip() or None,
                "experience_detail_raw": (row.get("experience_detail") or "").strip() or None,
                "projets_raw": (row.get("projets") or "").strip() or None,
                "certifications_raw": (row.get("certifications") or "").strip() or None,
                "disponibilite": (row.get("disponibilite") or "").strip() or None,
                "pretention_salaire": (row.get("pretention_salaire") or "").strip() or None,
            }
            cursor.execute("SELECT id FROM candidates WHERE email = %s", (email,))
            existed = cursor.fetchone()
            cursor.execute(insert_sql, data)
            if existed:
                rows_updated += 1
            else:
                rows_inserted += 1
        except Exception as e:
            rows_failed += 1
            errors.append(f"Row {rows_processed}: {str(e)}")

    conn.commit()
    cursor.close()
    conn.close()

    error_log = "\n".join(errors[:200])
    if len(errors) > 200:
        error_log += f"\n... and {len(errors) - 200} more."

    out = {
        "rows_processed": rows_processed,
        "rows_inserted": rows_inserted,
        "rows_updated": rows_updated,
        "rows_failed": rows_failed,
        "error_log": error_log,
        "status": "completed",
    }
    print(json.dumps(out))


if __name__ == "__main__":
    main()
