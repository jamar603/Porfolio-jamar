// veille technologique: each link opens a modal inside the portfolio instead of leaving it.
// The PDF fiche is shown whole; external sites refuse to be embedded (X-Frame-Options), so their modal
// shows my summary with a button that opens the real page in a window above the portfolio.
(() => {
  const links = [...document.querySelectorAll('[data-veille]')];
  if (!links.length) return;

  const dialog = document.createElement('dialog');
  dialog.className = 'vt-modal';
  dialog.setAttribute('aria-labelledby', 'vtModalTitle');
  dialog.innerHTML = `
    <div class="vt-modal-box">
      <header class="vt-modal-head">
        <div><span class="vt-modal-source"></span><h3 id="vtModalTitle"></h3></div>
        <button type="button" class="vt-modal-close" aria-label="Fermer">×</button>
      </header>
      <div class="vt-modal-body"></div>
      <footer class="vt-modal-foot"></footer>
    </div>`;
  document.body.append(dialog);

  const title = dialog.querySelector('h3');
  const source = dialog.querySelector('.vt-modal-source');
  const body = dialog.querySelector('.vt-modal-body');
  const foot = dialog.querySelector('.vt-modal-foot');

  // the real site opens in a separate window above the portfolio; blocked popup: new tab
  const openSite = url => {
    const width = Math.min(1200, Math.round(screen.availWidth * 0.8));
    const height = Math.min(820, Math.round(screen.availHeight * 0.85));
    const left = Math.round((screen.availWidth - width) / 2 + (screen.availLeft || 0));
    const top = Math.round((screen.availHeight - height) / 2 + (screen.availTop || 0));
    const popup = window.open(url, 'veille-site', `popup=yes,width=${width},height=${height},left=${left},top=${top}`);
    if (popup) { popup.opener = null; popup.focus(); }
    else window.open(url, '_blank', 'noopener');
  };

  const open = key => {
    const tpl = document.getElementById(`vt-modal-${key}`);
    if (!tpl) return false;
    const { title: t, source: s, kind, src, url } = tpl.dataset;
    title.textContent = t || '';
    source.textContent = s || (kind === 'pdf' ? 'PDF' : '');
    // PDF inside the page where the browser has a PDF viewer; elsewhere (Android) the text of the fiche
    const inlinePdf = kind === 'pdf' && navigator.pdfViewerEnabled !== false;
    dialog.classList.toggle('is-pdf', inlinePdf);
    foot.replaceChildren();
    if (kind === 'pdf') {
      if (inlinePdf) {
        const frame = document.createElement('iframe');
        frame.className = 'vt-modal-pdf';
        frame.src = src;
        frame.title = t || 'Document PDF';
        body.replaceChildren(frame);
      } else {
        body.replaceChildren(tpl.content.cloneNode(true));
      }
      const dl = document.createElement('a');
      dl.className = 'btn-outline';
      dl.href = src;
      dl.download = '';
      dl.textContent = 'Télécharger le PDF';
      foot.append(dl);
    } else {
      body.replaceChildren(tpl.content.cloneNode(true));
      const go = document.createElement('button');
      go.type = 'button';
      go.className = 'btn-primary';
      go.textContent = 'Ouvrir le site ↗';
      go.addEventListener('click', () => openSite(url));
      foot.append(go);
    }
    body.scrollTop = 0;
    dialog.showModal();
    dialog.querySelector('.vt-modal-close').focus({ preventScroll: true });
    return true;
  };

  links.forEach(link => link.addEventListener('click', event => {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
    if (open(link.dataset.veille)) event.preventDefault();
  }));
  dialog.querySelector('.vt-modal-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  // free the PDF viewer once closed
  dialog.addEventListener('close', () => { if (dialog.classList.contains('is-pdf')) body.replaceChildren(); });
})();
