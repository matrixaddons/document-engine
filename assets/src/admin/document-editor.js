/**
 * "Document file" panel for the document post type in the block editor.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, TextControl, SelectControl, Notice } from '@wordpress/components';
import { useSelect, useDispatch, dispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import qrcode from 'qrcode-generator';
import { __, sprintf } from '@wordpress/i18n';
import './editor-panels.scss';

const cfg = window.DocumentEngineEditor || {};
const nudges = cfg.nudges || {};

// Records a suggestion as dismissed (or shown once) for this user.
function dismissNudge(id) {
	if (!nudges.ajax) {
		return;
	}
	const body = new FormData();
	body.append('action', 'dengine_dismiss_nudge');
	body.append('nonce', nudges.nonce);
	body.append('id', id);
	window.fetch(nudges.ajax, { method: 'POST', body, credentials: 'same-origin' }).catch(() => {});
}
const PANEL_ID = 'document-engine-document-file/dengine-file';

function titleFromFilename(name) {
	return (name || '')
		.replace(/\.[a-z0-9]{1,5}$/i, '')
		.replace(/[-_]+/g, ' ')
		.replace(/\s+/g, ' ')
		.trim()
		.replace(/^./, (c) => c.toUpperCase());
}

function formatBytes(bytes) {
	if (!bytes) {
		return '';
	}
	const units = ['B', 'KB', 'MB', 'GB'];
	let i = 0;
	let n = bytes;
	while (n >= 1024 && i < units.length - 1) {
		n /= 1024;
		i++;
	}
	return `${n.toFixed(i && n < 10 ? 1 : 0)} ${units[i]}`;
}

const GROUPS = {
	pdf: ['pdf'],
	word: ['doc', 'docx', 'odt', 'rtf', 'pages', 'txt', 'md'],
	sheet: ['xls', 'xlsx', 'ods', 'csv', 'tsv', 'numbers'],
	slides: ['ppt', 'pptx', 'pps', 'ppsx', 'odp', 'key'],
	image: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'tif', 'tiff'],
};

export function FileBadge({ ext, link = false }) {
	const e = (ext || '').toLowerCase();
	const group = link && !e ? 'link' : Object.keys(GROUPS).find((g) => GROUPS[g].includes(e)) || 'other';
	return (
		<span className={`dengine-filebadge dengine-filebadge--${group}`} aria-hidden="true">
			{(e || (link ? 'URL' : 'FILE')).slice(0, 4).toUpperCase()}
		</span>
	);
}

const uploadIcon = (
	<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
		<path d="M11.25 16V6.56L7.53 10.28 6.47 9.22 12 3.69l5.53 5.53-1.06 1.06-3.72-3.72V16h-1.5zM5 18.5h14V20H5v-1.5z" />
	</svg>
);

/**
 * QR code for a document link (for noticeboards, printed agendas and handouts).
 */
export function QrCode({ url, name }) {
	const [open, setOpen] = useState(false);
	if (!url) {
		return null;
	}
	const qr = qrcode(0, 'M');
	qr.addData(url);
	qr.make();
	const svg = qr.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
	const save = (blob, ext) => {
		const a = document.createElement('a');
		a.href = URL.createObjectURL(blob);
		a.download = `${name || 'document'}-qr.${ext}`;
		document.body.appendChild(a);
		a.click();
		setTimeout(() => {
			URL.revokeObjectURL(a.href);
			a.remove();
		}, 500);
	};
	const png = () => {
		const size = 1024;
		const count = qr.getModuleCount() + 4;
		const cell = Math.floor(size / count);
		const canvas = document.createElement('canvas');
		canvas.width = canvas.height = cell * count;
		const ctx = canvas.getContext('2d');
		ctx.fillStyle = '#fff';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		ctx.fillStyle = '#000';
		for (let r = 0; r < qr.getModuleCount(); r++) {
			for (let c = 0; c < qr.getModuleCount(); c++) {
				if (qr.isDark(r, c)) {
					ctx.fillRect((c + 2) * cell, (r + 2) * cell, cell, cell);
				}
			}
		}
		canvas.toBlob((blob) => save(blob, 'png'));
	};
	return (
		<div className="dengine-side__section">
			<div className="dengine-side__row" style={{ justifyContent: 'space-between' }}>
				<p className="dengine-side__heading">{__('QR code', 'document-engine')}</p>
				<Button variant="link" onClick={() => setOpen(!open)} aria-expanded={open}>
					{open ? __('Hide', 'document-engine') : __('Show', 'document-engine')}
				</Button>
			</div>
			{open && (
				<>
					<div className="dengine-qr" dangerouslySetInnerHTML={{ __html: svg }} role="img" aria-label={__('QR code linking to this document', 'document-engine')} />
					<p className="dengine-side__text">{__('Print it on notices, agendas or handouts so people can open this document on their phone.', 'document-engine')}</p>
					<div className="dengine-side__row">
						<Button variant="secondary" size="compact" onClick={png}>
							{__('Download PNG', 'document-engine')}
						</Button>
						<Button variant="tertiary" size="compact" onClick={() => save(new Blob([svg], { type: 'image/svg+xml' }), 'svg')}>
							{__('Download SVG', 'document-engine')}
						</Button>
					</div>
				</>
			)}
		</div>
	);
}

