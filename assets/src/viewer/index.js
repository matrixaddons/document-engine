/**
 * Document Engine PDF viewer.
 *
 * Progressive enhancement of `[data-dengine-viewer]` shells rendered by PHP. PDF.js is
 * self-hosted and only loaded when a viewer scrolls near the viewport; pages render lazily.
 */

const settings = window.DocumentEngineViewer || {};
const ZOOM_STEPS = [0.5, 0.67, 0.8, 0.9, 1, 1.1, 1.25, 1.5, 1.75, 2, 2.5, 3];

let pdfjsPromise = null;

function loadPdfjs() {
	if (!pdfjsPromise) {
		pdfjsPromise = import(/* webpackIgnore: true */ settings.lib).then((lib) => {
			lib.GlobalWorkerOptions.workerSrc = settings.worker;
			return lib;
		});
	}
	return pdfjsPromise;
}

function debounce(fn, wait) {
	let t;
	return (...args) => {
		clearTimeout(t);
		t = setTimeout(() => fn(...args), wait);
	};
}

class Viewer {
	constructor(el) {
		this.el = el;
		this.config = JSON.parse(el.getAttribute('data-dengine-viewer') || '{}');
		this.pagesEl = el.querySelector('.dengine-viewer__pages');
		this.statusEl = el.querySelector('.dengine-viewer__status');
		this.pageInput = el.querySelector('.dengine-viewer__page');
		this.totalEl = el.querySelector('[data-total]');
		this.zoomSelect = el.querySelector('[data-zoom-select]');
		this.pages = [];
		this.rendered = new Map();
		this.mode = this.config.zoom || 'page-width';
		this.scale = 1;
		this.current = 1;
		this.baseViewport = null;
		this.pdf = null;
		this.lib = null;
		this.secure = !!this.config.secure;
		this.renderTokens = 0;
		this.searchEl = el.querySelector('.dengine-viewer__search');
		this.findInput = el.querySelector('.dengine-viewer__find');
		this.matchesEl = el.querySelector('.dengine-viewer__matches');
		this.sideEl = el.querySelector('.dengine-viewer__side');
		this.textCache = [];
		this.query = '';
		this.matches = [];
		this.matchIndex = -1;
		this.thumbsBuilt = false;

		el.dengineViewer = this;
		this.bindToolbar();

		if (this.secure) {
			el.addEventListener('contextmenu', (e) => e.preventDefault());
			el.addEventListener('dragstart', (e) => e.preventDefault());
		}

		if ('IntersectionObserver' in window) {
			const io = new IntersectionObserver(
				(entries) => {
					if (entries.some((e) => e.isIntersecting)) {
						io.disconnect();
						this.load();
					}
				},
				{ rootMargin: '400px 0px' }
			);
			io.observe(el);
		} else {
			this.load();
		}
	}

	emit(name, detail = {}) {
		this.el.dispatchEvent(new CustomEvent('dengine:' + name, { bubbles: true, detail: { viewer: this, ...detail } }));
	}

	async load() {
		try {
			this.lib = await loadPdfjs();
			const task = this.lib.getDocument({
				url: this.config.src,
				cMapUrl: settings.cMapUrl,
				cMapPacked: true,
				standardFontDataUrl: settings.standardFontDataUrl,
				wasmUrl: settings.wasmUrl,
				withCredentials: !!this.config.sameOrigin,
				isEvalSupported: false,
				enableXfa: false,
			});
			task.onPassword = (update, reason) => {
				const message = reason === this.lib.PasswordResponses.INCORRECT_PASSWORD ? settings.i18n.wrongPassword : settings.i18n.password;
				// eslint-disable-next-line no-alert
				const pass = window.prompt(message);
				if (pass === null) {
					task.destroy();
					this.fail();
				} else {
					update(pass);
				}
			};
			this.pdf = await task.promise;
			const first = await this.pdf.getPage(1);
			this.baseViewport = first.getViewport({ scale: 1 });
			this.setupPages();
			this.statusEl?.remove();
			this.emit('loaded', { pages: this.pdf.numPages });
			this.startTracking();
			const start = this.linkedPage() || this.config.page;
			if (start > 1) {
				this.goTo(start, false);
			}
		} catch (error) {
			// eslint-disable-next-line no-console
			console.warn('Document Engine viewer:', error);
			this.fail();
		}
	}

