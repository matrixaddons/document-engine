import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, RangeControl, Disabled, Notice } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import { listIcon } from '../components/icons';
import TermTokens from '../components/TermTokens';
import DocumentPicker from '../components/DocumentPicker';

const MODES = [
	{ label: __('Newest documents', 'document-engine'), value: 'recent' },
	{ label: __('Recently updated', 'document-engine'), value: 'updated' },
	{ label: __('Most downloaded', 'document-engine'), value: 'popular' },
	{ label: __('Related documents', 'document-engine'), value: 'related' },
];

function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();
	const { mode, count, categories, title, showMeta, documentId } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('List', 'document-engine')}>
					<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom label={__('Show', 'document-engine')} value={mode} options={MODES} onChange={(value) => setAttributes({ mode: value })} />
					<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom label={__('Number of documents', 'document-engine')} value={count} min={1} max={20} onChange={(value) => setAttributes({ count: value || 5 })} />
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={__('Heading', 'document-engine')} value={title} onChange={(value) => setAttributes({ title: value })} />
					<ToggleControl __nextHasNoMarginBottom label={__('Show file type, size and date', 'document-engine')} checked={showMeta} onChange={(value) => setAttributes({ showMeta: value })} />
					<TermTokens taxonomy="dengine_category" label={__('Only these categories (empty = all)', 'document-engine')} value={categories} onChange={(value) => setAttributes({ categories: value })} />
					{mode === 'related' && (
						<>
							<p className="components-base-control__help">{__('On a document page this lists documents that share its categories or tags. Elsewhere, pick the document to relate to.', 'document-engine')}</p>
							<DocumentPicker value={documentId} onChange={(id) => setAttributes({ documentId: id })} />
						</>
					)}
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				{mode === 'related' && !documentId ? (
					<Notice status="info" isDismissible={false}>
						{__('Related documents appear here when this block is shown on a document page.', 'document-engine')}
					</Notice>
				) : (
					<Disabled>
						<ServerSideRender block="document-engine/documents" attributes={attributes} skipBlockSupportAttributes />
					</Disabled>
				)}
			</div>
		</>
	);
}

registerBlockType('document-engine/documents', {
	apiVersion: 3,
	title: __('Document List', 'document-engine'),
	description: __('A short list of the newest, recently updated, most downloaded or related documents.', 'document-engine'),
	icon: listIcon,
	category: 'document-engine',
	keywords: [__('popular', 'document-engine'), __('recent', 'document-engine'), __('related', 'document-engine'), __('latest', 'document-engine')],
	supports: { html: false },
	attributes: {
		mode: { type: 'string', default: 'recent' },
		count: { type: 'number', default: 5 },
		categories: { type: 'array', default: [], items: { type: 'string' } },
		title: { type: 'string', default: '' },
		showMeta: { type: 'boolean', default: true },
		documentId: { type: 'number', default: 0 },
	},
	variations: [
		{ name: 'popular', title: __('Popular Documents', 'document-engine'), description: __('The most downloaded documents.', 'document-engine'), attributes: { mode: 'popular' }, scope: ['inserter'], icon: listIcon },
		{ name: 'related', title: __('Related Documents', 'document-engine'), description: __('Documents that share a category or tag with the current one.', 'document-engine'), attributes: { mode: 'related' }, scope: ['inserter'], icon: listIcon },
	],
	edit: Edit,
	save: () => null,
});
