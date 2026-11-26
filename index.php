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

// certificats (section Atouts): card + explanation shown in the certificate viewer (certificats.js)
// each one: what the document is, the result, the details, what it taught, the BTS SIO competences it supports
$certificats = [
  // each certificate: one card on the page; 'docs' = the documents shown in the viewer
  // (short text: one sentence, the result, up to 3 key points, competences C1 to C6)
  [
    'badge' => 'MOOC', 'title' => 'SecNumacadémie', 'meta' => 'ANSSI · 2023',
    'docs' => [[
      'file' => 'anssi.jpg', 'w' => 1754, 'h' => 1241,
      'alt' => "Attestation de suivi SecNumacadémie de l'ANSSI délivrée à Jamar Carty",
      'title' => 'SecNumacadémie', 'badge' => 'MOOC · ANSSI',
      'about' => 'Le MOOC de l’ANSSI sur les bases de la cybersécurité.',
      'result' => '4 modules validés · 82 à 96 %',
      'points' => ['Mots de passe et double authentification', 'Navigation sûre et hameçonnage', 'Sécurité du poste et sauvegardes'],
      'skills' => [1, 2],
    ]],
  ],
  [
    'badge' => 'Certificat', 'title' => 'Cyber Awareness 2025', 'meta' => 'DoD Cyber Exchange · 2024',
    'docs' => [[
      'file' => 'cyber-awareness.jpg', 'w' => 1754, 'h' => 1241,
      'alt' => 'Certificat Cyber Awareness Challenge 2025 délivré à Jamar Carty',
      'title' => 'Cyber Awareness Challenge 2025', 'badge' => 'Certificat · en anglais',
      'about' => 'Sensibilisation à la cybersécurité du Département de la Défense américain.',
      'result' => 'Terminé · novembre 2024',
      'points' => ['Hameçonnage et ingénierie sociale', 'Protection des informations sensibles', 'Sécurité mobile et télétravail'],
      'skills' => [1, 6],
    ]],
  ],
  [
    'badge' => 'Certification', 'title' => 'Pix · 434 pix', 'meta' => 'Niveau Indépendant 2 · 2026',
    'docs' => [[
      'file' => 'pix.jpg', 'w' => 1754, 'h' => 1241,
      'alt' => 'Certification Pix, niveau Indépendant 2, délivrée à Jamar Carty',
      'title' => 'Certification Pix', 'badge' => 'Certification nationale',
      'about' => 'La certification officielle des compétences numériques.',
      'result' => '434 pix · niveau Indépendant 2',
      'points' => ['Recherche et fiabilité de l’information', 'Outils bureautiques et collaboratifs', 'Protection des données'],
      'skills' => [6],
    ]],
  ],
  [
    'badge' => '5 modules', 'title' => 'RGPD · L’atelier CNIL', 'meta' => 'CNIL · 2023 · 90 à 100 %',
    'docs' => [
      ['file' => 'cnil-1.jpg', 'w' => 1754, 'h' => 1241, 'alt' => "Attestation CNIL, L'atelier RGPD, Module 1, délivrée à Jamar Carty",
       'title' => 'Le RGPD et ses notions clés', 'badge' => 'CNIL · Module 1', 'about' => 'Les bases du RGPD.', 'result' => 'Score 90 %',
       'points' => ['Donnée personnelle et traitement', 'Responsable de traitement et sous-traitant'], 'skills' => [1, 3]],
      ['file' => 'cnil-2.jpg', 'w' => 1754, 'h' => 1241, 'alt' => "Attestation CNIL, L'atelier RGPD, Module 2, délivrée à Jamar Carty",
       'title' => 'Les principes de la protection des données', 'badge' => 'CNIL · Module 2', 'about' => 'Les règles de chaque traitement de données.', 'result' => 'Score 100 %',
       'points' => ['Finalité et base légale', 'Minimisation et durée de conservation', 'Droits des personnes'], 'skills' => [1, 3]],
      ['file' => 'cnil-3.jpg', 'w' => 1754, 'h' => 1241, 'alt' => "Attestation CNIL, L'atelier RGPD, Module 3, délivrée à Jamar Carty",
       'title' => 'Les responsabilités des acteurs', 'badge' => 'CNIL · Module 3', 'about' => 'Qui est responsable de quoi.', 'result' => 'Score 100 %',
       'points' => ['Principe de responsabilité', 'Gestion d’une violation de données'], 'skills' => [1, 4]],
      ['file' => 'cnil-4.jpg', 'w' => 1754, 'h' => 1241, 'alt' => "Attestation CNIL, L'atelier RGPD, Module 4, délivrée à Jamar Carty",
       'title' => 'Le DPO et les outils de la conformité', 'badge' => 'CNIL · Module 4', 'about' => 'Le délégué à la protection des données et ses outils.', 'result' => 'Score 93 %',
       'points' => ['Rôle du DPO', 'Registre des traitements', 'Analyse d’impact (AIPD)'], 'skills' => [1, 4]],
      ['file' => 'cnil-5.png', 'w' => 1523, 'h' => 1523, 'alt' => "Attestation CNIL, L'atelier RGPD, Module 5, délivrée à Jamar Carty",
       'title' => 'Les collectivités territoriales', 'badge' => 'CNIL · Module 5', 'about' => 'Le RGPD dans les services publics locaux.', 'result' => 'Score 96 %',
       'points' => ['Traitements propres aux collectivités'], 'skills' => [1, 5]],
    ],
  ],
];

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
    ['AP M2L : installation du serveur LAMP', 'm2l-infrastructure', [1, 5]],
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
    ['Mission 2 : Laravel 12', 'laravel-stage', [1, 3, 4, 5, 6]],
  ],
  'Réalisations en cours de stage de seconde année' => [
    ['Mission 1 : Kadviser, modélisation 3D', 'kadviser-model3d', [4]],
    ['Mission 2 : amélioration du site web Kadviser', 'kadviser-themes', [3, 4, 5]],
  ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0b1120">
<script>
  // theme chosen by the visitor (light for a projector), applied before the page paints
  try { if (localStorage.getItem('theme') === 'light') document.documentElement.dataset.theme = 'light'; } catch (e) {}
</script>
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
        <button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="Passer en thème clair" title="Thème clair / sombre">
          <svg class="ico-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="ico-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        </button>
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
        <h1>
          <span class="neon-name">Jamar</span> <span class="neon-name">Carty</span><br>
          <span class="sw"><span class="sr-only">Support et mise à disposition de services informatiques</span><span aria-hidden="true">Portfolio <span class="hero-rotate" data-words="Épreuve E5|Bloc 1|BTS SIO SLAM">Épreuve E5</span></span></span>
        </h1>
        <p class="hero-sub">Développeur web et applicatif en 2<sup>e</sup> année de BTS SIO SLAM. <strong>13 réalisations</strong> qui couvrent <strong>les 6 compétences du bloc 1</strong>.</p>
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
        <span class="hb-v">AFIP · session 2027</span>
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
  ['#profil', 'Qui je suis', 'Profil, formation, projet', '1 min 30'],
  ['#experience', 'Mes stages', 'Missions de 1re et 2e année', '2 min'],
  ['#synthese', 'Tableau de synthèse', '6 compétences couvertes', '2 min'],
  ['#articles', 'Réalisations commentées', '2 ou 3 projets en détail', '3 min 30'],
  ['#veille', 'Développement professionnel', 'Veille, certifications', '1 min'],
];
?>
<section id="epreuve-e5" class="e5">
  <div class="container">
    <div class="section-head e5-head">
      <img class="e5-logo" src="images/logo-afip.svg" alt="AFIP" width="153" height="102">
      <div>
        <h2>Épreuve E5</h2>
        <p>Support et mise à disposition de services informatiques</p>
      </div>
    </div>

    <ul class="e5-facts" aria-label="Modalités de l’épreuve E5">
      <li><span class="e5-k">Bloc évalué</span><strong>Bloc 1</strong><span class="e5-v">6 compétences</span></li>
      <li><span class="e5-k">Coefficient</span><strong>4</strong><span class="e5-v">CCF</span></li>
      <li><span class="e5-k">Forme</span><strong>Oral · 40 min</strong><span class="e5-v">10 min + 30 min</span></li>
      <li><span class="e5-k">Commission</span><strong>2 membres</strong><span class="e5-v">enseignant + professionnel</span></li>
    </ul>

    <div class="e5-phases">
      <div class="e5-phase">
        <span class="e5-phase-time">Phase 1 · 10 min max</span>
        <h3>Présentation du parcours</h3>
        <p>Mon parcours et mes compétences, à l’appui du portfolio.</p>
        <button class="btn-primary e5-start" type="button" data-present-start>Lancer le mode présentation</button>
      </div>
      <div class="e5-phase">
        <span class="e5-phase-time">Phase 2 · 30 min</span>
        <h3>Échange avec la commission</h3>
        <p>Questions sur les réalisations choisies par le jury et ma contribution personnelle.</p>
        <a class="btn-outline" href="#articles">Ouvrir les réalisations</a>
      </div>
    </div>

    <div class="e5-grid e5-grid-single">
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
      <div><h2>Critères évalués</h2><p>Les 6 compétences du bloc 1 et leurs preuves</p></div>
    </div>
    <div class="e5-comps">
