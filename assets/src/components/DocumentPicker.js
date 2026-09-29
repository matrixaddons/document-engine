import { ComboboxControl, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

/**
 * Searchable select for documents (REST document-engine/v1/documents).
 */
export default function DocumentPicker({ value, onChange, pdfOnly = false, label }) {
	const [options, setOptions] = useState([]);
	const [loading, setLoading] = useState(false);
	const [search, setSearch] = useState('');

	useEffect(() => {
		let active = true;
		setLoading(true);
		const query = { search, type: pdfOnly ? 'pdf' : '' };
		if (value && !search) {
			query.include = [value];
		}
		const load = async () => {
			try {
				let items = await apiFetch({ path: addQueryArgs('/document-engine/v1/documents', { search, type: query.type }) });
				if (value && !items.some((i) => i.id === value)) {
					const current = await apiFetch({ path: addQueryArgs('/document-engine/v1/documents', { include: [value] }) });
					items = [...current, ...items];
				}
				if (active) {
					setOptions(
						items.map((item) => ({
							value: item.id,
							label: `${item.title || __('(no title)', 'document-engine')}${item.type ? ` · ${item.type.toUpperCase()}` : ''}${item.status !== 'publish' ? ` (${item.status})` : ''}`,
						}))
					);
				}
			} catch (e) {
				if (active) {
					setOptions([]);
				}
			}
			if (active) {
				setLoading(false);
			}
		};
		load();
		return () => {
			active = false;
		};
	}, [search, pdfOnly]); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<div className="dengine-document-picker">
			<ComboboxControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={label || __('Document', 'document-engine')}
				value={value || null}
				options={options}
				onChange={(id) => onChange(id ? parseInt(id, 10) : 0)}
				onFilterValueChange={(text) => setSearch(text)}
				help={pdfOnly ? __('Only PDF documents are listed.', 'document-engine') : undefined}
			/>
			{loading && <Spinner />}
		</div>
	);
}
