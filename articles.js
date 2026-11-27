const searchField = document.getElementById('articleSearch');
const periodChips = document.getElementById('articlePeriod');
const competenceChips = document.getElementById('articleCompetence');
const railPrev = document.getElementById('articlePrev');
const railNext = document.getElementById('articleNext');
// period and competences of each article, read from the tableau de synthèse (see index.php)
const synthData = (() => {
  try { return JSON.parse(document.getElementById('articleSynth')?.textContent || '{}'); }
  catch { return {}; }
})();
const synthArticles = synthData.articles || {};
const synthCompetences = synthData.competences || [];
const railCount = document.getElementById('articleRailCount');
const railThumb = document.getElementById('articleRailThumb');
let activePeriod = 'all';
let activeCompetence = 'all';
const articleGrid = document.getElementById('articleGrid');
const articleCount = document.getElementById('articleCount');
const articleStatus = document.getElementById('articleStatus');
const emptyMessage = document.getElementById('articleEmpty');
const resetButton = document.getElementById('resetArticles');
const articleDialog = document.getElementById('articleDialog');
const dialogContent = document.getElementById('articleDialogContent');
const dialogClose = document.getElementById('closeArticleDialog');
// the dialog lives directly in <body>: inside #articles it inherited the section's scale (fit to screen)
// and its fading in presentation mode
document.body.append(articleDialog);
let articles = [];

