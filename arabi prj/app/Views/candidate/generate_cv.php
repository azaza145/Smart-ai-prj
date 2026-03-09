<?php
$d = $cvData ?? [];
$name = $d['fullName'] ?? 'Mon CV';
$initials = $d['initials'] ?? 'CV';
$role = $d['role'] ?? 'Candidat';
$email = $d['email'] ?? '';
$phone = $d['telephone'] ?? '';
$ville = $d['ville'] ?? '';
$expYears = (int)($d['experienceYears'] ?? 0);
$pct = (int)($d['profileComplete'] ?? 0);
$skills = $d['skills'] ?? [];
$langs = $d['languages'] ?? [];
$experience = $d['experience'] ?? [];
$education = $d['education'] ?? [];
$projects = $d['projects'] ?? [];
$certs = $d['certifications'] ?? [];
$about = $d['about'] ?? '';
$cvJson = json_encode($cvData ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>RecruteIA — Générer mon CV</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --bg:#f7f5f0;--white:#fff;--border:#e8e4dc;--border2:#d8d3c8;
  --text:#1c1a17;--muted:#7a7569;--muted2:#b0ab9e;
  --ca:#1a6b4a;--ca-l:#e8f5ee;--ca-d:#0f3d2a;
  --am:#c9610a;--am-l:#fef3ea;
  --shadow:0 2px 12px rgba(28,26,23,.07);--shadow-md:0 8px 32px rgba(28,26,23,.12);
  --font-h:'Fraunces',serif;--font-b:'Plus Jakarta Sans',sans-serif;
}
body{background:var(--bg);color:var(--text);font-family:var(--font-b);font-size:14px;line-height:1.6;min-height:100vh;}