function DocumentFilePanel() {
	const { postType, meta, title, permalink, slug, published, hiddenPage } = useSelect((select) => {
		const editor = select('core/editor');
		return {
			postType: editor.getCurrentPostType(),
			meta: editor.getEditedPostAttribute('meta') || {},
			title: editor.getEditedPostAttribute('title'),
			permalink: editor.getPermalink(),
			slug: editor.getEditedPostAttribute('slug'),
			published: editor.getCurrentPostAttribute('status') === 'publish',
			hiddenPage: ['private', 'draft', 'pending', 'future'].includes(editor.getEditedPostAttribute('status')) || !!editor.getEditedPostAttribute('password'),
		};
	}, []);
	const { editPost, toggleEditorPanelOpened } = useDispatch('core/editor');
	const panelOpen = useSelect((select) => select('core/editor').isEditorPanelOpened(PANEL_ID), []);

	const fileId = meta._dengine_file_id || 0;
	const [privateDismissed, setPrivateDismissed] = useState(false);
	const media = useSelect((select) => (fileId ? select('core').getMedia(fileId, { context: 'view' }) : null), [fileId]);

	useEffect(() => {
		// Opened from "Add new" with ?dengine_file=ID (e.g. from the Media Library).
		const params = new URLSearchParams(window.location.search);
		const preset = parseInt(params.get('dengine_file') || '0', 10);
		if (preset && !fileId) {
			editPost({ meta: { ...meta, _dengine_file_id: preset } });
		}
		// The file is the one thing a document needs: show the panel open until one is chosen.
		if (!fileId && !meta._dengine_file_url && !panelOpen) {
			toggleEditorPanelOpened(PANEL_ID);
		}
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect(() => {
		// Name untitled documents after their file (also makes the post saveable).
		if (media && !title) {
			const name = media.dengine_file_name || (media.source_url ? media.source_url.split('/').pop() : '');
			editPost({ title: titleFromFilename(name) || media.title?.rendered || '' });
		}
	}, [media]); // eslint-disable-line react-hooks/exhaustive-deps

	if (postType !== 'dengine_document') {
		return null;
	}

	const setMeta = (changes) => editPost({ meta: { ...meta, ...changes } });

	const onSelect = (item) => {
		// T4: replacing the file of a published document keeps the link; the old file isn't kept (Pro keeps versions).
		if (published && fileId > 0 && item.id !== fileId && nudges.versions) {
			dispatch('core/notices').createNotice('info', __('File replaced. The document\'s link stays the same, but the previous file isn\'t kept. Pro keeps every version so you can restore it.', 'document-engine'), {
				isDismissible: true,
				actions: [{ label: __('See how Pro does this', 'document-engine'), url: nudges.versions }],
			});
			dismissNudge('t4-versions');
			nudges.versions = '';
		}
		setMeta({ _dengine_file_id: item.id, _dengine_file_url: '' });
		if (!title) {
			editPost({ title: titleFromFilename(item.filename || item.title || '') || item.title });
		}
	};

	// Protected files' URLs are the download link (?dengine_download=…): use the stored file name.
	const urlName = media ? decodeURIComponent((media.source_url || '').split('?')[0].split('/').pop() || '') : '';
	const fileName = media ? media.dengine_file_name || (urlName.includes('.') ? urlName : '') || media.title?.raw || media.title?.rendered || '' : '';
	const size = media?.media_details?.filesize;
	const ext = fileName.includes('.') ? fileName.split('.').pop() : (media?.mime_type || '').split('/').pop().replace(/^vnd\..*|^octet-stream$/, '');
	const url = meta._dengine_file_url || '';
	let host = '';
	try {
		host = url ? new URL(url).hostname : '';
	} catch (e) {
		host = '';
	}
	const urlExt = url ? (url.split('?')[0].split('.').pop() || '').slice(0, 5) : '';

	const picker = (label, variant) => (
		<MediaUploadCheck fallback={<Notice status="warning" isDismissible={false}>{__('You need permission to upload files.', 'document-engine')}</Notice>}>
			<MediaUpload
				onSelect={onSelect}
				value={fileId}
				title={__('Choose document file', 'document-engine')}
				render={({ open }) => (
					<Button variant={variant} onClick={open} __next40pxDefaultSize={variant === 'primary'} size={variant === 'primary' ? undefined : 'compact'}>
						{label}
					</Button>
				)}
			/>
		</MediaUploadCheck>
	);

	return (
		<PluginDocumentSettingPanel name="dengine-file" title={__('Document file', 'document-engine')} className="dengine-file-panel">
			<div className="dengine-side">
				{fileId > 0 && (
					<div className="dengine-filecard">
						<FileBadge ext={ext} />
						<div className="dengine-filecard__text">
							<span className="dengine-filecard__name" title={fileName}>{fileName || sprintf(__('File #%d', 'document-engine'), fileId)}</span>
							<span className="dengine-filecard__meta">{[ext.toUpperCase(), formatBytes(size)].filter(Boolean).join(' · ') || __('Loading…', 'document-engine')}</span>
						</div>
						<div className="dengine-filecard__actions">
							{picker(__('Replace', 'document-engine'), 'secondary')}
							{media?.source_url && (
								<Button variant="link" href={media.source_url} target="_blank" rel="noopener noreferrer">
									{__('Open', 'document-engine')}
								</Button>
							)}
							<Button className="dengine-filecard__remove" variant="link" isDestructive onClick={() => setMeta({ _dengine_file_id: 0 })}>
								{__('Remove', 'document-engine')}
							</Button>
						</div>
					</div>
				)}
				{!fileId && url && (
					<div className="dengine-filecard">
						<FileBadge ext={urlExt.length <= 4 ? urlExt : ''} link />
						<div className="dengine-filecard__text">
							<span className="dengine-filecard__name" title={url}>{host || url}</span>
							<span className="dengine-filecard__meta">{__('Link to a file on another site', 'document-engine')}</span>
						</div>
						<div className="dengine-filecard__actions">
							<Button variant="link" href={url} target="_blank" rel="noopener noreferrer">
								{__('Open', 'document-engine')}
							</Button>
							<Button className="dengine-filecard__remove" variant="link" isDestructive onClick={() => setMeta({ _dengine_file_url: '' })}>
								{__('Remove link', 'document-engine')}
							</Button>
						</div>
					</div>
				)}
				{!fileId && !url && (
					<>
						<div className="dengine-filedrop">
							{uploadIcon}
							<p className="dengine-filedrop__title">{__('Add the file for this document', 'document-engine')}</p>
							<p className="dengine-filedrop__hint">{__('PDF, Word, Excel, PowerPoint, images and more.', 'document-engine')}</p>
							{picker(__('Upload or choose file', 'document-engine'), 'primary')}
						</div>
						<div className="dengine-side__or">{__('or', 'document-engine')}</div>
					</>
				)}
				{!fileId && (
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={url ? __('Link', 'document-engine') : __('Link to a file elsewhere', 'document-engine')}
						type="url"
						placeholder="https://"
						value={url}
						onChange={(value) => setMeta({ _dengine_file_url: value })}
						help={url ? undefined : __('Google Drive, Dropbox, OneDrive or any web address.', 'document-engine')}
					/>
				)}
				<div className="dengine-side__section">
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('When visitors click Download', 'document-engine')}
						value={meta._dengine_link_behavior || ''}
						options={[
							{ label: sprintf(__('Site default (%s)', 'document-engine'), cfg.defaultBehavior === 'inline' ? __('open in browser', 'document-engine') : __('download', 'document-engine')), value: '' },
							{ label: __('Download the file', 'document-engine'), value: 'download' },
							{ label: __('Open in the browser', 'document-engine'), value: 'inline' },
						]}
						onChange={(value) => setMeta({ _dengine_link_behavior: value })}
					/>
				</div>
				{published && !hiddenPage && <QrCode url={permalink} name={slug} />}
				{hiddenPage && fileId > 0 && !cfg.isPro && (
					<div className="dengine-side__text">
						<p>{__('Only people who can edit documents can open this document\'s page and download link. The file itself stays in the public uploads folder, so anyone who has its direct file address could still download it.', 'document-engine')}</p>
						{nudges.privateFile && !privateDismissed && (
							<p>
								{__('Pro moves files of restricted documents to private storage.', 'document-engine')}{' '}
								<a href={nudges.privateFile}>{__('See how Pro does this', 'document-engine')}</a>{' · '}
								<Button variant="link" onClick={() => { dismissNudge('t1-private-file'); setPrivateDismissed(true); }}>{__('Don\'t show again', 'document-engine')}</Button>
							</p>
						)}
					</div>
				)}
			</div>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin('document-engine-document-file', { render: DocumentFilePanel });

