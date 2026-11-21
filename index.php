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

// tableau de synthèse (bloc 1, épreuve E5): used by the E5 section, the table and the article filters
// each competence: full title, its skills, short label for the mobile chips
$synthCompetences = [
  ['Gérer le patrimoine informatique', ['Recenser et identifier les ressources numériques', 'Exploiter des référentiels, normes et standards adoptés par le prestataire informatique', 'Mettre en place et vérifier les niveaux d’habilitation associés à un service', 'Vérifier les conditions de la continuité d’un service informatique', 'Gérer des sauvegardes', 'Vérifier le respect des règles d’utilisation des ressources numériques'], 'Patrimoine informatique'],
  ['Répondre aux incidents et aux demandes d’assistance et d’évolution', ['Collecter, suivre et orienter des demandes', 'Traiter des demandes concernant les services réseau et système, applicatifs', 'Traiter des demandes concernant les applications'], 'Incidents et demandes'],
  ['Développer la présence en ligne de l’organisation', ['Participer à la valorisation de l’image de l’organisation sur les médias numériques en tenant compte du cadre juridique et des enjeux économiques', 'Référencer les services en ligne de l’organisation et mesurer leur visibilité', 'Participer à l’évolution d’un site Web exploitant les données de l’organisation'], 'Présence en ligne'],
  ['Travailler en mode projet', ['Analyser les objectifs et les modalités d’organisation d’un projet', 'Planifier les activités', 'Évaluer les indicateurs de suivi d’un projet et analyser les écarts'], 'Mode projet'],
  ['Mettre à disposition des utilisateurs un service informatique', ['Réaliser les tests d’intégration et d’acceptation d’un service', 'Déployer un service', 'Accompagner les utilisateurs dans la mise en place d’un service'], 'Service informatique'],
  ['Organiser son développement professionnel', ['Mettre en place son environnement d’apprentissage personnel', 'Mettre en œuvre des outils et stratégies de veille informationnelle', 'Gérer son identité professionnelle', 'Développer son projet professionnel'], 'Développement professionnel'],
];
// each row: title, article id opened on click, competences covered (1 = first column)
$synthRealisations = [
  'Réalisations en cours de formation' => [
    ['AP C#', 'ap-csharp-slam', [4]],
    ['AP M2L : installation du serveur LAMP', 'm2l-infrastructure', [5]],
    ['TP GLPI', 'glpi-inventory', [1]],
    ['TP Gestion des incidents et des demandes', 'glpi-tickets', [2]],
    ['AP Electronitech', 'electronitech-web', [3, 5]],
    ['AP2 Océane : PHP et SQL', 'ap2-php-sql', [3, 4]],
    ['AP Mediateq C#', 'mediateq-csharp', [2, 4]],
    ['TP CV et empreinte numérique', 'cv-empreinte', [3, 6]],
    ['TP Portfolio avec WordPress', 'portfolio-wordpress', [6]],
  ],
  'Réalisations en cours de stage de première année' => [
    ['Mission 1 : React Native et API', 'react-native-stage', [4, 6]],
    ['Mission 2 : Laravel 12', 'laravel-stage', [3, 4, 5, 6]],
  ],
  'Réalisations en cours de stage de seconde année' => [
    ['Mission 1 : Kadviser, modélisation 3D', 'kadviser-model3d', [4]],
    ['Mission 2 : amélioration du site web Kadviser', 'kadviser-themes', [4]],
  ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0b1120">
<title>Jamar Carty — Portfolio épreuve E5 · BTS SIO SLAM</title>
<meta name="description" content="Portfolio de Jamar Carty pour l’épreuve E5 du BTS SIO option SLAM à l’AFIP : parcours de professionnalisation, tableau de synthèse et réalisations couvrant les compétences du bloc 1.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="logo-jc.svg" type="image/svg+xml">
<link rel="stylesheet" href="style.css?v=<?= md5_file(__DIR__ . '/style.css') ?>">
</head>
<body>

<nav>
  <div class="container">
    <a class="logo" href="#top" aria-label="Jamar Carty, retour à l’accueil"><img class="logo-mark" src="logo-jc.svg" alt=""><span><span class="neon-name">Jamar</span> <span class="neon-name">Carty</span></span></a>
    <button class="nav-menu-toggle" type="button" aria-expanded="false" aria-controls="siteNavLinks">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/></svg>
      <span class="menu-toggle-label">Menu</span>
    </button>
    <div class="site-menu" id="siteNavLinks">
      <ul class="menu-list">
        <li class="menu-group" data-group="e5">
          <button type="button" class="menu-top" aria-expanded="false" aria-controls="menu-e5">Épreuve E5<svg class="menu-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
          <div class="menu-panel" id="menu-e5">
          <p class="menu-panel-k">Épreuve E5 · bloc 1</p>
          <a href="#epreuve-e5" data-t="epreuve-e5"><span class="menu-ico" style="--c:var(--indigo)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span><span class="menu-txt"><strong>Modalités</strong><small>Déroulé de l’oral, dossier, plan</small></span></a>
          <a href="#criteres-e5" data-t="criteres-e5"><span class="menu-ico" style="--c:var(--amber)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/></svg></span><span class="menu-txt"><strong>Critères évalués</strong><small>Les 6 compétences et leurs preuves</small></span></a>
          <a href="#synthese" data-t="synthese"><span class="menu-ico" style="--c:var(--amber)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg></span><span class="menu-txt"><strong>Tableau de synthèse</strong><small>Réalisations × compétences</small></span></a>
          <a href="#conclusion" data-t="conclusion"><span class="menu-ico" style="--c:var(--green)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></span><span class="menu-txt"><strong>Conclusion</strong><small>Bilan et message au jury</small></span></a>
          <button type="button" class="menu-present" data-present-start><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>Lancer le mode présentation<kbd>P</kbd></button>
          </div>
        </li>
        <li class="menu-group" data-group="parcours">
          <button type="button" class="menu-top" aria-expanded="false" aria-controls="menu-parcours">Parcours<svg class="menu-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
          <div class="menu-panel" id="menu-parcours">
          <p class="menu-panel-k">Mon parcours</p>
          <a href="#profil" data-t="profil"><span class="menu-ico" style="--c:var(--indigo)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg></span><span class="menu-txt"><strong>Profil</strong><small>Qui je suis, mon objectif</small></span></a>
          <a href="#formation" data-t="formation"><span class="menu-ico" style="--c:var(--coral)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-5 10 5-10 5-10-5z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/></svg></span><span class="menu-txt"><strong>Formation</strong><small>BTS SIO SLAM, ICOF puis AFIP</small></span></a>
          <a href="#motivation" data-t="motivation"><span class="menu-ico" style="--c:var(--green)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg></span><span class="menu-txt"><strong>Motivation</strong><small>Ce qui me fait avancer</small></span></a>
          <a href="#experience" data-t="experience"><span class="menu-ico" style="--c:var(--amber)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></span><span class="menu-txt"><strong>Expérience</strong><small>Stages et missions</small></span></a>
          </div>
        </li>
        <li class="menu-group" data-group="realisations">
          <button type="button" class="menu-top" aria-expanded="false" aria-controls="menu-realisations">Réalisations<svg class="menu-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
          <div class="menu-panel" id="menu-realisations">
          <p class="menu-panel-k">Mes réalisations</p>
          <a href="#projets" data-t="projets"><span class="menu-ico" style="--c:var(--mint)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/></svg></span><span class="menu-txt"><strong>Projets</strong><small>Les réalisations principales</small></span></a>
          <a href="#articles" data-t="articles"><span class="menu-ico" style="--c:var(--coral)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14.5"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H20M8 7h8M8 11h8"/></svg></span><span class="menu-txt"><strong>Articles & TP</strong><small>13 fiches détaillées avec captures</small></span></a>
          </div>
        </li>
        <li class="menu-group" data-group="profil-pro">
          <button type="button" class="menu-top" aria-expanded="false" aria-controls="menu-profil-pro">Compétences<svg class="menu-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
          <div class="menu-panel" id="menu-profil-pro">
          <p class="menu-panel-k">Compétences et veille</p>
          <a href="#competences" data-t="competences"><span class="menu-ico" style="--c:var(--indigo)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-5 3 3 5-7"/></svg></span><span class="menu-txt"><strong>Compétences techniques</strong><small>Langages, frameworks, outils</small></span></a>
          <a href="#atouts" data-t="atouts"><span class="menu-ico" style="--c:var(--mint)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3z"/></svg></span><span class="menu-txt"><strong>Atouts & certificats</strong><small>Qualités, langues, attestations</small></span></a>
          <a href="#veille" data-t="veille"><span class="menu-ico" style="--c:var(--amber)" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M11 7v4l3 2"/></svg></span><span class="menu-txt"><strong>Veille technologique</strong><small>Inoreader, sources suivies</small></span></a>
          </div>
        </li>
      </ul>
      <div class="menu-end">
        <a class="menu-social" href="https://www.linkedin.com/in/jamar-carty" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn de Jamar Carty" title="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.35V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"/></svg><span>LinkedIn</span></a>
        <a class="menu-social" href="https://github.com/jamar603" target="_blank" rel="noopener noreferrer" aria-label="GitHub de Jamar Carty" title="GitHub"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5A11.5 11.5 0 0 0 8.36 22.91c.58.1.79-.25.79-.56v-2.02c-3.2.7-3.87-1.36-3.87-1.36-.52-1.33-1.28-1.69-1.28-1.69-1.04-.71.08-.7.08-.7 1.15.08 1.75 1.18 1.75 1.18 1.02 1.74 2.68 1.24 3.33.95.1-.74.4-1.24.73-1.53-2.55-.29-5.23-1.28-5.23-5.68 0-1.26.45-2.28 1.18-3.08-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.17a10.9 10.9 0 0 1 5.74 0c2.19-1.48 3.15-1.17 3.15-1.17.62 1.58.23 2.75.11 3.04.73.8 1.18 1.82 1.18 3.08 0 4.41-2.68 5.38-5.24 5.67.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5z"/></svg><span>GitHub</span></a>
        <a class="menu-cta" href="#contact" data-t="contact">Contact<span aria-hidden="true">→</span></a>
      </div>
    </div>
  </div>
</nav>

<section class="hero" id="accueil" data-step-label="Accueil">
  <div class="container">
    <div class="hero-grid">
      <div class="hero-copy">
        <a class="hero-school" href="#formation" aria-label="Formation actuelle : BTS SIO option SLAM à l’AFIP">
          <img src="images/logo-afip.svg" alt="" width="153" height="102">
          <span><strong>Formation actuelle</strong> BTS SIO · option SLAM · AFIP 2026–2027</span>
        </a>
        <div class="badge"><span class="d"></span> Épreuve E5 · BTS SIO option SLAM</div>
        <h1>
          <span class="neon-name">Jamar</span> <span class="neon-name">Carty</span><br>
          <span class="sw"><span class="sr-only">Support et mise à disposition de services informatiques</span><span aria-hidden="true">Portfolio <span class="hero-rotate" data-words="Épreuve E5|Bloc 1|BTS SIO SLAM">Épreuve E5</span></span></span>
        </h1>
        <p class="hero-sub">Étudiant en deuxième année de BTS SIO option SLAM à l’AFIP, je présente ici mon parcours de professionnalisation et les réalisations qui démontrent les compétences du bloc 1 : <strong>Support et mise à disposition de services informatiques</strong>.</p>
        <div class="hero-cta">
          <a href="#epreuve-e5" class="btn-primary">Dossier épreuve E5 →</a>
          <a href="#projets" class="btn-outline">Voir mes projets</a>
          <a href="CVJAMAR-C.pdf" class="btn-outline btn-cv" download="CV-Jamar-Carty.pdf"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg> Télécharger mon CV</a>
        </div>
      </div>

      <div class="hero-visual">
        <div class="hero-window">
          <div class="hero-window-bar" aria-hidden="true"><i></i><i></i><i></i><span>jamar@portfolio: ~/projets</span></div>
          <div class="hero-art" id="heroScene" role="img" aria-label="Ordinateur portable 3D affichant du code">
            <p class="hero-scene-fallback" aria-hidden="true">Ordinateur 3D · développement web</p>
          </div>
        </div>
        <pre class="hero-term" aria-hidden="true"><code><b>$</b> whoami
<em>jamar.carty</em> · BTS SIO SLAM
<b>$</b> cat epreuve.json
{ <s>"epreuve"</s>: <s>"E5"</s>, <s>"bloc"</s>: <u>1</u> }</code></pre>
      </div>
    </div>

    <ul class="hero-bento">
      <li>
        <span class="hb-k">Formation</span>
        <strong>BTS SIO · SLAM · 2<sup>e</sup> année</strong>
        <span class="hb-v">AFIP · session 2026</span>
      </li>
      <li>
        <span class="hb-k">Alternance</span>
        <strong>2 sem. / 2 sem.</strong>
        <span class="hb-v">entreprise · formation</span>
      </li>
      <li>
        <span class="hb-k">Réalisations</span>
        <strong><span data-count="13">13</span> projets</strong>
        <a class="hb-v hb-link" href="#synthese">dont 4 missions de stage →</a>
      </li>
      <li class="hb-stack">
        <span class="hb-k">Stack principale</span>
        <span class="hb-chips"><span>Laravel</span><span>PHP</span><span>React Native</span><span>C#</span><span>SQL</span></span>
      </li>
    </ul>
  </div>
  <a class="hero-scroll" href="#profil" aria-label="Défiler vers le profil"><span></span></a>
</section>

<div class="stack-band" role="region" aria-label="Technologies utilisées">
  <div class="stack-track">
    <ul><li>Laravel 12</li><li>PHP</li><li>React Native</li><li>JavaScript</li><li>C#</li><li>SQL</li><li>API REST</li><li>Node.js</li><li>Symfony</li><li>Docker</li><li>Git / GitHub</li><li>Linux</li><li>Modélisation 3D</li><li>OWASP Top 10</li></ul>
    <ul aria-hidden="true"><li>Laravel 12</li><li>PHP</li><li>React Native</li><li>JavaScript</li><li>C#</li><li>SQL</li><li>API REST</li><li>Node.js</li><li>Symfony</li><li>Docker</li><li>Git / GitHub</li><li>Linux</li><li>Modélisation 3D</li><li>OWASP Top 10</li></ul>
  </div>
</div>

<?php
// which réalisations prove each competence (from the tableau de synthèse)
$e5Coverage = array_fill(1, count($synthCompetences), []);
foreach ($synthRealisations as $rows) {
  foreach ($rows as [$title, $article, $covered]) {
    foreach ($covered as $c) $e5Coverage[$c][] = [$title, $article];
  }
}
$e5Covered = count(array_filter($e5Coverage));
$e5Total = array_sum(array_map('count', $synthRealisations));
// oral plan: phase 1 lasts 10 minutes at most
$e5Plan = [
  ['#profil', 'Présentation et parcours', 'Profil, formation ICOF puis AFIP, projet professionnel', '1 min 30'],
  ['#experience', 'Parcours de professionnalisation', 'Stages de 1re et 2e année, missions réalisées', '2 min'],
  ['#synthese', 'Tableau de synthèse', 'Réalisations et couverture des 6 compétences du bloc 1', '2 min'],
  ['#articles', 'Réalisations commentées', 'Deux ou trois réalisations : contexte, démarche, preuves, contribution personnelle', '3 min 30'],
  ['#veille', 'Développement professionnel', 'Veille technologique, certifications, identité numérique', '1 min'],
];
?>
<section id="epreuve-e5" class="e5">
  <div class="container">
    <div class="section-head e5-head">
      <img class="e5-logo" src="images/logo-afip.svg" alt="AFIP" width="153" height="102">
      <div>
        <h2>Épreuve E5</h2>
        <p>Support et mise à disposition de services informatiques · BTS SIO option SLAM · AFIP 2026–2027</p>
      </div>
    </div>

    <ul class="e5-facts" aria-label="Modalités de l’épreuve E5">
      <li><span class="e5-k">Bloc évalué</span><strong>Bloc 1</strong><span class="e5-v">6 compétences</span></li>
      <li><span class="e5-k">Coefficient</span><strong>4</strong><span class="e5-v">contrôle en cours de formation</span></li>
      <li><span class="e5-k">Forme</span><strong>Oral · 40 min</strong><span class="e5-v">10 min de présentation · 30 min d’échange</span></li>
      <li><span class="e5-k">Commission</span><strong>2 membres</strong><span class="e5-v">un enseignant de l’option et un professionnel</span></li>
    </ul>

    <div class="e5-phases">
      <div class="e5-phase">
        <span class="e5-phase-time">Phase 1 · 10 min max</span>
        <h3>Présentation du parcours</h3>
        <p>Le candidat présente son parcours de professionnalisation et explique comment il a acquis les compétences du bloc 1, à l’appui de son portfolio.</p>
        <button class="btn-primary e5-start" type="button" data-present-start>Lancer le mode présentation</button>
      </div>
      <div class="e5-phase">
        <span class="e5-phase-time">Phase 2 · 30 min</span>
        <h3>Échange avec la commission</h3>
        <p>Retour sur le parcours, approfondissement d’une ou plusieurs réalisations choisies par la commission, et mise en évidence de la contribution personnelle au sein d’un travail d’équipe.</p>
        <a class="btn-outline" href="#articles">Ouvrir les réalisations</a>
      </div>
    </div>

    <div class="e5-grid">
      <div class="e5-block">
        <h3>Ce que contient le dossier</h3>
        <ul class="e5-checklist">
          <li><span class="e5-tick" aria-hidden="true">✓</span><div><strong>Portfolio numérique</strong> retraçant le parcours et décrivant chaque réalisation avec les compétences mobilisées. <a href="#articles"><?= $e5Total ?> réalisations</a></div></li>
          <li><span class="e5-tick" aria-hidden="true">✓</span><div><strong>Tableau de synthèse</strong> des réalisations professionnelles, modèle national. <a href="#synthese">Voir le tableau</a> · <a href="tableau-synthese-bts-sio.pdf" target="_blank" rel="noopener">PDF</a></div></li>
          <li><span class="e5-tick" aria-hidden="true">✓</span><div><strong>Couverture du bloc 1</strong> : <?= $e5Covered ?> compétences sur <?= count($synthCompetences) ?> mises en œuvre dans au moins une réalisation.</div></li>
          <li><span class="e5-tick" aria-hidden="true">✓</span><div><strong>Preuves</strong> : captures, documentation technique, procédures et code présentés dans chaque article. <a href="documentation-technique-mediateq.pdf" target="_blank" rel="noopener">Exemple de documentation</a></div></li>
          <li><span class="e5-tick e5-tick-doc" aria-hidden="true">✓</span><div><strong>Attestations de stage</strong> : remises à la commission avec le dossier.</div></li>
        </ul>
      </div>

      <div class="e5-block">
        <h3>Plan de ma présentation <small>phase 1 · 10 min</small></h3>
        <ol class="e5-plan">
<?php foreach ($e5Plan as $i => [$href, $title, $detail, $time]): ?>
          <li><a href="<?= e($href) ?>"><span class="e5-plan-num"><?= $i + 1 ?></span><span class="e5-plan-text"><strong><?= e($title) ?></strong><span><?= e($detail) ?></span></span><span class="e5-plan-time"><?= e($time) ?></span></a></li>
<?php endforeach; ?>
        </ol>
      </div>
    </div>
  </div>
</section>

<section id="criteres-e5" class="e5 e5-criteria" data-step-label="Critères E5">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--amber-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e5b567" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/></svg>
      </div>
      <div><h2>Critères évalués</h2><p>Les 6 compétences du bloc 1 : savoir-faire du référentiel et réalisations qui les prouvent. Cliquez sur une réalisation pour l’ouvrir.</p></div>
    </div>
    <div class="e5-comps">
<?php foreach ($synthCompetences as $i => [$label, $skills, $short]): ?>
      <article class="e5-comp">
        <div class="e5-comp-top"><span class="synth-num">C<?= $i + 1 ?></span><span class="e5-comp-count"><?= count($e5Coverage[$i + 1]) ?> réalisation<?= count($e5Coverage[$i + 1]) > 1 ? 's' : '' ?></span></div>
        <h4><?= e($label) ?></h4>
        <ul class="e5-skills">
<?php foreach ($skills as $skill): ?>
          <li><?= e($skill) ?></li>
<?php endforeach; ?>
        </ul>
        <div class="e5-proofs">
<?php foreach ($e5Coverage[$i + 1] as [$title, $article]): ?>
          <a href="#articles" data-open-article="<?= e($article) ?>"><?= e($title) ?></a>
<?php endforeach; ?>
        </div>
      </article>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section id="profil">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--indigo-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
      </div>
      <div><h2>Profil</h2><p>Qui je suis, en quelques lignes</p></div>
    </div>
    <div class="profile-grid">
<?php
// profile photo: the first image found in images/ named profil.* (same photo as LinkedIn); initials until it exists
$profilePhoto = null;
foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
  if (is_file(__DIR__ . "/images/profil.$ext")) { $profilePhoto = "images/profil.$ext"; break; }
}
?>
      <div class="profile-intro">
        <div class="profile-id">
          <figure class="profile-photo">
  <?php if ($profilePhoto): ?>
            <img src="<?= e($profilePhoto) ?>?v=<?= filemtime(__DIR__ . '/' . $profilePhoto) ?>" alt="Photo de Jamar Carty" width="400" height="400">
  <?php else: ?>
            <span class="profile-photo-initials" aria-hidden="true">JC</span>
  <?php endif; ?>
            <a class="profile-photo-badge" href="https://www.linkedin.com/in/jamar-carty" target="_blank" rel="noopener noreferrer" aria-label="Profil LinkedIn de Jamar Carty"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.35V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.555V9h3.564v11.452z"/></svg></a>
          </figure>
          <a class="profile-github" href="https://github.com/jamar603" target="_blank" rel="noopener noreferrer" aria-label="GitHub de Jamar Carty : github.com/jamar603">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5A11.5 11.5 0 0 0 8.36 22.91c.58.1.79-.25.79-.56v-2.02c-3.2.7-3.87-1.36-3.87-1.36-.52-1.33-1.28-1.69-1.28-1.69-1.04-.71.08-.7.08-.7 1.15.08 1.75 1.18 1.75 1.18 1.02 1.74 2.68 1.24 3.33.95.1-.74.4-1.24.73-1.53-2.55-.29-5.23-1.28-5.23-5.68 0-1.26.45-2.28 1.18-3.08-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.17a10.9 10.9 0 0 1 5.74 0c2.19-1.48 3.15-1.17 3.15-1.17.62 1.58.23 2.75.11 3.04.73.8 1.18 1.82 1.18 3.08 0 4.41-2.68 5.38-5.24 5.67.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5z"/></svg>
            <span><strong>GitHub</strong><small>github.com/jamar603</small></span>
          </a>
        </div>
        <div class="profile-intro-main">
          <div class="profile-school">
            <img src="images/logo-afip.svg" alt="AFIP" width="153" height="102">
            <div><strong>BTS SIO · option SLAM · 2<sup>e</sup> année</strong><span>AFIP · 2026–2027 · épreuve E5, bloc 1</span></div>
          </div>
          <blockquote>« Ce portfolio retrace mon parcours de professionnalisation : chaque réalisation montre comment j’ai acquis les compétences du bloc 1. »</blockquote>
        </div>
      </div>
      <div class="profile-facts">
        <article class="profile-fact">
          <h3 class="profile-fact-k">Mon parcours</h3>
          <ol class="profile-steps">
            <li><span class="profile-step-y">2024 – 2026</span><span><strong>BTS SIO SLAM à l’ICOF</strong> · plusieurs épreuves validées</span></li>
            <li class="is-now"><span class="profile-step-y">2026 – 2027</span><span><strong>2<sup>e</sup> année à l’AFIP</strong> · épreuves restantes</span></li>
            <li><span class="profile-step-y">2027</span><span><strong>Obtention du BTS SIO</strong></span></li>
          </ol>
        </article>
        <article class="profile-fact">
          <h3 class="profile-fact-k">Compétences du bloc 1</h3>
          <p class="profile-fact-lead">Les <strong>6 compétences</strong> sont couvertes par mes réalisations.</p>
          <ul class="profile-comps">
<?php foreach ($synthCompetences as $i => [$label, $skills, $short]): ?>
            <li><a href="#synthese" title="<?= e($label) ?>"><span class="synth-num">C<?= $i + 1 ?></span><?= e($short) ?></a></li>
<?php endforeach; ?>
          </ul>
        </article>
        <article class="profile-fact">
          <h3 class="profile-fact-k">Technologies utilisées</h3>
          <div class="project-tags profile-tech"><span>Laravel</span><span>PHP</span><span>React Native</span><span>API REST</span><span>C#</span><span>SQL</span><span>GLPI</span><span>Modélisation 3D</span></div>
        </article>
        <article class="profile-fact">
          <h3 class="profile-fact-k">Mon objectif</h3>
          <p class="profile-fact-lead"><strong>Alternance en développement informatique</strong></p>
          <p>Rythme : deux semaines en entreprise, deux semaines en formation.</p>
        </article>
      </div>
    </div>
  </div>
</section>

<section id="formation">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--coral-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-5 10 5-10 5-10-5z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/></svg>
      </div>
      <div><h2>Formation</h2><p>BTS SIO SLAM · 2<sup>e</sup> année · AFIP, 2026–2027</p></div>
    </div>

    <div class="branch-grid">
      <div class="branch-card current">
        <img class="branch-logo" src="images/logo-afip.svg" alt="AFIP" width="153" height="102">
        <div class="branch-top"><h4>BTS SIO · option SLAM</h4><span class="pill-current">En cours</span></div>
        <p class="branch-summary">AFIP · 2026–2027 · 2<sup>e</sup> année · rattrapage des épreuves restantes pour obtenir le BTS en 2027</p>
      </div>
      <div class="branch-card">
        <div class="branch-top"><h4>BTS SIO · option SLAM</h4><span class="pill-validated"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>Épreuves validées</span></div>
        <p class="branch-summary">ICOF · 2024–2026 · plusieurs épreuves du BTS déjà validées</p>
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

<section id="motivation">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--green-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6ee7b7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>
      </div>
      <div><h2>Motivation</h2><p>Ce qui me fait avancer</p></div>
    </div>
    <figure class="motivation-card">
      <blockquote>
        <p>Personne n’est parfait. Mon parcours n’a pas toujours été linéaire, et il comportera encore des défis.</p>
        <p>Mais chaque obstacle me rend plus solide. Je ne lâche rien : j’avance, <strong>un pas après l’autre</strong>, avec la volonté d’apprendre et de réussir.</p>
      </blockquote>
      <figcaption>Jamar Carty</figcaption>
    </figure>
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
        <div class="project-icon" style="background:var(--amber-soft);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e5b567" stroke-width="2" stroke-linecap="round"><path d="M4 19V5a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2H6a2 2 0 01-2-2z"/><path d="M14 3v6h6"/></svg></div>
        <div class="project-title">Application Mediateq</div>
        <div class="project-desc">Application Windows Forms organisée en onglets pour gérer plusieurs fonctionnalités métier.</div>
        <div class="project-tags"><span>C#</span><span>Windows Forms</span></div>
        <a class="project-link" href="#articles" data-open-article="mediateq-csharp">Lire le projet →</a>
      </div>
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

<section id="synthese">
  <div class="container">
    <div class="section-head">
      <div class="section-icon" style="background:var(--amber-soft);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e5b567" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
      </div>
      <div><h2>Tableau de synthèse</h2><p>BTS SIO option SLAM · session 2026 · réalisations professionnelles</p></div>
    </div>

    <div class="synth-tools">
      <div class="synth-chips" role="group" aria-label="Mettre en évidence une compétence du bloc 1">
        <button type="button" data-focus="0" aria-pressed="true" aria-keyshortcuts="0">Vue d’ensemble</button>
<?php foreach ($synthCompetences as $i => [$label, $skills, $short]): ?>
        <button type="button" data-focus="<?= $i + 1 ?>" aria-pressed="false" aria-keyshortcuts="<?= $i + 1 ?>" title="<?= e($label) ?>"><span class="synth-num">C<?= $i + 1 ?></span><span class="synth-chip-label"><?= e($short) ?></span><span class="synth-chip-count"><?= count($e5Coverage[$i + 1]) ?></span></button>
<?php endforeach; ?>
      </div>
      <button type="button" class="synth-full" data-synth-full aria-pressed="false">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
        <span>Plein écran</span>
      </button>
    </div>

<?php foreach ($synthCompetences as $i => [$label, $skills, $short]): ?>
    <div class="synth-focus" data-panel="<?= $i + 1 ?>" hidden>
      <div class="synth-focus-head">
        <span class="synth-num">C<?= $i + 1 ?></span>
        <h3><?= e($label) ?></h3>
        <span class="synth-focus-count"><?= count($e5Coverage[$i + 1]) ?> réalisation<?= count($e5Coverage[$i + 1]) > 1 ? 's' : '' ?></span>
      </div>
      <ul>
<?php foreach ($skills as $skill): ?>
        <li><?= e($skill) ?></li>
<?php endforeach; ?>
      </ul>
    </div>
<?php endforeach; ?>

<?php
// preview data for the table "loupe" (synthese.js): title, summary, cover and date of each article
$synthPreview = [];
$articlesJson = json_decode((string) @file_get_contents(__DIR__ . '/articles.json'), true) ?: [];
foreach ($articlesJson as $item) {
  if (!isset($item['id'])) continue;
  $synthPreview[$item['id']] = [
    'title' => $item['title'] ?? '',
    'summary' => $item['summary'] ?? '',
    'cover' => $item['cover'] ?? '',
    'coverAlt' => $item['coverAlt'] ?? '',
    'date' => $item['date'] ?? '',
  ];
}
?>
    <script type="application/json" id="synthPreviewData"><?= json_encode(['articles' => $synthPreview, 'competences' => array_map(fn($c) => $c[2], $synthCompetences)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <div class="synth-wrap" tabindex="0" role="region" aria-label="Tableau de synthèse des réalisations professionnelles, défilable horizontalement">
      <table class="synth-table">
        <caption class="sr-only">Compétences du bloc 1 mises en œuvre dans chaque réalisation professionnelle</caption>
        <thead>
          <tr>
            <th scope="col" class="synth-corner">Réalisations professionnelles</th>
<?php foreach ($synthCompetences as $i => [$label, $skills, $short]): ?>
            <th scope="col" data-col="<?= $i + 1 ?>" title="<?= e($label . ' : ' . implode(' · ', $skills)) ?>"><span class="synth-num">C<?= $i + 1 ?></span><span class="sr-only"><?= e($label) ?></span><span aria-hidden="true"><?= e($short) ?></span></th>
<?php endforeach; ?>
          </tr>
        </thead>
<?php foreach ($synthRealisations as $period => $rows): ?>
        <tbody>
          <tr class="synth-period"><th scope="rowgroup" colspan="<?= count($synthCompetences) + 1 ?>"><?= e($period) ?> <span class="synth-period-count"><?= count($rows) ?></span></th></tr>
<?php foreach ($rows as [$title, $article, $covered]): ?>
          <tr data-covers="<?= e(implode(' ', $covered)) ?>" data-article="<?= e($article) ?>" data-period="<?= e($period) ?>">
            <th scope="row"><a href="#articles" data-open-article="<?= e($article) ?>"><?= e($title) ?></a></th>
<?php for ($c = 1; $c <= count($synthCompetences); $c++): ?>
<?php if (in_array($c, $covered, true)): ?>
            <td class="is-on" data-col="<?= $c ?>" data-c="C<?= $c ?>" data-label="<?= e($synthCompetences[$c - 1][2]) ?>"><span class="synth-check" aria-hidden="true">✓</span><span class="sr-only">Oui</span></td>
<?php else: ?>
            <td data-col="<?= $c ?>"><span class="sr-only">Non</span></td>
<?php endif; ?>
<?php endfor; ?>
          </tr>
<?php endforeach; ?>
        </tbody>
<?php endforeach; ?>
        <tfoot>
          <tr>
            <th scope="row">Réalisations par compétence</th>
<?php foreach ($synthCompetences as $i => $competence): ?>
            <td data-col="<?= $i + 1 ?>"><span class="synth-total"><?= count($e5Coverage[$i + 1]) ?></span></td>
<?php endforeach; ?>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="synth-skills">
<?php foreach ($synthCompetences as $i => [$label, $skills]): ?>
      <details>
        <summary><span class="synth-num">C<?= $i + 1 ?></span><?= e($label) ?></summary>
        <ul>
<?php foreach ($skills as $skill): ?>
          <li><?= e($skill) ?></li>
<?php endforeach; ?>
        </ul>
      </details>
<?php endforeach; ?>
    </div>

    <p class="synth-hint">Choisissez une compétence ou cliquez sur une colonne pour voir les réalisations qui la prouvent. En présentation : touches <kbd>1</kbd> à <kbd>6</kbd>, <kbd>0</kbd> pour tout afficher.</p>
    <div class="synth-actions">
      <a class="btn-outline" href="tableau-synthese-bts-sio.pdf" target="_blank" rel="noopener">Ouvrir le tableau officiel (PDF)</a>
      <a class="btn-outline" href="tableau-synthese-bts-sio.pdf" download>Télécharger le PDF</a>
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
      <div class="article-toolbar" role="search">
        <label class="article-search" for="articleSearch">
          <span class="sr-only">Rechercher un article</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
          <input type="search" id="articleSearch" placeholder="Titre, technologie, sujet…" autocomplete="off">
        </label>
<?php
// article filters come from the tableau de synthèse: period = row group, competences = ticked columns
$synthPeriods = [['formation', 'Formation'], ['stage1', 'Stage 1re année'], ['stage2', 'Stage 2e année']];
$synthArticleMap = [];
foreach (array_values($synthRealisations) as $p => $rows) {
  foreach ($rows as [$title, $article, $covered]) {
    $synthArticleMap[$article] = ['period' => $synthPeriods[$p][0], 'periodLabel' => $synthPeriods[$p][1], 'competences' => $covered];
  }
}
?>
        <div class="article-chips" id="articlePeriod" role="group" aria-label="Filtrer par période du tableau de synthèse">
          <button type="button" data-filter="all" aria-pressed="true">Toutes</button>
<?php foreach ($synthPeriods as [$slug, $label]): ?>
          <button type="button" data-filter="<?= e($slug) ?>" aria-pressed="false"><?= e($label) ?></button>
<?php endforeach; ?>
        </div>
        <button class="article-reset" id="resetArticles" type="button" hidden>Réinitialiser</button>
        <p class="article-count" id="articleCount" aria-live="polite">13 articles</p>
      </div>
      <div class="article-chips article-chips-comp" id="articleCompetence" role="group" aria-label="Filtrer par compétence du tableau de synthèse">
        <button type="button" data-filter="all" aria-pressed="true">Toutes les compétences</button>
<?php foreach ($synthCompetences as $i => [$label, $skills, $short]): ?>
        <button type="button" data-filter="<?= $i + 1 ?>" aria-pressed="false" title="<?= e($label) ?>"><span class="chip-num">C<?= $i + 1 ?></span><?= e($short) ?></button>
<?php endforeach; ?>
      </div>
      <script type="application/json" id="articleSynth"><?= json_encode(['articles' => $synthArticleMap, 'competences' => array_map(fn($c) => ['label' => $c[0], 'short' => $c[2], 'skills' => $c[1]], $synthCompetences)], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
      <p class="article-status" id="articleStatus" role="status" aria-live="polite">Chargement des articles…</p>
      <div class="article-rail-wrap">
      <button type="button" class="article-rail-btn article-rail-prev" id="articlePrev" aria-label="Articles précédents" disabled>‹</button>
      <button type="button" class="article-rail-btn article-rail-next" id="articleNext" aria-label="Articles suivants">›</button>
      <div class="article-grid article-rail" id="articleGrid" tabindex="-1">
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
          <details class="article-details"><summary>Lire la fiche</summary><p>Le projet vise à protéger l’accès de l’application et à faciliter le suivi administratif des abonnés. L’authentification identifie les employés avant l’accès aux fonctionnalités sensibles.</p><p>Le module abonnés permet la création, la consultation, la modification et la suppression. Des règles métier signalent les abonnements arrivant à expiration. Les requêtes SQL paramétrées et la gestion des rôles contribuent à sécuriser l’application.</p><p><a class="article-download" href="documentation-technique-mediateq.pdf" download>Télécharger la documentation technique <small>PDF · 2,6 Mo</small></a></p></details>
          <div class="project-tags"><span>C#</span><span>Windows Forms</span><span>MySQL</span><span>POO</span><span>Sécurité</span></div>
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
      </div>
      <div class="article-rail-foot" aria-hidden="true">
        <span class="article-rail-count" id="articleRailCount">13 articles</span>
        <span class="article-rail-track"><span class="article-rail-thumb" id="articleRailThumb"></span></span>
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
      <div class="skill-group"><h3>Langages et programmation</h3><div class="project-tags"><span>POO</span><span>C#</span><span>C++</span><span>Java</span><span>JavaScript</span><span>TypeScript</span><span>PHP</span><span>Python</span><span>HTML</span><span>CSS</span></div></div>
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
    <div class="atouts-grid">
      <article class="atout-card">
        <span class="atout-icon" style="background:var(--indigo-soft);" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M21.5 20c0-2.8-1.6-4.8-4-5.6"/></svg></span>
        <h3>Qualités</h3>
        <div class="project-tags"><span>Esprit d’équipe</span><span>Esprit d’analyse</span><span>Adaptabilité</span><span>Organisation</span></div>
      </article>
      <article class="atout-card">
        <span class="atout-icon" style="background:var(--green-soft);" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6ee7b7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/></svg></span>
        <h3>Langues</h3>
        <div class="project-tags"><span>Anglais · bilingue</span></div>
      </article>
      <article class="atout-card">
        <span class="atout-icon" style="background:var(--amber-soft);" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e5b567" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="M8.5 13.8 7 22l5-3 5 3-1.5-8.2"/></svg></span>
        <h3>Certifications</h3>
        <div class="project-tags"><span>MOOC ANSSI</span><span>CNIL · RGPD</span><span>Cyber Awareness</span><span>Pix</span></div>
      </article>
      <article class="atout-card">
        <span class="atout-icon" style="background:var(--coral-soft);" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7.5-4.6-9.3-9.2C1.4 8.4 3.6 5 7 5c2 0 3.3 1 5 3 1.7-2 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8C19.5 16.4 12 21 12 21z"/></svg></span>
        <h3>Centres d’intérêt</h3>
        <div class="project-tags"><span>Boxe anglaise</span><span>Guitare électrique</span></div>
      </article>
    </div>

    <div class="cert-head">
      <h3 class="cert-heading">Mes certificats</h3>
      <p class="cert-hint">8 attestations · cliquez sur un certificat pour l’agrandir</p>
    </div>
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
        <a class="veille-tool" href="https://www.inoreader.com/" target="_blank" rel="noopener">
          <img class="veille-tool-icon" src="images/logo-inoreader-icon.svg" alt="" width="32" height="32">
          <span class="veille-tool-text">
            <span class="veille-tool-k">Mon outil de veille</span>
            <strong class="veille-tool-name">Inoreader</strong>
            <span class="veille-tool-v">Agrégateur de flux RSS · développement, réseaux, cybersécurité</span>
          </span>
          <span class="veille-tool-arrow" aria-hidden="true">↗</span>
        </a>
      </div>
      <ul class="veille-links">
        <li><a href="https://thehackernews.com/2026/03/anthropic-finds-22-firefox.html" target="_blank" rel="noopener">Sécurité de Firefox <span>The Hacker News ↗</span></a></li>
        <li><a href="https://www.freecodecamp.org/news/how-to-use-docker-compose-for-production-workloads/" target="_blank" rel="noopener">Docker Compose en production <span>freeCodeCamp ↗</span></a></li>
        <li><a href="https://4d540f49-3025-4bac-87d5-74f740d12e4d.filesusr.com/ugd/f4edf3_02d5633a5eac46c7a41dccbc82e0c321.pdf" target="_blank" rel="noopener">Consulter ma veille technologique <span>PDF ↗</span></a></li>
      </ul>
    </div>
  </div>
</section>

<section id="conclusion" class="conclu" data-step-label="Conclusion">
  <div class="conclu-aurora" aria-hidden="true"><span></span><span></span></div>
  <div class="container">
    <p class="conclu-eyebrow"><span class="conclu-eyebrow-line" aria-hidden="true"></span>Conclusion</p>
    <h2 class="conclu-title">
      <span class="sr-only">Bilan de mon parcours</span>
      <span aria-hidden="true"><span class="conclu-word" style="--w:0">Bilan</span> <span class="conclu-word" style="--w:1">de</span> <span class="conclu-word" style="--w:2">mon</span> <span class="conclu-word conclu-word-accent" style="--w:3">parcours</span></span>
    </h2>


    <div class="conclu-path" aria-label="Étapes de mon parcours" role="group">
      <span class="conclu-path-line" aria-hidden="true"><span class="conclu-comet"></span></span>
      <ol>
        <li style="--d:0"><span class="conclu-dot" aria-hidden="true"></span><strong>2024</strong><span>Premier stage · programmation web</span></li>
        <li style="--d:1"><span class="conclu-dot" aria-hidden="true"></span><strong>2025</strong><span>Stages BTS · Laravel, React Native, 3D</span></li>
        <li style="--d:2"><span class="conclu-dot" aria-hidden="true"></span><strong>2026–2027</strong><span>2<sup>e</sup> année de BTS SIO SLAM à l’AFIP</span></li>
        <li style="--d:3"><span class="conclu-dot conclu-dot-goal" aria-hidden="true"></span><strong>Objectif</strong><span>BTS SIO en 2027 et alternance</span></li>
      </ol>
    </div>

    <div class="conclu-main">
      <figure class="conclu-speech">
        <p class="conclu-speech-k">Mon message au jury</p>
        <blockquote>
          <p>Mon parcours n’a pas été linéaire, mais chaque étape m’a fait progresser : de l’ICOF à l’AFIP, j’ai mené <strong><?= $e5Total ?> réalisations</strong> qui couvrent <strong>les six compétences du bloc 1</strong>.</p>
          <p>Ce qui me motive : créer des services utiles et fiables, <strong>un pas après l’autre</strong>.</p>
          <p>Mon objectif : obtenir mon BTS SIO en 2027, puis poursuivre en alternance dans le développement.</p>
        </blockquote>
        <figcaption>Jamar Carty</figcaption>
      </figure>
      <ul class="conclu-stats">
        <li style="--d:0"><strong><span data-conclu-count="<?= $e5Total ?>"><?= $e5Total ?></span></strong><span>réalisations professionnelles</span></li>
        <li style="--d:1"><strong><span data-conclu-count="<?= $e5Covered ?>"><?= $e5Covered ?></span>/<?= count($synthCompetences) ?></strong><span>compétences du bloc 1 couvertes</span></li>
        <li style="--d:2"><strong><span data-conclu-count="3">3</span></strong><span>stages en entreprise</span></li>
      </ul>
    </div>

    <div class="conclu-end">
      <p class="conclu-thanks">Merci pour votre attention</p>
      <p class="conclu-next">Place à vos questions</p>
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
          <div class="row-text"><div class="k">CV</div><a class="v" href="CVJAMAR-C.pdf" download="CV-Jamar-Carty.pdf">Télécharger mon CV (PDF)</a></div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <span>© <?= date('Y') ?> Jamar Carty</span>
    <span class="footer-school"><img src="images/logo-afip.svg" alt="AFIP" width="153" height="102">BTS SIO — Option SLAM</span>
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

<script src="script.js?v=<?= md5_file(__DIR__ . '/script.js') ?>"></script>
<script src="articles.js"></script>
<script src="nav.js"></script>
<script src="background.js"></script>
<script src="cursor.js"></script>
<script src="certificats.js"></script>
<script src="motion.js"></script>
<script src="hero-motion.js"></script>
<script src="presentation.js"></script>
<script src="conclusion.js"></script>
<script src="synthese.js"></script>
<script src="article-reader.js"></script>
<script type="module" src="hero-3d.js"></script>

</body>
</html>
