<?php
declare(strict_types=1);
require __DIR__ . '/php/bootstrap.php';
require __DIR__ . '/php/admin-service.php';

// result of a contact form post when JavaScript is off (see contact.php)
$contactFlash = take_flash();
$contactErrors = $contactFlash['errors'] ?? [];
$contactOld = $contactFlash['old'] ?? [];
// time-trap: bots that submit within a few seconds of loading the page are ignored
$_SESSION['form_started'] = time();

// when the admin is logged in, show a shortcut with the unread count
$adminUnread = null;
$adminLatestId = 0;
if (is_admin()) {
    try {
        $adminPdo = contact_database();
        $adminUnread = count_messages($adminPdo)['unread'];
        $adminLatestId = latest_message_id($adminPdo);
    } catch (PDOException) {
        $adminUnread = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jamar Carty — Développeur Full Stack</title>
<meta name="description" content="Jamar Carty, étudiant en BTS SIO SLAM à l’AFIP, recherche une alternance en développement Full Stack au rythme de deux semaines en entreprise et deux semaines en formation.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="logo-jc.svg" type="image/svg+xml">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
  <div class="container">
    <div class="logo"><img class="logo-mark" src="logo-jc.svg" alt=""><span><span class="neon-name">Jamar</span> <span class="neon-name">Carty</span></span></div>
    <button class="nav-menu-toggle" type="button" aria-expanded="false" aria-controls="siteNavLinks">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/></svg>
      <span class="menu-toggle-label">Menu</span>
    </button>
    <div class="nav-links" id="siteNavLinks">
      <a class="nav-linkedin" href="https://www.linkedin.com/in/jamar-carty" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn de Jamar Carty" title="LinkedIn">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.35V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg>
        <span>LinkedIn</span>
      </a>
      <a class="nav-github" href="https://github.com/jamar603" target="_blank" rel="noopener noreferrer" aria-label="GitHub de Jamar Carty" title="GitHub">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5A11.5 11.5 0 0 0 8.36 22.91c.58.1.79-.25.79-.56v-2.02c-3.2.7-3.87-1.36-3.87-1.36-.52-1.33-1.28-1.69-1.28-1.69-1.04-.71.08-.7.08-.7 1.15.08 1.75 1.18 1.75 1.18 1.02 1.74 2.68 1.24 3.33.95.1-.74.4-1.24.73-1.53-2.55-.29-5.23-1.28-5.23-5.68 0-1.26.45-2.28 1.18-3.08-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.17a10.9 10.9 0 0 1 5.74 0c2.19-1.48 3.15-1.17 3.15-1.17.62 1.58.23 2.75.11 3.04.73.8 1.18 1.82 1.18 3.08 0 4.41-2.68 5.38-5.24 5.67.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5z"/></svg>
        <span>GitHub</span>
      </a>
      <a href="#profil" data-t="profil">Profil</a>
      <a href="#formation" data-t="formation">Formation</a>
      <a href="#projets" data-t="projets">Projets</a>
      <a href="#articles" data-t="articles">Articles</a>
      <a href="#competences" data-t="competences">Compétences</a>
      <a href="#atouts" data-t="atouts">Atouts</a>
      <a href="#veille" data-t="veille">Veille</a>
      <a href="#contact" data-t="contact">Contact</a>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="container hero-grid">
    <div>
      <div class="badge"><span class="d"></span> Recherche alternance · rythme 2 semaines / 2 semaines</div>
      <h1><span class="neon-name">Jamar</span> <span class="neon-name">Carty</span><br><span class="sw">Développeur Full Stack</span></h1>
      <p class="hero-sub">Étudiant en BTS SIO SLAM à l’AFIP, je recherche une alternance en développement informatique. Rythme : deux semaines en entreprise et deux semaines en formation.</p>
      <div class="hero-cta">
        <a href="#projets" class="btn-primary">Voir mes projets →</a>
        <a href="#contact" class="btn-outline">Parler d’alternance</a>
        <a href="cv-jamar-carty.pdf" class="btn-outline btn-cv" download="CV-Jamar-Carty.pdf"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg> Télécharger mon CV</a>
      </div>
    </div>
    <div class="hero-art" id="heroScene" role="img" aria-label="Ordinateur portable 3D affichant du code">
      <p class="hero-scene-fallback" aria-hidden="true">Ordinateur 3D · développement web</p>
    </div>
  </div>
</section>

<div class="stack-band" role="region" aria-label="Technologies utilisées">
  <div class="stack-track">
    <ul><li>Laravel 12</li><li>PHP</li><li>React Native</li><li>JavaScript</li><li>C#</li><li>SQL</li><li>API REST</li><li>Node.js</li><li>Symfony</li><li>Docker</li><li>Git / GitHub</li><li>Linux</li><li>Modélisation 3D</li><li>OWASP Top 10</li></ul>
    <ul aria-hidden="true"><li>Laravel 12</li><li>PHP</li><li>React Native</li><li>JavaScript</li><li>C#</li><li>SQL</li><li>API REST</li><li>Node.js</li><li>Symfony</li><li>Docker</li><li>Git / GitHub</li><li>Linux</li><li>Modélisation 3D</li><li>OWASP Top 10</li></ul>
  </div>
</div>

<section id="profil">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--indigo-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
      </div>
      <div><h2>Profil</h2><p>Qui je suis, en quelques lignes</p></div>
    </div>
    <div class="profile-grid">
      <blockquote>« Développeur Full Stack, je conçois des applications web et mobiles, des API REST et des expériences 3D interactives. »</blockquote>
      <p>Actuellement en BTS Services Informatiques aux Organisations, option SLAM, à l’AFIP pour 2026–2027, j’ai auparavant suivi cette formation à l’ICOF de 2023 à 2026.</p>
      <p>Je recherche une alternance en développement informatique au rythme de <strong>deux semaines en entreprise et deux semaines en formation</strong>. Mes expériences couvrent Laravel, React Native, les API REST, WordPress et la modélisation 3D.</p>
    </div>
  </div>
</section>

<section id="formation">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--coral-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-5 10 5-10 5-10-5z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/></svg>
      </div>
      <div><h2>Formation</h2><p>BTS SIO SLAM · AFIP, 2026–2027</p></div>
    </div>

    <div class="branch-grid">
      <div class="branch-card current">
        <div class="branch-top"><h4>BTS SIO · option SLAM</h4><span class="pill-current">En cours</span></div>
        <p class="branch-summary">AFIP · 2026–2027</p>
      </div>
      <div class="branch-card">
        <div class="branch-top"><h4>BTS SIO · option SLAM</h4></div>
        <p class="branch-summary">ICOF · 2023–2026</p>
      </div>
    </div>

    <div class="timeline">
      <div class="t-row">
        <div class="t-node"><div class="t-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.4" stroke-linecap="round"><circle cx="12" cy="12" r="1"/></svg></div><div class="t-line"></div></div>
        <div class="t-content">
          <div class="t-yr">2019 – 2020</div>
          <div class="t-title">BTS Système Numérique · option Réseaux</div>
          <div class="t-org">Lycée Hyrome</div>
        </div>
      </div>
      <div class="t-row">
        <div class="t-node"><div class="t-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.4" stroke-linecap="round"><circle cx="12" cy="12" r="1"/></svg></div></div>
        <div class="t-content">
          <div class="t-yr">2017 – 2019</div>
          <div class="t-title">Baccalauréat STI2D</div>
          <div class="t-org">Lycée Robert Weinum</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="experience">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--amber-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e5b567" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
      </div>
      <div><h2>Expérience</h2><p>Développement web, applications et 3D</p></div>
    </div>

    <div class="timeline">
      <div class="t-row">
        <div class="t-node"><div class="t-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2.4" stroke-linecap="round"><rect x="4" y="4" width="16" height="16" rx="3"/></svg></div><div class="t-line"></div></div>
        <div class="t-content">
          <div class="t-yr">10 novembre – 19 décembre 2025</div>
          <div class="t-title">Stage · Développement d’applications et web</div>
          <div class="t-org">COF</div>
          <ul class="experience-points"><li>Conception d’une application 3D de chargeur avec Kadviser et Geometry, incluant modélisation et interactions.</li><li>Développement de fonctionnalités dynamiques pour le site Kadviser.</li><li>Tests et corrections pour fiabiliser l’application.</li></ul>
        </div>
      </div>
      <div class="t-row">
        <div class="t-node"><div class="t-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2.4" stroke-linecap="round"><rect x="4" y="4" width="16" height="16" rx="3"/></svg></div><div class="t-line"></div></div>
        <div class="t-content">
          <div class="t-yr">12 mai – 20 juin 2025</div>
          <div class="t-title">Stage · Développement web et API REST</div>
          <ul class="experience-points"><li>Développement d’un outil de gestion des conventions avec Laravel 12.</li><li>Création d’une API REST reliée à la base Océane pour une application React Native.</li><li>Code modulaire en POO, travail collaboratif avec Git/GitHub et revues de code.</li></ul>
        </div>
      </div>
      <div class="t-row">
        <div class="t-node"><div class="t-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2.4" stroke-linecap="round"><rect x="4" y="4" width="16" height="16" rx="3"/></svg></div><div class="t-line"></div></div>
        <div class="t-content">
          <div class="t-yr">2024</div>
          <div class="t-title">Stage · Programmation web</div>
          <div class="t-org">AAB Fabrication · Saint-Martin</div>
          <ul class="experience-points"><li>Développement d’un site web dynamique pour soutenir la vente en ligne et la visibilité de l’entreprise.</li><li>Intégration front-end en HTML, CSS et JavaScript et mise en place des fonctionnalités principales.</li></ul>
        </div>
      </div>
      <div class="t-row">
        <div class="t-node"><div class="t-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.4" stroke-linecap="round"><circle cx="12" cy="12" r="1"/></svg></div></div>
        <div class="t-content">
          <div class="t-yr">2023</div>
          <div class="t-title">Développement de sites web · Indépendant</div>
          <ul class="experience-points"><li>Conception et gestion d’un site e-commerce WordPress pour un photographe : portfolio, rendez-vous et vente en ligne.</li></ul>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="projets">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--mint-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/></svg>
      </div>
      <div><h2>Projets</h2><p>Mes réalisations</p></div>
      <div class="cube-scene" aria-hidden="true"><div class="cube"><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
    </div>

    <div class="projects-grid">
      <div class="project-card">
        <div class="project-icon" style="background:var(--indigo-soft);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg></div>
        <div class="project-title">Gestion des conventions de stage</div>
        <div class="project-desc">Application web avec des espaces et des droits adaptés aux étudiants, enseignants et membres du secrétariat.</div>
        <div class="project-tags"><span>Laravel 12</span><span>PHP</span><span>Gestion des accès</span></div>
        <a class="project-link" href="#articles" data-open-article="laravel-stage">Lire la mission →</a>
      </div>
      <div class="project-card">
        <div class="project-icon" style="background:var(--coral-soft);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 010 18 14 14 0 010-18z"/></svg></div>
        <div class="project-title">Application mobile connectée à une API</div>
        <div class="project-desc">Mission de développement mobile réalisée pendant la formation.</div>
        <div class="project-tags"><span>React Native</span><span>API</span></div>
        <a class="project-link" href="#articles" data-open-article="react-native-stage">Lire la mission →</a>
      </div>
      <div class="project-card">
        <div class="project-icon" style="background:var(--amber-soft);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e5b567" stroke-width="2" stroke-linecap="round"><path d="M4 19V5a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2H6a2 2 0 01-2-2z"/><path d="M14 3v6h6"/></svg></div>
        <div class="project-title">Application Mediateq</div>
        <div class="project-desc">Application Windows Forms organisée en onglets pour gérer plusieurs fonctionnalités métier.</div>
        <div class="project-tags"><span>C#</span><span>Windows Forms</span></div>
        <a class="project-link" href="#articles" data-open-article="mediateq-csharp">Lire le projet →</a>
      </div>
      <div class="project-card">
        <div class="project-icon" style="background:var(--mint-soft);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round"><path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg></div>
        <div class="project-title">Gestion de parc informatique</div>
        <div class="project-desc">Travail pratique consacré à la gestion du patrimoine informatique avec GLPI.</div>
        <div class="project-tags"><span>GLPI</span><span>Support IT</span></div>
        <a class="project-link" href="#articles" data-open-article="glpi-inventory">Lire le TP →</a>
      </div>
      <div class="project-card">
        <div class="project-icon" style="background:var(--mint-soft);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round"><path d="M12 2l9 5v10l-9 5-9-5V7l9-5z"/><path d="M3 7l9 5 9-5M12 12v10"/></svg></div>
        <div class="project-title">K-Advisor et modélisation 3D</div>
        <div class="project-desc">Mission de stage de deuxième année autour d'une application et de la modélisation 3D.</div>
        <div class="project-tags"><span>K-Advisor</span><span>Modélisation 3D</span></div>
        <a class="project-link" href="#articles" data-open-article="kadviser-model3d">Lire la mission →</a>
      </div>
    </div>
    <div class="articles-cta-wrap">
      <a class="btn-outline" href="#articles">Parcourir les 13 articles ↓</a>
    </div>
  </div>
</section>

<section id="articles">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--coral-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V5a2 2 0 012-2h12a2 2 0 012 2v14.5"/><path d="M4 19.5A1.5 1.5 0 005.5 21H20M8 7h8M8 11h8M8 15h5"/></svg>
      </div>
      <div><h2>Articles & travaux pratiques</h2><p>Missions de stage, projets de développement et TP</p></div>
    </div>
      <div class="article-controls" role="search">
        <label for="articleSearch">Rechercher un article
          <input type="search" id="articleSearch" placeholder="Titre, technologie, sujet…" autocomplete="off">
        </label>
        <label for="articleCategory">Thème
          <select id="articleCategory">
            <option value="all">Tous les thèmes</option>
            <option value="stage">Missions de stage</option>
            <option value="development">Développement</option>
            <option value="systems">Systèmes & support</option>
            <option value="portfolio">Portfolio & parcours</option>
          </select>
        </label>
        <button id="resetArticles" type="button">Réinitialiser</button>
      </div>
      <p class="article-count" id="articleCount" aria-live="polite">13 articles</p>
      <p class="article-status" id="articleStatus" role="status" aria-live="polite">Chargement des articles…</p>
      <div class="article-grid" id="articleGrid">
        <article class="article-card" data-category="portfolio">
          <div class="article-meta"><span class="article-category">Portfolio & parcours</span><time>6 mars</time></div>
          <h3>TP Portfolio avec WordPress</h3>
          <p class="article-summary">Création d’un portfolio professionnel pour présenter les missions, projets, compétences et expériences du BTS SIO, avec des pages, catégories et étiquettes organisées.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>Réalisé en atelier professionnel, ce travail avait pour objectif de centraliser les productions du BTS SIO et de présenter le parcours dans le cadre de l’épreuve E4.</p><p>La mission portait sur la structure des pages et du menu, l’organisation des articles par catégories et étiquettes, puis la publication des projets, compétences et expériences avec WordPress.</p></details>
          <div class="project-tags"><span>WordPress</span><span>CMS</span><span>UX</span></div>
          <button class="article-read" type="button" data-article-id="portfolio-wordpress">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="development">
          <div class="article-meta"><span class="article-category">Développement</span><time>3 mars</time></div>
          <h3>AP C# Mediateq</h3>
          <p class="article-summary">Application de gestion de médiathèque : authentification, rôles et CRUD des abonnés, avec mots de passe protégés et requêtes SQL paramétrées.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>Le projet vise à protéger l’accès de l’application et à faciliter le suivi administratif des abonnés. L’authentification identifie les employés avant l’accès aux fonctionnalités sensibles.</p><p>Le module abonnés permet la création, la consultation, la modification et la suppression. Des règles métier signalent les abonnements arrivant à expiration. Les requêtes SQL paramétrées et la gestion des rôles contribuent à sécuriser l’application.</p></details>
          <div class="project-tags"><span>C#</span><span>Windows Forms</span><span>SQL</span></div>
          <button class="article-read" type="button" data-article-id="mediateq-csharp">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="stage">
          <div class="article-meta"><span class="article-category">Mission de stage</span><time datetime="2025-12-18">18 décembre 2025</time></div>
          <h3>Kadviser web : thèmes personnalisables</h3>
          <p class="article-summary">Amélioration de l’application web Kadviser avec une sélection de thèmes, un thème personnalisé et la sauvegarde des choix dans le navigateur.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>La mission ajoute une liste de thèmes et un panneau de personnalisation permettant de modifier les couleurs du fond, du texte et des bordures. Les variables CSS mettent à jour les composants de façon cohérente.</p><p>Les choix personnalisés sont conservés dans localStorage. Les événements de formulaire appliquent les changements immédiatement, tandis qu’un observateur prend en compte les éléments ajoutés dynamiquement.</p></details>
          <div class="project-tags"><span>HTML</span><span>CSS</span><span>JavaScript</span><span>localStorage</span></div>
          <button class="article-read" type="button" data-article-id="kadviser-themes">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="stage">
          <div class="article-meta"><span class="article-category">Mission de stage</span><time datetime="2025-12-16">16 décembre 2025</time></div>
          <h3>Kadviser : modélisation 3D</h3>
          <p class="article-summary">Modélisation d’une tête de chargeur à partir de formes géométriques, puis intégration dans Kadviser et ajout d’une interaction de personnalisation du site.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>Dans le service Kadviser, la mission associe modélisation 3D en GDL et amélioration d’un site web existant. L’objectif est de concevoir un modèle de tête de chargeur et de l’intégrer dans l’environnement de visualisation.</p><p>Le modèle est construit avec des formes géométriques simples, des opérations booléennes, puis des translations et rotations. La mission comprend aussi une interaction web pour modifier la couleur de fond de l’écran.</p></details>
          <div class="project-tags"><span>GDL</span><span>3D</span><span>Kadviser</span></div>
          <button class="article-read" type="button" data-article-id="kadviser-model3d">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="development">
          <div class="article-meta"><span class="article-category">Développement</span><time datetime="2025-09-21">21 septembre 2025</time></div>
          <h3>AP2 SLAM — PHP & SQL</h3>
          <p class="article-summary">Projet d’équipe pour moderniser le back-office de la compagnie Océane : architecture MVC, rôles, opérations CRUD, requêtes préparées et travail en sprints.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>Le back-office gère les données liées aux bateaux, ports, trajets et affectations. Les visiteurs, gestionnaires et administrateurs disposent de droits différents.</p><p>Le projet met en pratique l’architecture MVC, l’authentification, les opérations CRUD sécurisées avec PDO et requêtes préparées, ainsi que les contrôles côté client et serveur. Le développement est organisé en sprints avec GitHub et inclut documentation et fonctionnalités avancées comme l’import de données.</p></details>
          <div class="project-tags"><span>PHP</span><span>SQL</span><span>MVC</span><span>PDO</span></div>
          <button class="article-read" type="button" data-article-id="ap2-php-sql">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="development">
          <div class="article-meta"><span class="article-category">Développement</span><time datetime="2025-09-21">21 septembre 2025</time></div>
          <h3>AP C# SLAM</h3>
          <p class="article-summary">Application Windows Forms pour gérer bateaux, ports, traversées et billets. Les fonctionnalités sont construites progressivement en sprints.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>L’application de la Compagnie Océane s’appuie sur un TabControl et des classes métier pour organiser la gestion des bateaux, ports, traversées et billets. Les données sont manipulées en mémoire dans des collections.</p><p>Huit sprints couvrent notamment l’affichage et la modification des bateaux, la création des traversées, la billetterie, les contrôles de saisie et les statistiques de vente. Le projet applique la programmation orientée objet et événementielle.</p></details>
          <div class="project-tags"><span>C#</span><span>POO</span><span>Windows Forms</span></div>
          <button class="article-read" type="button" data-article-id="ap-csharp-slam">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="stage">
          <div class="article-meta"><span class="article-category">Mission de stage</span><time datetime="2025-06-23">23 juin 2025</time></div>
          <h3>Stage 1 · Mission 2 : conventions</h3>
          <p class="article-summary">Application web de gestion des conventions de stage, avec des accès adaptés aux étudiants, enseignants et personnels administratifs.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>L’application permet de créer, modifier, suivre et valider les conventions selon le rôle de l’utilisateur. Les routes et middlewares Laravel protègent les différentes sections.</p><p>La mise en service comprend l’installation des dépendances, la configuration de l’environnement, les migrations et les tests des droits. Les données sont validées côté serveur et les requêtes préparées contribuent à prévenir les injections SQL.</p></details>
          <div class="project-tags"><span>Laravel</span><span>PHP</span><span>Sécurité</span><span>Kanban</span></div>
          <button class="article-read" type="button" data-article-id="laravel-stage">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="stage">
          <div class="article-meta"><span class="article-category">Mission de stage</span><time datetime="2025-06-23">23 juin 2025</time></div>
          <h3>Stage 1 · Mission 1 : application mobile</h3>
          <p class="article-summary">Application React Native et Expo connectée à une API REST : consultation de bateaux, navigation vers les détails et ajout d’éléments.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>L’application pédagogique consomme une API externe pour afficher une liste de bateaux et leurs détails. React Navigation structure les écrans et un formulaire permet d’ajouter un élément.</p><p>Le travail couvre aussi l’affichage avec FlatList, les états de chargement et la gestion des erreurs réseau. Le projet se lance avec Expo et peut être testé sur Expo Go ou un émulateur, à condition que l’API soit accessible.</p></details>
          <div class="project-tags"><span>React Native</span><span>Expo</span><span>API REST</span></div>
          <button class="article-read" type="button" data-article-id="react-native-stage">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="systems">
          <div class="article-meta"><span class="article-category">Systèmes & support</span><time datetime="2025-03-17">17 mars 2025</time></div>
          <h3>AP M2L : réseau et serveur LAMP</h3>
          <p class="article-summary">Configuration d’un routeur virtuel, installation de Debian, Apache, PHP et MariaDB, puis déploiement et migration de l’application MRBS.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>Le TP commence par un routeur virtuel avec accès NAT et réseau interne, une adresse IP statique et un service DHCP. Des tests de connectivité vérifient la configuration.</p><p>La suite consiste à installer Debian et une pile LAMP, déployer MRBS pour la réservation de salles, puis migrer l’application et sa base de données vers une machine sécurisée en DMZ. PuTTY, WinSCP et phpMyAdmin sont utilisés au cours des étapes.</p></details>
          <div class="project-tags"><span>Debian</span><span>Réseau</span><span>LAMP</span><span>VirtualBox</span></div>
          <button class="article-read" type="button" data-article-id="m2l-infrastructure">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="development">
          <div class="article-meta"><span class="article-category">Développement web</span><time datetime="2025-03-16">16 mars 2025</time></div>
          <h3>AP Electronitech · HTML & CSS</h3>
          <p class="article-summary">Conception d’un site responsive pour un catalogue de produits, avec une navigation simplifiée, des filtres et une présentation adaptée au mobile.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>Le besoin est de moderniser le site d’Electronitech et de présenter les produits, leurs caractéristiques, marques et promotions dans une interface claire.</p><p>La démarche passe par l’analyse du cahier des charges, la conception des pages, l’intégration des filtres et formulaires, puis des tests sur plusieurs supports. Le projet aborde également la gestion du contenu, la documentation et la maintenance.</p></details>
          <div class="project-tags"><span>HTML</span><span>CSS</span><span>Responsive</span></div>
          <button class="article-read" type="button" data-article-id="electronitech-web">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="systems">
          <div class="article-meta"><span class="article-category">Systèmes & support</span><time datetime="2025-04-20">20 avril 2025</time></div>
          <h3>Gestion des incidents et demandes</h3>
          <p class="article-summary">Utilisation de GLPI pour gérer les tickets, les rôles, le suivi et l’escalade des incidents, et faciliter la collaboration entre techniciens.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>Le TP explore la consultation et le suivi des tickets GLPI, y compris ceux attribués à d’autres techniciens lorsque les permissions le permettent. Cette organisation aide à assurer la continuité du service.</p><p>La mise en place couvre les utilisateurs, profils, groupes, catégories de tickets, notifications, SLA et droits d’accès. Des tests fonctionnels vérifient que les demandes sont traitées de façon structurée et traçable.</p></details>
          <div class="project-tags"><span>GLPI</span><span>Support IT</span><span>Gestion des tickets</span></div>
          <button class="article-read" type="button" data-article-id="glpi-tickets">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="systems">
          <div class="article-meta"><span class="article-category">Systèmes & support</span><time datetime="2025-04-27">27 avril 2025</time></div>
          <h3>Gestion de parc informatique avec GLPI</h3>
          <p class="article-summary">Installation de GLPI sous Linux, inventaire manuel et automatique, organisation des équipements et découverte du suivi administratif du parc.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>Le travail pratique installe GLPI dans un environnement Linux avec les services nécessaires, puis organise l’inventaire des ordinateurs, imprimantes et consommables. L’agent GLPI automatise la remontée des informations.</p><p>Les équipements sont structurés par lieux et entités, avec des dictionnaires, tags et règles d’importation pour limiter les doublons. Le TP aborde aussi les coûts, contrats et cycles de vie du matériel.</p></details>
          <div class="project-tags"><span>GLPI</span><span>Linux</span><span>Inventaire</span></div>
          <button class="article-read" type="button" data-article-id="glpi-inventory">Lire l’article →</button>
        </article>

        <article class="article-card" data-category="portfolio">
          <div class="article-meta"><span class="article-category">Portfolio & parcours</span><time datetime="2025-10-08">8 octobre 2025</time></div>
          <h3>CV et empreinte numérique</h3>
          <p class="article-summary">Travaux pratiques autour de la création d’un CV professionnel avec Canva et de la maîtrise de son image et de ses traces sur LinkedIn.</p>
          <details class="article-details"><summary>Lire la fiche</summary><p>La première partie porte sur la structure d’un CV lisible et professionnel, adapté aux attentes du monde du travail. Canva sert à organiser et mettre en forme le document.</p><p>La seconde partie s’intéresse à l’empreinte numérique sur LinkedIn : les traces laissées sur la plateforme et l’image professionnelle qu’elles renvoient.</p></details>
          <div class="project-tags"><span>Canva</span><span>CV</span><span>LinkedIn</span></div>
          <button class="article-read" type="button" data-article-id="cv-empreinte">Lire l’article →</button>
        </article>
      </div>
      <p class="article-empty" id="articleEmpty" hidden>Aucun article ne correspond à cette recherche.</p>
  </div>
  <dialog class="article-dialog" id="articleDialog" aria-labelledby="articleDialogTitle">
    <div class="article-dialog-surface">
      <button class="article-dialog-close" id="closeArticleDialog" type="button" aria-label="Fermer l’article" title="Fermer">×</button>
      <div class="article-dialog-content" id="articleDialogContent"></div>
    </div>
  </dialog>
</section>

<section id="competences">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--indigo-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-5 3 3 5-7"/></svg>
      </div>
      <div><h2>Compétences</h2><p>Bloc B1</p></div>
      <div class="cube-scene" aria-hidden="true"><div class="cube"><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
    </div>

    <div class="skills-groups">
      <div class="skill-group"><h3>Langages et programmation</h3><div class="project-tags"><span>POO</span><span>C#</span><span>C++</span><span>Java</span><span>JavaScript</span><span>PHP</span><span>HTML</span><span>CSS</span></div></div>
      <div class="skill-group"><h3>Frameworks et applications</h3><div class="project-tags"><span>Laravel 12</span><span>Symfony</span><span>React Native</span><span>Node.js</span></div></div>
      <div class="skill-group"><h3>API et données</h3><div class="project-tags"><span>API REST</span><span>SQL</span><span>Merise</span><span>Postman</span></div></div>
      <div class="skill-group"><h3>Outils et environnements</h3><div class="project-tags"><span>Git / GitHub</span><span>npm</span><span>Docker</span><span>Visual Studio Code</span><span>Visual Studio</span><span>Windows</span><span>Linux · Ubuntu / Debian</span></div></div>
      <div class="skill-group"><h3>Cybersécurité</h3><div class="project-tags"><span>OWASP Top 10</span></div></div>
    </div>
  </div>
</section>

<section id="atouts">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--mint-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3z"/></svg>
      </div>
      <div><h2>Atouts</h2><p>Qualités, langues et certifications</p></div>
    </div>
    <div class="skills-groups">
      <div class="skill-group"><h3>Qualités</h3><div class="project-tags"><span>Esprit d’équipe</span><span>Esprit d’analyse</span><span>Adaptabilité</span><span>Organisation</span></div></div>
      <div class="skill-group"><h3>Langues</h3><div class="project-tags"><span>Anglais · bilingue</span></div></div>
      <div class="skill-group"><h3>Certifications</h3><div class="project-tags"><span>MOOC ANSSI</span><span>CNIL · RGPD</span><span>Cyber Awareness</span><span>Pix</span></div></div>
      <div class="skill-group"><h3>Centres d’intérêt</h3><div class="project-tags"><span>Boxe anglaise</span><span>Guitare électrique</span></div></div>
    </div>

    <h3 class="cert-heading">Mes certificats</h3>
    <div class="cert-slider" aria-roledescription="carrousel" aria-label="Mes certificats">
      <div class="cert-grid" tabindex="0">
        <a class="cert-card" href="images/certifications/anssi.jpg" target="_blank" rel="noopener">
          <img src="images/certifications/anssi.jpg" alt="Attestation de suivi SecNumacadémie de l'ANSSI délivrée à Jamar Carty" loading="lazy" width="1754" height="1241">
          <span class="cert-info"><strong>MOOC SecNumacadémie</strong><span>ANSSI · octobre 2023</span></span>
        </a>
        <a class="cert-card" href="images/certifications/cyber-awareness.jpg" target="_blank" rel="noopener">
          <img src="images/certifications/cyber-awareness.jpg" alt="Certificat Cyber Awareness Challenge 2025 délivré à Jamar Carty" loading="lazy" width="1754" height="1241">
          <span class="cert-info"><strong>Cyber Awareness Challenge 2025</strong><span>DoD Cyber Exchange · novembre 2024</span></span>
        </a>
        <a class="cert-card" href="images/certifications/pix.jpg" target="_blank" rel="noopener">
          <img src="images/certifications/pix.jpg" alt="Certification Pix, niveau Indépendant 2, délivrée à Jamar Carty" loading="lazy" width="1754" height="1241">
          <span class="cert-info"><strong>Certification Pix · 434 pix</strong><span>Pix · février 2026</span></span>
        </a>
        <a class="cert-card" href="images/certifications/cnil-1.jpg" target="_blank" rel="noopener">
          <img src="images/certifications/cnil-1.jpg" alt="Attestation CNIL, L'atelier RGPD, Module 1 · Le RGPD et ses notions clés, délivrée à Jamar Carty" loading="lazy" width="1754" height="1241">
          <span class="cert-info"><strong>CNIL · Module 1 · Le RGPD et ses notions clés</strong><span>L'atelier RGPD · 90 % · 28 octobre 2023</span></span>
        </a>
        <a class="cert-card" href="images/certifications/cnil-2.jpg" target="_blank" rel="noopener">
          <img src="images/certifications/cnil-2.jpg" alt="Attestation CNIL, L'atelier RGPD, Module 2 · Les principes de la protection des données, délivrée à Jamar Carty" loading="lazy" width="1754" height="1241">
          <span class="cert-info"><strong>CNIL · Module 2 · Les principes de la protection des données</strong><span>L'atelier RGPD · 100 % · 29 octobre 2023</span></span>
        </a>
        <a class="cert-card" href="images/certifications/cnil-3.jpg" target="_blank" rel="noopener">
          <img src="images/certifications/cnil-3.jpg" alt="Attestation CNIL, L'atelier RGPD, Module 3 · Les responsabilités des acteurs, délivrée à Jamar Carty" loading="lazy" width="1754" height="1241">
          <span class="cert-info"><strong>CNIL · Module 3 · Les responsabilités des acteurs</strong><span>L'atelier RGPD · 100 % · 29 octobre 2023</span></span>
        </a>
        <a class="cert-card" href="images/certifications/cnil-4.jpg" target="_blank" rel="noopener">
          <img src="images/certifications/cnil-4.jpg" alt="Attestation CNIL, L'atelier RGPD, Module 4 · Le DPO et les outils de la conformité, délivrée à Jamar Carty" loading="lazy" width="1754" height="1241">
          <span class="cert-info"><strong>CNIL · Module 4 · Le DPO et les outils de la conformité</strong><span>L'atelier RGPD · 93 % · 29 octobre 2023</span></span>
        </a>
        <a class="cert-card" href="images/certifications/cnil-5.png" target="_blank" rel="noopener">
          <img src="images/certifications/cnil-5.png" alt="Attestation CNIL, L'atelier RGPD, Module 5 · Les collectivités territoriales, délivrée à Jamar Carty" loading="lazy" width="1523" height="1523">
          <span class="cert-info"><strong>CNIL · Module 5 · Les collectivités territoriales</strong><span>L'atelier RGPD · 96 % · 29 octobre 2023</span></span>
        </a>
      </div>
      <div class="cert-controls">
        <button class="cert-nav" type="button" data-dir="-1" aria-label="Certificat précédent">‹</button>
        <div class="cert-dots" role="tablist"></div>
        <button class="cert-nav" type="button" data-dir="1" aria-label="Certificat suivant">›</button>
      </div>
    </div>
  </div>
</section>

<section id="veille">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--amber-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e5b567" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M11 7v4l3 2"/></svg>
      </div>
      <div><h2>Veille technologique</h2><p>Développement, réseaux et cybersécurité</p></div>
      <div class="cube-scene" aria-hidden="true"><div class="cube"><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
    </div>
    <div class="veille-grid">
      <div class="veille-copy">
        <h3>Rester curieux, rester à jour</h3>
        <p>Je suis les évolutions des technologies et des pratiques informatiques à l'aide d'Inoreader. Ma veille porte notamment sur le développement, les réseaux et la cybersécurité.</p>
      </div>
      <ul class="veille-links">
        <li><a href="https://thehackernews.com/2026/03/anthropic-finds-22-firefox.html" target="_blank" rel="noopener">Sécurité de Firefox <span>The Hacker News ↗</span></a></li>
        <li><a href="https://www.freecodecamp.org/news/how-to-use-docker-compose-for-production-workloads/" target="_blank" rel="noopener">Docker Compose en production <span>freeCodeCamp ↗</span></a></li>
        <li><a href="https://4d540f49-3025-4bac-87d5-74f740d12e4d.filesusr.com/ugd/f4edf3_02d5633a5eac46c7a41dccbc82e0c321.pdf" target="_blank" rel="noopener">Consulter ma veille technologique <span>PDF ↗</span></a></li>
      </ul>
    </div>
  </div>
</section>

<section id="contact">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--coral-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M4 6l8 7 8-7"/></svg>
      </div>
      <div><h2>Contact</h2><p>Discutons de votre besoin</p></div>
    </div>

    <div class="contact-wrap">
      <div class="contact-message">
        <h3>Un projet ou une opportunité ?</h3>
        <p>Pour échanger au sujet de mon parcours ou d'une collaboration, écrivez-moi directement.</p>
        <form class="contact-form" id="contactForm" action="contact.php" method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="field-trap" aria-hidden="true">
            <label for="contactWebsite">Ne pas remplir</label>
            <input id="contactWebsite" name="website" type="text" tabindex="-1" autocomplete="off">
          </div>
<?php foreach ([
    ['name', 'contactName', 'Nom', 'text', 'name', 'field', 80],
    ['email', 'contactEmail', 'E-mail', 'email', 'email', 'field', 254],
    ['subject', 'contactSubject', 'Objet', 'text', 'off', 'field field-wide', 120],
] as [$field, $id, $label, $type, $autocomplete, $class, $max]): ?>
          <div class="<?= $class ?><?= isset($contactErrors[$field]) ? ' has-error' : '' ?>">
            <label for="<?= $id ?>"><?= $label ?></label>
            <input id="<?= $id ?>" name="<?= $field ?>" type="<?= $type ?>" autocomplete="<?= $autocomplete ?>" maxlength="<?= $max ?>" required value="<?= e($contactOld[$field] ?? '') ?>" aria-describedby="<?= $id ?>Error"<?= isset($contactErrors[$field]) ? ' aria-invalid="true"' : '' ?>>
            <p class="field-error" id="<?= $id ?>Error" data-error-for="<?= $field ?>"><?= e($contactErrors[$field] ?? '') ?></p>
          </div>
<?php endforeach; ?>
          <div class="field field-wide<?= isset($contactErrors['message']) ? ' has-error' : '' ?>">
            <label for="contactMessage">Message</label>
            <textarea id="contactMessage" name="message" rows="5" maxlength="5000" required aria-describedby="contactMessageError"<?= isset($contactErrors['message']) ? ' aria-invalid="true"' : '' ?>><?= e($contactOld['message'] ?? '') ?></textarea>
            <p class="field-error" id="contactMessageError" data-error-for="message"><?= e($contactErrors['message'] ?? '') ?></p>
          </div>
          <button class="btn-primary contact-submit" type="submit"><span class="contact-submit-label">Envoyer le message →</span></button>
          <p class="contact-status" id="contactStatus" role="status" aria-live="polite" data-state="<?= e($contactFlash['status'] ?? '') ?>"><?= e($contactFlash['message'] ?? '') ?></p>
          <p class="contact-form-note">Votre message m’est envoyé directement par e-mail. Vos données servent uniquement à vous répondre.</p>
        </form>
      </div>
      <div class="contact-side">
        <div class="row">
          <div class="row-icon" style="background:var(--mint-soft);"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.4 2.8a2 2 0 0 1-.6 1.7L7.6 9.5a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 1.7-.6l2.8.4a2 2 0 0 1 1.6 1.9z"/></svg></div>
          <div class="row-text"><div class="k">Téléphone</div><a class="v" href="tel:+33767320432">07 67 32 04 32</a></div>
        </div>
        <div class="row">
          <div class="row-icon" style="background:var(--indigo-soft);"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 6l8 7 8-7"/></svg></div>
          <div class="row-text"><div class="k">Email</div><a class="v" href="mailto:jamarcarty131@gmail.com">jamarcarty131@gmail.com</a></div>
        </div>
        <div class="row">
          <div class="row-icon" style="background:var(--mint-soft);"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="3"/><circle cx="8" cy="9" r="1.2"/><path d="M8 12v5M12 12v5M12 14c0-1.5 1-2 2-2s2 .5 2 2v3"/></svg></div>
          <div class="row-text"><div class="k">LinkedIn</div><a class="v" href="http://www.linkedin.com/in/jamar-carty" target="_blank" rel="noopener">linkedin.com/in/jamar-carty</a></div>
        </div>
        <div class="row">
          <div class="row-icon" style="background:var(--indigo-soft);"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8l4 4-4 4"/></svg></div>
          <div class="row-text"><div class="k">GitHub</div><a class="v" href="https://github.com/jamar603" target="_blank" rel="noopener">github.com/jamar603</a></div>
        </div>
        <div class="row">
          <div class="row-icon" style="background:var(--coral-soft);"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3h7v7M10 14 21 3"/><path d="M19 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6"/></svg></div>
          <div class="row-text"><div class="k">Portfolio</div><a class="v" href="https://jamarcarty131.wixsite.com/portfoliojamarcarty" target="_blank" rel="noopener">Mon portfolio en ligne</a></div>
        </div>
        <div class="row">
          <div class="row-icon" style="background:var(--coral-soft);"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2"><path d="M12 21s-7-6-7-11a7 7 0 1114 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></div>
          <div class="row-text"><div class="k">Localisation</div><div class="v">Lyon, France</div></div>
        </div>
        <div class="row">
          <div class="row-icon" style="background:var(--indigo-soft);"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 12v6M9 15l3 3 3-3"/></svg></div>
          <div class="row-text"><div class="k">CV</div><a class="v" href="cv-jamar-carty.pdf" download="CV-Jamar-Carty.pdf">Télécharger mon CV (PDF)</a></div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <span>© <?= date('Y') ?> Jamar Carty</span>
    <span>BTS SIO — Option SLAM — AFIP</span>
    <a class="footer-admin" href="admin/" rel="nofollow"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Espace admin</a>
  </div>
</footer>
<?php if ($adminUnread !== null): ?>
<a class="admin-fab" href="admin/" data-notify-endpoint="admin/api.php" data-admin-url="admin/" data-latest-id="<?= $adminLatestId ?>" data-notify-icon="logo-jc.svg">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M4 6l8 7 8-7"/></svg>
  Admin <span class="admin-fab-count" data-unread-count="label"<?= $adminUnread > 0 ? '' : ' hidden' ?>><?= $adminUnread ?> non lu<?= $adminUnread > 1 ? 's' : '' ?></span>
</a>
<link rel="stylesheet" href="admin/notify.css">
<script src="admin/notify.js" defer></script>
<?php endif; ?>

<script src="script.js"></script>
<script src="articles.js"></script>
<script src="nav.js"></script>
<script src="background.js"></script>
<script src="cursor.js"></script>
<script src="certificats.js"></script>
<script src="motion.js"></script>
<script type="module" src="hero-3d.js"></script>

</body>
</html>