	fail() {
		this.statusEl?.remove();
		this.pagesEl.hidden = true;
		const toolbar = this.el.querySelector('.dengine-viewer__toolbar');
		if (toolbar) {
			toolbar.hidden = true;
		}
		const fallback = this.el.querySelector('.dengine-viewer__fallback');
		if (fallback) {
			fallback.hidden = false;
		}
		this.emit('error');
	}

	computeScale() {
		const available = Math.max(200, this.pagesEl.clientWidth - 32);
		const widthScale = available / this.baseViewport.width;
		if (this.mode === 'page-fit') {
			const heightScale = (this.pagesEl.clientHeight - 32) / this.baseViewport.height;
			return Math.max(0.25, Math.min(widthScale, heightScale));
		}
		if (this.mode === 'auto') {
			return Math.min(widthScale, 1.5);
		}
		if (this.mode === 'page-width') {
			return widthScale;
		}
		const numeric = parseFloat(this.mode);
		return numeric > 0 ? (numeric > 10 ? numeric / 100 : numeric) : widthScale;
	}

	setupPages() {
		const total = this.pdf.numPages;
		if (this.totalEl) {
			this.totalEl.textContent = String(total);
		}
		if (this.pageInput) {
			this.pageInput.max = String(total);
		}
		this.pagesEl.innerHTML = '';
		this.pages = [];
		for (let n = 1; n <= total; n++) {
			const page = document.createElement('div');
			page.className = 'dengine-viewer__sheet';
			page.dataset.page = String(n);
			page.setAttribute('aria-label', (settings.i18n.pageOf || 'Page %1$d of %2$d').replace('%1$d', n).replace('%2$d', total));
			page.setAttribute('role', 'img');
			this.pagesEl.appendChild(page);
			this.pages.push(page);
		}

		this.pageObserver = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						this.renderPage(parseInt(entry.target.dataset.page, 10));
					}
				});
			},
			{ root: this.pagesEl, rootMargin: '800px 0px' }
		);
		this.pages.forEach((p) => this.pageObserver.observe(p));

		this.pagesEl.addEventListener('scroll', debounce(() => this.trackPage(), 60), { passive: true });

		if ('ResizeObserver' in window) {
			let lastWidth = this.pagesEl.clientWidth;
			new ResizeObserver(
				debounce(() => {
					const width = this.pagesEl.clientWidth;
					if (Math.abs(width - lastWidth) > 4 && ['page-width', 'page-fit', 'auto'].includes(this.mode)) {
						lastWidth = width;
						this.relayout();
					}
				}, 150)
			).observe(this.pagesEl);
		}

		this.layout();
		this.updateControls();
	}

	layout() {
		this.scale = this.computeScale();
		const w = Math.floor(this.baseViewport.width * this.scale);
		const h = Math.floor(this.baseViewport.height * this.scale);
		this.pages.forEach((page) => {
			if (!page.dataset.w) {
				page.style.width = w + 'px';
				page.style.height = h + 'px';
			}
		});
		this.syncZoomSelect();
	}

	syncZoomSelect() {
		if (!this.zoomSelect) {
			return;
		}
		const custom = this.zoomSelect.querySelector('[data-custom]');
		const pct = String(Math.round(this.scale * 100));
		if (this.mode === 'page-width' || this.mode === 'page-fit') {
			this.zoomSelect.value = this.mode;
			custom.hidden = true;
			return;
		}
		const match = [...this.zoomSelect.options].find((o) => o.value === pct);
		if (match) {
			this.zoomSelect.value = pct;
			custom.hidden = true;
		} else {
			custom.textContent = pct + '%';
			custom.hidden = false;
			this.zoomSelect.value = 'custom';
		}
	}

	relayout() {
		const keep = this.current;
		this.renderTokens++;
		this.rendered.clear();
		this.pages.forEach((page) => {
			page.innerHTML = '';
			delete page.dataset.w;
		});
		this.layout();
		this.goTo(keep, false);
		// Re-render what is visible now.
		this.pages.forEach((p) => {
			this.pageObserver.unobserve(p);
			this.pageObserver.observe(p);
		});
	}

	async renderPage(n) {
		if (this.rendered.has(n)) {
			return;
		}
		const token = this.renderTokens;
		this.rendered.set(n, true);
		const holder = this.pages[n - 1];
		try {
			const page = await this.pdf.getPage(n);
			if (token !== this.renderTokens) {
				return;
			}
			const viewport = page.getViewport({ scale: this.scale });
			const ratio = Math.min(window.devicePixelRatio || 1, 2);
			const canvas = document.createElement('canvas');
			canvas.width = Math.floor(viewport.width * ratio);
			canvas.height = Math.floor(viewport.height * ratio);
			canvas.style.width = Math.floor(viewport.width) + 'px';
			canvas.style.height = Math.floor(viewport.height) + 'px';

			holder.style.width = Math.floor(viewport.width) + 'px';
			holder.style.height = Math.floor(viewport.height) + 'px';
			holder.dataset.w = '1';
			holder.style.setProperty('--scale-factor', String(viewport.scale));
			holder.style.setProperty('--total-scale-factor', String(viewport.scale));

			const ctx = canvas.getContext('2d');
			await page.render({
				canvasContext: ctx,
				canvas,
				viewport,
				transform: ratio !== 1 ? [ratio, 0, 0, ratio, 0, 0] : null,
			}).promise;
			if (token !== this.renderTokens) {
				return;
			}

			if (this.config.watermark) {
				this.drawWatermark(ctx, canvas.width, canvas.height, ratio);
			}

			holder.innerHTML = '';
			holder.appendChild(canvas);
			this.addLinks(page, viewport, holder);

			if (this.config.textLayer && !this.secure && this.lib.TextLayer) {
				const textDiv = document.createElement('div');
				textDiv.className = 'textLayer';
				holder.appendChild(textDiv);
				const textLayer = new this.lib.TextLayer({
					textContentSource: page.streamTextContent(),
					container: textDiv,
					viewport,
				});
				await textLayer.render();
			}
			holder.removeAttribute('role');
			if (this.query) {
				this.highlight(n);
			}
			this.emit('pagerendered', { page: n });
		} catch (error) {
			this.rendered.delete(n);
		}
	}

	/**
	 * Makes links inside the PDF clickable: web links open in a new tab, internal links jump to their page.
	 */
	async addLinks(page, viewport, holder) {
		let annotations = [];
		try {
			annotations = await page.getAnnotations({ intent: 'display' });
		} catch (e) {
			return;
		}
		annotations
			.filter((a) => a.subtype === 'Link' && (a.url || a.dest))
			.forEach((a) => {
				const [x1, y1, x2, y2] = viewport.convertToViewportRectangle(a.rect);
				const link = document.createElement('a');
				link.className = 'dengine-viewer__link';
				link.style.cssText = `left:${Math.min(x1, x2)}px;top:${Math.min(y1, y2)}px;width:${Math.abs(x2 - x1)}px;height:${Math.abs(y2 - y1)}px`;
				if (a.url && /^(https?:|mailto:)/i.test(a.url)) {
					link.href = a.url;
					link.target = '_blank';
					link.rel = 'noopener noreferrer';
					link.setAttribute('aria-label', a.url);
				} else if (a.dest) {
					link.href = '#';
					link.addEventListener('click', async (event) => {
						event.preventDefault();
						try {
							const dest = typeof a.dest === 'string' ? await this.pdf.getDestination(a.dest) : a.dest;
							const index = typeof dest[0] === 'number' ? dest[0] : await this.pdf.getPageIndex(dest[0]);
							this.goTo(index + 1);
						} catch (e) {
							// Broken internal link: ignore.
						}
					});
				} else {
					return;
				}
				holder.appendChild(link);
			});
	}

	drawWatermark(ctx, width, height, ratio) {
		const text = String(this.config.watermark);
		ctx.save();
		ctx.globalAlpha = this.config.watermarkOpacity || 0.14;
		ctx.fillStyle = this.config.watermarkColor || '#1e1e1e';
		const size = Math.max(12, Math.round(width / 28));
		ctx.font = `600 ${size}px system-ui, sans-serif`;
		ctx.translate(width / 2, height / 2);
		ctx.rotate(-Math.PI / 6);
		const step = size * 7;
		const textWidth = ctx.measureText(text).width + size * 3;
		for (let y = -height; y < height; y += step) {
			for (let x = -width; x < width; x += textWidth) {
				ctx.fillText(text, x, y);
			}
		}
		ctx.restore();
		void ratio;
	}

	/**
	 * Reading time per page (only when an add-on asks for it, e.g. Pro reading analytics).
	 * Counts seconds while the viewer is on screen and the tab is visible, and reports in the background.
	 */
	startTracking() {
		const track = this.config.track;
		if (!track || !track.url || this.tracking) {
			return;
		}
		this.tracking = { session: Array.from(window.crypto?.getRandomValues?.(new Uint8Array(8)) || [], (b) => b.toString(16).padStart(2, '0')).join('') || String(Math.random()).slice(2, 18).padEnd(16, '0'), times: {}, max: 1, dirty: false, visible: false };
		if (this.tracking.session.length !== 16) {
			this.tracking.session = (this.tracking.session + '0000000000000000').slice(0, 16);
		}
		if ('IntersectionObserver' in window) {
			new IntersectionObserver((entries) => {
				this.tracking.visible = entries.some((e) => e.isIntersecting);
			}, { threshold: 0.2 }).observe(this.pagesEl);
		} else {
			this.tracking.visible = true;
		}
		const send = (beacon) => {
			if (!this.tracking.dirty) {
				return;
			}
			this.tracking.dirty = false;
			const body = JSON.stringify({ doc: track.doc, session: this.tracking.session, pages: this.pdf.numPages, max: this.tracking.max, times: this.tracking.times });
			if (beacon && navigator.sendBeacon) {
				navigator.sendBeacon(track.url, new Blob([body], { type: 'application/json' }));
			} else {
				window.fetch(track.url, { method: 'POST', body, credentials: 'same-origin', keepalive: true, headers: { 'Content-Type': 'application/json' } }).catch(() => {});
			}
		};
		setInterval(() => {
			if (document.visibilityState === 'visible' && this.tracking.visible) {
				const page = this.current;
				this.tracking.times[page] = (this.tracking.times[page] || 0) + 1;
				this.tracking.max = Math.max(this.tracking.max, page);
				this.tracking.dirty = true;
			}
		}, 1000);
		setInterval(() => send(false), 20000);
		document.addEventListener('visibilitychange', () => {
			if (document.visibilityState === 'hidden') {
				send(true);
			}
		});
		window.addEventListener('pagehide', () => send(true));
	}

	/**
	 * Deep links: #page=5 opens the first viewer on the page at page 5.
	 */
	linkedPage() {
		const first = document.querySelector('[data-dengine-viewer]');
		const match = /(?:^|[#&])page=(\d+)/.exec(window.location.hash || '');
		return first === this.el && match ? parseInt(match[1], 10) : 0;
	}

	/* ---------- Search ---------- */

	openSearch() {
		if (!this.searchEl) {
			return;
		}
		this.searchEl.hidden = false;
		this.el.querySelector('[data-action="search"]')?.setAttribute('aria-pressed', 'true');
		this.findInput.focus();
		this.findInput.select();
	}

	closeSearch() {
		if (!this.searchEl) {
			return;
		}
		this.searchEl.hidden = true;
		this.el.querySelector('[data-action="search"]')?.setAttribute('aria-pressed', 'false');
		this.query = '';
		this.matches = [];
		this.matchIndex = -1;
		this.clearHighlights();
		this.matchesEl.textContent = '';
		this.pagesEl.focus();
	}

	async pageText(n) {
		if (this.textCache[n] === undefined) {
			const page = await this.pdf.getPage(n);
			const content = await page.getTextContent();
			this.textCache[n] = content.items.map((item) => item.str).join(' ');
		}
		return this.textCache[n];
	}

	async find(query) {
		const q = query.trim().toLowerCase();
		this.query = q;
		this.matches = [];
		this.matchIndex = -1;
		this.clearHighlights();
		if (!q || !this.pdf) {
			this.matchesEl.textContent = '';
			return;
		}
		this.matchesEl.textContent = settings.i18n.searching || '…';
		for (let n = 1; n <= this.pdf.numPages; n++) {
			const text = (await this.pageText(n)).toLowerCase();
			if (this.query !== q) {
				return; // A newer search started.
			}
			let at = text.indexOf(q);
			let k = 0;
			while (at !== -1) {
				this.matches.push({ page: n, index: k++ });
				at = text.indexOf(q, at + q.length);
			}
		}
		if (!this.matches.length) {
			this.matchesEl.textContent = settings.i18n.noMatches || '0';
			return;
		}
		// Start from the first match on or after the current page.
		const next = this.matches.findIndex((m) => m.page >= this.current);
		this.showMatch(next === -1 ? 0 : next);
	}

	showMatch(i) {
		if (!this.matches.length) {
			return;
		}
		this.matchIndex = (i + this.matches.length) % this.matches.length;
		const match = this.matches[this.matchIndex];
		this.matchesEl.textContent = (settings.i18n.matchOf || '%1$d / %2$d').replace('%1$d', this.matchIndex + 1).replace('%2$d', this.matches.length);
		if (match.page !== this.current) {
			this.goTo(match.page);
		}
		this.highlight(match.page);
	}

	clearHighlights() {
		this.el.querySelectorAll('mark.dengine-hl').forEach((mark) => {
			const parent = mark.parentNode;
			parent.replaceChild(document.createTextNode(mark.textContent), mark);
			parent.normalize();
		});
	}

	/**
	 * Marks the query inside a page's text layer (matches split across text runs still jump to the page).
	 */
	highlight(n) {
		const holder = this.pages[n - 1];
		const layer = holder && holder.querySelector('.textLayer');
		if (!layer || !this.query) {
			return;
		}
		if (!layer.querySelector('mark.dengine-hl')) {
			const q = this.query;
			layer.querySelectorAll('span').forEach((span) => {
				if (span.children.length) {
					return;
				}
				const text = span.textContent;
				const lower = text.toLowerCase();
				let at = lower.indexOf(q);
				if (at === -1) {
					return;
				}
				const frag = document.createDocumentFragment();
				let last = 0;
				while (at !== -1) {
					frag.appendChild(document.createTextNode(text.slice(last, at)));
					const mark = document.createElement('mark');
					mark.className = 'dengine-hl';
					mark.textContent = text.slice(at, at + q.length);
					frag.appendChild(mark);
					last = at + q.length;
					at = lower.indexOf(q, last);
				}
				frag.appendChild(document.createTextNode(text.slice(last)));
				span.textContent = '';
				span.appendChild(frag);
			});
		}
		const match = this.matches[this.matchIndex];
		if (!match || match.page !== n) {
			return;
		}
		const marks = layer.querySelectorAll('mark.dengine-hl');
		marks.forEach((m) => m.classList.remove('is-current'));
		const current = marks[Math.min(match.index, marks.length - 1)];
		if (current) {
			current.classList.add('is-current');
			const top = current.getBoundingClientRect().top - this.pagesEl.getBoundingClientRect().top + this.pagesEl.scrollTop - this.pagesEl.clientHeight / 3;
			this.pagesEl.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
		}
	}

	/* ---------- Sidebar: thumbnails and outline ---------- */

	toggleSide() {
		if (!this.sideEl) {
			return;
		}
		const open = this.sideEl.hidden;
		this.sideEl.hidden = !open;
		this.el.classList.toggle('has-side', open);
		this.el.querySelector('[data-action="sidebar"]')?.setAttribute('aria-pressed', open ? 'true' : 'false');
		if (open && this.pdf && !this.thumbsBuilt) {
			this.buildThumbs();
			this.buildOutline();
		}
	}

	buildThumbs() {
		this.thumbsBuilt = true;
		const panel = this.sideEl.querySelector('[data-side-panel="thumbs"]');
		const width = 112;
		const height = Math.round((this.baseViewport.height / this.baseViewport.width) * width);
		const io = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						io.unobserve(entry.target);
						this.renderThumb(entry.target, width);
					}
				});
			},
			{ root: panel, rootMargin: '300px 0px' }
		);
		for (let n = 1; n <= this.pdf.numPages; n++) {
			const button = document.createElement('button');
			button.type = 'button';
			button.className = 'dengine-viewer__thumb';
			button.dataset.page = String(n);
			button.setAttribute('aria-label', (settings.i18n.pageN || 'Page %d').replace('%d', n));
			button.innerHTML = `<span class="dengine-viewer__thumbimg" style="width:${width}px;height:${height}px"></span><span class="dengine-viewer__thumbnum">${n}</span>`;
			button.addEventListener('click', () => {
				this.goTo(n);
				// On small screens the sidebar covers the page: close it after a pick.
				if (this.el.clientWidth < 600) {
					this.toggleSide();
				}
			});
			panel.appendChild(button);
			io.observe(button);
		}
		this.markThumb();
	}

	async renderThumb(button, width) {
		try {
			const page = await this.pdf.getPage(parseInt(button.dataset.page, 10));
			const base = page.getViewport({ scale: 1 });
			const viewport = page.getViewport({ scale: width / base.width });
			const canvas = document.createElement('canvas');
			canvas.width = Math.floor(viewport.width);
			canvas.height = Math.floor(viewport.height);
			const ctx = canvas.getContext('2d');
			await page.render({ canvasContext: ctx, canvas, viewport }).promise;
			if (this.config.watermark) {
				this.drawWatermark(ctx, canvas.width, canvas.height, 1);
			}
			const slot = button.querySelector('.dengine-viewer__thumbimg');
			slot.innerHTML = '';
			slot.appendChild(canvas);
		} catch (e) {
			// A thumbnail that fails to render just stays blank.
		}
	}

	markThumb() {
		if (!this.sideEl || !this.thumbsBuilt) {
			return;
		}
		this.sideEl.querySelectorAll('.dengine-viewer__thumb').forEach((b) => {
			const active = parseInt(b.dataset.page, 10) === this.current;
			b.classList.toggle('is-current', active);
			if (active) {
				b.setAttribute('aria-current', 'page');
				if (!this.sideEl.hidden) {
					const panel = b.parentElement;
					if (b.offsetTop < panel.scrollTop || b.offsetTop + b.offsetHeight > panel.scrollTop + panel.clientHeight) {
						panel.scrollTo({ top: b.offsetTop - 12 });
					}
				}
			} else {
				b.removeAttribute('aria-current');
			}
		});
	}

	async buildOutline() {
		let outline = null;
		try {
			outline = await this.pdf.getOutline();
		} catch (e) {
			outline = null;
		}
		if (!outline || !outline.length) {
			return;
		}
		const nav = this.sideEl.querySelector('[data-side-panel="outline"]');
		const build = (items) => {
			const ul = document.createElement('ul');
			items.forEach((item) => {
				const li = document.createElement('li');
				const button = document.createElement('button');
				button.type = 'button';
				button.textContent = item.title || '—';
				button.addEventListener('click', async () => {
					try {
						let dest = item.dest;
						if (typeof dest === 'string') {
							dest = await this.pdf.getDestination(dest);
						}
						if (Array.isArray(dest) && dest[0]) {
							const index = typeof dest[0] === 'number' ? dest[0] : await this.pdf.getPageIndex(dest[0]);
							this.goTo(index + 1);
						}
					} catch (e) {
						// Broken outline entry: ignore.
					}
				});
				li.appendChild(button);
				if (item.items && item.items.length) {
					li.appendChild(build(item.items));
				}
				ul.appendChild(li);
			});
			return ul;
		};
		nav.appendChild(build(outline));
		const tab = this.sideEl.querySelector('[data-side-tab="outline"]');
		tab.hidden = false;
	}

	switchSideTab(name) {
		this.sideEl.querySelectorAll('[data-side-tab]').forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.sideTab === name ? 'true' : 'false'));
		this.sideEl.querySelectorAll('[data-side-panel]').forEach((panel) => {
			panel.hidden = panel.dataset.sidePanel !== name;
		});
	}

	trackPage() {
		const top = this.pagesEl.scrollTop + this.pagesEl.clientHeight * 0.3;
		let current = 1;
		for (const page of this.pages) {
			if (page.offsetTop - this.pagesEl.offsetTop <= top) {
				current = parseInt(page.dataset.page, 10);
			} else {
				break;
			}
		}
		if (current !== this.current) {
			this.current = current;
			this.updateControls();
			this.markThumb();
			this.emit('pagechange', { page: current });
		}
	}

	goTo(n, smooth = true) {
		if (!this.pdf) {
			return;
		}
		const page = Math.min(Math.max(1, n), this.pdf.numPages);
		const target = this.pages[page - 1];
		this.pagesEl.scrollTo({ top: target.offsetTop - this.pagesEl.offsetTop - 8, behavior: smooth ? 'smooth' : 'auto' });
		this.current = page;
		this.updateControls();
		this.markThumb();
	}

	updateControls() {
		if (this.pageInput) {
			this.pageInput.value = String(this.current);
		}
		const prev = this.el.querySelector('[data-action="prev"]');
		const next = this.el.querySelector('[data-action="next"]');
		if (prev) {
			prev.disabled = this.current <= 1;
		}
		if (next) {
			next.disabled = !this.pdf || this.current >= this.pdf.numPages;
		}
	}

	zoom(direction) {
		if (!this.baseViewport) {
			return;
		}
		const current = this.scale;
		let nextScale;
		if (direction > 0) {
			nextScale = ZOOM_STEPS.find((s) => s > current + 0.01) || ZOOM_STEPS[ZOOM_STEPS.length - 1];
		} else {
			nextScale = [...ZOOM_STEPS].reverse().find((s) => s < current - 0.01) || ZOOM_STEPS[0];
		}
		this.mode = String(nextScale);
		this.relayout();
	}

	print() {
		const url = this.config.src;
		if (!this.config.sameOrigin) {
			window.open(url, '_blank', 'noopener');
			return;
		}
		const frame = document.createElement('iframe');
		frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
		frame.src = url;
		frame.onload = () => {
			try {
				frame.contentWindow.focus();
				frame.contentWindow.print();
			} catch (e) {
				window.open(url, '_blank', 'noopener');
			}
			setTimeout(() => frame.remove(), 60000);
		};
		document.body.appendChild(frame);
	}

	toggleFullscreen() {
		const button = this.el.querySelector('[data-action="fullscreen"]');
		if (document.fullscreenElement === this.el) {
			document.exitFullscreen();
		} else if (this.el.requestFullscreen) {
			this.el.requestFullscreen().catch(() => this.el.classList.toggle('is-fullscreen'));
		} else {
			this.el.classList.toggle('is-fullscreen');
		}
		document.addEventListener(
			'fullscreenchange',
			() => {
				button?.setAttribute('aria-pressed', document.fullscreenElement === this.el ? 'true' : 'false');
				if (['page-width', 'page-fit', 'auto'].includes(this.mode)) {
					setTimeout(() => this.relayout(), 100);
				}
			},
			{ once: true }
		);
	}

	bindToolbar() {
		this.el.addEventListener('click', (event) => {
			const control = event.target.closest('[data-action]');
			if (!control || !this.el.contains(control)) {
				return;
			}
			const action = control.getAttribute('data-action');
			switch (action) {
				case 'prev':
					this.goTo(this.current - 1);
					break;
				case 'next':
					this.goTo(this.current + 1);
					break;
				case 'zoom-in':
					this.zoom(1);
					break;
				case 'zoom-out':
					this.zoom(-1);
					break;
				case 'fit':
					this.mode = 'page-width';
					this.relayout();
					break;
				case 'print':
					this.print();
					break;
				case 'fullscreen':
					this.toggleFullscreen();
					break;
				case 'download':
					this.emit('download');
					return;
				case 'search':
					if (this.searchEl && !this.searchEl.hidden) {
						this.closeSearch();
					} else {
						this.openSearch();
					}
					break;
				case 'search-close':
					this.closeSearch();
					break;
				case 'find-next':
					this.showMatch(this.matchIndex + 1);
					break;
				case 'find-prev':
					this.showMatch(this.matchIndex - 1);
					break;
				case 'sidebar':
					this.toggleSide();
					break;
			}
		});

		if (this.pageInput) {
			this.pageInput.addEventListener('change', () => this.goTo(parseInt(this.pageInput.value, 10) || 1));
		}
		if (this.findInput) {
			const run = debounce(() => this.find(this.findInput.value), 250);
			this.findInput.addEventListener('input', run);
			this.findInput.addEventListener('keydown', (event) => {
				if (event.key === 'Enter') {
					event.preventDefault();
					if (this.findInput.value.trim().toLowerCase() !== this.query) {
						this.find(this.findInput.value);
					} else {
						this.showMatch(this.matchIndex + (event.shiftKey ? -1 : 1));
					}
				} else if (event.key === 'Escape') {
					event.preventDefault();
					this.closeSearch();
				}
			});
		}
		if (this.sideEl) {
			this.sideEl.addEventListener('click', (event) => {
				const tab = event.target.closest('[data-side-tab]');
				if (tab) {
					this.switchSideTab(tab.dataset.sideTab);
				}
			});
		}
		// Ctrl/Cmd+F inside the viewer searches the document instead of the web page.
		this.el.addEventListener('keydown', (event) => {
			if (this.searchEl && (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'f') {
				event.preventDefault();
				this.openSearch();
			}
		});
		if (this.zoomSelect) {
			this.zoomSelect.addEventListener('change', () => {
				const value = this.zoomSelect.value;
				if (value === 'custom') {
					return;
				}
				this.mode = value === 'page-width' || value === 'page-fit' ? value : String(parseInt(value, 10) / 100);
				this.relayout();
			});
		}

		this.pagesEl.addEventListener('keydown', (event) => {
			if (event.target !== this.pagesEl) {
				return;
			}
			if (event.key === 'ArrowRight' || event.key === 'n') {
				this.goTo(this.current + 1);
				event.preventDefault();
			} else if (event.key === 'ArrowLeft' || event.key === 'p') {
				this.goTo(this.current - 1);
				event.preventDefault();
			} else if (event.key === '+' || event.key === '=') {
				this.zoom(1);
				event.preventDefault();
			} else if (event.key === '-') {
				this.zoom(-1);
				event.preventDefault();
			}
		});
	}
}

function boot(root = document) {
	root.querySelectorAll('[data-dengine-viewer]').forEach((el) => {
		if (!el.dengineViewer) {
			new Viewer(el); // eslint-disable-line no-new
		}
	});
}

window.DocumentEngineViewerBoot = boot;

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => boot());
} else {
	boot();
}