<?php foreach ($synthCompetences as $i => [$label, $skills, $short]): ?>
      <article class="e5-comp">
        <div class="e5-comp-top"><span class="synth-num">C<?= $i + 1 ?></span><span class="e5-comp-count"><?= count($e5Coverage[$i + 1]) ?> réalisation<?= count($e5Coverage[$i + 1]) > 1 ? 's' : '' ?></span></div>
        <h4><?= e($label) ?></h4>
        <details class="e5-skills-wrap">
          <summary>Savoir-faire du référentiel</summary>
          <ul class="e5-skills">
<?php foreach ($skills as $skill): ?>
            <li><?= e($skill) ?></li>
<?php endforeach; ?>
          </ul>
        </details>
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
      <div><h2>Profil</h2><p>Qui je suis</p></div>
    </div>
    <div class="profile-grid">
<?php
// profile photo: the first image found in images/ named profil.* (same photo as LinkedIn); initials until it exists
$profilePhoto = null;
foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
  if (is_file(__DIR__ . "/images/profil.$ext")) { $profilePhoto = "images/profil.$ext"; break; }
}
// stage missions = every réalisation outside the "formation" period
$profileStageMissions = 0;
foreach ($synthRealisations as $period => $rows) {
  if (str_contains($period, 'stage')) $profileStageMissions += count($rows);
}
?>
      <div class="pf-layout">
        <aside class="pf-id pf-anim" style="--i:0" aria-label="Carte d’identité du candidat">
          <figure class="profile-photo">
  <?php if ($profilePhoto): ?>
            <img src="<?= e($profilePhoto) ?>?v=<?= filemtime(__DIR__ . '/' . $profilePhoto) ?>" alt="Photo de Jamar Carty" width="400" height="400">
  <?php else: ?>
            <span class="profile-photo-initials" aria-hidden="true">JC</span>
  <?php endif; ?>
          </figure>
          <div class="pf-id-main">
          <h3 class="pf-name">Jamar Carty</h3>
          <p class="pf-role">Développeur web et applicatif</p>
          </div>
          <div class="pf-id-side">
          <div class="pf-school">
            <img src="images/logo-afip.svg" alt="AFIP" width="153" height="102">
            <div><strong>BTS SIO · option SLAM</strong><span>2<sup>e</sup> année · AFIP · 2026–2027</span></div>
          </div>
          <div class="pf-links">
            <a class="pf-link" href="https://www.linkedin.com/in/jamar-carty" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.35V9h3.414v1.561h.049c.476-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.119 20.452H3.555V9h3.564v11.452z"/></svg>LinkedIn</a>
            <a class="pf-link" href="https://github.com/jamar603" target="_blank" rel="noopener noreferrer" aria-label="GitHub : github.com/jamar603"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5A11.5 11.5 0 0 0 8.36 22.91c.58.1.79-.25.79-.56v-2.02c-3.2.7-3.87-1.36-3.87-1.36-.52-1.33-1.28-1.69-1.28-1.69-1.04-.71.08-.7.08-.7 1.15.08 1.75 1.18 1.75 1.18 1.02 1.74 2.68 1.24 3.33.95.1-.74.4-1.24.73-1.53-2.55-.29-5.23-1.28-5.23-5.68 0-1.26.45-2.28 1.18-3.08-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.17a10.9 10.9 0 0 1 5.74 0c2.19-1.48 3.15-1.17 3.15-1.17.62 1.58.23 2.75.11 3.04.73.8 1.18 1.82 1.18 3.08 0 4.41-2.68 5.38-5.24 5.67.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5z"/></svg>GitHub</a>
            <a class="pf-link pf-link-cv" href="CVJAMAR-C.pdf" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15l3 3 3-3"/></svg>Mon CV</a>
          </div>
          </div>
        </aside>

        <div class="pf-main">
          <ul class="profile-stats pf-anim" style="--i:2" aria-label="Mon parcours en chiffres">
            <li><strong><span data-count="<?= $e5Total ?>"><?= $e5Total ?></span></strong><span>réalisations professionnelles</span></li>
            <li><strong><span data-count="3">3</span></strong><span>stages en entreprise</span></li>
            <li class="is-key"><strong><span data-count="<?= $e5Covered ?>"><?= $e5Covered ?></span>/<?= count($synthCompetences) ?></strong><span>compétences du bloc 1 couvertes</span></li>
          </ul>

          <article class="profile-pitch pf-anim" style="--i:3">
            <div class="profile-pitch-head">
              <h3 class="profile-fact-k">Ma présentation</h3>
            </div>
            <ol class="profile-pitch-steps">
              <li style="--s:0"><div class="pf-step-top"><span class="pf-step-dot" aria-hidden="true">1</span><span class="profile-pitch-n">Qui je suis</span></div><p>BTS SIO SLAM commencé à l’<strong>ICOF</strong> en 2024, terminé à l’<strong>AFIP</strong> en 2027.</p></li>
              <li style="--s:1"><div class="pf-step-top"><span class="pf-step-dot" aria-hidden="true">2</span><span class="profile-pitch-n">Ce que j’ai fait</span></div><p><strong>Laravel 12</strong>, <strong>API REST + React Native</strong>, <strong>application 3D</strong> et <strong>site Kadviser</strong>.</p></li>
              <li style="--s:2"><div class="pf-step-top"><span class="pf-step-dot" aria-hidden="true">3</span><span class="profile-pitch-n">Ce que je prouve</span></div><p><strong><?= $e5Total ?> réalisations</strong> qui couvrent <strong><?= $e5Covered ?>/<?= count($synthCompetences) ?> compétences</strong> du bloc 1.</p></li>
              <li style="--s:3"><div class="pf-step-top"><span class="pf-step-dot" aria-hidden="true">4</span><span class="profile-pitch-n">Où je vais</span></div><p>Une <strong>alternance en développement</strong>, sur des projets réels.</p></li>
            </ol>
          </article>
        </div>
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

    <div class="fpath">
      <div class="fpath-grid" role="list" aria-label="Mon parcours de formation, du plus ancien au plus récent">
        <svg class="fpath-curves" viewBox="0 0 1000 300" preserveAspectRatio="none" aria-hidden="true">
          <defs>
            <linearGradient id="fpathGrad" x1="0" x2="1" y1="0" y2="0">
              <stop offset="0" stop-color="#64748b"/><stop offset=".45" stop-color="#a5b4fc"/><stop offset=".75" stop-color="#6ee7b7"/><stop offset="1" stop-color="#60a5fa"/>
            </linearGradient>
          </defs>
          <path class="fpath-track" d="M109 225 C 218 225, 217 75, 326 75 S 434 225, 543 225 S 652 75, 761 75 S 860 150, 934 150"/>
          <path class="fpath-line fpath-l1" pathLength="1" d="M109 225 C 218 225, 217 75, 326 75"/>
          <path class="fpath-line fpath-l2" pathLength="1" d="M326 75 C 435 75, 434 225, 543 225"/>
          <path class="fpath-line fpath-l3" pathLength="1" d="M543 225 C 652 225, 652 75, 761 75"/>
          <path class="fpath-line fpath-l4 fpath-future" pathLength="1" d="M761 75 C 850 75, 860 150, 934 150"/>
          <path class="fpath-comet" pathLength="1" d="M109 225 C 218 225, 217 75, 326 75 S 434 225, 543 225 S 652 75, 761 75"/>
        </svg>
        <div class="fpath-node fpath-bac" role="listitem" style="--d:0">
          <span class="fpath-year">2018 – 2020</span>
          <strong>Baccalauréat STI2D</strong>
          <span class="fpath-org">Lycée Robert Weinum</span>
        </div>
        <div class="fpath-node fpath-but" role="listitem" style="--d:1">
          <span class="fpath-year">2020 – 2021</span>
          <strong>BUT MMI</strong>
          <span class="fpath-org">IUT de Nantes · Nantes Université</span>
        </div>
        <div class="fpath-node fpath-icof" role="listitem" style="--d:2">
          <span class="fpath-year">2024 – 2026</span>
          <strong>BTS SIO · option SLAM</strong>
          <span class="fpath-org">ICOF</span>
          <span class="pill-validated"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>Épreuves validées</span>
        </div>
        <div class="fpath-node fpath-afip" role="listitem" style="--d:3">
          <div class="fpath-top">
            <img class="fpath-logo" src="images/logo-afip.svg" alt="AFIP" width="153" height="102">
            <span class="pill-current">En cours</span>
          </div>
          <span class="fpath-year">2026 – 2027</span>
          <strong>BTS SIO · option SLAM</strong>
          <span class="fpath-org">AFIP · 2<sup>e</sup> année</span>
        </div>
        <div class="fpath-goal" role="listitem" style="--d:4">
          <span class="fpath-goal-dot" aria-hidden="true"></span>
          <span class="fpath-year">2027</span>
          <strong>Diplôme</strong>
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
        <p>Mon parcours n’a pas toujours été linéaire. Chaque obstacle me rend plus solide : j’avance, <strong>un pas après l’autre</strong>.</p>
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

    <div class="xpath">
      <div class="xpath-rail" aria-hidden="true">
        <svg class="xpath-curves" viewBox="0 0 1000 100" preserveAspectRatio="none">
          <defs>
            <linearGradient id="xpathGrad" x1="0" x2="1" y1="0" y2="0">
              <stop offset="0" stop-color="#64748b"/><stop offset=".35" stop-color="#e5b567"/><stop offset=".7" stop-color="#a5b4fc"/><stop offset="1" stop-color="#60a5fa"/>
            </linearGradient>
          </defs>
          <path class="xpath-track" d="M125 75 C 250 75, 250 45, 375 45 S 500 75, 625 75 S 750 45, 875 45"/>
          <path class="xpath-stem" pathLength="1" d="M125 75 V 100"/>
          <path class="xpath-stem" pathLength="1" d="M375 45 V 100"/>
          <path class="xpath-stem" pathLength="1" d="M625 75 V 100"/>
          <path class="xpath-stem" pathLength="1" d="M875 45 V 100"/>
          <path class="xpath-line xpath-l1" pathLength="1" d="M125 75 C 250 75, 250 45, 375 45"/>
          <path class="xpath-line xpath-l2" pathLength="1" d="M375 45 C 500 45, 500 75, 625 75"/>
          <path class="xpath-line xpath-l3" pathLength="1" d="M625 75 C 750 75, 750 45, 875 45"/>
          <path class="xpath-comet" pathLength="1" d="M125 75 C 250 75, 250 45, 375 45 S 500 75, 625 75 S 750 45, 875 45"/>
        </svg>
        <span class="xpath-dot xpath-low" style="--d:0"><b>2023</b></span>
        <span class="xpath-dot xpath-high" style="--d:1"><b>2024</b></span>
        <span class="xpath-dot xpath-low" style="--d:2"><b>2025</b></span>
        <span class="xpath-dot xpath-high xpath-now" style="--d:3"><b>2025</b></span>
      </div>

      <ol class="xpath-cards" aria-label="Mes expériences, de la plus ancienne à la plus récente">
        <li class="xpath-card" style="--d:0">
          <span class="xpath-year">2023</span>
          <h3 class="xpath-title">Développement de sites web · Indépendant</h3>
          <ul class="experience-points"><li>Site e-commerce WordPress pour un photographe</li></ul>
        </li>
        <li class="xpath-card" style="--d:1">
          <span class="xpath-year">2024</span>
          <h3 class="xpath-title">Stage · Programmation web</h3>
          <span class="xpath-org">AAB Fabrication · Saint-Martin</span>
          <ul class="experience-points"><li>Site web dynamique pour la vente en ligne</li><li>HTML, CSS et JavaScript</li></ul>
        </li>
        <li class="xpath-card" style="--d:2">
          <span class="xpath-year">12 mai – 20 juin 2025</span>
          <h3 class="xpath-title">Stage · Développement web et API REST</h3>
          <ul class="experience-points"><li>Gestion des conventions avec Laravel 12</li><li>API REST pour une application React Native</li><li>Git/GitHub et revues de code</li></ul>
          <button type="button" class="exp-shot" data-open-article="laravel-stage" aria-label="Ouvrir la mission : gestion des conventions Laravel"><img src="images/articles/laravel-stage/cover.webp" alt="" loading="lazy"><span>Voir la mission</span></button>
        </li>
        <li class="xpath-card xpath-card-now" style="--d:3">
          <span class="xpath-year">10 novembre – 19 décembre 2025</span>
          <h3 class="xpath-title">Stage · Développement d’applications et web</h3>
          <span class="xpath-org">COF</span>
          <ul class="experience-points"><li>Application 3D d’un chargeur (Kadviser)</li><li>Nouvelles fonctionnalités du site Kadviser</li><li>Tests et corrections</li></ul>
          <button type="button" class="exp-shot" data-open-article="kadviser-model3d" aria-label="Ouvrir la mission : application 3D Kadviser"><img src="images/articles/kadviser-model3d/cover.webp" alt="" loading="lazy"><span>Voir la mission</span></button>
        </li>
      </ol>
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
        <img class="project-cover" src="images/articles/mediateq-csharp/cover.webp" alt="" loading="lazy">
        <div class="project-title">Application Mediateq</div>
        <div class="project-desc">Application Windows Forms pour gérer une médiathèque.</div>
        <div class="project-tags"><span>C#</span><span>Windows Forms</span></div>
        <a class="project-link" href="#articles" data-open-article="mediateq-csharp">Lire le projet →</a>
      </div>
      <div class="project-card">
        <img class="project-cover" src="images/articles/laravel-stage/cover.webp" alt="" loading="lazy">
        <div class="project-title">Gestion des conventions de stage</div>
        <div class="project-desc">Espaces et droits pour étudiants, enseignants et secrétariat.</div>
        <div class="project-tags"><span>Laravel 12</span><span>PHP</span><span>Gestion des accès</span></div>
        <a class="project-link" href="#articles" data-open-article="laravel-stage">Lire la mission →</a>
      </div>
      <div class="project-card">
        <img class="project-cover" src="images/articles/react-native-stage/cover.webp" alt="" loading="lazy">
        <div class="project-title">Application mobile connectée à une API</div>
        <div class="project-desc">Application mobile reliée à une API REST.</div>
        <div class="project-tags"><span>React Native</span><span>API</span></div>
        <a class="project-link" href="#articles" data-open-article="react-native-stage">Lire la mission →</a>
      </div>
      <div class="project-card">
        <img class="project-cover" src="images/articles/glpi-inventory/cover.webp" alt="" loading="lazy">
        <div class="project-title">Gestion de parc informatique</div>
        <div class="project-desc">Inventaire du parc informatique avec GLPI.</div>
        <div class="project-tags"><span>GLPI</span><span>Support IT</span></div>
        <a class="project-link" href="#articles" data-open-article="glpi-inventory">Lire le TP →</a>
      </div>
      <div class="project-card">
        <img class="project-cover" src="images/articles/kadviser-model3d/cover.webp" alt="" loading="lazy">
        <div class="project-title">K-Advisor et modélisation 3D</div>
        <div class="project-desc">Application et modélisation 3D en stage.</div>
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
      <div><h2>Tableau de synthèse</h2><p>Mes réalisations et les compétences du bloc 1</p></div>
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

    <p class="synth-hint">Cliquez sur une compétence pour voir ses réalisations.</p>
    <div class="synth-actions">
      <a class="btn-outline" href="tableau-synthese-bts-sio.pdf" target="_blank" rel="noopener">Tableau officiel · PDF</a>
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
      <div><h2>Compétences techniques</h2><p>Langages, frameworks et outils mobilisés dans les réalisations</p></div>
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
      <div><h2>Atouts</h2><p>Qualités, langues, certificats</p></div>
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
        <span class="atout-icon" style="background:var(--coral-soft);" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f28b82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7.5-4.6-9.3-9.2C1.4 8.4 3.6 5 7 5c2 0 3.3 1 5 3 1.7-2 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8C19.5 16.4 12 21 12 21z"/></svg></span>
        <h3>Centres d’intérêt</h3>
        <div class="project-tags"><span>Boxe anglaise</span><span>Guitare électrique</span></div>
      </article>
    </div>

    <div class="cert-head">
      <h3 class="cert-heading">Mes certificats</h3>
      <p class="cert-hint"><?= array_sum(array_map(fn($c) => count($c['docs']), $certificats)) ?> attestations · cliquez pour voir</p>
    </div>
    <div class="cert-slider" aria-roledescription="carrousel" aria-label="Mes certificats">
      <div class="cert-grid" tabindex="0">
