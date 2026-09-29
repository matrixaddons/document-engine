import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, AlignmentControl, BlockControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { pdfIcon } from '../components/icons';

const cfg = window.DocumentEngineBlocks || {};

function Edit({ attributes, setAttributes }) {
	const { text, alignment } = attributes;
	const blockProps = useBlockProps({ style: { textAlign: alignment || cfg.pdfButtonAlignment || 'right' } });
	return (
		<>
			<BlockControls group="block">
				<AlignmentControl value={alignment} onChange={(value) => setAttributes({ alignment: value || '' })} />
			</BlockControls>
			<InspectorControls>
				<PanelBody title={__('Save as PDF', 'document-engine')}>
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={__('Button text', 'document-engine')} value={text} placeholder={cfg.pdfButtonText} onChange={(value) => setAttributes({ text: value })} />
					<p className="description">{__('Visitors get this page as a PDF using your PDF export settings (header, footer, style).', 'document-engine')}</p>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<span className="document-engine-pdf-button button wp-element-button">{pdfIcon} {text || cfg.pdfButtonText || __('Download PDF', 'document-engine')}</span>
			</div>
		</>
	);
}

registerBlockType('document-engine/pdf-button', {
	apiVersion: 3,
	title: __('Save as PDF Button', 'document-engine'),
	description: __('Lets visitors download the current page or post as a PDF.', 'document-engine'),
	icon: pdfIcon,
	category: 'document-engine',
	keywords: [__('pdf', 'document-engine'), __('print', 'document-engine'), __('download', 'document-engine'), __('export', 'document-engine')],
	supports: { html: false, multiple: false, spacing: { margin: true } },
	attributes: {
		text: { type: 'string', default: '' },
		alignment: { type: 'string', default: '' },
	},
	edit: Edit,
	save: () => null,
});
