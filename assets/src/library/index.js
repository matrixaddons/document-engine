/**
 * Document Engine library: in-place search, filters, sorting and pagination.
 *
 * The server renders a working form and paginated links; this script swaps results
 * through the REST route and keeps the URL shareable. Any failure falls back to a normal page load.
 */

const settings = window.DocumentEngineLibrary || {};

function debounce(fn, wait) {
	let t;
	return (...args) => {
		clearTimeout(t);
		t = setTimeout(() => fn(...args), wait);
	};
}

class Library {
	constructor(el) {
		this.el = el;
		this.config = JSON.parse(el.getAttribute('data-dengine-library') || '{}');
		this.prefix = this.config.id + '_';
		this.form = el.querySelector('.dengine-library__controls');
		this.results = el.querySelector('.dengine-library__results');
		this.controller = null;
		el.dengineLibrary = this;
		this.bind();
	}

	params(extra = {}) {
		const params = new URLSearchParams();
		if (this.form) {
			new FormData(this.form).forEach((value, key) => {
				if (key.indexOf(this.prefix) !== 0 || value === '') {
					return;
				}
				// Checkbox pickers (name[]) travel as one comma list: dl_cat=a,b.
				if (key.slice(-2) === '[]') {
					const name = key.slice(0, -2);
					params.set(name, params.has(name) ? params.get(name) + ',' + value : value);
				} else {
					params.set(key, value);
				}
			});
		}
		Object.keys(extra).forEach((key) => {
			const name = this.prefix + key;
			if (extra[key] === '' || extra[key] === null) {
				params.delete(name);
			} else {
				params.set(name, extra[key]);
			}
		});
		return params;
	}

	async fetch(params, { push = true, scroll = false } = {}) {
		if (this.controller) {
			this.controller.abort();
		}
		this.controller = new AbortController();

		const pageUrl = new URL(window.location.href);
		[...pageUrl.searchParams.keys()].forEach((key) => {
			if (key.indexOf(this.prefix) === 0) {
				pageUrl.searchParams.delete(key);
			}
		});
		params.forEach((value, key) => pageUrl.searchParams.set(key, value));

		const request = new URL(settings.rest, window.location.href);
		request.searchParams.set('atts', JSON.stringify(this.config.atts));
		request.searchParams.set('_page', pageUrl.pathname + pageUrl.search);
		params.forEach((value, key) => request.searchParams.set(key, value));

		this.results.setAttribute('aria-busy', 'true');
		this.el.classList.add('is-loading');

		try {
			const response = await window.fetch(request.toString(), {
				credentials: 'same-origin',
				headers: settings.nonce ? { 'X-WP-Nonce': settings.nonce } : {},
				signal: this.controller.signal,
			});
			if (!response.ok) {
				throw new Error(String(response.status));
			}
			const data = await response.json();
			this.results.innerHTML = data.html;
			if (push) {
				window.history.pushState({ dengineLibrary: this.config.id }, '', pageUrl.toString());
			}
			if (scroll) {
				const top = this.el.getBoundingClientRect().top + window.scrollY - 80;
				if (top < window.scrollY) {
					window.scrollTo({ top, behavior: 'smooth' });
				}
			}
			this.el.dispatchEvent(new CustomEvent('dengine:library-updated', { bubbles: true, detail: { library: this } }));
			if (window.DocumentEngineViewerBoot) {
				window.DocumentEngineViewerBoot(this.results);
			}
		} catch (error) {
			if (error.name !== 'AbortError') {
				window.location.href = pageUrl.toString() + '#' + this.el.id;
			}
		} finally {
			this.results.setAttribute('aria-busy', 'false');
			this.el.classList.remove('is-loading');
		}
	}

