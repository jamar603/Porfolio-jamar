// interactive terminal in the Profil section: the jury types a command (or clicks a suggestion)
// and gets a short answer, sometimes with links that scroll to a section or open an article
(() => {
  const root = document.querySelector('[data-term]');
  if (!root) return;
  const out = root.querySelector('[data-term-out]');
  const form = root.querySelector('[data-term-form]');
  const input = form.querySelector('input');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  // helpers to build answer lines: plain text, a link to a section, a link that opens an article
  const go = (hash, label) => `<a href="${hash}" data-term-go>${label}</a>`;
  const art = (id, label) => `<a href="#articles" data-open-article="${id}">${label}</a>`;

  const commands = {
    help: {
      about: 'liste des commandes',
      run: () => [
        'Commandes disponibles :',
        ...Object.entries(commands)
          .filter(([, c]) => c.about)
          .map(([name, c]) => `  <b>${name.padEnd(12, ' ')}</b> ${c.about}`),
        'Astuce : <kbd>↑</kbd> rappelle la commande précédente, <kbd>Tab</kbd> complète.',
      ],
    },
    whoami: {
      about: 'qui je suis',
      run: () => [
        '<em>Jamar Carty</em> · développeur web et applicatif',
        'BTS SIO option SLAM · 2<sup>e</sup> année à l’AFIP (2026–2027)',
        'Objectif : une alternance en développement, sur des projets réels.',
      ],
    },
    formation: {
      about: 'mon parcours scolaire',
      run: () => [
        '2018–2020  Bac STI2D · Lycée Robert Weinum',
        '2020–2021  BUT MMI · IUT de Nantes',
        '2024–2026  BTS SIO SLAM · ICOF <s>✓ épreuves validées</s>',
        '2026–2027  BTS SIO SLAM · AFIP <u>● en cours</u>',
        go('#formation', '→ voir la section Formation'),
      ],
    },
    stages: {
      about: 'mes stages et missions',
      run: () => [
        '2024  AAB Fabrication · site de vente en ligne (HTML, CSS, JS)',
        '2025  Laravel 12 + API REST pour React Native',
        '      ' + art('laravel-stage', 'ouvrir la mission Laravel'),
        '2025  COF · application 3D Kadviser et site web',
        '      ' + art('kadviser-model3d', 'ouvrir la mission 3D'),
      ],
    },
    projets: {
      about: 'mes réalisations principales',
      run: () => [
        '<b>mediateq/</b>      C# Windows Forms · ' + art('mediateq-csharp', 'ouvrir'),
        '<b>conventions/</b>   Laravel 12 · ' + art('laravel-stage', 'ouvrir'),
        '<b>mobile-api/</b>    React Native + API · ' + art('react-native-stage', 'ouvrir'),
        '<b>glpi/</b>          parc informatique · ' + art('glpi-inventory', 'ouvrir'),
        '<b>kadviser-3d/</b>   modélisation 3D · ' + art('kadviser-model3d', 'ouvrir'),
        go('#articles', '→ les 13 articles'),
      ],
    },
    competences: {
      about: 'ma stack technique',
      run: () => [
        'Langages    C#, PHP, JavaScript, TypeScript, SQL, Python',
        'Frameworks  Laravel 12, Symfony, React Native, Node.js',
        'Outils      Git/GitHub, Docker, Postman, Linux',
        'Sécurité    OWASP Top 10, RGPD (CNIL), ANSSI',
        go('#competences', '→ voir les compétences'),
      ],
    },
    e5: {
      about: 'l’épreuve E5 en bref',
      run: () => {
        const covered = document.querySelector('.profile-stats .is-key strong')?.textContent.trim() || '6/6';
        return [
          'Épreuve E5 · bloc 1 · oral de 40 min (10 + 30)',
          `Compétences couvertes : <s>${covered}</s>`,
          go('#synthese', '→ tableau de synthèse') + '   ' + go('#criteres-e5', '→ critères évalués'),
        ];
      },
    },
    veille: {
      about: 'ma veille technologique',
      run: () => ['Sujet : IA et cybersécurité · flux suivis sur Inoreader', go('#veille', '→ voir la veille')],
    },
    hobbies: {
      about: 'en dehors du code',
      run: () => ['🥊 Boxe anglaise   🎸 Guitare électrique   🌍 Anglais bilingue', go('#motivation', '→ en savoir plus')],
    },
    contact: {
      about: 'me contacter',
      run: () => [
        'Email     <a href="mailto:jamarcarty131@gmail.com">jamarcarty131@gmail.com</a>',
        'LinkedIn  <a href="https://www.linkedin.com/in/jamar-carty" target="_blank" rel="noopener noreferrer">linkedin.com/in/jamar-carty</a>',
        'GitHub    <a href="https://github.com/jamar603" target="_blank" rel="noopener noreferrer">github.com/jamar603</a>',
      ],
    },
    cv: {
      about: 'ouvrir mon CV (PDF)',
      run: () => { window.open('CVJAMAR-C.pdf', '_blank', 'noopener'); return ['Ouverture de CVJAMAR-C.pdf…']; },
    },
    clear: {
      about: 'effacer l’écran',
      run: () => { out.innerHTML = ''; return []; },
    },
    // hidden commands, for the curious
    sudo: { run: () => ['<s>Permission refusée</s> : seul le jury a les droits administrateur ici 😉'] },
    ls: { run: () => commands.projets.run() },
    pwd: { run: () => ['/home/jamar/portfolio'] },
    date: { run: () => [new Date().toLocaleString('fr-FR', { dateStyle: 'full', timeStyle: 'short' })] },
    exit: { run: () => ['Pas si vite : il reste encore 30 minutes d’échange 😄'] },
    coffee: { run: () => ['☕ Café en cours… compilation de la motivation : 100 %'] },
  };
  const aliases = { aide: 'help', moi: 'whoami', projects: 'projets', skills: 'competences', 'compétences': 'competences', stage: 'stages', experience: 'stages', cls: 'clear' };

  const history = [];
  let historyIndex = 0;

  const print = (html, cls) => {
    const line = document.createElement('p');
    if (cls) line.className = cls;
    line.innerHTML = html;
    out.append(line);
    return line;
  };

  const run = async (raw) => {
    const text = raw.trim();
    if (!text) return;
    history.push(text);
    historyIndex = history.length;
    const safe = text.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    print(`<b>$</b> ${safe}`, 'pf-term-cmd');

    const key = text.toLowerCase().split(/\s+/)[0];
    const command = commands[aliases[key] || key];
    const lines = command ? command.run() : [`commande introuvable : <s>${safe}</s>. Tapez <kbd>help</kbd>.`];

    // lines appear one after another, like real output
    for (const html of lines) {
      print(html);
      out.scrollTop = out.scrollHeight;
      if (!reducedMotion.matches) await new Promise((r) => setTimeout(r, 45));
    }
    out.scrollTop = out.scrollHeight;
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const value = input.value;
    input.value = '';
    run(value);
  });

  input.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowUp' && history.length) {
      event.preventDefault();
      historyIndex = Math.max(0, historyIndex - 1);
      input.value = history[historyIndex];
    } else if (event.key === 'ArrowDown' && history.length) {
      event.preventDefault();
      historyIndex = Math.min(history.length, historyIndex + 1);
      input.value = history[historyIndex] || '';
    } else if (event.key === 'Tab' && input.value) {
      const match = Object.keys(commands).find((name) => commands[name].about && name.startsWith(input.value.toLowerCase()));
      if (match) { event.preventDefault(); input.value = match; }
    }
  });

  root.querySelector('.pf-term-chips').addEventListener('click', (event) => {
    const chip = event.target.closest('[data-cmd]');
    if (chip) run(chip.dataset.cmd);
  });

  // clicking anywhere on the screen puts the cursor in the prompt (not when selecting text or following a link)
  out.addEventListener('click', (event) => {
    if (event.target.closest('a') || String(window.getSelection())) return;
    input.focus({ preventScroll: true });
  });

  // section links scroll smoothly instead of jumping
  out.addEventListener('click', (event) => {
    const link = event.target.closest('[data-term-go]');
    if (!link) return;
    const target = document.querySelector(link.getAttribute('href'));
    if (!target) return;
    event.preventDefault();
    target.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'start' });
  });
})();