<?php $certDoc = 0; foreach ($certificats as $c):
  $first = $c['docs'][0];
  $src = 'images/certifications/' . $first['file']; ?>
        <a class="cert-card" href="<?= e($src) ?>" target="_blank" rel="noopener" data-doc="<?= $certDoc ?>">
          <span class="cert-thumb"><img src="<?= e($src) ?>" alt="<?= e($first['alt']) ?>" loading="lazy" width="<?= $first['w'] ?>" height="<?= $first['h'] ?>"><span class="cert-zoom" aria-hidden="true">Voir</span></span>
          <span class="cert-info"><span class="cert-badge"><?= e($c['badge']) ?></span><strong><?= e($c['title']) ?></strong><span class="cert-meta"><?= str_replace(' %', '&nbsp;%', e($c['meta'])) ?></span></span>
        </a>
<?php $certDoc += count($c['docs']); endforeach; ?>
      </div>
      <!-- documents of the viewer (certificats.js): one per attestation, a card opens the first of its group -->
      <div class="cert-docs" hidden>
<?php foreach ($certificats as $c): foreach ($c['docs'] as $d): ?>
        <template data-src="images/certifications/<?= e($d['file']) ?>" data-alt="<?= e($d['alt']) ?>" data-title="<?= e($d['title']) ?>" data-badge="<?= e($d['badge']) ?>">
          <p class="cd-about"><?= e($d['about']) ?></p>
          <p class="cd-result"><?= str_replace(' %', '&nbsp;%', e($d['result'])) ?></p>
          <h4>L’essentiel</h4>
          <ul class="cd-learned">