	bind() {
		if (this.form) {
			this.form.addEventListener('submit', (event) => {
				event.preventDefault();
				this.fetch(this.params());
			});
			const pick = debounce(() => this.fetch(this.params()), 300);
			this.form.addEventListener('change', (event) => {
				if (event.target.matches('select')) {
					this.fetch(this.params());
				} else if (event.target.matches('[data-dengine-multi] input[type="checkbox"]')) {
					this.updateCounts();
					pick();
				}
			});
			// Open pickers towards the side with room.
			this.form.querySelectorAll('details[data-dengine-multi]').forEach((d) => {
				d.addEventListener('toggle', () => {
					if (d.open) {
						d.classList.remove('is-flip');
						const panel = d.querySelector('.dengine-multi__panel');
						if (panel && panel.getBoundingClientRect().right > document.documentElement.clientWidth - 8) {
							d.classList.add('is-flip');
						}
					}
				});
			});
			// Close checkbox pickers on outside click or Escape.
			document.addEventListener('click', (event) => {
				this.form.querySelectorAll('details[data-dengine-multi][open]').forEach((d) => {
					if (!d.contains(event.target)) {
						d.open = false;
					}
				});
			});
			this.form.addEventListener('keydown', (event) => {
				const open = event.key === 'Escape' && event.target.closest('details[data-dengine-multi][open]');
				if (open) {
					open.open = false;
					open.querySelector('summary').focus();
				}
			});
			const search = this.form.querySelector('input[type="search"]');
			if (search) {
				const run = debounce(() => this.fetch(this.params(), { push: false }), 350);
				search.addEventListener('input', () => {
					if (search.value.length === 0 || search.value.length >= 2) {
						run();
					}
				});
			}
			this.form.classList.add('is-enhanced');
		}

		this.el.addEventListener('click', (event) => {
			// Filter chips, "Clear all" and empty-state links carry the target state in their URL.
			const nav = event.target.closest('a[data-dengine-nav]');
			if (nav) {
				event.preventDefault();
				const params = new URLSearchParams();
				new URL(nav.href, window.location.href).searchParams.forEach((value, key) => {
					if (key.indexOf(this.prefix) === 0) {
						params.set(key, value);
					}
				});
				this.syncForm(params);
				this.fetch(params, { scroll: true });
				return;
			}

			const pageLink = event.target.closest('a.dengine-pagination__link[data-page]');
			if (pageLink) {
				event.preventDefault();
				this.fetch(this.params({ p: pageLink.getAttribute('data-page') }), { scroll: true });
				return;
			}

			const filterLink = event.target.closest('a[data-filter]');
			if (filterLink && this.form) {
				const name = this.prefix + filterLink.getAttribute('data-filter');
				const value = filterLink.getAttribute('data-value');
				const select = this.form.querySelector(`select[name="${name}"]`);
				const boxes = this.form.querySelectorAll(`input[type="checkbox"][name="${name}[]"]`);
				if (select) {
					event.preventDefault();
					select.value = value;
					this.fetch(this.params(), { scroll: true });
				} else if (boxes.length) {
					event.preventDefault();
					boxes.forEach((box) => {
						box.checked = box.value === value;
					});
					this.updateCounts();
					this.fetch(this.params(), { scroll: true });
				}
			}
		});
	}

	syncForm(params) {
		if (!this.form) {
			return;
		}
		this.form.querySelectorAll('[name]').forEach((field) => {
			if (field.name.indexOf(this.prefix) !== 0) {
				return;
			}
			if (field.type === 'checkbox' && field.name.slice(-2) === '[]') {
				field.checked = (params.get(field.name.slice(0, -2)) || '').split(',').includes(field.value);
			} else {
				field.value = params.get(field.name) || '';
			}
		});
		this.updateCounts();
	}

	updateCounts() {
		this.form.querySelectorAll('[data-dengine-multi]').forEach((picker) => {
			const count = picker.querySelectorAll('input[type="checkbox"]:checked').length;
			const badge = picker.querySelector('.dengine-multi__count');
			if (badge) {
				badge.textContent = count ? count.toLocaleString() : '';
				badge.hidden = !count;
			}
		});
	}

	restoreFromUrl() {
		const url = new URL(window.location.href);
		const params = new URLSearchParams();
		url.searchParams.forEach((value, key) => {
			if (key.indexOf(this.prefix) !== 0) {
				return;
			}
			const name = key.slice(-2) === '[]' ? key.slice(0, -2) : key;
			params.set(name, params.has(name) && name !== key ? params.get(name) + ',' + value : value);
		});
		this.syncForm(params);
		this.fetch(params, { push: false });
	}
}

