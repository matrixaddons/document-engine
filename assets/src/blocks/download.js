import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, Placeholder, Disabled, ExternalLink } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import DocumentPicker from '../components/DocumentPicker';
import { downloadIcon } from '../components/icons';

const cfg = window.DocumentEngineBlocks || {};

function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();
	const { documentId, variant, label, showMeta } = attributes;

	const picker = <DocumentPicker value={documentId} onChange={(id) => setAttributes({ documentId: id })} />;

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Download', 'document-engine')}>
					{picker}
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Style', 'document-engine')}
						value={variant}
						options={[
							{ label: __('Card', 'document-engine'), value: 'card' },
							{ label: __('Button', 'document-engine'), value: 'button' },
						]}
						onChange={(value) => setAttributes({ variant: value })}
					/>
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={__('Button text', 'document-engine')} value={label} placeholder={__('Download', 'document-engine')} onChange={(value) => setAttributes({ label: value })} />
					<ToggleControl __nextHasNoMarginBottom label={__('Show file type and size', 'document-engine')} checked={showMeta} onChange={(value) => setAttributes({ showMeta: value })} />
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				{!documentId ? (
					<Placeholder icon={downloadIcon} label={__('Document Download', 'document-engine')} instructions={__('Pick a document to offer as a download.', 'document-engine')}>
						<div style={{ width: '100%' }}>
							{picker}
							{cfg.newDocumentUrl && <ExternalLink href={cfg.newDocumentUrl}>{__('Add a new document', 'document-engine')}</ExternalLink>}
						</div>
					</Placeholder>
				) : (
					<Disabled>
						<ServerSideRender block="document-engine/download" attributes={attributes} skipBlockSupportAttributes />
					</Disabled>
				)}
			</div>
		</>
	);
}

registerBlockType('document-engine/download', {
	apiVersion: 3,
	title: __('Document Download', 'document-engine'),
	description: __('A download button or card for one document, with file type and size.', 'document-engine'),
	icon: downloadIcon,
	category: 'document-engine',
	keywords: [__('download', 'document-engine'), __('file', 'document-engine'), __('button', 'document-engine')],
	supports: { html: false },
	attributes: {
		documentId: { type: 'number', default: 0 },
		variant: { type: 'string', default: 'card' },
		label: { type: 'string', default: '' },
		showMeta: { type: 'boolean', default: true },
	},
	edit: Edit,
	save: () => null,
});
