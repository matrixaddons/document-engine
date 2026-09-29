import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Disabled, Placeholder } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import { SVG, Path } from '@wordpress/primitives';

const searchIcon = (
	<SVG viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<Path d="M10.5 4a6.5 6.5 0 0 1 5.2 10.4l4.4 4.5-1.1 1-4.4-4.4A6.5 6.5 0 1 1 10.5 4zm0 1.5a5 5 0 1 0 0 10 5 5 0 0 0 0-10z" />
	</SVG>
);

function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();
	const pages = useSelect((select) => select('core').getEntityRecords('postType', 'page', { per_page: 100, status: 'publish', _fields: 'id,title' }) || [], []);
	const options = [{ label: __('— Choose the library page —', 'document-engine'), value: 0 }, ...pages.map((p) => ({ label: p.title.rendered || `#${p.id}`, value: p.id }))];
	const picker = <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom label={__('Show results on', 'document-engine')} value={attributes.pageId} options={options} onChange={(value) => setAttributes({ pageId: parseInt(value, 10) || 0 })} help={__('The page that has your Document Library block.', 'document-engine')} />;
	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Search box', 'document-engine')}>
					{picker}
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={__('Placeholder', 'document-engine')} value={attributes.placeholder} placeholder={__('Search documents…', 'document-engine')} onChange={(placeholder) => setAttributes({ placeholder })} />
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={__('Button text', 'document-engine')} value={attributes.buttonText} placeholder={__('Search', 'document-engine')} onChange={(buttonText) => setAttributes({ buttonText })} />
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				{!attributes.pageId ? (
					<Placeholder icon={searchIcon} label={__('Document Search', 'document-engine')} instructions={__('A search box that shows its results in your document library.', 'document-engine')}>
						<div style={{ width: '100%' }}>{picker}</div>
					</Placeholder>
				) : (
					<Disabled>
						<ServerSideRender block="document-engine/search" attributes={attributes} skipBlockSupportAttributes />
					</Disabled>
				)}
			</div>
		</>
	);
}

registerBlockType('document-engine/search', {
	apiVersion: 3,
	title: __('Document Search', 'document-engine'),
	description: __('A search box for your documents that you can place anywhere, such as the header or home page.', 'document-engine'),
	icon: searchIcon,
	category: 'document-engine',
	keywords: [__('search', 'document-engine'), __('find', 'document-engine'), __('documents', 'document-engine')],
	supports: { html: false },
	attributes: {
		pageId: { type: 'number', default: 0 },
		placeholder: { type: 'string', default: '' },
		buttonText: { type: 'string', default: '' },
	},
	edit: Edit,
	save: () => null,
});