function normalizeText(value){
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

function addText(parent, tagName, className, text){
  const element = document.createElement(tagName);
  if (className) element.className = className;
  element.textContent = text;
  parent.append(element);
  return element;
}

function addTags(parent, tags){
  const tagList = document.createElement('div');
  tagList.className = 'project-tags';
  tags.forEach(tag => addText(tagList, 'span', '', tag));
  parent.append(tagList);
}

function addCompetenceBadges(parent, articleId){
  const competences = synthArticles[articleId]?.competences || [];
  if (!competences.length) return;
  const list = document.createElement('span');
  list.className = 'article-comps';
  competences.forEach(number => {
    const badge = addText(list, 'span', '', `C${number}`);
    const competence = synthCompetences[number - 1];
    if (competence) badge.title = competence.label;
  });
  parent.append(list);
}

// "Tableau de synthèse" line of a card: the row of the table this article proves, then short
// C1–C6 badges (full name on hover and in the reader; a click filters the articles on that competence)
function addSynthBlock(parent, articleId){
  const synth = synthArticles[articleId];
  if (!synth) return;
  const block = document.createElement('div');
  block.className = 'article-synth';
  const row = addText(block, 'span', 'article-synth-row', synth.row || '');
  row.title = `Ligne du tableau de synthèse : ${synth.row || ''}`;
  const list = document.createElement('span');
  list.className = 'article-synth-comps';
  (synth.competences || []).forEach(number => {
    const competence = synthCompetences[number - 1];
    const chip = addText(list, 'button', '', `C${number}`);
    chip.type = 'button';
    chip.dataset.filterCompetence = String(number);
    if (competence){
      chip.title = `${competence.label} · voir les articles`;
      chip.setAttribute('aria-label', `C${number} ${competence.label} : filtrer les articles`);
    }
  });
  block.append(list);
  parent.append(block);
}

function addArticlePlaceholder(media, article){
  media.classList.add('article-media-placeholder');
  media.setAttribute('aria-hidden', 'true');
  addText(media, 'span', '', article.tags[0] || article.categoryLabel);
}

function createArticleCard(article, index){
  const card = document.createElement('article');
  card.className = 'article-card';
  card.dataset.category = article.category;
  card.dataset.articleId = article.id;
  if (synthArticles[article.id]) card.dataset.period = synthArticles[article.id].period;
  card.style.setProperty('--article-delay', `${index * 35}ms`);

  const media = document.createElement('div');
  media.className = 'article-media';
  media.dataset.category = article.category;
  if (article.cover){
    const image = document.createElement('img');
    image.src = article.cover;
    image.alt = article.coverAlt || '';
    image.loading = 'lazy';
    image.decoding = 'async';
    image.addEventListener('error', () => addArticlePlaceholder(media, article), { once:true });
    media.append(image);
  } else {
    addArticlePlaceholder(media, article);
  }
  card.append(media);

  const content = document.createElement('div');
  content.className = 'article-card-content';
  const meta = document.createElement('div');
  meta.className = 'article-meta';
  addText(meta, 'span', 'article-category', synthArticles[article.id]?.periodLabel || article.categoryLabel);
  addText(meta, 'time', '', article.date);
  content.append(meta);
  addText(content, 'h3', '', article.title);
  addText(content, 'p', 'article-summary', article.summary);
  addSynthBlock(content, article.id);
  addTags(content, article.tags);

  const foot = document.createElement('div');
  foot.className = 'article-foot';
  const openButton = addText(foot, 'button', 'article-read', 'Lire l’article');
  openButton.type = 'button';
  openButton.dataset.articleId = article.id;
  content.append(foot);
  card.append(content);
  return card;
}

function renderArticle(article){
  dialogContent.replaceChildren();

  if (article.cover){
    const image = document.createElement('img');
    image.className = 'article-dialog-cover';
    image.src = article.cover;
    image.alt = article.coverAlt || '';
    image.decoding = 'async';
    dialogContent.append(image);
  }

  const meta = document.createElement('div');
  meta.className = 'article-dialog-meta';
  const metaStart = document.createElement('span');
  metaStart.className = 'article-dialog-tags';
  addText(metaStart, 'span', 'article-category', synthArticles[article.id]?.periodLabel || article.categoryLabel);
  addCompetenceBadges(metaStart, article.id);
  meta.append(metaStart);
  addText(meta, 'time', '', article.date);
  dialogContent.append(meta);
  addText(dialogContent, 'h2', '', article.title).id = 'articleDialogTitle';
  addText(dialogContent, 'p', 'article-dialog-summary', article.summary);

  // photos shown inside the part they illustrate (section.photos = indexes in the gallery);
  // a click opens the viewer on the whole gallery, at that photo
  const gallery = article.gallery || [];
  // photos of a part, laid out by count:
  //   1 photo  → one large framed photo;
  //   2 photos → side by side, same frame size;
  //   3 and +  → a viewer: large photo, caption and counter, ‹ › arrows, strip of thumbnails, swipe on touch.
  // A click on a large photo opens the full-screen viewer on the photos of that part.
  const makeImage = (photo, eager) => {
    const image = document.createElement('img');
    image.src = photo.src;
    image.alt = photo.caption || '';
    if (photo.width) image.width = photo.width;
    if (photo.height) image.height = photo.height;
    image.loading = eager ? 'eager' : 'lazy';
    image.decoding = 'async';
    return image;
  };
  const makeZoom = (photos, i) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'gal-frame';
    button.setAttribute('aria-label', `Agrandir : ${photos[i].caption || 'capture'}`);
    button.addEventListener('click', () => openLightbox(photos, Number(button.dataset.index ?? i)));
    button.append(makeImage(photos[i]));
    return button;
  };
  const addFigures = (parent, indexes) => {
    const photos = indexes.map(index => gallery[index]).filter(Boolean);
    if (!photos.length) return;

    if (photos.length < 3){
      const grid = document.createElement('div');
      grid.className = `gal gal-${photos.length}`;
      photos.forEach((photo, i) => {
        const figure = document.createElement('figure');
        figure.append(makeZoom(photos, i));
        if (photo.caption) addText(figure, 'figcaption', '', photo.caption);
        grid.append(figure);
      });
      parent.append(grid);
      return;
    }

    const viewer = document.createElement('div');
    viewer.className = 'gal gal-viewer';
    viewer.setAttribute('role', 'group');
    viewer.setAttribute('aria-roledescription', 'galerie');
    viewer.setAttribute('aria-label', `Galerie de ${photos.length} captures`);
    const stage = document.createElement('div');
    stage.className = 'gal-stage';
    const frame = makeZoom(photos, 0);
    frame.dataset.index = '0';
    const prev = document.createElement('button');
    prev.type = 'button';
    prev.className = 'gal-arrow gal-prev';
    prev.setAttribute('aria-label', 'Capture précédente');
    prev.textContent = '‹';
    const next = document.createElement('button');
    next.type = 'button';
    next.className = 'gal-arrow gal-next';
    next.setAttribute('aria-label', 'Capture suivante');
    next.textContent = '›';
    const count = addText(stage, 'span', 'gal-count', '');
    stage.prepend(frame);
    stage.append(prev, next);
    const caption = document.createElement('p');
    caption.className = 'gal-caption';
    caption.setAttribute('aria-live', 'polite');
    const thumbs = document.createElement('div');
    thumbs.className = 'gal-thumbs';
    const thumbButtons = photos.map((photo, i) => {
      const thumb = document.createElement('button');
      thumb.type = 'button';
      thumb.setAttribute('aria-label', `Capture ${i + 1} : ${photo.caption || ''}`);
      const image = makeImage(photo);
      image.alt = '';
      thumb.append(image);
      thumb.addEventListener('click', () => show(i));
      thumbs.append(thumb);
      return thumb;
    });
    viewer.append(stage, caption, thumbs);
    parent.append(viewer);

    let current = 0;
    const show = i => {
      current = (i + photos.length) % photos.length;
      const photo = photos[current];
      const image = frame.querySelector('img');
      image.src = photo.src;
      image.alt = photo.caption || '';
      if (photo.width){ image.width = photo.width; image.height = photo.height; }
      frame.dataset.index = String(current);
      frame.setAttribute('aria-label', `Agrandir : ${photo.caption || 'capture'}`);
      caption.textContent = photo.caption || '';
      count.textContent = `${current + 1} / ${photos.length}`;
      thumbButtons.forEach((thumb, n) => thumb.setAttribute('aria-current', String(n === current)));
      const active = thumbButtons[current];
      if (thumbs.scrollWidth > thumbs.clientWidth) thumbs.scrollTo({ left: active.offsetLeft - (thumbs.clientWidth - active.offsetWidth) / 2, behavior: 'smooth' });
    };
    prev.addEventListener('click', () => show(current - 1));
    next.addEventListener('click', () => show(current + 1));
    // ← → inside the gallery change the photo (and not the article)
    viewer.addEventListener('keydown', event => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault();
      event.stopPropagation();
      show(current + (event.key === 'ArrowRight' ? 1 : -1));
    });
    let startX = null;
    stage.addEventListener('touchstart', event => { startX = event.touches[0].clientX; }, { passive:true });
    stage.addEventListener('touchend', event => {
      if (startX === null) return;
      const distance = event.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(distance) > 40) show(current + (distance < 0 ? 1 : -1));
    }, { passive:true });
    show(0);
  };

  article.sections.forEach(section => {
    const sectionElement = document.createElement('section');
    sectionElement.className = 'article-dialog-section';
    addText(sectionElement, 'h3', '', section.heading);
    section.paragraphs.forEach(paragraph => addText(sectionElement, 'p', '', paragraph));
    if (section.list?.length){
      const list = document.createElement('ul');
      list.className = 'article-list';
      section.list.forEach(item => addText(list, 'li', '', item));
      sectionElement.append(list);
    }
    if (section.photos?.length) addFigures(sectionElement, section.photos);
    // "Réalisation": numbered steps, each with its text, list and photos
    if (section.steps?.length){
      const steps = document.createElement('ol');
      steps.className = 'article-steps';
      section.steps.forEach(step => {
        const item = document.createElement('li');
        addText(item, 'h4', '', step.title);
        step.paragraphs.forEach(paragraph => addText(item, 'p', '', paragraph));
        if (step.list?.length){
          const list = document.createElement('ul');
          list.className = 'article-list';
          step.list.forEach(entry => addText(list, 'li', '', entry));
          item.append(list);
        }
        if (step.photos?.length) addFigures(item, step.photos);
        steps.append(item);
      });
      sectionElement.append(steps);
    }
    // "Compétences mobilisées": each competence of the tableau de synthèse with its proof
    if (section.competences){
      const list = document.createElement('ul');
      list.className = 'article-proofs';
      Object.entries(section.competences).forEach(([number, proof]) => {
        const item = document.createElement('li');
        addText(item, 'span', 'article-proof-num', `C${number}`);
        const text = document.createElement('div');
        addText(text, 'strong', '', synthCompetences[number - 1]?.label || '');
        addText(text, 'p', '', proof);
        item.append(text);
        list.append(item);
      });
      sectionElement.append(list);
    }
    dialogContent.append(sectionElement);
  });

  // photos not placed in a part: grouped at the end
  const placed = new Set(article.sections.flatMap(section => [...(section.photos || []), ...(section.steps || []).flatMap(step => step.photos || [])]));
  const others = gallery.map((photo, index) => index).filter(index => !placed.has(index));
  if (others.length){
    const gallerySection = document.createElement('section');
    gallerySection.className = 'article-dialog-section';
    addText(gallerySection, 'h3', '', placed.size ? 'Autres captures' : 'Captures du projet');
    addFigures(gallerySection, others);
    dialogContent.append(gallerySection);
  }

  if (article.documents?.length){
    const docSection = document.createElement('section');
    docSection.className = 'article-dialog-section article-documents';
    addText(docSection, 'h3', '', 'Documentation');
    article.documents.forEach(doc => {
      const link = document.createElement('a');
      link.className = 'article-download';
      link.href = doc.href;
      link.setAttribute('download', '');
      addText(link, 'span', '', doc.label);
      if (doc.meta) addText(link, 'small', '', doc.meta);
      docSection.append(link);
    });
    dialogContent.append(docSection);
  }

  addTags(dialogContent, article.tags);
  // already open (previous / next article from the reader): only the content changes
  articleDialog.querySelector('.article-dialog-surface')?.scrollTo(0, 0);
  if (!articleDialog.open) articleDialog.showModal();
  dialogClose.focus({ preventScroll: true });
}

