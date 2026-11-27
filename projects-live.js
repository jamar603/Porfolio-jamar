// live project previews: hovering (or focusing) a project card plays its screenshots in the cover,
// with a progress bar per screenshot; on touch screens the preview plays while the card is in view
(() => {
  const cards = document.querySelectorAll('.project-card[data-shots]');
  if (!cards.length) return;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
  const STEP = 1400; // ms per screenshot

  cards.forEach((card) => {
    const cover = card.querySelector('.project-cover');
    if (!cover) return;
    const [folder, list] = card.dataset.shots.split(':');
    const shots = [cover.getAttribute('src'), ...list.split(',').map((n) => `images/articles/${folder}/${n.trim()}.webp`)];

    // wrapper: frame with a browser bar, the cover, a second image for the cross-fade, progress segments
    const frame = document.createElement('div');
    frame.className = 'project-live';
    cover.before(frame);
    const bar = document.createElement('div');
    bar.className = 'project-live-bar';
    bar.setAttribute('aria-hidden', 'true');
    bar.innerHTML = '<i></i><i></i><i></i><span>Aperçu · survolez</span>';
    const next = document.createElement('img');
    next.className = 'project-cover project-live-next';
    next.alt = '';
    next.decoding = 'async';
    const progress = document.createElement('div');
    progress.className = 'project-live-progress';
    progress.setAttribute('aria-hidden', 'true');
    progress.innerHTML = shots.map(() => '<span><b></b></span>').join('');
    frame.append(bar, cover, next, progress);
    const segments = [...progress.children];
    const label = bar.querySelector('span');

    if (reducedMotion.matches) return; // static frame only

    let index = 0;
    let timer = 0;
    let swap = 0;
    let playing = false;
    let preloaded = false;

    const preload = () => {
      if (preloaded) return;
      preloaded = true;
      shots.slice(1).forEach((src) => { const img = new Image(); img.src = src; });
    };

    const paint = () => {
      segments.forEach((seg, i) => {
        seg.classList.toggle('is-done', i < index);
        seg.classList.toggle('is-on', i === index && playing);
      });
    };

    const show = (i) => {
      index = i;
      next.classList.remove('is-shown');
      // swap the hidden image, then fade it in over the current one
      next.onload = () => {
        if (!playing) return;
        requestAnimationFrame(() => next.classList.add('is-shown'));
        // once faded in, the cover takes the new picture so the next fade starts from it
        clearTimeout(swap);
        swap = setTimeout(() => { cover.src = shots[i]; next.classList.remove('is-shown'); }, 420);
      };
      next.src = shots[i];
      paint();
    };

    const play = () => {
      if (playing) return;
      playing = true;
      preload();
      card.classList.add('is-live');
      label.textContent = `Aperçu · ${shots.length} captures`;
      paint();
      timer = setInterval(() => show((index + 1) % shots.length), STEP);
    };

    const stop = () => {
      if (!playing) return;
      playing = false;
      clearInterval(timer);
      clearTimeout(swap);
      next.onload = null;
      card.classList.remove('is-live');
      label.textContent = 'Aperçu · survolez';
      index = 0;
      next.classList.remove('is-shown');
      cover.src = shots[0];
      paint();
    };

    card.style.setProperty('--live-step', `${STEP}ms`);

    if (finePointer.matches) {
      card.addEventListener('pointerenter', play);
      card.addEventListener('pointerleave', stop);
      card.addEventListener('focusin', play);
      card.addEventListener('focusout', (event) => { if (!card.contains(event.relatedTarget)) stop(); });
    } else if ('IntersectionObserver' in window) {
      label.textContent = 'Aperçu';
      new IntersectionObserver(([entry]) => {
        if (entry.intersectionRatio > 0.7) play(); else stop();
      }, { threshold: [0, 0.7] }).observe(card);
    }
  });
})();
