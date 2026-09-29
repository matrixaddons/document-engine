import { registerBlockType, createBlock } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, MediaPlaceholder, MediaReplaceFlow, BlockControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	RadioControl,
	Placeholder,
	Button,
	ToolbarGroup,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import DocumentPicker from '../components/DocumentPicker';
import { viewerIcon } from '../components/icons';

const TRI = [
	{ label: __('Use default', 'document-engine'), value: '' },
	{ label: __('Show', 'document-engine'), value: 'yes' },
	{ label: __('Hide', 'document-engine'), value: 'no' },
];

function Edit({ attributes, setAttributes }) {
	const { source, documentId, fileId, url, height, zoom, toolbar, download, print, fullscreen, page } = attributes;
	const blockProps = useBlockProps({ className: 'dengine-viewer-editor' });

	const media = useSelect((select) => (fileId ? select('core').getMedia(fileId) : null), [fileId]);
	const doc = useSelect(
		(select) => (documentId ? select('core').getEntityRecord('postType', 'dengine_document', documentId) : null),
		[documentId]
	);

	const hasSource = (source === 'document' && documentId) || (source === 'media' && fileId) || (source === 'url' && url);

	let name = '';
	if (source === 'document' && doc) {
		name = doc.title?.raw || doc.title?.rendered || '';
	} else if (source === 'media' && media) {
		name = media.title?.raw || media.source_url?.split('/').pop();
	} else if (source === 'url') {
		name = url;
	}

	const sourcePanel = (
		<>
			<RadioControl
				label={__('PDF source', 'document-engine')}
				selected={source}
				options={[
					{ label: __('Document from your library', 'document-engine'), value: 'document' },
					{ label: __('Media Library file', 'document-engine'), value: 'media' },
					{ label: __('URL', 'document-engine'), value: 'url' },
				]}
				onChange={(value) => setAttributes({ source: value })}
			/>
			{source === 'document' && (
				<DocumentPicker pdfOnly value={documentId} onChange={(id) => setAttributes({ documentId: id })} />
			)}
			{source === 'url' && (
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={__('PDF URL', 'document-engine')}
					type="url"
					value={url}
					onChange={(value) => setAttributes({ url: value })}
					help={__('Files on other websites only display if that site allows it (CORS). Otherwise visitors get an "Open the PDF" link.', 'document-engine')}
				/>
			)}
		</>
	);

	return (
		<>
			{source === 'media' && fileId > 0 && (
				<BlockControls>
					<ToolbarGroup>
						<MediaReplaceFlow
							mediaId={fileId}
							allowedTypes={['application/pdf']}
							accept="application/pdf"
							onSelect={(m) => setAttributes({ fileId: m.id })}
							name={__('Replace PDF', 'document-engine')}
						/>
					</ToolbarGroup>
				</BlockControls>
			)}
			<InspectorControls>
				<PanelBody title={__('PDF', 'document-engine')}>{sourcePanel}</PanelBody>
				<PanelBody title={__('Display', 'document-engine')}>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Height', 'document-engine')}
						value={height}
						placeholder="800px"
						onChange={(value) => setAttributes({ height: value })}
						help={__('For example 800px, 80vh. Leave empty for the default.', 'document-engine')}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Initial zoom', 'document-engine')}
						value={zoom}
						options={[
							{ label: __('Use default', 'document-engine'), value: '' },
							{ label: __('Fit width', 'document-engine'), value: 'page-width' },
							{ label: __('Fit page', 'document-engine'), value: 'page-fit' },
							{ label: __('Automatic', 'document-engine'), value: 'auto' },
							{ label: '100%', value: '100' },
							{ label: '150%', value: '150' },
						]}
						onChange={(value) => setAttributes({ zoom: value })}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						type="number"
						min={1}
						label={__('Open at page', 'document-engine')}
						value={page}
						onChange={(value) => setAttributes({ page: parseInt(value, 10) || 1 })}
					/>
				</PanelBody>
				<PanelBody title={__('Toolbar', 'document-engine')} initialOpen={false}>
					<SelectControl __nextHasNoMarginBottom label={__('Toolbar', 'document-engine')} value={toolbar} options={TRI} onChange={(v) => setAttributes({ toolbar: v })} />
					<SelectControl __nextHasNoMarginBottom label={__('Download button', 'document-engine')} value={download} options={TRI} onChange={(v) => setAttributes({ download: v })} />
					<SelectControl __nextHasNoMarginBottom label={__('Print button', 'document-engine')} value={print} options={TRI} onChange={(v) => setAttributes({ print: v })} />
					<SelectControl __nextHasNoMarginBottom label={__('Full screen button', 'document-engine')} value={fullscreen} options={TRI} onChange={(v) => setAttributes({ fullscreen: v })} />
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				{!hasSource && source === 'media' && (
					<MediaPlaceholder
						icon={viewerIcon}
						labels={{
							title: __('PDF Viewer', 'document-engine'),
							instructions: __('Upload a PDF or pick one from the Media Library. It is shown with the built-in viewer; nothing is sent to Google.', 'document-engine'),
						}}
						accept="application/pdf"
						allowedTypes={['application/pdf']}
						onSelect={(m) => setAttributes({ fileId: m.id })}
					>
						<Button variant="link" onClick={() => setAttributes({ source: 'document' })}>
							{__('Use a document instead', 'document-engine')}
						</Button>
					</MediaPlaceholder>
				)}
				{!hasSource && source !== 'media' && (
					<Placeholder icon={viewerIcon} label={__('PDF Viewer', 'document-engine')} instructions={__('Choose which PDF to show.', 'document-engine')}>
						<div style={{ width: '100%' }}>{sourcePanel}</div>
					</Placeholder>
				)}
				{hasSource && (
					<div className="dengine-viewer-preview" style={{ '--dengine-viewer-height': height || '480px' }}>
						<div className="dengine-viewer-preview__bar">
							<span>‹ 1 / … ›</span>
							<span>− 100% +</span>
							<span>⤓ ⛶</span>
						</div>
						<div className="dengine-viewer-preview__page">
							{viewerIcon}
							<strong>{name || __('PDF', 'document-engine')}</strong>
							<span>{sprintf(__('Shown with the built-in PDF viewer (%s).', 'document-engine'), height || __('default height', 'document-engine'))}</span>
						</div>
					</div>
				)}
			</div>
		</>
	);
}