const libraries = [];

function boot(root = document) {
	root.querySelectorAll('[data-dengine-library]').forEach((el) => {
		if (!el.dengineLibrary && settings.rest && window.fetch && window.URLSearchParams) {
			libraries.push(new Library(el));
		}
	});
}

/**
 * Preview popup: "View" opens PDFs (in the viewer), images, audio and video without leaving the library.
 */
let dialog = null;

function previewDialog() {
	if (dialog) {
		return dialog;
	}
	dialog = document.createElement('dialog');
	dialog.className = 'dengine-preview';
	dialog.setAttribute('aria-labelledby', 'dengine-preview-title');
	dialog.innerHTML =
		'<div class="dengine-preview__bar"><h2 class="dengine-preview__title" id="dengine-preview-title"></h2><div class="dengine-preview__actions"></div>' +
		'<button type="button" class="dengine-preview__close" aria-label="' + (settings.i18n?.close || 'Close') + '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>' +
		'<div class="dengine-preview__body"></div>';
	document.body.appendChild(dialog);
	dialog.querySelector('.dengine-preview__close').addEventListener('click', () => dialog.close());
	dialog.addEventListener('click', (event) => {
		if (event.target === dialog) {
			dialog.close();
		}
	});
	dialog.addEventListener('close', () => {
		dialog.querySelector('.dengine-preview__body').innerHTML = '';
		document.body.classList.remove('dengine-preview-open');
		if (dialog.opener) {
			dialog.opener.focus();
		}
	});
	return dialog;
}

async function openPreview(link) {
	if (!settings.preview || !window.fetch || typeof HTMLDialogElement === 'undefined') {
		return false;
	}
	const d = previewDialog();
	d.opener = link;
	const body = d.querySelector('.dengine-preview__body');
	const actions = d.querySelector('.dengine-preview__actions');
	d.querySelector('.dengine-preview__title').textContent = link.closest('[class*="dengine-"]')?.querySelector('.dengine-title__link, .dengine-card__title')?.textContent || '';
	actions.innerHTML = '';
	body.innerHTML = '<p class="dengine-preview__status">' + (settings.i18n?.loading || '…') + '</p>';
	document.body.classList.add('dengine-preview-open');
	d.showModal();
	try {
		const response = await window.fetch(settings.preview + encodeURIComponent(link.getAttribute('data-dengine-preview')), {
			credentials: 'same-origin',
			headers: settings.nonce ? { 'X-WP-Nonce': settings.nonce } : {},
		});
		const data = await response.json();
		if (!response.ok) {
			throw new Error(data.message || 'error');
		}
		d.querySelector('.dengine-preview__title').textContent = data.title;
		body.innerHTML = data.html;
		const addAction = (href, label, cls) => {
			if (!href) {
				return;
			}
			const a = document.createElement('a');
			a.href = href;
			a.className = 'dengine-button dengine-button--sm ' + cls;
			a.textContent = label;
			actions.appendChild(a);
		};
		addAction(data.url, settings.i18n?.openPage || 'Open', 'dengine-button--ghost');
		addAction(data.download, settings.i18n?.download || 'Download', '');
		if (window.DocumentEngineViewerBoot) {
			window.DocumentEngineViewerBoot(body);
		}
	} catch (error) {
		body.innerHTML = '';
		const p = document.createElement('p');
		p.className = 'dengine-preview__status';
		p.textContent = error.message || settings.i18n?.failed || 'Error';
		body.appendChild(p);
	}
	return true;
}

document.addEventListener('click', (event) => {
	const link = event.target.closest('[data-dengine-preview]');
	if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
		return;
	}
	if (typeof HTMLDialogElement !== 'undefined' && settings.preview) {
		event.preventDefault();
		openPreview(link);
	}
});

// Page builders (e.g. Elementor) insert libraries after load; they call this for the new markup.
window.DocumentEngineLibraryBoot = boot;

window.addEventListener('popstate', () => libraries.forEach((lib) => lib.restoreFromUrl()));

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => boot());
} else {
	boot();
}
