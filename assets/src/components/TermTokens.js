import { FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Pick taxonomy terms by name; stores slugs.
 */
export default function TermTokens({ taxonomy, label, value, onChange }) {
	const terms = useSelect((select) => select('core').getEntityRecords('taxonomy', taxonomy, { per_page: 100, hide_empty: false }) || [], [taxonomy]);
	const bySlug = {};
	const byName = {};
	terms.forEach((t) => {
		bySlug[t.slug] = t.name;
		byName[t.name] = t.slug;
	});
	return (
		<FormTokenField
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={label}
			value={value.map((slug) => bySlug[slug] || slug)}
			suggestions={terms.map((t) => t.name)}
			onChange={(tokens) => onChange(tokens.map((token) => byName[token] || token))}
		/>
	);
}
