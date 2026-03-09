# Documentation générale — RecruteIA / SmartRecruit

Cette documentation décrit le projet en détail : objectifs, enchaînement des fonctionnalités, structure du code, base de données, installation et toutes les fonctionnalités (y compris les plus petites).

---

## 1. Vue d’ensemble

**RecruteIA** (nom commercial) / **SmartRecruit** (nom technique) est une plateforme de recrutement qui :

- Permet aux **candidats** de s’inscrire, compléter leur profil, télécharger un CV et voir des offres.
- Permet aux **recruteurs** de gérer des offres (postes), de lancer un classement des candidats par IA (TF-IDF + similarité cosinus) et de consulter les résultats.
- Permet aux **administrateurs** de gérer les utilisateurs, d’importer des candidats en masse (CSV), de lancer la normalisation des profils et la génération des recommandations, et de gérer les postes.

**Stack technique :**

| Couche        | Technologie                          |
|---------------|--------------------------------------|
| Front-end     | HTML, CSS (RecruteIA), pas de JS framework |
| Back-end      | PHP 8.x (MVC minimal, Router, Middleware) |
| Base de données | MySQL 8                            |
| IA / Données  | Python 3.11, pandas, scikit-learn   |
| Échange PHP ↔ Python | Appels CLI, échange JSON/CSV |

---

## 2. Enchaînement global (parcours utilisateur)

Voici l’enchaînement de toutes les entrées et actions possibles.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ENTRÉE : http://localhost:8080/                                            │
└─────────────────────────────────────────────────────────────────────────────┘
                                      │
                    ┌─────────────────┴─────────────────┐
                    │                                   │
              [Non connecté]                     [Connecté]
                    │                                   │
                    ▼                                   ▼
