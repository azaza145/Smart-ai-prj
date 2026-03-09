# SmartRecruit / RecruteIA

SmartRecruit automates candidate pre-selection and ranking against job descriptions using **TF-IDF + Cosine Similarity**.

> **Documentation détaillée** : voir **[DOCUMENTATION.md](DOCUMENTATION.md)** pour l’enchaînement complet des fonctionnalités, les routes, la base de données, l’installation pas à pas et toutes les petites fonctionnalités (mot de passe oublié, démo, page d’accueil, etc.). The primary data source is a structured CSV dataset (5000 candidates); PDF CV upload is an optional second source.

## Tech stack

- **Frontend:** HTML, CSS, Bootstrap 5
- **Backend:** PHP 8.x (simple MVC, router, middleware)
- **Database:** MySQL 8
- **AI/Data:** Python 3.11, pandas, scikit-learn
- **PHP ↔ Python:** CLI calls; JSON + CSV exchange

## Quick start (Docker)

### 1. Start the stack

```bash
docker compose up -d
```

- App: **http://localhost:8080**
- MySQL: port 3306 (user `smartrecruit`, password `smartrecruit_secret`)

### 2. Install PHP dependencies

On the host (if you have Composer): `composer install`  
Or inside the PHP container: `docker compose exec php composer install --no-interaction`

### 3. Create admin user (seed)

From project root (with DB reachable, e.g. from host with port 3306 mapped):

```bash
php scripts/seed_admin.php
```

Default admin: **admin@smartrecruit.local** / **Admin123!**

Or inside the PHP container:

```bash
docker compose exec php php /var/www/html/scripts/seed_admin.php
```

### 4. Import CSV dataset

- Place **dataset_cvs_5000.csv** in the project root (or set `CSV_DATASET_PATH` in `.env` / docker-compose to its path).
- Log in as Admin → **Import CSV** → click **Import CSV dataset**.
- Wait for completion; check “Last import” for processed/inserted/updated/failed.

### 5. Run normalization

- Admin → **Dashboard** → **Run normalization pipeline**.
- This fills `candidate_profiles` (normalized skills, languages, education, experience) used for ranking.

### 6. Create a job and generate recommendations

- Admin or Recruiter → **Jobs** → create a job (title, optional department, description, requirements).
- Open the job → **Generate recommendations** (optionally set top_k, default 200).
- Open **View ranked results** to see candidates ordered by TF-IDF + cosine similarity score.

### 7. View results

- **View results** shows: Rank, Candidate, City, Current role, Score, Details.
- Use filters: city, min score, experience range.
- Click **Details** to see full candidate profile (raw + normalized) and any uploaded CVs.

## CSV format

Required columns:

`id`, `nom`, `prenom`, `email`, `telephone`, `age`, `ville`, `experience_annees`, `poste_actuel`, `entreprise_actuelle`, `education_niveau`, `diplome`, `universite`, `annee_diplome`, `competences_techniques`, `competences_langues`, `langues_niveau`, `experience_detail`, `projets`, `certifications`, `disponibilite`, `pretention_salaire`

Import is **idempotent**: same email → update; new email → insert.

## Roles

- **Admin:** Users (create recruiter/admin, enable/disable, reset password), Import CSV, Jobs CRUD, Run normalization, Generate recommendations per job, Statistics dashboard.
- **Recruiter:** View jobs, Select job, Generate recommendations, View ranked candidates + scores, Open candidate details.
- **Candidate:** Register/login, Edit profile (same structure as CSV), Optional PDF CV upload, View stored profile.

## Project layout

```
public/index.php          # Front controller
app/Core/                 # Router, DB, Auth, Middleware, Csrf, Validator
app/Controllers/          # Auth, Admin, Recruiter, Candidate
app/Models/               # User, Candidate, CandidateProfile, Job, Recommendation, Cv, ImportLog, PipelineLog
app/Views/                # Bootstrap templates (layouts + auth, admin, recruiter, candidate)
app/Services/             # PythonRunner, CsvImporter, NormalizationService, RecommendationService
python/                   # import_csv_to_mysql.py, normalize_profiles.py, recommend.py, extract_pdf_text.py, cv_parser.py, cv_ai_extract.py (PDF → text → sections → regex + optional LLM → JSON)
schema.sql                # MySQL schema
docker-compose.yml        # php-apache + mysql
```

## Environment

Copy `env.example` to `.env` and set:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `CSV_DATASET_PATH` (e.g. `/var/www/html/dataset_cvs_5000.csv` in Docker)
- `PYTHON_PATH` (e.g. `python3`)

## Optional: PDF CV upload and parsing pipeline

- Candidate (or Admin) can upload a PDF in **My profile** → Upload CV.
- **Pipeline:**
  1. **Extract text** from PDF (PyMuPDF → pdfplumber → pdfminer → pypdf).
  2. **Detect sections** (FORMATION, EXPÉRIENCE PROFESSIONNELLE, COMPÉTENCES, LANGUES, etc.).
  3. **Regex** for email, phone, LinkedIn, dates.
  4. **Rule-based** parsing for education, experience, skills (keywords).
  5. **Optional AI/NLP** (LLM): job title, summary, education/experience entries, skill extraction — set `CV_LLM_API_URL` (and optionally `CV_LLM_API_KEY`, `CV_LLM_MODEL`) to enable.
  6. **Normalize** into JSON (`structured` + `parsed` for PHP).
- Stored text is used for TF-IDF ranking; parsed fields pre-fill empty profile fields.
- Install: `pip install pymupdf pdfplumber` (see `python/requirements.txt`).
- **AI step (optional, désactivé par défaut):** Sans IA, l’extraction est plus rapide et le résultat repose uniquement sur les règles (sections + regex). Pour activer un LLM (Ollama, OpenAI…) : `CV_AI_ENABLED=1`, `CV_LLM_API_URL=...`, `CV_LLM_MODEL=...`. Un délai de 45 s s’applique ; en cas de dépassement, le pipeline utilise uniquement le parsing par règles.

## CLI (optional)

- **Import CSV:**  
  `python python/import_csv_to_mysql.py --path=/path/to/dataset_cvs_5000.csv`

- **Normalize:**  
  `python python/normalize_profiles.py`

- **Recommendations:**  
  `python python/recommend.py --job_id=123 --top_k=200`

Output is JSON; the app uses it to update the DB (recommendations table) and display results.
