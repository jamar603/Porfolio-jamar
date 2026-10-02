const searchField = document.getElementById('articleSearch');
const categoryFilter = document.getElementById('articleCategory');
const articleGrid = document.getElementById('articleGrid');
const articleCount = document.getElementById('articleCount');
const articleStatus = document.getElementById('articleStatus');
const emptyMessage = document.getElementById('articleEmpty');
const resetButton = document.getElementById('resetArticles');
const articleDialog = document.getElementById('articleDialog');
const dialogContent = document.getElementById('articleDialogContent');
const dialogClose = document.getElementById('closeArticleDialog');
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
  addText(meta, 'span', 'article-category', article.categoryLabel);
  addText(meta, 'time', '', article.date);
  content.append(meta);
  addText(content, 'h3', '', article.title);
  addText(content, 'p', 'article-summary', article.summary);
  addTags(content, article.tags);

  const openButton = addText(content, 'button', 'article-read', 'Lire l’article');
  openButton.type = 'button';
  openButton.dataset.articleId = article.id;
  content.append(openButton);
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
  addText(meta, 'span', 'article-category', article.categoryLabel);
  addText(meta, 'time', '', article.date);
  dialogContent.append(meta);
  addText(dialogContent, 'h2', '', article.title).id = 'articleDialogTitle';
  addText(dialogContent, 'p', 'article-dialog-summary', article.summary);

  article.sections.forEach(section => {
    const sectionElement = document.createElement('section');
    sectionElement.className = 'article-dialog-section';
    addText(sectionElement, 'h3', '', section.heading);
    section.paragraphs.forEach(paragraph => addText(sectionElement, 'p', '', paragraph));
    dialogContent.append(sectionElement);
  });

  addTags(dialogContent, article.tags);
  articleDialog.showModal();
  dialogClose.focus();
}

function filterArticles(){
  const query = normalizeText(searchField.value.trim());
  const category = categoryFilter.value;
  let visibleCount = 0;

  [...articleGrid.querySelectorAll('.article-card')].forEach(card => {
    const article = articles.find(item => item.id === card.dataset.articleId);
    const searchableText = article
      ? `${card.textContent} ${article.sections.flatMap(section => [section.heading, ...section.paragraphs]).join(' ')}`
      : card.textContent;
    const matchesText = normalizeText(searchableText).includes(query);
    const matchesCategory = category === 'all' || card.dataset.category === category;
    card.hidden = !matchesText || !matchesCategory;
    if (!card.hidden) visibleCount += 1;
  });

  articleCount.textContent = `${visibleCount} article${visibleCount === 1 ? '' : 's'}`;
  emptyMessage.hidden = visibleCount !== 0;
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
categoryFilter.addEventListener('change', filterArticles);
resetButton.addEventListener('click', () => {
  searchField.value = '';
  categoryFilter.value = 'all';
  filterArticles();
  searchField.focus();
});

dialogClose.addEventListener('click', closeArticleDialog);
articleDialog.addEventListener('cancel', event => {
  event.preventDefault();
  closeArticleDialog();
});
articleDialog.addEventListener('click', event => {
  if (event.target === articleDialog) closeArticleDialog();
});

loadArticles();
