<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RecruteIA — Plateforme Intelligente de Recrutement</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link href="/css/recruteia.css?v=<?= time() ?>" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</head>
<body>

<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $flashClass = isset($flash['type']) ? 'alert-' . $flash['type'] : 'alert-info';
    echo '<div class="alert-recruteia ' . htmlspecialchars($flashClass) . '" style="position:fixed;top:68px;left:0;right:0;z-index:99;margin:0;border-radius:0;text-align:center;">' . htmlspecialchars($flash['message'] ?? '') . '</div>';
}
?>

  <!-- ─── NAV ─── -->
  <nav id="navbar">
  <div class="nav-top">
    <a href="/" class="nav-logo">Recrute <em>IA</em></a>
    <div class="nav-right">
      <?php if (!empty($loggedIn) && !empty($dashboardUrl)) { ?>
        <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn btn-gold"><?= htmlspecialchars($dashboardLabel) ?></a>
        <a href="/logout" class="btn btn-ghost">Déconnexion</a>
      <?php } else { ?>
        <a href="/login" class="btn btn-ghost">Connexion</a>
        <a href="/register" class="btn btn-gold">S'inscrire</a>
      <?php } ?>
    </div>
  </div>
  <div class="nav-links">
    <a href="#features">Fonctionnalités</a>
    <a href="#compare">Pourquoi nous ?</a>
    <a href="#roles">Parcours</a>
  </div>