// photo viewer: opens the photo full size inside the page, with previous / next
const lightbox = document.createElement('dialog');
lightbox.className = 'photo-lightbox';
lightbox.setAttribute('aria-label', 'Photo agrandie');
lightbox.innerHTML = `
  <figure class="photo-lightbox-figure">
    <figcaption><span class="photo-lightbox-caption"></span><span class="photo-lightbox-count"></span></figcaption>
  </figure>
  <button type="button" class="photo-lightbox-btn photo-lightbox-close" aria-label="Fermer">×</button>
  <button type="button" class="photo-lightbox-btn photo-lightbox-prev" aria-label="Photo précédente">‹</button>
  <button type="button" class="photo-lightbox-btn photo-lightbox-next" aria-label="Photo suivante">›</button>`;
document.body.append(lightbox);
// the image gets its src when a photo is opened
const lightboxImage = document.createElement('img');
lightbox.querySelector('figure').prepend(lightboxImage);
const lightboxCaption = lightbox.querySelector('.photo-lightbox-caption');
const lightboxCount = lightbox.querySelector('.photo-lightbox-count');
const lightboxPrev = lightbox.querySelector('.photo-lightbox-prev');
const lightboxNext = lightbox.querySelector('.photo-lightbox-next');
let lightboxPhotos = [];
let lightboxIndex = 0;