┌───────────────────────────┐              Redirection selon le rôle :
│  PAGE D’ACCUEIL (Home)    │              • admin    → /admin/stats
│  • Présentation           │              • recruiter → /recruiter/jobs
│  • Pourquoi RecruteIA ?   │              • candidate → /candidate/profile
│  • Liens : Connexion,     │              (en pratique on affiche toujours
│    S'inscrire, Démo       │               la home ; "Mon espace" mène au dash)
└───────────────────────────┘
        │           │           │
        ▼           ▼           ▼
   Connexion   Inscription   Démo (no auth)
        │           │           │
        ▼           ▼           ▼
   /login     /register    /recruteia-demo
        │           │           │
        │           │           └── Page prototype : switch Candidat / Recruteur / Admin
        │           │               (données fictives, pas de vraie base)
        │           │
        │           └── Création compte candidat → /candidate/profile
        │
        └── Si succès → redirection selon rôle (admin/stats, recruiter/jobs, candidate/profile)

┌─────────────────────────────────────────────────────────────────────────────┐
│  MOT DE PASSE OUBLIÉ (lien sur la page Connexion)                             │
└─────────────────────────────────────────────────────────────────────────────┘
   /forgot-password (saisie email) → génération token → redirection
   /reset-password?token=xxx (saisie nouveau mot de passe) → mise à jour → /login

┌─────────────────────────────────────────────────────────────────────────────┐
│  DÉCONNEXION                                                                 │
└─────────────────────────────────────────────────────────────────────────────┘
   GET ou POST /logout → destruction session → redirection vers / (home) + message flash

═══════════════════════════════════════════════════════════════════════════════
  ESPACE ADMIN
═══════════════════════════════════════════════════════════════════════════════
   /admin/stats          → Tableau de bord (stats, dernier import, normalisation, etc.)
   /admin/users          → Liste des utilisateurs + formulaire création (recruiter/admin)
   /admin/users/{id}     → Édition utilisateur (rôle, statut, etc.)
   /admin/import-csv     → Import CSV candidats (fichier ou dataset par défaut)
   /admin/run-normalization → Lance le pipeline de normalisation (remplit candidate_profiles)
   /admin/jobs           → Liste des postes + création
   /admin/jobs/{id}      → Détail poste, édition, suppression, génération recommandations
   → Génération recommandations : appel Python → remplissage table recommendations

═══════════════════════════════════════════════════════════════════════════════
  ESPACE RECRUTEUR
═══════════════════════════════════════════════════════════════════════════════
   /recruiter/jobs           → Liste des postes
   /recruiter/jobs/{id}      → Détail poste, bouton "Générer recommandations", lien "Voir résultats"
   /recruiter/jobs/{id}/results → Liste des candidats classés (score IA, filtres)
   /recruiter/jobs/{jobId}/candidates/{candidateId} → Fiche candidat (profil, CV, etc.)

═══════════════════════════════════════════════════════════════════════════════
  ESPACE CANDIDAT
═══════════════════════════════════════════════════════════════════════════════
   /candidate/profile    → Formulaire profil (nom, prénom, ville, compétences, etc.)
                          + upload CV (PDF) optionnel
   POST /candidate/profile     → Sauvegarde du profil
   POST /candidate/upload-cv  → Upload du CV (extraction texte pour le ranking)
```

Résumé des « petites » choses intégrées dans cet enchaînement :

- **Page d’accueil** : toujours affichée à `/` ; si connecté, liens « Mon espace » et « Déconnexion » au lieu de « Connexion » / « S'inscrire ».
- **Mot de passe oublié** : lien sur la page de connexion → `/forgot-password` → email → token → `/reset-password?token=...` → nouveau mot de passe.
- **Déconnexion** : GET ou POST `/logout` → redirection vers `/` avec message « Vous avez été déconnecté ».
- **Démo** : `/recruteia-demo` affiche un prototype avec boutons Candidat / Recruteur / Admin (données fictives) ; pas de barre « Vue : » dans l’app réelle (admin/recruteur/candidat), uniquement dans la démo.
- **Barre de rôle** : supprimée du layout principal (admin/recruteur/candidat) ; conservée uniquement sur la page démo.

---

## 3. Structure du projet

```
arabi prj/
├── public/
│   ├── index.php          # Point d’entrée unique (Router, dispatch)
│   ├── .htaccess          # Réécriture vers index.php
│   └── css/
│       └── recruteia.css  # Styles communs (app + démo)
├── app/
│   ├── Core/
│   │   ├── Router.php      # Enregistrement des routes GET/POST, dispatch
│   │   ├── DB.php          # Connexion PDO, chargement .env
│   │   ├── Auth.php        # Session, check(), role(), logout()
│   │   ├── Middleware.php  # guest(), auth(), admin(), recruiter(), candidate()
│   │   ├── Csrf.php        # Token CSRF (champ formulaire, vérification)
│   │   └── Validator.php   # Validation des champs (required, email, etc.)
│   ├── Controllers/
│   │   ├── AuthController.php    # login, register, forgot/reset password, logout
│   │   ├── HomeController.php    # page d’accueil (/)
│   │   ├── DemoController.php    # page démo (/recruteia-demo)
│   │   ├── AdminController.php   # stats, users, import-csv, jobs, normalization, recommend
│   │   ├── RecruiterController.php # jobs, showJob, results, candidateDetail
│   │   └── CandidateController.php  # profile, upload-cv
│   ├── Models/
│   │   ├── User.php
│   │   ├── Candidate.php
│   │   ├── CandidateProfile.php
│   │   ├── Job.php
│   │   ├── Recommendation.php
│   │   ├── Cv.php
│   │   ├── ImportLog.php
│   │   ├── PipelineLog.php
│   │   └── PasswordReset.php
│   ├── Services/
│   │   ├── CsvImporter.php         # Import CSV → candidates
│   │   ├── NormalizationService.php # Appel Python normalize_profiles.py
│   │   ├── RecommendationService.php # Appel Python recommend.py
│   │   └── PythonRunner.php        # Exécution scripts Python, lecture JSON
│   └── Views/
│       ├── layouts/
│       │   └── recruteia.php       # Layout commun (navbar, hero, flash)
│       ├── auth/
│       │   ├── login.php
│       │   ├── register.php
│       │   ├── forgot_password.php
│       │   └── reset_password.php
│       ├── home.php
│       ├── recruteia_demo_static.html  # Contenu HTML démo (injecté par DemoController)
│       ├── admin/                    # stats, users, user_edit, import_csv, jobs, job_edit
│       ├── recruiter/                # jobs, job_show, results, candidate_detail
│       └── candidate/
│           └── profile.php
├── python/
│   ├── config.py            # Lecture config DB / chemins
│   ├── import_csv_to_mysql.py
│   ├── normalize_profiles.py
│   ├── recommend.py
│   ├── extract_pdf_text.py
│   └── requirements.txt
├── scripts/
│   ├── seed_admin.php                      # Création utilisateur admin par défaut
│   ├── add_password_reset_table.sql        # Création table password_reset_tokens
│   └── add_import_and_pipeline_tables.sql  # Création import_logs, pipeline_logs
├── schema.sql                 # Schéma MySQL complet (toutes les tables)
├── docker-compose.yml         # Services php (port 8080), mysql (port 3307→3306)
├── Dockerfile
├── env.example
├── README.md
└── DOCUMENTATION.md           # Ce fichier
```

---

## 4. Routes (référence complète)

| Méthode | Route | Contrôleur / action | Middleware | Description |
|--------|------|---------------------|------------|-------------|
| GET | `/` | HomeController::index | — | Page d’accueil (ou redirection si connecté selon rôle) |
| GET | `/login` | AuthController::showLogin | guest | Formulaire connexion |
| POST | `/login` | AuthController::login | guest | Traitement connexion |
| GET | `/register` | AuthController::showRegister | guest | Formulaire inscription candidat |
| POST | `/register` | AuthController::register | guest | Création compte candidat |
| GET | `/forgot-password` | AuthController::showForgotPassword | guest | Formulaire « mot de passe oublié » |
| POST | `/forgot-password` | AuthController::forgotPassword | guest | Envoi token (redirection vers reset) |
| GET | `/reset-password` | AuthController::showResetPassword | guest | Formulaire nouveau mot de passe (avec token) |
| POST | `/reset-password` | AuthController::resetPassword | guest | Mise à jour du mot de passe |
| GET | `/logout` | AuthController::logout | auth | Déconnexion |
| POST | `/logout` | AuthController::logout | auth | Déconnexion |
| GET | `/recruteia-demo` | DemoController::showDemo | — | Page démo (Candidat/Recruteur/Admin) |
| GET | `/admin/stats` | AdminController::stats | auth, admin | Tableau de bord admin |
| GET | `/admin/users` | AdminController::users | auth, admin | Liste utilisateurs |
| POST | `/admin/users` | AdminController::createUser | auth, admin | Création utilisateur |
| GET | `/admin/users/{id}` | AdminController::editUser | auth, admin | Formulaire édition utilisateur |
| POST | `/admin/users/{id}` | AdminController::editUser | auth, admin | Sauvegarde édition |
| GET | `/admin/import-csv` | AdminController::showImportCsv | auth, admin | Page import CSV |
| POST | `/admin/import-csv` | AdminController::importCsv | auth, admin | Traitement import |
| POST | `/admin/run-normalization` | AdminController::runNormalization | auth, admin | Lance normalisation |
| GET | `/admin/jobs` | AdminController::jobs | auth, admin | Liste postes |
| POST | `/admin/jobs` | AdminController::createJob | auth, admin | Création poste |
| GET | `/admin/jobs/{id}` | AdminController::editJob | auth, admin | Formulaire édition poste |
| POST | `/admin/jobs/{id}` | AdminController::editJob | auth, admin | Sauvegarde poste |
| POST | `/admin/jobs/{id}/delete` | AdminController::deleteJob | auth, admin | Suppression poste |
| POST | `/admin/jobs/{id}/recommend` | AdminController::recommendJob | auth, admin | Génération recommandations IA |
| GET | `/recruiter/jobs` | RecruiterController::jobs | auth, recruiter | Liste postes |
| GET | `/recruiter/jobs/create` | RecruiterController::showCreateJob | auth, recruiter | Formulaire publier une offre |
| POST | `/recruiter/jobs` | RecruiterController::createJob | auth, recruiter | Création poste (publier offre) |
| GET | `/recruiter/jobs/{id}` | RecruiterController::showJob | auth, recruiter | Détail poste |
| GET | `/recruiter/jobs/{id}/edit` | RecruiterController::showEditJob | auth, recruiter | Formulaire modifier poste |
| POST | `/recruiter/jobs/{id}/edit` | RecruiterController::editJob | auth, recruiter | Mise à jour poste |
| GET | `/recruiter/jobs/{id}/applications` | RecruiterController::jobApplications | auth, recruiter | Candidatures reçues pour ce poste |
| POST | `/recruiter/jobs/{jobId}/applications/{appId}/status` | RecruiterController::updateApplicationStatus | auth, recruiter | Modifier statut candidature |
| POST | `/recruiter/jobs/{id}/recommend` | RecruiterController::recommend | auth, recruiter | Génération recommandations IA |
| GET | `/recruiter/jobs/{id}/results` | RecruiterController::results | auth, recruiter | Classement candidats (scores) |
| GET | `/recruiter/jobs/{jobId}/candidates/{candidateId}` | RecruiterController::candidateDetail | auth, recruiter | Fiche candidat détaillée |
| GET | `/candidate/profile` | CandidateController::profile | auth, candidate | Formulaire profil |
| POST | `/candidate/profile` | CandidateController::profile | auth, candidate | Sauvegarde profil |
| POST | `/candidate/upload-cv` | CandidateController::uploadCv | auth, candidate | Upload CV PDF |
| GET | `/candidate/jobs` | CandidateController::jobs | auth, candidate | Liste des offres disponibles |
| GET | `/candidate/jobs/{id}/apply` | CandidateController::showApply | auth, candidate | Formulaire de candidature (postuler) |
| POST | `/candidate/jobs/{id}/apply` | CandidateController::submitApply | auth, candidate | Envoi de la candidature |
| GET | `/candidate/applications` | CandidateController::applications | auth, candidate | Mes candidatures (suivi) |
| GET | `/candidate/profile/generate-cv` | CandidateController::generateCvPdf | auth, candidate | Page CV pour impression / PDF |
| GET | `/candidate/profile/download-cv-pdf` | CandidateController::downloadCvPdf | auth, candidate | Téléchargement CV PDF standardisé (Dompdf) |
| GET | `/candidate/profile/download-cv-word` | CandidateController::downloadCvWord | auth, candidate | Téléchargement CV Word |
| GET | `/candidate/results` | CandidateController::results | auth, candidate | Scores IA par poste (recommandations où le candidat apparaît) |

---

## 5. Base de données (tables et rôles)

| Table | Rôle |
|-------|------|
| **users** | Comptes (admin, recruiter, candidate). Champs : name, email, password_hash, role, status. |
| **password_reset_tokens** | Tokens « mot de passe oublié » (email, token, expires_at). Valides 2 h. |
| **candidates** | Profils candidats (nom, prénom, email, ville, compétences brutes, expérience, etc.). Lié à `user_id` si inscrit via le site. |
| **candidate_profiles** | Profils normalisés pour l’IA (skills_norm, languages_norm, education_norm, experience_norm en JSON). Rempli par le pipeline de normalisation. |
| **cvs** | Fichiers CV uploadés (candidate_id, file_path, extracted_text). |
| **jobs** | Postes (title, department, description, requirements, skills_raw, type_contrat, created_by). Compétences et type de contrat alimentent le matching IA. |
| **applications** | Candidatures : lien candidat ↔ offre (job_id, candidate_id, status : submitted, viewed, shortlisted, rejected, cover_letter). |
| **recommendations** | Résultats du ranking (job_id, candidate_id, score, rank). Une ligne par (job, candidat). |
| **import_logs** | Historique des imports CSV (file_path, rows_processed, rows_inserted, status, started_at, completed_at). |
| **pipeline_logs** | Historique des runs normalisation / recommandation (type, job_id, rows_affected, status). |

Si la base a été créée avant l’ajout de certaines tables :

- **password_reset_tokens** : exécuter `scripts/add_password_reset_table.sql`.
- **import_logs** et **pipeline_logs** : exécuter `scripts/add_import_and_pipeline_tables.sql`.
- **applications** : exécuter `scripts/add_applications_table.sql`.
- **jobs** (colonnes compétences / type de contrat) : exécuter `scripts/add_jobs_skills_type_contrat.sql` si la table jobs a été créée sans `skills_raw` et `type_contrat`.

---

## 5.1 Pipeline IA — Recommandation (TF-IDF + similarité cosinus)

Le classement des candidats par offre est assuré par un script Python (`python/recommend.py`) appelé par PHP via CLI ; l’échange se fait en **JSON** sur stdout.

- **Document offre** : texte concaténé à partir de `title`, `department`, `description`, `requirements`, **`skills_raw`** (compétences recherchées) et **`type_contrat`** (CDI, CDD, Stage, etc.), pour un matching plus précis.
- **Document candidat** : profil (poste actuel, expérience, compétences brutes et normalisées, formation, langues) + texte extrait du CV si présent.
- **TF-IDF + similarité cosinus** (scikit-learn) : vectorisation des documents, puis scores de similarité entre l’offre et chaque candidat.
- **Stack Python** : **pandas** (structuration des documents), **scikit-learn** (TfidfVectorizer, cosine_similarity). Stop words **français** pour de meilleurs scores sur contenus FR.
- **Sortie** : JSON `{ "job_id", "recommendations": [{ "candidate_id", "score", "rank", ... }], "status" }` ; PHP persiste dans la table `recommendations`.

### 5.2 Normalisation des profils

Le script `python/normalize_profiles.py` prépare les données pour le matching IA :

- Lit tous les enregistrements de la table **candidates**.
- Produit pour chaque candidat des champs normalisés : **skills_norm**, **languages_norm**, **languages_level_norm**, **education_norm**, **experience_norm** (listes de tokens ou chaînes en JSON).
- Upsert dans **candidate_profiles** (une ligne par candidat). Ces champs sont utilisés par `recommend.py` pour construire le document candidat.
- Lancé depuis l’admin (Tableau de bord ou CV & Données) ou en CLI ; sortie JSON pour PHP.

### 5.3 Import CSV (format et upload)

- **Colonnes attendues** (noms exacts) : id, nom, prenom, email, telephone, age, ville, experience_annees, poste_actuel, entreprise_actuelle, education_niveau, diplome, universite, annee_diplome, competences_techniques, competences_langues, langues_niveau, experience_detail, projets, certifications, disponibilite, pretention_salaire.
- **Mapping** : les colonnes `competences_techniques`, `experience_detail`, etc. sont enregistrées dans `competences_techniques_raw`, `experience_detail_raw`, etc.
- **Import** : Admin → CV & Données (Import CSV). Soit utiliser le fichier par défaut (chemin configuré ou `dataset_cvs_5000.csv` à la racine), soit **envoyer un fichier CSV** via le formulaire (upload). Upsert par email.

---

## 6. Installation et premier lancement (étape par étape)

1. **Cloner / ouvrir le projet**, puis à la racine :
   ```bash
   docker compose up -d
   ```
   - App : **http://localhost:8080**
   - MySQL : port **3307** (mappé sur 3306 dans le conteneur).

2. **Dépendances PHP** (sur l’hôte ou dans le conteneur) :
   ```bash
   composer install
   # ou : docker compose exec php composer install --no-interaction
   ```

3. **Créer l’admin par défaut** (depuis la racine, PHP capable de joindre la DB) :
   ```bash
   php scripts/seed_admin.php
   ```
   Ou depuis le conteneur PHP :
   ```bash
   docker compose exec php php /var/www/html/scripts/seed_admin.php
   ```
   Identifiants : **admin@smartrecruit.local** / **Admin123!**

4. **Tables manquantes éventuelles** (si erreur « Table … doesn’t exist ») :
   ```bash
   Get-Content scripts\add_import_and_pipeline_tables.sql | docker exec -i smartrecruit-mysql mysql -u root -proot_secret smartrecruit
   ```
   (Mot de passe MySQL root défini dans `docker-compose.yml` : `root_secret`.)

5. **Optionnel — Import CSV** : Admin → CV & Données. Placer `dataset_cvs_5000.csv` à la racine (ou configurer `CSV_DATASET_PATH`). Puis Admin → Import CSV → lancer l’import.

6. **Optionnel — Normalisation** : Admin → Tableau de bord → Lancer le pipeline de normalisation (remplit `candidate_profiles`).

7. **Optionnel — Poste et recommandations** : Admin ou Recruteur → Postes → créer un poste → Générer recommandations → Voir résultats.

---

## 7. Fonctionnalités détaillées par rôle

### 7.1 Visiteur (non connecté)

- **/** : Page d’accueil (présentation, « Pourquoi RecruteIA », liens Connexion, S'inscrire, Démo).
- **/login** : Connexion (email, mot de passe). Lien « Mot de passe oublié » vers `/forgot-password`.
- **/register** : Inscription candidat (nom, email, mot de passe). Après succès → `/candidate/profile`.
- **/forgot-password** : Saisie email → création token → redirection vers `/reset-password?token=...`.
- **/reset-password** : Saisie nouveau mot de passe (+ confirmation). Token requis. Après succès → `/login`.
- **/recruteia-demo** : Démo statique avec switch Candidat / Recruteur / Admin (données fictives).

### 7.2 Admin

- **Tableau de bord** : statistiques (candidats, postes, utilisateurs), dernier import, dernière normalisation, dernière recommandation, répartition par ville, top compétences.
- **Utilisateurs** : liste, création (recruteur ou admin), édition (rôle, statut).
- **Import CSV** : upload fichier ou import du dataset par défaut ; enregistrement dans `candidates` et trace dans `import_logs`.
- **Pipeline de normalisation** : lance le script Python qui remplit `candidate_profiles`.
- **Postes** : CRUD ; pour chaque poste, bouton « Générer recommandations » (appel Python, remplissage `recommendations`).

### 7.3 Recruteur (interface)

L’espace recruteur (nav : Recommandations IA, Postes, Tous les candidats, Statistiques) permet de :

- **Recommandations IA** (`/recruiter/recommendations`) : vue d’ensemble des postes avec nombre de candidatures et dernier run de classement ; accès rapide au classement par poste.
- **Postes** (`/recruiter/jobs`) : liste des offres avec **barre de recherche** (intitulé), filtres par compétences et type de contrat, actions (Ouvrir, Candidatures, IA, Dupliquer). Création via « + Nouveau poste » ou « Publier une offre ».
- **Détail poste** (`/recruiter/jobs/{id}`) : description, compétences, type de contrat ; bouton **Lancer l’analyse** (TF-IDF) ; lien **Voir les résultats** vers le classement.
- **Résultats** (`/recruiter/jobs/{id}/results`) : classement des candidats par score IA, filtres (ville, score min, exp. min/max), pagination, lien Profil par candidat.
- **Candidatures** (`/recruiter/jobs/{id}/applications`) : liste des candidatures reçues, statut (Envoyée, Consultée, Shortlist, Refusée), mise à jour du statut.
- **Tous les candidats** : liste de la base candidats avec filtres. **Statistiques** : vue synthétique.

- **Ajouter et gérer les descriptions de postes** : « Publier une offre » (`/recruiter/jobs/create`) et « Modifier » depuis un poste (`/recruiter/jobs/{id}/edit`) — titre, département, description, exigences, compétences, type de contrat.
- **Publier des offres** : création d’un poste = offre publiée (visible aux candidats).
- **Consulter les candidatures reçues** : par poste, « Candidatures reçues (X) » ou onglet Candidatures → `/recruiter/jobs/{id}/applications` — liste des candidatures avec lettre de motivation, date, statut.
- **Sélectionner un poste** : liste des postes → clic sur un poste pour ouvrir le détail.
- **Lancer la génération des recommandations** : sur la fiche poste, bouton « Lancer l’analyse » (TF-IDF + similarité cosinus).
- **Visualiser le classement des candidats** : « Classement IA » ou « Voir les résultats » → `/recruiter/jobs/{id}/results` — tableau rang, candidat, ville, poste actuel, score IA, lien Profil.
- **Consulter les scores de correspondance** : dans le classement (score %) et dans la fiche candidat (score IA).
- **Accéder aux profils détaillés** : bouton « Profil » sur chaque candidat (classement ou liste candidatures) → `/recruiter/jobs/{jobId}/candidates/{candidateId}` — profil, compétences, CV, score.
- **Modifier le statut des candidatures** : dans la liste des candidatures (menu déroulant par ligne) ou sur la fiche candidat (menu Statut) — Envoyée, Consultée, Shortlist, Refusée.

### 7.4 Candidat

- **Créer un compte** : inscription (register) → compte candidat.
- **Consulter les offres** : `/candidate/jobs` — liste des offres avec bouton « Postuler » (ou « Déjà postulé »).
- **Postuler à une offre** : formulaire structuré (lettre de motivation optionnelle) ; envoi crée une ligne dans `applications` (statut `submitted`). Un candidat ne peut postuler qu’une fois par offre.
- **Générer un CV PDF** : depuis le profil, lien « Générer mon CV PDF » → page d’impression (données du profil) ; l’utilisateur utilise « Enregistrer au format PDF » dans la fenêtre d’impression du navigateur.
- **Suivre l’état des candidatures** : `/candidate/applications` — liste des candidatures avec statut (Envoyée, Consultée, Shortlist, Refusée).
- **Profil** : formulaire (nom, prénom, email, téléphone, ville, expérience, formation, compétences, etc.) ; sauvegarde en POST.
- **Upload CV** : envoi d’un PDF ; extraction du texte **directe** (PyMuPDF / pdfplumber puis pdfminer/pypdf en fallback), découpage par sections (EXPERIENCE PROFESSIONNELLE, FORMATION, COMPÉTENCES, LANGUES, LOISIRS), regex pour email/téléphone/LinkedIn, et mapping vers un schéma JSON structuré (personal_info, experience, education, skills, languages, hobbies). Le texte est stocké et utilisé pour le ranking TF-IDF ; les champs parsés pré-remplissent le profil candidat. Installer : `pip install pymupdf pdfplumber` (voir `python/requirements.txt`).

---

## 8. Variables d’environnement

Fichier **env.example** (à copier en `.env` si besoin) :

- **APP_ENV**, **APP_DEBUG**, **APP_URL** : environnement et URL de l’app.
- **DB_HOST**, **DB_PORT**, **DB_NAME**, **DB_USER**, **DB_PASS** : connexion MySQL.
- **CSV_DATASET_PATH** : chemin du CSV d’import (ex. `/var/www/html/dataset_cvs_5000.csv` dans Docker).
- **PYTHON_PATH** : commande Python (ex. `python3`).

Dans **docker-compose.yml**, la partie PHP définit déjà `DB_*` et `CSV_DATASET_PATH` ; MySQL utilise `MYSQL_ROOT_PASSWORD: root_secret`, `MYSQL_DATABASE: smartrecruit`, `MYSQL_USER` / `MYSQL_PASSWORD` pour l’utilisateur applicatif.

---

## 9. Récapitulatif des « petites » choses

- **Home** : première page à `/` ; pas de redirection automatique vers login si non connecté ; si connecté, liens « Mon espace » et « Déconnexion ».
- **Mot de passe oublié** : lien sur la page de connexion → `/forgot-password` → `/reset-password?token=...` ; table `password_reset_tokens` ; tokens valides 2 h.
- **Déconnexion** : GET ou POST `/logout` ; redirection vers `/` avec message flash.
- **Démo** : `/recruteia-demo` ; barre de switch Candidat / Recruteur / Admin ; pas d’auth ; données en dur dans le HTML.
- **Barre « Vue : »** : absente du layout principal (admin/recruteur/candidat) ; présente uniquement sur la démo.
- **Scripts SQL** : `add_password_reset_table.sql` et `add_import_and_pipeline_tables.sql` pour bases créées avant l’ajout de ces tables.
- **Compte admin par défaut** : créé par `scripts/seed_admin.php` (admin@smartrecruit.local / Admin123!).

---

Ce document sert de référence unique pour comprendre l’enchaînement de tout le projet et retrouver chaque détail (routes, tables, rôles, installation, petites fonctionnalités).