</nav>

  <!-- ─── HERO ─── -->
  <section id="hero">
    <canvas id="three-canvas"></canvas>
    <div class="hero-glow-1"></div>
    <div class="hero-glow-2"></div>

    <div class="hero-inner">
      <div class="hero-eyebrow">Recrutement augmenté par l'IA</div>

      <h1 class="hero-title">
        Le recrutement<br><em>intelligent</em> commence ici
      </h1>

      <p class="hero-subtitle">
        Analyse de CV, matching sémantique, scoring objectif. RecruteIA connecte les bons profils aux bonnes opportunités — rapidement, équitablement, transparemment.
      </p>

      <div class="hero-cta">
        <a href="<?= !empty($loggedIn) ? htmlspecialchars($dashboardUrl ?? '/register') : '/register?role=candidate' ?>" class="cta-card candidate">
          <div class="cta-card-icon">🎓</div>
          <h3>Je suis candidat</h3>
          <p>Postulez aux offres et suivez vos candidatures en temps réel</p>
          <span class="cta-arrow">↗</span>
        </a>
        <a href="<?= !empty($loggedIn) ? htmlspecialchars($dashboardUrl ?? '/register') : '/register?role=recruiter' ?>" class="cta-card recruiter">
          <div class="cta-card-icon">💼</div>
          <h3>Je suis recruteur</h3>
          <p>Gérez vos offres et trouvez les meilleurs profils par l'IA</p>
          <span class="cta-arrow">↗</span>
        </a>
      </div>
    </div>

    <div class="scroll-hint">
      <div class="scroll-line"></div>
      <span>Découvrir</span>
    </div>
  </section>

  <!-- ─── STATS ─── -->
  <div class="stats-strip">
    <div class="stat-item">
      <div class="stat-num"><em>98</em>%</div>
      <div class="stat-label">Précision du matching IA</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">3×</div>
      <div class="stat-label">Plus rapide qu'un tri manuel</div>
    </div>
    <div class="stat-item">
      <div class="stat-num"><em>+</em>500</div>
      <div class="stat-label">Entreprises utilisatrices</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">24h</div>
      <div class="stat-label">Délai moyen de réponse</div>
    </div>
  </div>

  <!-- ─── FEATURES ─── -->
  <section id="features">
    <div class="section-label">Fonctionnalités</div>
    <h2 class="section-title">Une technologie au<br>service <em>de l'humain</em></h2>
    <p class="section-sub">Nous ne remplaçons pas le recruteur — nous lui donnons des super-pouvoirs. Chaque outil est conçu pour rendre le processus plus juste et plus efficace.</p>

    <div class="features-grid">
      <div class="feature-card" style="transition-delay:0s">
        <div class="feat-icon gold">🧠</div>
        <h4>IA de matching sémantique</h4>
        <p>Notre moteur analyse en profondeur le contenu des CV pour calculer un score de correspondance objectif par rapport à chaque offre d'emploi.</p>
      </div>
      <div class="feature-card" style="transition-delay:0.1s">
        <div class="feat-icon green">📄</div>
        <h4>Génération de CV automatique</h4>
        <p>À partir du profil candidat, RecruteIA génère automatiquement un CV professionnel au format PDF, prêt à l'emploi.</p>
      </div>
      <div class="feature-card" style="transition-delay:0.2s">
        <div class="feat-icon blue">📊</div>
        <h4>Suivi transparent en temps réel</h4>
        <p>Candidats et recruteurs suivent l'avancement des dossiers avec un tableau de bord clair et des notifications instantanées.</p>
      </div>
      <div class="feature-card" style="transition-delay:0.3s">
        <div class="feat-icon gold">⚡</div>
        <h4>Shortlist pré-triée</h4>
        <p>Fini les centaines de CV à éplucher. RecruteIA présente une shortlist ordonnée par pertinence, avec des justifications détaillées.</p>
      </div>
      <div class="feature-card" style="transition-delay:0.4s">
        <div class="feat-icon green">🎯</div>
        <h4>Recommandations candidats</h4>
        <p>Le système identifie les points faibles du profil et suggère des améliorations concrètes pour maximiser les chances d'embauche.</p>
      </div>
      <div class="feature-card" style="transition-delay:0.5s">
        <div class="feat-icon blue">🔒</div>
        <h4>Processus objectif et équitable</h4>
        <p>Critères de sélection standardisés, biais réduits, et traçabilité complète des décisions pour un recrutement plus juste.</p>
      </div>
    </div>
  </section>

  <!-- ─── COMPARE ─── -->
  <div class="compare-section" id="compare">
    <div class="compare-inner">
      <div class="section-label">Pourquoi changer ?</div>
      <h2 class="section-title">Ancienne méthode vs<br><em>RecruteIA</em></h2>
      <p class="section-sub">Chaque minute passée à trier manuellement des CV est une minute de moins consacrée à vos candidats. Il est temps de changer d'approche.</p>

      <div class="compare-grid">
        <div class="compare-card old">
          <span class="compare-badge old">⚠ Méthode traditionnelle</span>
          <h3>Le recrutement tel qu'il était</h3>
          <ul class="compare-list">
            <li><span class="li-icon cross">✕</span>Tri manuel de centaines de CV chronophage</li>
            <li><span class="li-icon cross">✕</span>Critères subjectifs et biais involontaires</li>
            <li><span class="li-icon cross">✕</span>Délais longs pour les candidats sans retour</li>
            <li><span class="li-icon cross">✕</span>Aucune visibilité sur l'avancement du dossier</li>
            <li><span class="li-icon cross">✕</span>Risque de passer à côté des meilleurs profils</li>
            <li><span class="li-icon cross">✕</span>Processus non documenté, difficile à auditer</li>
          </ul>
        </div>

        <div class="compare-card new">
          <span class="compare-badge new">✦ RecruteIA</span>
          <h3>Le recrutement de demain, aujourd'hui</h3>
          <ul class="compare-list">
            <li><span class="li-icon check">✓</span>Analyse automatique des CV par intelligence artificielle</li>
            <li><span class="li-icon check">✓</span>Score de correspondance objectif et standardisé</li>
            <li><span class="li-icon check">✓</span>Shortlist pré-triée prête en quelques secondes</li>
            <li><span class="li-icon check">✓</span>Suivi en temps réel pour chaque candidat</li>
            <li><span class="li-icon check">✓</span>Recommandations personnalisées pour améliorer les profils</li>
            <li><span class="li-icon check">✓</span>Historique complet et traçabilité totale</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── PROCESS ─── -->
  <section id="process">
    <div class="section-label">Comment ça marche ?</div>
    <h2 class="section-title">Un processus <em>en 4 étapes</em></h2>
    <p class="section-sub">De l'inscription à la sélection finale, chaque étape est conçue pour être simple, rapide et transparente pour toutes les parties.</p>

    <div class="process-grid">
      <div class="process-step">
        <div class="step-num">01</div>
        <h4>Inscription & profil</h4>
        <p>Créez votre compte candidat ou recruteur en quelques minutes et complétez votre profil.</p>
      </div>
      <div class="process-step" style="transition-delay:0.1s">
        <div class="step-num">02</div>
        <h4>Analyse IA du CV</h4>
        <p>Notre moteur extrait et normalise automatiquement toutes les informations de votre CV.</p>
      </div>
      <div class="process-step" style="transition-delay:0.2s">
        <div class="step-num">03</div>
        <h4>Matching intelligent</h4>
        <p>Les profils sont scorés et classés automatiquement par rapport à chaque offre disponible.</p>
      </div>
      <div class="process-step" style="transition-delay:0.3s">
        <div class="step-num">04</div>
        <h4>Sélection & retour</h4>
        <p>Le recruteur finalise sa sélection, le candidat reçoit un retour clair et actionnable.</p>
      </div>
    </div>
  </section>

  <!-- ─── ROLES ─── -->
  <div class="roles-section" id="roles">
    <div class="roles-inner">
      <div class="section-label">Parcours utilisateur</div>
      <h2 class="section-title">Une plateforme, <em>trois rôles</em></h2>
      <p class="section-sub">Chaque utilisateur dispose d'un espace adapté à ses besoins avec les outils qui lui correspondent.</p>

      <div class="roles-grid">

        <div class="role-card candidate">
          <div class="role-icon">🎓</div>
          <h3>Candidat</h3>
          <span class="role-tag">Espace personnel</span>
          <p>Postulez aux offres qui correspondent à votre profil et suivez l'avancement de vos candidatures avec une transparence totale.</p>
          <ul class="role-features">
            <li><span class="rf-dot"></span>Création et gestion de profil complet</li>
            <li><span class="rf-dot"></span>Import et analyse automatique du CV</li>
            <li><span class="rf-dot"></span>Candidature en un clic aux offres</li>
            <li><span class="rf-dot"></span>Suivi en temps réel des dossiers</li>
            <li><span class="rf-dot"></span>Recommandations d'amélioration IA</li>
            <li><span class="rf-dot"></span>Génération PDF du CV professionnel</li>
          </ul>
          <a href="<?= !empty($loggedIn) ? htmlspecialchars($dashboardUrl ?? '/register') : '/register?role=candidate' ?>" class="btn btn-green" style="width:100%; justify-content:center;">Créer mon profil candidat →</a>
        </div>

        <div class="role-card recruiter">
          <div class="role-icon">💼</div>
          <h3>Recruteur</h3>
          <span class="role-tag">Espace entreprise</span>
          <p>Publiez vos offres et laissez l'IA trier et classer les candidatures pour vous présenter les meilleurs profils en priorité.</p>
          <ul class="role-features">
            <li><span class="rf-dot"></span>Publication et gestion des offres</li>
            <li><span class="rf-dot"></span>Shortlist triée par score IA</li>
            <li><span class="rf-dot"></span>Vue détaillée des profils candidats</li>
            <li><span class="rf-dot"></span>Pipeline de sélection personnalisable</li>
            <li><span class="rf-dot"></span>Statistiques de recrutement en temps réel</li>
            <li><span class="rf-dot"></span>Export des données et rapports</li>
          </ul>
          <a href="<?= !empty($loggedIn) ? htmlspecialchars($dashboardUrl ?? '/register') : '/register?role=recruiter' ?>" class="btn btn-gold" style="width:100%; justify-content:center;">Accéder à l'espace recruteur →</a>
        </div>

        <div class="role-card admin">
          <div class="role-icon">⚙️</div>
          <h3>Administrateur</h3>
          <span class="role-tag">Supervision globale</span>
          <p>Supervisez l'ensemble de la plateforme, gérez les utilisateurs et accédez aux statistiques globales d'utilisation.</p>
          <ul class="role-features">
            <li><span class="rf-dot"></span>Gestion complète des utilisateurs</li>
            <li><span class="rf-dot"></span>Dashboard de statistiques globales</li>
            <li><span class="rf-dot"></span>Import CSV de candidats en masse</li>
            <li><span class="rf-dot"></span>Configuration du pipeline IA</li>
            <li><span class="rf-dot"></span>Logs d'activité et audit trail</li>
            <li><span class="rf-dot"></span>Export et reporting avancé</li>
          </ul>
          <a href="/login" class="btn btn-ghost" style="width:100%; justify-content:center; border-color:rgba(26,140,92,0.25); color:var(--green)">Connexion admin →</a>
        </div>

      </div>
    </div>
  </div>

  <!-- ─── CTA ─── -->
  <div class="cta-section">
    <div class="cta-bg-glow"></div>
    <div class="cta-inner">
      <div class="section-label">Prêt à commencer ?</div>
      <h2 class="section-title">Transformez votre<br>recrutement <em>dès aujourd'hui</em></h2>
      <p>Rejoignez les entreprises qui font confiance à RecruteIA pour trouver les meilleurs talents, plus vite, avec plus de justesse.</p>
      <div class="cta-buttons">
        <?php if (!empty($loggedIn) && !empty($dashboardUrl)) { ?>
          <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn btn-green" style="padding: 13px 32px; font-size:14px;"><?= htmlspecialchars($dashboardLabel) ?></a>
        <?php } else { ?>
          <a href="/register" class="btn btn-green" style="padding: 13px 32px; font-size:14px;">Créer un compte gratuit</a>
        <?php } ?>
        <a href="/recruteia-demo" class="btn btn-ghost" style="padding: 13px 32px; font-size:14px;">Voir la démo →</a>
      </div>
    </div>
  </div>

  <!-- ─── FOOTER ─── -->
  <footer>
    <a href="/" class="footer-logo">Recrute<em>IA</em></a>
    <div class="footer-links">
      <a href="#features">Fonctionnalités</a>
      <a href="<?= !empty($loggedIn) ? htmlspecialchars($dashboardUrl ?? '/register') : '/register?role=candidate' ?>">Candidats</a>
      <a href="<?= !empty($loggedIn) ? htmlspecialchars($dashboardUrl ?? '/register') : '/register?role=recruiter' ?>">Recruteurs</a>
      <a href="/login">Connexion</a>
      <a href="/register">Inscription</a>
    </div>
    <div class="footer-right">Plateforme intelligente de recrutement &copy; 2025</div>
  </footer>

  <script>
    /* ─── NAV SCROLL ─── */
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    });

    /* ─── THREE.JS BACKGROUND ─── */
    (function() {
      const canvas = document.getElementById('three-canvas');
      if (!canvas || typeof THREE === 'undefined') return;

      const scene = new THREE.Scene();
      const camera = new THREE.PerspectiveCamera(70, window.innerWidth / window.innerHeight, 1, 3000);
      camera.position.z = 1200;

      const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
      renderer.setSize(window.innerWidth, window.innerHeight);
      renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

      const COUNT = 120;
      const geo = new THREE.BufferGeometry();
      const pos = new Float32Array(COUNT * 3);
      const col = new Float32Array(COUNT * 3);

      const gold = new THREE.Color(0x22c07a);
      const green = new THREE.Color(0x1a8c5c);
      const silver = new THREE.Color(0x86efac);

      const particles = [];
      for (let i = 0; i < COUNT; i++) {
        const x = (Math.random() - 0.5) * 2400;
        const y = (Math.random() - 0.5) * 2400;
        const z = (Math.random() - 0.5) * 1200;
        pos[i*3] = x; pos[i*3+1] = y; pos[i*3+2] = z;
        const r = Math.random();
        const c = r < 0.4 ? gold : r < 0.7 ? green : silver;
        col[i*3] = c.r * (0.5 + Math.random()*0.5);
        col[i*3+1] = c.g * (0.5 + Math.random()*0.5);
        col[i*3+2] = c.b * (0.5 + Math.random()*0.5);
        particles.push({ x, y, z,
          vx: (Math.random()-0.5)*0.3,
          vy: (Math.random()-0.5)*0.3,
          vz: (Math.random()-0.5)*0.15
        });
      }

      geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
      geo.setAttribute('color', new THREE.BufferAttribute(col, 3));

      const mat = new THREE.PointsMaterial({
        size: 2.5, vertexColors: true,
        transparent: true, opacity: 0.65,
        blending: THREE.AdditiveBlending, depthWrite: false
      });

      const points = new THREE.Points(geo, mat);
      scene.add(points);

      /* Lines */
      const lineGeo = new THREE.BufferGeometry();
      const linePts = [];
      const MAX_DIST = 320;

      const lineMatMesh = new THREE.LineBasicMaterial({
        color: 0x22c07a, transparent: true, opacity: 0.12,
        blending: THREE.AdditiveBlending
      });
      const lineSegments = new THREE.LineSegments(lineGeo, lineMatMesh);
      scene.add(lineSegments);

      let mouseX = 0, mouseY = 0;
      document.addEventListener('mousemove', e => {
        mouseX = (e.clientX / window.innerWidth - 0.5) * 80;
        mouseY = (e.clientY / window.innerHeight - 0.5) * 80;
      });

      window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
      });

      const clock = new THREE.Clock();
      function animate() {
        requestAnimationFrame(animate);
        const t = clock.getElapsedTime();

        camera.position.x += (mouseX - camera.position.x) * 0.03;
        camera.position.y += (-mouseY - camera.position.y) * 0.03;
        camera.lookAt(scene.position);

        const positions = geo.attributes.position.array;
        for (let i = 0; i < COUNT; i++) {
          const p = particles[i];
          p.x += p.vx;
          p.y += p.vy + Math.sin(t * 0.4 + i) * 0.08;
          p.z += p.vz;
          if (Math.abs(p.x) > 1200) p.vx *= -1;
          if (Math.abs(p.y) > 1200) p.vy *= -1;
          if (Math.abs(p.z) > 600) p.vz *= -1;
          positions[i*3] = p.x;
          positions[i*3+1] = p.y;
          positions[i*3+2] = p.z;
        }
        geo.attributes.position.needsUpdate = true;
        points.rotation.y += 0.0008;
        points.rotation.x += 0.0002;

        /* Update lines every 3 frames */
        if (Math.round(t * 60) % 3 === 0) {
          linePts.length = 0;
          for (let a = 0; a < COUNT; a++) {
            for (let b = a+1; b < COUNT; b++) {
              const dx = particles[a].x - particles[b].x;
              const dy = particles[a].y - particles[b].y;
              const dz = particles[a].z - particles[b].z;
              const d2 = dx*dx + dy*dy + dz*dz;
              if (d2 < MAX_DIST*MAX_DIST) {
                linePts.push(particles[a].x, particles[a].y, particles[a].z);
                linePts.push(particles[b].x, particles[b].y, particles[b].z);
              }
            }
          }
          lineGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(linePts), 3));
        }

        renderer.render(scene, camera);
      }
      animate();
    })();

    /* ─── SCROLL REVEAL ─── */
    const revealEls = document.querySelectorAll('.feature-card, .process-step');
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          obs.unobserve(e.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(el => obs.observe(el));
  </script>
</body>
</html>