function showLightboxPhoto(index){
  lightboxIndex = (index + lightboxPhotos.length) % lightboxPhotos.length;
  const photo = lightboxPhotos[lightboxIndex];
  lightboxImage.src = photo.src;
  lightboxImage.alt = photo.caption || '';
  lightboxCaption.textContent = photo.caption || '';
  lightboxCount.textContent = lightboxPhotos.length > 1 ? `${lightboxIndex + 1} / ${lightboxPhotos.length}` : '';
}

function openLightbox(photos, index){
  lightboxPhotos = photos;
  const several = photos.length > 1;
  lightboxPrev.hidden = !several;
  lightboxNext.hidden = !several;
  showLightboxPhoto(index);
  lightbox.showModal();
}

lightbox.querySelector('.photo-lightbox-close').addEventListener('click', () => lightbox.close());
lightboxPrev.addEventListener('click', () => showLightboxPhoto(lightboxIndex - 1));
lightboxNext.addEventListener('click', () => showLightboxPhoto(lightboxIndex + 1));
lightbox.addEventListener('click', event => {
  if (event.target === lightbox || event.target.classList.contains('photo-lightbox-figure')) lightbox.close();
});
lightbox.addEventListener('keydown', event => {
  if (lightboxPhotos.length < 2) return;
  if (event.key === 'ArrowLeft') showLightboxPhoto(lightboxIndex - 1);
  if (event.key === 'ArrowRight') showLightboxPhoto(lightboxIndex + 1);
});
let touchStartX = null;
lightbox.addEventListener('touchstart', event => { touchStartX = event.touches[0].clientX; }, { passive:true });
lightbox.addEventListener('touchend', event => {
  if (touchStartX === null || lightboxPhotos.length < 2) return;
  const distance = event.changedTouches[0].clientX - touchStartX;
  touchStartX = null;
  if (Math.abs(distance) > 50) showLightboxPhoto(lightboxIndex + (distance < 0 ? 1 : -1));
}, { passive:true });