registerBlockType('document-engine/viewer', {
	apiVersion: 3,
	title: __('PDF Viewer', 'document-engine'),
	description: __('Show a PDF with the built-in viewer: page navigation, zoom, full screen, download and print. Works on mobile and with private files.', 'document-engine'),
	icon: viewerIcon,
	category: 'document-engine',
	keywords: [__('pdf', 'document-engine'), __('embed', 'document-engine'), __('viewer', 'document-engine'), __('document', 'document-engine'), __('flipbook', 'document-engine')],
	supports: { align: ['wide', 'full'], html: false, spacing: { margin: true } },
	attributes: {
		source: { type: 'string', default: 'media' },
		documentId: { type: 'number', default: 0 },
		fileId: { type: 'number', default: 0 },
		url: { type: 'string', default: '' },
		height: { type: 'string', default: '' },
		zoom: { type: 'string', default: '' },
		page: { type: 'number', default: 1 },
		toolbar: { type: 'string', default: '' },
		download: { type: 'string', default: '' },
		print: { type: 'string', default: '' },
		fullscreen: { type: 'string', default: '' },
	},
	transforms: {
		from: [
			{
				type: 'block',
				blocks: ['core/file'],
				isMatch: ({ href }) => /\.pdf($|\?)/i.test(href || ''),
				transform: ({ id, href }) =>
					createBlock('document-engine/viewer', id ? { source: 'media', fileId: id } : { source: 'url', url: href }),
			},
			{
				type: 'block',
				blocks: ['document-engine/pdf'],
				transform: (attrs) =>
					createBlock(
						'document-engine/viewer',
						attrs.pdf_type === 'file' && attrs.pdf_id
							? { source: 'media', fileId: attrs.pdf_id, height: `${attrs.height_size || 1000}${attrs.height_unit === '%' ? 'vh' : attrs.height_unit || 'px'}` }
							: { source: 'url', url: attrs.pdf_url || '', height: `${attrs.height_size || 1000}${attrs.height_unit === '%' ? 'vh' : attrs.height_unit || 'px'}` }
					),
			},
		],
	},
	edit: Edit,
	save: () => null,
});