.action-bar{background:var(--white);border-bottom:1px solid var(--border);padding:12px 40px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:var(--shadow);animation:slideDown .35s ease both;}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.back-link{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:var(--muted);text-decoration:none;cursor:pointer;transition:color .15s;border:none;background:none;font-family:var(--font-b);}
.back-link:hover{color:var(--ca);}
.back-arrow{width:28px;height:28px;border-radius:50%;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:13px;transition:all .15s;}
.back-link:hover .back-arrow{border-color:var(--ca);background:var(--ca-l);}
.action-bar-center{display:flex;align-items:center;gap:10px;}
.action-bar-right{display:flex;align-items:center;gap:10px;}
.tpl-pill{padding:6px 16px;border-radius:20px;border:1.5px solid var(--border);font-size:12px;font-weight:600;cursor:pointer;font-family:var(--font-b);transition:all .18s;background:transparent;color:var(--muted);}
.tpl-pill:hover{border-color:var(--ca);color:var(--ca);background:var(--ca-l);}
.tpl-pill.active{border-color:var(--ca);color:var(--ca);background:var(--ca-l);}
.btn-dl{display:inline-flex;align-items:center;gap:8px;background:var(--ca);color:#fff;padding:9px 22px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:var(--font-b);transition:all .2s;box-shadow:0 2px 8px rgba(26,107,74,.2);}
.btn-dl:hover{background:var(--ca-d);transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,107,74,.3);}
.btn-ghost{display:inline-flex;align-items:center;gap:7px;background:transparent;color:var(--muted);border:1.5px solid var(--border);padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font-b);transition:all .18s;}
.btn-ghost:hover{background:var(--bg);color:var(--text);}
.hint-bar{background:var(--am-l);border-bottom:1px solid rgba(201,97,10,.15);padding:8px 40px;font-size:12px;color:#7a4a1e;display:flex;align-items:center;gap:8px;}
.page-wrap{display:grid;grid-template-columns:260px 1fr;gap:0;min-height:calc(100vh - 88px);}
.left-panel{background:var(--white);border-right:1px solid var(--border);padding:24px 20px;overflow-y:auto;position:sticky;top:88px;height:calc(100vh - 88px);}
.panel-section{margin-bottom:24px;}
.panel-section-title{font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted2);margin-bottom:10px;}
.mini-profile{background:var(--ca-l);border:1.5px solid rgba(26,107,74,.15);border-radius:12px;padding:16px;margin-bottom:20px;}
.mini-av{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--ca),#2d9b6a);display:flex;align-items:center;justify-content:center;font-family:var(--font-h);font-size:18px;font-weight:700;color:#fff;margin-bottom:10px;}
.mini-name{font-family:var(--font-h);font-size:15px;font-weight:700;line-height:1.2;}
.mini-role{font-size:11px;color:var(--muted);margin-top:2px;}
.complete-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;font-size:11px;}
.complete-bar{height:4px;background:var(--border);border-radius:4px;overflow:hidden;margin-bottom:12px;}
.complete-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--ca),#52d490);transition:width .6s ease;}
.section-toggle{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:8px;cursor:pointer;transition:background .12s;margin-bottom:4px;}
.section-toggle:hover{background:var(--bg);}
.section-toggle-left{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:500;}
.section-icon{width:26px;height:26px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;}
.toggle-switch{position:relative;width:34px;height:18px;flex-shrink:0;}
.toggle-switch input{opacity:0;width:0;height:0;}
.ts-slider{position:absolute;cursor:pointer;inset:0;background:var(--border2);border-radius:18px;transition:.25s;}
.ts-slider:before{position:absolute;content:"";height:12px;width:12px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.25s;}
input:checked+.ts-slider{background:var(--ca);}
input:checked+.ts-slider:before{transform:translateX(16px);}
.color-picker-row{display:flex;gap:8px;flex-wrap:wrap;}
.color-dot{width:26px;height:26px;border-radius:50%;cursor:pointer;border:2.5px solid transparent;transition:transform .15s,border-color .15s;}
.color-dot:hover{transform:scale(1.15);}
.color-dot.active{border-color:var(--text);}
.font-opt{padding:7px 12px;border-radius:7px;border:1.5px solid var(--border);font-size:12px;cursor:pointer;transition:all .15s;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;}
.font-opt:hover{border-color:var(--ca);}
.font-opt.active{border-color:var(--ca);background:var(--ca-l);color:var(--ca);}
.cv-area{padding:36px 48px;display:flex;flex-direction:column;align-items:center;background:var(--bg);}
.cv-page{width:794px;min-height:1123px;background:#fff;box-shadow:var(--shadow-md),0 0 0 1px rgba(0,0,0,.04);border-radius:4px;overflow:hidden;animation:fadeUp .4s ease both;position:relative;font-family:var(--font-b);}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.cv-page[data-tpl="1"] .cv-sidebar{position:absolute;top:0;left:0;bottom:0;width:240px;background:var(--cv-accent,#1a6b4a);padding:36px 24px;color:#fff;}
.cv-page[data-tpl="1"] .cv-main{margin-left:240px;padding:36px 32px;}
.cv-sb-avatar{width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-family:var(--font-h);font-size:28px;font-weight:700;color:#fff;margin:0 auto 16px;border:3px solid rgba(255,255,255,.4);}
.cv-sb-name{font-family:var(--font-h);font-size:20px;font-weight:700;text-align:center;line-height:1.2;margin-bottom:4px;}
.cv-sb-role{font-size:11px;text-align:center;opacity:.8;margin-bottom:20px;letter-spacing:.5px;}
.cv-sb-divider{height:1px;background:rgba(255,255,255,.2);margin:16px 0;}
.cv-sb-section{margin-bottom:18px;}
.cv-sb-section-title{font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;opacity:.6;margin-bottom:8px;}
.cv-sb-contact-item{display:flex;align-items:flex-start;gap:8px;font-size:11px;opacity:.9;margin-bottom:7px;line-height:1.4;}
.cv-sb-contact-icon{width:16px;flex-shrink:0;opacity:.7;margin-top:1px;}
.cv-sb-skill-row{display:flex;align-items:center;gap:8px;margin-bottom:7px;}
.cv-sb-skill-name{font-size:11px;flex:1;opacity:.9;}
.cv-sb-skill-bar{height:3px;background:rgba(255,255,255,.2);border-radius:2px;overflow:hidden;flex:2;}
.cv-sb-skill-fill{height:100%;border-radius:2px;background:rgba(255,255,255,.8);}
.cv-sb-lang-item{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;font-size:11px;}
.cv-sb-lang-dots{display:flex;gap:3px;}
.cv-sb-lang-dot{width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,.25);}
.cv-sb-lang-dot.filled{background:rgba(255,255,255,.9);}
.cv-name-main{font-family:var(--font-h);font-size:28px;font-weight:800;letter-spacing:-.5px;color:var(--text);line-height:1.1;margin-bottom:4px;}
.cv-role-main{font-size:13px;color:var(--cv-accent,#1a6b4a);font-weight:600;letter-spacing:.3px;margin-bottom:20px;}
.cv-section{margin-bottom:22px;}
.cv-section-title{font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:var(--cv-accent,#1a6b4a);padding-bottom:7px;border-bottom:2px solid var(--cv-accent,#1a6b4a);margin-bottom:14px;}
.cv-item{margin-bottom:14px;padding-left:12px;border-left:2px solid var(--border);}
.cv-item-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:2px;}
.cv-item-title{font-weight:700;font-size:13px;color:var(--text);}
.cv-item-date{font-size:11px;color:var(--muted);white-space:nowrap;margin-left:10px;flex-shrink:0;}
.cv-item-company{font-size:12px;color:var(--cv-accent,#1a6b4a);font-weight:600;margin-bottom:4px;}
.cv-item-desc{font-size:12px;color:var(--muted);line-height:1.55;}
.cv-chips{display:flex;flex-wrap:wrap;gap:6px;}
.cv-chip{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;background:var(--ca-l);color:var(--cv-accent,#1a6b4a);border:1px solid rgba(26,107,74,.15);}
.cv-page[data-tpl="2"] .cv-header{padding:32px 40px;display:flex;align-items:center;gap:24px;}
.cv-page[data-tpl="2"] .cv-header-avatar{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-family:var(--font-h);font-size:24px;font-weight:700;color:#fff;border:3px solid rgba(255,255,255,.4);flex-shrink:0;}
.cv-page[data-tpl="2"] .cv-header-info{color:#fff;flex:1;}
.cv-page[data-tpl="2"] .cv-header-name{font-family:var(--font-h);font-size:26px;font-weight:800;line-height:1.1;margin-bottom:3px;}
.cv-page[data-tpl="2"] .cv-header-role{font-size:13px;opacity:.8;font-weight:500;}
.cv-page[data-tpl="2"] .cv-header-contacts{display:flex;gap:16px;margin-top:10px;flex-wrap:wrap;}
.cv-page[data-tpl="2"] .cv-header-contact{font-size:11px;opacity:.85;}
.cv-page[data-tpl="2"] .cv-body{display:grid;grid-template-columns:1fr 200px;gap:0;}
.cv-page[data-tpl="2"] .cv-body-main{padding:28px 32px;border-right:1px solid var(--border);}
.cv-page[data-tpl="2"] .cv-body-side{padding:28px 20px;}
.cv-page[data-tpl="3"] .cv-min-header{padding:40px 48px 24px;border-bottom:3px solid var(--cv-accent,#1a6b4a);}
.cv-page[data-tpl="3"] .cv-min-name{font-family:var(--font-h);font-size:36px;font-weight:800;letter-spacing:-1px;line-height:1;}
.cv-page[data-tpl="3"] .cv-min-role{font-size:14px;font-weight:600;margin-top:4px;margin-bottom:12px;}
.cv-page[data-tpl="3"] .cv-min-contacts{display:flex;gap:20px;font-size:11px;color:var(--muted);flex-wrap:wrap;}
.cv-page[data-tpl="3"] .cv-min-body{padding:24px 48px;display:grid;grid-template-columns:1fr 180px;gap:32px;}
.cv-page[data-tpl="3"] .cv-min-section-title{font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;margin-bottom:12px;}
.cv-page[data-tpl="3"] .cv-min-section-title::after{content:'';flex:1;height:1px;background:var(--border);}
@media print{.action-bar,.hint-bar,.left-panel{display:none!important;}.cv-area{padding:0!important;background:#fff!important;}.page-wrap{grid-template-columns:1fr!important;}.cv-page{box-shadow:none!important;border-radius:0!important;width:100%!important;}}
::-webkit-scrollbar{width:5px;}::-webkit-scrollbar-track{background:transparent;}::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px;}
.toast{position:fixed;bottom:24px;right:24px;background:var(--text);color:#fff;padding:11px 18px;border-radius:10px;font-size:13px;font-weight:500;display:flex;align-items:center;gap:9px;box-shadow:0 8px 24px rgba(0,0,0,.2);z-index:999;transform:translateY(20px);opacity:0;transition:all .3s ease;pointer-events:none;}
.toast.show{transform:translateY(0);opacity:1;}
</style>
</head>
<body>

<div class="action-bar">
  <a href="/candidate/profile" class="back-link">
    <div class="back-arrow">←</div>
    Retour au profil
  </a>
  <div class="action-bar-center">
    <span style="font-size:11px;color:var(--muted);font-weight:600;margin-right:4px;">Modèle :</span>
    <button class="tpl-pill active" type="button" onclick="setTemplate(1,this)">Classique</button>
    <button class="tpl-pill" type="button" onclick="setTemplate(2,this)">Moderne</button>
    <button class="tpl-pill" type="button" onclick="setTemplate(3,this)">Minimaliste</button>
  </div>
  <div class="action-bar-right">
    <button class="btn-ghost" type="button" onclick="showToast('🔗','Lien de partage copié !')">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
      Partager
    </button>
    <button type="button" class="btn-dl" onclick="downloadPdfFromPreview()" style="cursor:pointer;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Télécharger en PDF
    </button>
    <a href="/candidate/profile/download-cv-word" class="btn-ghost" style="text-decoration:none;" target="_blank" rel="noopener" download>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Télécharger en Word
    </a>
  </div>
</div>

<div class="hint-bar">💡 Le PDF télécharge exactement ce que vous voyez à l'écran (modèle, couleur, police). Word : design similaire.</div>

<div class="page-wrap">
  <div class="left-panel">
    <div class="mini-profile">
      <div class="mini-av"><?= htmlspecialchars($initials) ?></div>
      <div class="mini-name"><?= htmlspecialchars($name) ?></div>
      <div class="mini-role"><?= htmlspecialchars($role) ?></div>
      <div class="complete-row" style="margin-top:12px;">
        <span style="font-size:11px;color:var(--ca);font-weight:600;">Profil complété</span>
        <span style="font-size:11px;color:var(--ca);font-weight:700;"><?= $pct ?>%</span>
      </div>
      <div class="complete-bar"><div class="complete-fill" style="width:<?= min(100, $pct) ?>%;"></div></div>
    </div>
    <div class="panel-section">
      <div class="panel-section-title">Sections à inclure</div>
      <div class="section-toggle" onclick="toggleSection('exp')">
        <div class="section-toggle-left"><div class="section-icon" style="background:#fff0e8;">💼</div>Expérience</div>
        <label class="toggle-switch" onclick="event.stopPropagation()"><input type="checkbox" id="toggle-exp" checked onchange="toggleSection('exp',this.checked)"><span class="ts-slider"></span></label>
      </div>
      <div class="section-toggle" onclick="toggleSection('form')">
        <div class="section-toggle-left"><div class="section-icon" style="background:#fef9ec;">🎓</div>Formation</div>
        <label class="toggle-switch" onclick="event.stopPropagation()"><input type="checkbox" id="toggle-form" checked onchange="toggleSection('form',this.checked)"><span class="ts-slider"></span></label>
      </div>
      <div class="section-toggle" onclick="toggleSection('skills')">
        <div class="section-toggle-left"><div class="section-icon" style="background:#eff6ff;">⚡</div>Compétences</div>
        <label class="toggle-switch" onclick="event.stopPropagation()"><input type="checkbox" id="toggle-skills" checked onchange="toggleSection('skills',this.checked)"><span class="ts-slider"></span></label>
      </div>
      <div class="section-toggle" onclick="toggleSection('langs')">
        <div class="section-toggle-left"><div class="section-icon" style="background:#f5f3ff;">🌍</div>Langues</div>
        <label class="toggle-switch" onclick="event.stopPropagation()"><input type="checkbox" id="toggle-langs" checked onchange="toggleSection('langs',this.checked)"><span class="ts-slider"></span></label>
      </div>
      <div class="section-toggle" onclick="toggleSection('projects')">
        <div class="section-toggle-left"><div class="section-icon" style="background:var(--ca-l);">🚀</div>Projets</div>
        <label class="toggle-switch" onclick="event.stopPropagation()"><input type="checkbox" id="toggle-projects" checked onchange="toggleSection('projects',this.checked)"><span class="ts-slider"></span></label>
      </div>
      <div class="section-toggle" onclick="toggleSection('certs')">
        <div class="section-toggle-left"><div class="section-icon" style="background:#fff0e8;">🏅</div>Certifications</div>
        <label class="toggle-switch" onclick="event.stopPropagation()"><input type="checkbox" id="toggle-certs" checked onchange="toggleSection('certs',this.checked)"><span class="ts-slider"></span></label>
      </div>
    </div>
    <div class="panel-section">
      <div class="panel-section-title">Couleur d'accent</div>
      <div class="color-picker-row">
        <div class="color-dot active" style="background:#1a6b4a;" onclick="setColor('#1a6b4a',this)" title="Vert forêt"></div>
        <div class="color-dot" style="background:#2563eb;" onclick="setColor('#2563eb',this)" title="Bleu royal"></div>
        <div class="color-dot" style="background:#7c3aed;" onclick="setColor('#7c3aed',this)" title="Violet"></div>
        <div class="color-dot" style="background:#c9610a;" onclick="setColor('#c9610a',this)" title="Orange"></div>
        <div class="color-dot" style="background:#c0392b;" onclick="setColor('#c0392b',this)" title="Rouge"></div>
        <div class="color-dot" style="background:#0891b2;" onclick="setColor('#0891b2',this)" title="Cyan"></div>
        <div class="color-dot" style="background:#1c1a17;" onclick="setColor('#1c1a17',this)" title="Noir"></div>
        <div class="color-dot" style="background:#78716c;" onclick="setColor('#78716c',this)" title="Gris chaud"></div>
      </div>
    </div>
    <div class="panel-section">
      <div class="panel-section-title">Police</div>
      <div class="font-opt active" onclick="setFont('Fraunces',this)" style="font-family:'Fraunces',serif;">Fraunces — Classique</div>
      <div class="font-opt" onclick="setFont('Plus Jakarta Sans',this)">Plus Jakarta Sans — Moderne</div>
      <div class="font-opt" onclick="setFont('Georgia',this)" style="font-family:Georgia,serif;">Georgia — Traditionnel</div>
    </div>
  </div>

  <div class="cv-area" id="cv-area">
    <div class="cv-page" id="cv-page" data-tpl="1">
      <div class="cv-sidebar">
        <div class="cv-sb-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="cv-sb-name"><?= htmlspecialchars($name) ?></div>
        <div class="cv-sb-role"><?= htmlspecialchars($role) ?></div>
        <div class="cv-sb-divider"></div>
        <div class="cv-sb-section">
          <div class="cv-sb-section-title">Contact</div>
          <?php if ($email) { ?><div class="cv-sb-contact-item"><span class="cv-sb-contact-icon">✉</span><?= htmlspecialchars($email) ?></div><?php } ?>
          <?php if ($phone) { ?><div class="cv-sb-contact-item"><span class="cv-sb-contact-icon">📱</span><?= htmlspecialchars($phone) ?></div><?php } ?>
          <?php if ($ville) { ?><div class="cv-sb-contact-item"><span class="cv-sb-contact-icon">📍</span><?= htmlspecialchars($ville) ?></div><?php } ?>
        </div>
        <div class="cv-sb-divider"></div>
        <div class="cv-sb-section" id="cv-sb-skills">
          <div class="cv-sb-section-title">Compétences</div>
          <?php
          $skillList = array_slice($skills, 0, 8);
          $defaultW = 80;
          foreach ($skillList as $i => $s) {
            $w = min(100, $defaultW - $i * 5);
            echo '<div class="cv-sb-skill-row"><span class="cv-sb-skill-name">' . htmlspecialchars($s) . '</span><div class="cv-sb-skill-bar"><div class="cv-sb-skill-fill" style="width:' . $w . '%;"></div></div></div>';
          }
          if (empty($skillList)) {
            echo '<div class="cv-sb-skill-row"><span class="cv-sb-skill-name">—</span><div class="cv-sb-skill-bar"><div class="cv-sb-skill-fill" style="width:0%;"></div></div></div>';
          }
          ?>
        </div>
        <div class="cv-sb-divider"></div>
        <div class="cv-sb-section" id="cv-sb-langs">
          <div class="cv-sb-section-title">Langues</div>
          <?php foreach (array_slice($langs, 0, 5) as $i => $l) {
            $level = 5 - $i;
            if ($level < 1) $level = 1;
            $dots = str_repeat('<div class="cv-sb-lang-dot filled"></div>', $level) . str_repeat('<div class="cv-sb-lang-dot"></div>', 5 - $level);
          ?><div class="cv-sb-lang-item"><span style="font-size:11px;opacity:.9;"><?= htmlspecialchars($l) ?></span><div class="cv-sb-lang-dots"><?= $dots ?></div></div><?php } ?>
          <?php if (empty($langs)) { ?><div class="cv-sb-lang-item"><span style="font-size:11px;opacity:.9;">—</span><div class="cv-sb-lang-dots"><div class="cv-sb-lang-dot"></div><div class="cv-sb-lang-dot"></div><div class="cv-sb-lang-dot"></div><div class="cv-sb-lang-dot"></div><div class="cv-sb-lang-dot"></div></div></div><?php } ?>
        </div>
      </div>
      <div class="cv-main">
        <div class="cv-name-main" id="cv-main-name"><?= htmlspecialchars($name) ?></div>
        <div class="cv-role-main"><?= htmlspecialchars($role) ?><?= $expYears > 0 ? ' · ' . $expYears . ' an(s) d\'expérience' : '' ?></div>
        <?php if ($about) { ?>
        <div class="cv-section">
          <div class="cv-section-title">À propos</div>
          <p style="font-size:12px;color:var(--muted);line-height:1.65;"><?= nl2br(htmlspecialchars(mb_substr($about, 0, 400))) ?><?= mb_strlen($about) > 400 ? '…' : '' ?></p>
        </div>
        <?php } ?>
        <div class="cv-section" id="cv-section-exp">
          <div class="cv-section-title">💼 Expérience</div>
          <?php foreach ($experience as $e) { ?>
          <div class="cv-item">
            <div class="cv-item-head"><div class="cv-item-title"><?= htmlspecialchars($e['title']) ?></div><div class="cv-item-date"><?= htmlspecialchars($e['date']) ?></div></div>
            <?php if (!empty($e['company'])) { ?><div class="cv-item-company"><?= htmlspecialchars($e['company']) ?></div><?php } ?>
            <?php if (!empty($e['description'])) { ?><div class="cv-item-desc"><?= nl2br(htmlspecialchars($e['description'])) ?></div><?php } ?>
          </div>
          <?php } ?>
          <?php if (empty($experience)) { ?><p style="font-size:12px;color:var(--muted);">—</p><?php } ?>
        </div>
        <div class="cv-section" id="cv-section-form">
          <div class="cv-section-title">🎓 Formation</div>
          <?php foreach ($education as $e) { ?>
          <div class="cv-item">
            <div class="cv-item-head"><div class="cv-item-title"><?= htmlspecialchars($e['title']) ?></div><div class="cv-item-date"><?= htmlspecialchars($e['date']) ?></div></div>
            <?php if (!empty($e['school'])) { ?><div class="cv-item-company"><?= htmlspecialchars($e['school']) ?></div><?php } ?>
            <?php if (!empty($e['description'])) { ?><div class="cv-item-desc"><?= htmlspecialchars($e['description']) ?></div><?php } ?>
          </div>
          <?php } ?>
          <?php if (empty($education)) { ?><p style="font-size:12px;color:var(--muted);">—</p><?php } ?>
        </div>
        <div class="cv-section" id="cv-section-projects">
          <div class="cv-section-title">🚀 Projets</div>
          <?php foreach ($projects as $p) {
            $tit = is_array($p) ? ($p['title'] ?? '') : $p;
            $desc = is_array($p) ? ($p['description'] ?? '') : '';
          ?><div class="cv-item"><div class="cv-item-head"><div class="cv-item-title"><?= htmlspecialchars($tit) ?></div></div><?php if ($desc) { ?><div class="cv-item-desc"><?= nl2br(htmlspecialchars($desc)) ?></div><?php } ?></div><?php } ?>
          <?php if (empty($projects)) { ?><p style="font-size:12px;color:var(--muted);">—</p><?php } ?>
        </div>
        <div class="cv-section" id="cv-section-certs">
          <div class="cv-section-title">🏅 Certifications</div>
          <div class="cv-chips">
            <?php foreach (array_slice($certs, 0, 8) as $cert) { ?><span class="cv-chip"><?= htmlspecialchars($cert) ?></span><?php } ?>
          </div>
          <?php if (empty($certs)) { ?><p style="font-size:12px;color:var(--muted);">—</p><?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"><span id="toast-icon">✅</span><span id="toast-msg">Action effectuée</span></div>

<script>
window.candidateData = <?= $cvJson ?>;

let currentColor = '#1a6b4a';
let currentTpl = 1;

function setTemplate(n, el){
  currentTpl = n;
  document.querySelectorAll('.tpl-pill').forEach(p=>p.classList.remove('active'));
  el.classList.add('active');
  const page = document.getElementById('cv-page');
  page.style.opacity='0';page.style.transform='translateY(10px)';
  setTimeout(()=>{
    page.dataset.tpl = n;
    page.innerHTML = n===1 ? buildTpl1() : n===2 ? buildTpl2() : buildTpl3();
    applySectionVisibility();
    applyAccentColor(currentColor);
    page.style.transition='all .3s ease';
    page.style.opacity='1';page.style.transform='translateY(0)';
    setTimeout(()=>page.style.transition='',350);
  }, 200);
}

function setColor(color, dotEl){
  currentColor = color;
  document.querySelectorAll('.color-dot').forEach(d=>d.classList.remove('active'));
  dotEl.classList.add('active');
  applyAccentColor(color);
  showToast('🎨','Couleur mise à jour');
}

function applyAccentColor(color){
  const page = document.getElementById('cv-page');
  if(!page) return;
  page.style.setProperty('--cv-accent', color);
  const sb = page.querySelector('.cv-sidebar');
  if(sb) sb.style.background = color;
  const h = page.querySelector('.cv-header');
  if(h) h.style.background = color;
  page.querySelectorAll('.cv-chip').forEach(c=>{ c.style.color = color; c.style.borderColor = hexToRgba(color, 0.2); });
  page.querySelectorAll('.cv-section-title, .cv-min-section-title').forEach(t=>t.style.color=color);
  page.querySelectorAll('.cv-item-company').forEach(t=>t.style.color=color);
  page.querySelectorAll('.cv-role-main, .cv-min-role').forEach(t=>t.style.color=color);
}

function hexToRgba(hex,a){ const r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16); return 'rgba('+r+','+g+','+b+','+a+')'; }

function setFont(font, el){
  document.querySelectorAll('.font-opt').forEach(f=>f.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('cv-page').style.fontFamily = "'"+font+"', sans-serif";
  showToast('✏️','Police mise à jour');
}

function toggleSection(id, checked){
  const map = {'exp':'cv-section-exp','form':'cv-section-form','skills':'cv-sb-skills','langs':'cv-sb-langs cv-section-langs','projects':'cv-section-projects','certs':'cv-section-certs'};
  const ids = (map[id]||'').split(' ');
  ids.forEach(sid=>{
    const el = document.getElementById(sid);
    if(el){ const visible = checked !== undefined ? checked : document.getElementById('toggle-'+id)?.checked; el.style.display = visible ? '' : 'none'; }
  });
  if(checked===undefined){ const cb = document.getElementById('toggle-'+id); if(cb){ cb.checked = !cb.checked; toggleSection(id, cb.checked); } }
}

function applySectionVisibility(){
  ['exp','form','skills','langs','projects','certs'].forEach(id=>{ const cb=document.getElementById('toggle-'+id); if(cb) toggleSection(id,cb.checked); });
}

function d(){ return window.candidateData || {}; }
function esc(s){ const div=document.createElement('div'); div.textContent=s||''; return div.innerHTML; }

function buildTpl1(){
  const d0=d();
  const skills = (d0.skills||[]).slice(0,8);
  const skillRows = skills.length ? skills.map((s,i)=>'<div class="cv-sb-skill-row"><span class="cv-sb-skill-name">'+esc(s)+'</span><div class="cv-sb-skill-bar"><div class="cv-sb-skill-fill" style="width:'+(80-i*5)+'%;"></div></div></div>').join('') : '<div class="cv-sb-skill-row"><span class="cv-sb-skill-name">—</span><div class="cv-sb-skill-bar"><div class="cv-sb-skill-fill" style="width:0%;"></div></div></div>';
  const langs = (d0.languages||[]).slice(0,5);
  const langRows = langs.length ? langs.map((l,i)=>'<div class="cv-sb-lang-item"><span style="font-size:11px;opacity:.9">'+esc(l)+'</span><div class="cv-sb-lang-dots">'+[1,2,3,4,5].map(j=>'<div class="cv-sb-lang-dot'+(j<=5-i?' filled':'')+'"></div>').join('')+'</div></div>').join('') : '';
  const contact = [];
  if(d0.email) contact.push('<div class="cv-sb-contact-item"><span class="cv-sb-contact-icon">✉</span>'+esc(d0.email)+'</div>');
  if(d0.telephone) contact.push('<div class="cv-sb-contact-item"><span class="cv-sb-contact-icon">📱</span>'+esc(d0.telephone)+'</div>');
  if(d0.ville) contact.push('<div class="cv-sb-contact-item"><span class="cv-sb-contact-icon">📍</span>'+esc(d0.ville)+'</div>');
  const expRows = (d0.experience||[]).map(e=>'<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(e.title)+'</div><div class="cv-item-date">'+esc(e.date)+'</div></div>'+(e.company?'<div class="cv-item-company">'+esc(e.company)+'</div>':'')+(e.description?'<div class="cv-item-desc">'+esc(e.description)+'</div>':'')+'</div>').join('');
  const eduRows = (d0.education||[]).map(e=>'<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(e.title)+'</div><div class="cv-item-date">'+esc(e.date)+'</div></div>'+(e.school?'<div class="cv-item-company">'+esc(e.school)+'</div>':'')+'</div>').join('');
  const projRows = (d0.projects||[]).map(p=>{ const t=typeof p==='object'?p.title:p; const desc=typeof p==='object'?p.description:''; return '<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(t)+'</div></div>'+(desc?'<div class="cv-item-desc">'+esc(desc)+'</div>':'')+'</div>'; }).join('');
  const certChips = (d0.certifications||[]).slice(0,8).map(c=>'<span class="cv-chip">'+esc(c)+'</span>').join('');
  const roleLine = d0.role + (d0.experienceYears ? ' · '+d0.experienceYears+" an(s) d'expérience" : '');
  const aboutBlock = d0.about ? '<div class="cv-section"><div class="cv-section-title">À propos</div><p style="font-size:12px;color:var(--muted);line-height:1.65">'+esc(d0.about.substring(0,400))+(d0.about.length>400?'…':'')+'</p></div>' : '';
  return '<div class="cv-sidebar" style="background:'+currentColor+'"><div class="cv-sb-avatar">'+esc(d0.initials)+'</div><div class="cv-sb-name">'+esc(d0.fullName)+'</div><div class="cv-sb-role">'+esc(d0.role)+'</div><div class="cv-sb-divider"></div><div class="cv-sb-section"><div class="cv-sb-section-title">Contact</div>'+contact.join('')+'</div><div class="cv-sb-divider"></div><div class="cv-sb-section" id="cv-sb-skills"><div class="cv-sb-section-title">Compétences</div>'+skillRows+'</div><div class="cv-sb-divider"></div><div class="cv-sb-section" id="cv-sb-langs"><div class="cv-sb-section-title">Langues</div>'+langRows+'</div></div><div class="cv-main"><div class="cv-name-main">'+esc(d0.fullName)+'</div><div class="cv-role-main" style="color:'+currentColor+'">'+esc(roleLine)+'</div>'+aboutBlock+'<div class="cv-section" id="cv-section-exp"><div class="cv-section-title" style="color:'+currentColor+'">💼 Expérience</div>'+expRows+'</div><div class="cv-section" id="cv-section-form"><div class="cv-section-title" style="color:'+currentColor+'">🎓 Formation</div>'+eduRows+'</div><div class="cv-section" id="cv-section-projects"><div class="cv-section-title" style="color:'+currentColor+'">🚀 Projets</div>'+projRows+'</div><div class="cv-section" id="cv-section-certs"><div class="cv-section-title" style="color:'+currentColor+'">🏅 Certifications</div><div class="cv-chips">'+certChips+'</div></div></div>';
}

function buildTpl2(){
  const d0=d();
  const roleLine = d0.role + (d0.experienceYears ? ' · '+d0.experienceYears+" an(s) d'expérience" : '');
  const contacts = [];
  if(d0.email) contacts.push('<span class="cv-header-contact">✉ '+esc(d0.email)+'</span>');
  if(d0.telephone) contacts.push('<span class="cv-header-contact">📱 '+esc(d0.telephone)+'</span>');
  if(d0.ville) contacts.push('<span class="cv-header-contact">📍 '+esc(d0.ville)+'</span>');
  const expRows = (d0.experience||[]).map(e=>'<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(e.title)+'</div><div class="cv-item-date">'+esc(e.date)+'</div></div><div class="cv-item-company">'+esc(e.company||'')+'</div><div class="cv-item-desc">'+esc(e.description||'')+'</div></div>').join('');
  const eduRows = (d0.education||[]).map(e=>'<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(e.title)+'</div><div class="cv-item-date">'+esc(e.date)+'</div></div><div class="cv-item-company">'+esc(e.school||'')+'</div></div>').join('');
  const projRows = (d0.projects||[]).map(p=>{ const t=typeof p==='object'?p.title:p; return '<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(t)+'</div></div></div>'; }).join('');
  const certChips = (d0.certifications||[]).slice(0,6).map(c=>'<span class="cv-chip">'+esc(c)+'</span>').join('');
  const skillBars = (d0.skills||[]).slice(0,6).map((s,i)=>'<div style="margin-bottom:9px"><div style="font-size:11px;font-weight:600;margin-bottom:4px">'+esc(s)+'</div><div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden"><div style="width:'+(85-i*5)+'%;height:100%;background:'+currentColor+';border-radius:2px"></div></div></div>').join('');
  const langList = (d0.languages||[]).slice(0,5).map(l=>'<div style="display:flex;justify-content:space-between;margin-bottom:7px;font-size:11px"><span style="font-weight:600">'+esc(l)+'</span><span style="color:var(--muted)">—</span></div>').join('');
  return '<div class="cv-header" style="background:'+currentColor+'"><div class="cv-header-avatar">'+esc(d0.initials)+'</div><div class="cv-header-info"><div class="cv-header-name">'+esc(d0.fullName)+'</div><div class="cv-header-role">'+esc(roleLine)+'</div><div class="cv-header-contacts">'+contacts.join('')+'</div></div></div><div class="cv-body"><div class="cv-body-main"><div class="cv-section" id="cv-section-exp"><div class="cv-section-title" style="color:'+currentColor+'">💼 Expérience</div>'+expRows+'</div><div class="cv-section" id="cv-section-form"><div class="cv-section-title" style="color:'+currentColor+'">🎓 Formation</div>'+eduRows+'</div><div class="cv-section" id="cv-section-projects"><div class="cv-section-title" style="color:'+currentColor+'">🚀 Projets</div>'+projRows+'</div><div class="cv-section" id="cv-section-certs"><div class="cv-section-title" style="color:'+currentColor+'">🏅 Certifications</div><div class="cv-chips">'+certChips+'</div></div></div><div class="cv-body-side"><div class="cv-sb-section" id="cv-sb-skills"><div style="font-size:9px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:'+currentColor+';margin-bottom:8px">Compétences</div>'+skillBars+'</div><div style="height:1px;background:var(--border);margin:16px 0"></div><div class="cv-sb-section" id="cv-sb-langs"><div style="font-size:9px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:'+currentColor+';margin-bottom:10px">Langues</div>'+langList+'</div></div></div>';
}

function buildTpl3(){
  const d0=d();
  const contacts = [];
  if(d0.email) contacts.push('<span>✉ '+esc(d0.email)+'</span>');
  if(d0.telephone) contacts.push('<span>📱 '+esc(d0.telephone)+'</span>');
  if(d0.ville) contacts.push('<span>📍 '+esc(d0.ville)+'</span>');
  const expRows = (d0.experience||[]).map(e=>'<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(e.title)+'</div><div class="cv-item-date">'+esc(e.date)+'</div></div><div class="cv-item-company">'+esc(e.company||'')+'</div><div class="cv-item-desc">'+esc(e.description||'')+'</div></div>').join('');
  const eduRows = (d0.education||[]).map(e=>'<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(e.title)+'</div><div class="cv-item-date">'+esc(e.date)+'</div></div><div class="cv-item-company">'+esc(e.school||'')+'</div></div>').join('');
  const projRows = (d0.projects||[]).map(p=>{ const t=typeof p==='object'?p.title:p; return '<div class="cv-item"><div class="cv-item-head"><div class="cv-item-title">'+esc(t)+'</div></div></div>'; }).join('');
  const certChips = (d0.certifications||[]).slice(0,6).map(c=>'<span class="cv-chip">'+esc(c)+'</span>').join('');
  const skillList = (d0.skills||[]).slice(0,8).map(s=>'<div style="font-size:12px;padding:5px 0;border-bottom:1px solid var(--border)">'+esc(s)+'</div>').join('');
  const langList = (d0.languages||[]).slice(0,5).map(l=>'<div style="font-size:12px;padding:5px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between"><span>'+esc(l)+'</span><span style="color:var(--muted)">—</span></div>').join('');
  return '<div class="cv-min-header"><div class="cv-min-name">'+esc(d0.fullName)+'</div><div class="cv-min-role" style="color:'+currentColor+'">'+esc(d0.role)+'</div><div class="cv-min-contacts">'+contacts.join('')+'</div></div><div class="cv-min-body"><div><div class="cv-section" id="cv-section-exp"><div class="cv-min-section-title" style="color:'+currentColor+'">Expérience</div>'+expRows+'</div><div class="cv-section" id="cv-section-form"><div class="cv-min-section-title" style="color:'+currentColor+'">Formation</div>'+eduRows+'</div><div class="cv-section" id="cv-section-projects"><div class="cv-min-section-title" style="color:'+currentColor+'">Projets</div>'+projRows+'</div><div class="cv-section" id="cv-section-certs"><div class="cv-min-section-title" style="color:'+currentColor+'">Certifications</div><div class="cv-chips">'+certChips+'</div></div></div><div><div id="cv-sb-skills"><div class="cv-min-section-title" style="color:'+currentColor+'">Compétences</div>'+skillList+'</div><div style="margin-top:18px" id="cv-sb-langs"><div class="cv-min-section-title" style="color:'+currentColor+'">Langues</div>'+langList+'</div></div></div>';
}

function printCV(){ showToast('📄','Ouverture de la fenêtre d\'impression…'); setTimeout(()=>window.print(), 600); }

function downloadPdfFromPreview(){
  var el = document.getElementById('cv-page');
  if(!el){ showToast('❌','Aucun contenu à exporter'); return; }
  showToast('📄','Génération du PDF…');
  var opt = {
    margin: 0,
    filename: 'CV_' + (window.candidateData && window.candidateData.fullName ? window.candidateData.fullName.replace(/[^\w\s\-]/g,'_') : 'CV') + '.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, letterRendering: true, logging: false },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
  };
  html2pdf().set(opt).from(el).save().then(function(){ showToast('✅','PDF téléchargé'); }).catch(function(){ showToast('❌','Erreur lors de la génération'); });
}

var tt;
function showToast(icon,msg){ clearTimeout(tt); document.getElementById('toast-icon').textContent=icon; document.getElementById('toast-msg').textContent=msg; document.getElementById('toast').classList.add('show'); tt=setTimeout(()=>document.getElementById('toast').classList.remove('show'),2600); }

document.getElementById('cv-page').style.setProperty('--cv-accent', currentColor);
</script>
</body>
</html>