function filterArticles(){
  const query = normalizeText(searchField.value.trim());
  let visibleCount = 0;

  [...articleGrid.querySelectorAll('.article-card')].forEach(card => {
    // the static fallback cards carry their id on the read button only
    const articleId = card.dataset.articleId || card.querySelector('[data-article-id]')?.dataset.articleId;
    const article = articles.find(item => item.id === articleId);
    const synth = synthArticles[articleId];
    // competence names count as searchable text, so "sauvegardes" or "mode projet" finds the right articles
    const competenceText = (synth?.competences || []).map(number => {
      const competence = synthCompetences[number - 1];
      return competence ? `C${number} ${competence.label} ${(competence.skills || []).join(' ')}` : '';
    }).join(' ');
    const sectionText = article ? article.sections.flatMap(section => [section.heading, ...section.paragraphs, ...(section.list || []), ...Object.values(section.competences || {}), ...(section.steps || []).flatMap(step => [step.title, ...step.paragraphs, ...(step.list || [])])]).join(' ') : '';
    const matchesText = normalizeText(`${card.textContent} ${synth?.periodLabel || ''} ${synth?.row || ''} ${competenceText} ${sectionText}`).includes(query);
    const matchesPeriod = activePeriod === 'all' || synth?.period === activePeriod;
    const matchesCompetence = activeCompetence === 'all' || (synth?.competences || []).includes(Number(activeCompetence));
    card.hidden = !matchesText || !matchesPeriod || !matchesCompetence;
    if (!card.hidden) visibleCount += 1;
  });

  articleCount.textContent = `${visibleCount} article${visibleCount === 1 ? '' : 's'}`;
  if (railCount) railCount.textContent = articleCount.textContent;
  emptyMessage.hidden = visibleCount !== 0;
  resetButton.hidden = !query && activePeriod === 'all' && activeCompetence === 'all';
  articleGrid.scrollTo({ left:0 });
  updateRailButtons();
}

// horizontal rail: arrows scroll by one screen of cards, and turn off at each end
function updateRailButtons(){
  const maxScroll = articleGrid.scrollWidth - articleGrid.clientWidth;
  railPrev.disabled = articleGrid.scrollLeft <= 4;
  railNext.disabled = articleGrid.scrollLeft >= maxScroll - 4;
  // progress bar under the rail (shown on phones, where the arrows are hidden)
  if (railThumb){
    const visibleShare = articleGrid.scrollWidth ? Math.min(1, articleGrid.clientWidth / articleGrid.scrollWidth) : 1;
    const progress = maxScroll > 0 ? articleGrid.scrollLeft / maxScroll : 0;
    // thumb spans the whole track; scaleX sizes it, translateX moves it (no layout work while swiping)
    railThumb.style.transform = `translateX(${progress * (1 - visibleShare) * 100}%) scaleX(${visibleShare})`;
  }
}

function scrollRail(direction){
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  articleGrid.scrollBy({ left:direction * articleGrid.clientWidth * 0.9, behavior:reduceMotion ? 'auto' : 'smooth' });
}

function pressChip(group, value){
  group.querySelectorAll('[data-filter]').forEach(chip => {
    chip.setAttribute('aria-pressed', String(chip.dataset.filter === value));
  });
}

function setFilters(period, competence){
  activePeriod = period;
  activeCompetence = competence;
  pressChip(periodChips, period);
  pressChip(competenceChips, competence);
  // the competence row stays hidden until a card chip (or a search link) picks a competence
  competenceChips.classList.toggle('is-shown', competence !== 'all');
  filterArticles();
}