<?php foreach ($d['points'] as $point): ?>
            <li><?= e($point) ?></li>
<?php endforeach; ?>
          </ul>
          <h4>Compétences E5</h4>
          <ul class="cd-skills">
<?php foreach ($d['skills'] as $n): ?>
            <li><b>C<?= $n ?></b> <?= e($synthCompetences[$n - 1][2]) ?></li>
<?php endforeach; ?>
          </ul>
        </template>
<?php endforeach; endforeach; ?>
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
      <div><h2>Veille technologique</h2><p>Sujet : IA et cybersécurité</p></div>
      <div class="cube-scene" aria-hidden="true"><div class="cube"><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
    </div>

    <div class="vx">
      <article class="vx-col vx-subject" style="--d:0">
        <header class="vx-head"><span class="vx-n">1</span><span class="vx-k">Mon sujet</span></header>
        <h3>IA et cybersécurité</h3>
        <p>L’IA sert de plus en plus à <strong>trouver les failles avant les attaquants</strong>. Je suis comment elle change la sécurité des logiciels.</p>
        <ul class="vx-themes" aria-label="Thèmes suivis">
          <li>Détection de failles</li><li>IA appliquée au code</li><li>Sécurité des logiciels</li>
        </ul>
        <span class="vx-comp"><b>C6</b> Outils et stratégies de veille</span>
      </article>

      <span class="vx-arrow" aria-hidden="true"></span>

      <article class="vx-col vx-tool" style="--d:1">
        <header class="vx-head"><span class="vx-n">2</span><span class="vx-k">Mon outil et ma méthode</span></header>
        <a class="vx-ino" href="https://www.inoreader.com/" target="_blank" rel="noopener" data-veille="inoreader">
          <img src="images/logo-inoreader-icon.svg" alt="" width="56" height="56">
          <span class="vx-ino-text"><strong>Inoreader</strong><span>Agrégateur de flux RSS</span></span>
          <span class="vx-ino-go" aria-hidden="true">↗</span>
        </a>
        <ol class="vx-steps">
          <li><b>1</b><span><strong>Collecter</strong> les flux RSS dans Inoreader</span></li>
          <li><b>2</b><span><strong>Trier</strong> : un dossier par thème</span></li>
          <li><b>3</b><span><strong>Analyser</strong> : vérifier la source, résumer</span></li>
          <li><b>4</b><span><strong>Exploiter</strong> : fiche de synthèse, projets</span></li>
        </ol>
        <div class="vx-feeds">
          <span class="vx-k">Flux suivis</span>
          <ul><li>Cybersécurité</li><li>Développement</li><li>DevOps</li></ul>
        </div>
      </article>

      <span class="vx-arrow" aria-hidden="true"></span>

      <article class="vx-col vx-case" style="--d:2">
        <header class="vx-head"><span class="vx-n">3</span><span class="vx-k">Article analysé · mars 2026</span></header>
        <h3>Une IA audite le code de Firefox</h3>
        <p>Anthropic et Mozilla : Claude Opus 4.6 analyse le navigateur pour trouver des failles.</p>
        <ul class="vx-nums">
          <li><strong>22</strong><span>failles trouvées</span></li>
          <li><strong>14</strong><span>critiques</span></li>
          <li><strong>6 000</strong><span>fichiers C++</span></li>
          <li><strong>20 min</strong><span>pour la 1<sup>re</sup></span></li>
        </ul>
        <div class="vx-take">
          <span class="vx-k">Ce que j’en retiens</span>
          <p>L’IA trouve vite les failles, mais <strong>un humain doit valider</strong>.</p>
        </div>
        <div class="vx-actions">
          <a class="btn-primary" href="veille-technologique.pdf" target="_blank" rel="noopener" data-veille="pdf">Ma fiche de veille · PDF</a>
          <a class="vx-source" href="https://thehackernews.com/2026/03/anthropic-finds-22-firefox.html" target="_blank" rel="noopener" data-veille="hackernews">Résumé de l’article</a>
        </div>
      </article>
    </div>

    <!-- veille links open in a modal inside the portfolio (veille.js); the sites themselves refuse to be embedded -->
    <template id="vt-modal-pdf" data-title="Ma fiche de veille · IA et cybersécurité" data-kind="pdf" data-src="veille-technologique.pdf">
      <!-- text of the fiche, shown where the browser cannot display a PDF inside the page (Android) -->
      <h4>Sujet de veille</h4>
      <p>IA et cybersécurité</p>
      <h4>Contexte</h4>
      <p>Dans la cybersécurité, l’intelligence artificielle est de plus en plus utilisée pour détecter des vulnérabilités dans les logiciels. Anthropic a collaboré avec Mozilla pour analyser la sécurité de Firefox avec son modèle Claude Opus 4.6, afin d’identifier les failles avant qu’elles ne soient exploitées.</p>
      <h4>Résumé de l’article</h4>
      <ul>
        <li>Claude Opus 4.6 a découvert <strong>22 vulnérabilités</strong>, dont <strong>14 critiques</strong>.</li>
        <li>Première faille trouvée <strong>20 minutes</strong> après le début : un bug <em>use-after-free</em> dans le moteur JavaScript.</li>
        <li>Près de <strong>6 000 fichiers C++</strong> examinés, <strong>112 rapports</strong> de bugs envoyés à Mozilla.</li>
        <li>La plupart des failles corrigées dans <strong>Firefox 148</strong>, les autres dans les versions suivantes.</li>
        <li>L’IA reste moins performante pour créer des exploits fonctionnels.</li>
      </ul>
      <h4>Conclusion</h4>
      <p>L’IA peut devenir un outil puissant pour renforcer la sécurité des logiciels, mais <strong>une validation humaine reste nécessaire</strong>. À l’avenir, elle pourrait permettre de détecter les failles plus vite que les attaquants.</p>
    </template>
    <template id="vt-modal-hackernews" data-title="Une IA audite le code de Firefox" data-source="The Hacker News · mars 2026" data-url="https://thehackernews.com/2026/03/anthropic-finds-22-firefox.html">
      <h4>Contexte</h4>
      <p>L’intelligence artificielle est de plus en plus utilisée pour détecter des vulnérabilités. Anthropic a collaboré avec Mozilla pour analyser la sécurité de Firefox avec son modèle Claude Opus 4.6, afin de trouver les failles avant les cybercriminels.</p>
      <h4>Résumé</h4>
      <ul>
        <li><strong>22 vulnérabilités</strong> découvertes, dont <strong>14 critiques</strong>.</li>
        <li>La première faille trouvée en <strong>20 minutes</strong> : un bug <em>use-after-free</em> dans le moteur JavaScript.</li>
        <li>Près de <strong>6 000 fichiers C++</strong> analysés et <strong>112 rapports</strong> de bugs envoyés à Mozilla.</li>
        <li>La plupart des failles sont corrigées dans <strong>Firefox 148</strong>.</li>
        <li>L’IA est moins performante pour créer des exploits fonctionnels.</li>
      </ul>
      <h4>Ce que j’en retiens</h4>
      <p>L’IA peut renforcer la sécurité des logiciels en détectant vite des failles complexes, mais <strong>une validation humaine reste nécessaire</strong>.</p>
    </template>
    <template id="vt-modal-inoreader" data-title="Inoreader · mon outil de veille" data-source="Agrégateur de flux RSS" data-url="https://www.inoreader.com/">
      <h4>À quoi il sert</h4>
      <p>Inoreader rassemble au même endroit les nouveaux articles des sites que je suis, grâce à leurs flux RSS. Je n’ai pas besoin de visiter chaque site.</p>
      <h4>Comment je l’utilise</h4>
      <ul>
        <li>Je m’abonne aux flux de sites spécialisés (cybersécurité, développement, DevOps).</li>
        <li>Je classe les flux dans un dossier par thème.</li>
        <li>Je garde les articles utiles pour ma fiche de veille.</li>
      </ul>
    </template>
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


    <div class="conclu-main conclu-main-single">
      <figure class="conclu-speech">
        <p class="conclu-speech-k">Mon message au jury</p>
        <blockquote>
          <p><strong><?= $e5Total ?> réalisations</strong>, <strong>6 compétences sur 6</strong>, de l’ICOF à l’AFIP.</p>
          <p>Objectif : BTS SIO en 2027, puis <strong>bac+3</strong> en <strong>automatisation et cybersécurité</strong>.</p>
        </blockquote>
        <figcaption>Jamar Carty</figcaption>
      </figure>
    </div>

    <div class="conclu-after">
      <p class="conclu-after-k">Après mon BTS · Université Lyon 1</p>
      <div class="road" role="group" aria-label="Mon projet après le BTS">
        <svg class="road-curves" viewBox="0 0 1000 300" preserveAspectRatio="none" aria-hidden="true">
          <defs>
            <linearGradient id="roadGrad" x1="0" x2="1" y1="0" y2="0">
              <stop offset="0" stop-color="#60a5fa"/><stop offset=".5" stop-color="#a5b4fc"/><stop offset="1" stop-color="#e5b567"/>
            </linearGradient>
          </defs>
          <path class="road-path" pathLength="1" d="M125 150 C 250 150, 250 62, 375 62"/>
          <path class="road-path" pathLength="1" d="M125 150 C 250 150, 250 238, 375 238"/>
          <path class="road-path road-path-2" pathLength="1" d="M375 62 C 500 62, 500 150, 625 150"/>
          <path class="road-path road-path-2" pathLength="1" d="M375 238 C 500 238, 500 150, 625 150"/>
          <path class="road-path road-path-3 road-future" pathLength="1" d="M625 150 L 875 150"/>
        </svg>
        <div class="road-node road-start" style="--d:0">
          <span class="road-year">2027</span>
          <strong>BTS SIO SLAM</strong>
          <span>Diplôme · AFIP</span>
        </div>
        <div class="road-node road-up" style="--d:1">
          <span class="road-year">2027 – 2028</span>
          <strong>Licence MIAGE</strong>
          <span>Développement et systèmes d’information</span>
        </div>
        <span class="road-or" aria-hidden="true">ou</span>
        <div class="road-node road-down" style="--d:1">
          <span class="road-year">2027 – 2028</span>
          <strong>Licence pro DevOps</strong>
          <span>CI/CD, conteneurs, mise en production</span>
        </div>
        <div class="road-node road-spec" style="--d:2">
          <span class="road-year">Spécialisation</span>
          <strong>Automatisation et cybersécurité</strong>
          <span>Sécuriser et automatiser les services</span>
        </div>
        <div class="road-node road-goal" style="--d:3">
          <span class="road-year">Plus tard</span>
          <strong>Ma propre entreprise</strong>
          <span>Services web et sécurité</span>
        </div>
      </div>
      <blockquote class="road-quote">« Plus tard, j’aimerais <strong>créer ma propre entreprise</strong> : proposer des services web sûrs et automatisés, avec tout ce que j’ai appris. »</blockquote>
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
<script src="veille.js"></script>
<script src="motion.js"></script>
<script src="hero-motion.js"></script>
<script src="presentation.js"></script>
<script src="conclusion.js"></script>
<script src="section-motion.js?v=<?= md5_file(__DIR__ . '/section-motion.js') ?>"></script>
<script src="synthese.js"></script>
<script src="article-reader.js"></script>
<script type="module" src="hero-3d.js"></script>

</body>
</html>
