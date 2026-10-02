import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	RangeControl,
	CheckboxControl,
	BaseControl,
	Disabled,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';
import { libraryIcon } from '../components/icons';
import TermTokens from '../components/TermTokens';

const cfg = window.DocumentEngineBlocks || {};

function CheckboxList({ label, options, value, onChange }) {
	return (
		<BaseControl label={label} __nextHasNoMarginBottom>
			{Object.keys(options).map((key) => (
				<CheckboxControl
					__nextHasNoMarginBottom
					key={key}
					label={options[key]}
					checked={value.includes(key)}
					onChange={(checked) => onChange(checked ? [...value, key] : value.filter((v) => v !== key))}
				/>
			))}
		</BaseControl>
	);
}

function Edit({ attributes, setAttributes, clientId }) {
	const blockProps = useBlockProps();
	const a = attributes;

	useEffect(() => {
		if (!a.libraryId) {
			setAttributes({ libraryId: 'l' + clientId.replace(/[^a-z0-9]/g, '').slice(0, 5) });
		}
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	const columnOptions = { ...(cfg.columns || {}) };
	const layouts = cfg.layouts || { table: 'Table', grid: 'Grid', folders: 'Folders' };

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Layout', 'document-engine')}>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Layout', 'document-engine')}
						value={a.layout}
						options={Object.keys(layouts).map((k) => ({ label: layouts[k], value: k }))}
						onChange={(layout) => setAttributes({ layout })}
					/>
					{a.layout === 'grid' && (
						<RangeControl __nextHasNoMarginBottom label={__('Grid columns', 'document-engine')} value={a.gridColumns} min={1} max={6} onChange={(gridColumns) => setAttributes({ gridColumns })} />
					)}
					{a.layout !== 'folders' && (
						<RangeControl __nextHasNoMarginBottom label={__('Documents per page', 'document-engine')} value={a.perPage} min={1} max={100} onChange={(perPage) => setAttributes({ perPage })} />
					)}
					{a.layout === 'folders' && (
						<ToggleControl __nextHasNoMarginBottom label={__('Open folders by default', 'document-engine')} checked={a.openFolders} onChange={(openFolders) => setAttributes({ openFolders })} />
					)}
					{a.layout !== 'folders' && (
						<CheckboxList
							label={a.layout === 'grid' ? __('Details on cards', 'document-engine') : __('Columns', 'document-engine')}
							options={columnOptions}
							value={a.columns}
							onChange={(columns) => setAttributes({ columns: Object.keys(columnOptions).filter((k) => columns.includes(k)) })}
						/>
					)}
					{a.layout === 'grid' && (
						<>
							<ToggleControl __nextHasNoMarginBottom label={__('Show thumbnails', 'document-engine')} checked={a.showThumbnails} onChange={(showThumbnails) => setAttributes({ showThumbnails })} />
							<ToggleControl __nextHasNoMarginBottom label={__('Show descriptions', 'document-engine')} checked={a.showExcerpt} onChange={(showExcerpt) => setAttributes({ showExcerpt })} />
						</>
					)}
				</PanelBody>
				<PanelBody title={__('Documents to include', 'document-engine')}>
					<TermTokens taxonomy="dengine_category" label={__('Categories (empty = all)', 'document-engine')} value={a.categories} onChange={(categories) => setAttributes({ categories })} />
					<TermTokens taxonomy="dengine_tag" label={__('Tags', 'document-engine')} value={a.tags} onChange={(tags) => setAttributes({ tags })} />
					<CheckboxList label={__('File types (none = all)', 'document-engine')} options={cfg.fileTypes || {}} value={a.fileTypes} onChange={(fileTypes) => setAttributes({ fileTypes })} />
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Order by', 'document-engine')}
						value={`${a.orderby}-${a.order}`}
						options={Object.keys(cfg.sortOptions || {}).map((k) => ({ label: cfg.sortOptions[k], value: k }))}
						onChange={(v) => {
							const [orderby, order] = v.split('-');
							setAttributes({ orderby, order });
						}}
					/>
				</PanelBody>
				<PanelBody title={__('Search and filters', 'document-engine')}>
					<ToggleControl __nextHasNoMarginBottom label={__('Search box', 'document-engine')} checked={a.search} onChange={(search) => setAttributes({ search })} />
					<CheckboxList
						label={__('Filters', 'document-engine')}
						options={cfg.filters || { category: __('Category', 'document-engine'), tag: __('Tag', 'document-engine'), type: __('File type', 'document-engine'), year: __('Year', 'document-engine'), author: __('Author', 'document-engine'), sort: __('Sort order', 'document-engine') }}
						value={a.filters}
						onChange={(filters) => setAttributes({ filters })}
					/>
					{['category', 'tag', 'type'].some((f) => a.filters.includes(f)) && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Let visitors pick several', 'document-engine')}
							help={__('Category, tag and file type become checkboxes, so visitors can combine choices (for example two categories at once).', 'document-engine')}
							checked={!!a.multiFilters}
							onChange={(multiFilters) => setAttributes({ multiFilters })}
						/>
					)}
					{a.layout !== 'folders' && <ToggleControl __nextHasNoMarginBottom label={__('Pagination', 'document-engine')} checked={a.pagination} onChange={(pagination) => setAttributes({ pagination })} />}
					{a.layout !== 'folders' && a.pagination && (
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Pagination style', 'document-engine')}
							value={a.paginationStyle || 'numbers'}
							options={[
								{ label: __('Page numbers', 'document-engine'), value: 'numbers' },
								{ label: __('"Load more" button', 'document-engine'), value: 'load-more' },
							]}
							onChange={(paginationStyle) => setAttributes({ paginationStyle })}
						/>
					)}
					{a.layout === 'table' && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Sort by clicking column headings', 'document-engine')}
							help={__('Title, date, size and downloads headings sort the table; a second click reverses the order.', 'document-engine')}
							checked={a.sortable !== false}
							onChange={(sortable) => setAttributes({ sortable })}
						/>
					)}
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Document titles link to', 'document-engine')}
						value={a.linkTo}
						options={[
							{ label: __('Document page', 'document-engine'), value: 'document' },
							{ label: __('The file', 'document-engine'), value: 'file' },
							{ label: __('Nothing', 'document-engine'), value: 'none' },
						]}
						onChange={(linkTo) => setAttributes({ linkTo })}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<Disabled>
					<ServerSideRender block="document-engine/library" attributes={attributes} skipBlockSupportAttributes />
				</Disabled>
			</div>
		</>
	);
}

registerBlockType('document-engine/library', {
	apiVersion: 3,
	title: __('Document Library', 'document-engine'),
	description: __('A searchable list of your documents as a table, grid or folders, with filters by category, tag and file type.', 'document-engine'),
	icon: libraryIcon,
	category: 'document-engine',
	keywords: [__('documents', 'document-engine'), __('files', 'document-engine'), __('downloads', 'document-engine'), __('library', 'document-engine'), __('table', 'document-engine')],
	supports: { align: ['wide', 'full'], html: false, spacing: { margin: true } },
	edit: Edit,
	save: () => null,
});