async function loadArticles(){
  try {
    const response = await fetch('articles.json', { headers:{ Accept:'application/json' } });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();
    if (!Array.isArray(data)) throw new Error('Invalid article data');

    articles = data;
    articleGrid.replaceChildren(...articles.map(createArticleCard));
    articleStatus.hidden = true;
    filterArticles();

    const requestedArticleId = new URLSearchParams(window.location.search).get('article');
    const requestedArticle = articles.find(article => article.id === requestedArticleId);
    if (requestedArticle) openArticleById(requestedArticle.id);
  } catch (error){
    articleStatus.textContent = 'Les fiches restent consultables ci-dessous. Pour activer le chargement AJAX, ouvre le site depuis son adresse locale HTTP.';
    console.error('Impossible de charger articles.json', error);
  }
}

function closeArticleDialog(){
  if (!articleDialog.open) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    articleDialog.close();
    return;
  }

  articleDialog.classList.add('is-closing');
  articleDialog.addEventListener('animationend', () => {
    articleDialog.classList.remove('is-closing');
    if (articleDialog.open) articleDialog.close();
  }, { once:true });
}

function openFallbackDetails(details){
  if (details.open) return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const startHeight = details.getBoundingClientRect().height;
  details.open = true;

  if (!reduceMotion){
    const endHeight = details.getBoundingClientRect().height;
    details.style.height = `${startHeight}px`;
    details.style.overflow = 'hidden';

    const animation = details.animate([
      { height:`${startHeight}px`, opacity:0.9 },
      { height:`${endHeight}px`, opacity:1 }
    ], { duration:250, easing:'cubic-bezier(0.23,1,0.32,1)' });
    const clearTemporaryStyles = () => {
      details.style.removeProperty('height');
      details.style.removeProperty('overflow');
    };
    animation.addEventListener('finish', clearTemporaryStyles, { once:true });
    animation.addEventListener('cancel', clearTemporaryStyles, { once:true });
  }

  details.scrollIntoView({ behavior:reduceMotion ? 'auto' : 'smooth', block:'center' });
}

articleGrid.addEventListener('click', event => {
  // competence chip of a card: filter the articles on that competence
  const competenceChip = event.target.closest('[data-filter-competence]');
  if (competenceChip){
    setFilters(activePeriod, competenceChip.dataset.filterCompetence);
    competenceChips.scrollIntoView({ behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block:'nearest' });
    return;
  }
  const button = event.target.closest('[data-article-id]');
  if (!button) return;

  const article = articles.find(item => item.id === button.dataset.articleId);
  if (article){
    renderArticle(article);
    return;
  }

  const fallbackDetails = button.closest('.article-card')?.querySelector('.article-details');
  if (fallbackDetails) openFallbackDetails(fallbackDetails);
});

// open an article from anywhere on the page (e.g. the project cards)
function openArticleById(id){
  const article = articles.find(item => item.id === id);
  if (article){
    renderArticle(article);
    return true;
  }
  return false;
}

document.addEventListener('click', event => {
  const link = event.target.closest('[data-open-article]');
  if (!link) return;
  if (openArticleById(link.dataset.openArticle)) event.preventDefault();
});

searchField.addEventListener('input', filterArticles);
periodChips.addEventListener('click', event => {
  const chip = event.target.closest('[data-filter]');
  if (chip) setFilters(chip.dataset.filter, activeCompetence);
});
competenceChips.addEventListener('click', event => {
  const chip = event.target.closest('[data-filter]');
  if (chip) setFilters(activePeriod, chip.dataset.filter);
});
resetButton.addEventListener('click', () => {
  searchField.value = '';
  setFilters('all', 'all');
  searchField.focus();
});
railPrev.addEventListener('click', () => scrollRail(-1));
railNext.addEventListener('click', () => scrollRail(1));
articleGrid.addEventListener('scroll', updateRailButtons, { passive:true });
window.addEventListener('resize', updateRailButtons, { passive:true });
updateRailButtons();

dialogClose.addEventListener('click', closeArticleDialog);
articleDialog.addEventListener('cancel', event => {
  event.preventDefault();
  closeArticleDialog();
});
articleDialog.addEventListener('click', event => {
  if (event.target === articleDialog) closeArticleDialog();
});

loadArticles();
