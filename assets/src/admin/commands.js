/**
 * Command palette (Cmd/Ctrl + K): quick actions and "find a document" anywhere in wp-admin.
 */
import { store as commandsStore } from '@wordpress/commands';
import { useSelect, dispatch } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { SVG, Path } from '@wordpress/primitives';

const icon = (d) => (
	<SVG viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<Path d={d} />
	</SVG>
);
const plus = icon('M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z');
const file = icon('M12.85 4H6.5A1.5 1.5 0 0 0 5 5.5v13A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5V10.2L12.85 4zm4.65 14.5h-11v-13h5.5V11h5.5v7.5zM12.5 9.5V6.1l3.4 3.4h-3.4z');
const cog = icon('M10.3 3h3.4l.5 2.4a7 7 0 0 1 1.6.9l2.3-.8 1.7 2.9-1.8 1.6a7 7 0 0 1 0 1.9l1.8 1.6-1.7 2.9-2.3-.8a7 7 0 0 1-1.6.9l-.5 2.4h-3.4l-.5-2.4a7 7 0 0 1-1.6-.9l-2.3.8-1.7-2.9 1.8-1.6a7 7 0 0 1 0-1.9L4.2 8.4l1.7-2.9 2.3.8a7 7 0 0 1 1.6-.9L10.3 3zM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z');
const search = icon('M10.5 4a6.5 6.5 0 0 1 5.2 10.4l4.4 4.5-1.1 1-4.4-4.4A6.5 6.5 0 1 1 10.5 4zm0 1.5a5 5 0 1 0 0 10 5 5 0 0 0 0-10z');
const chartBar = icon('M5 19h14v1.5H5V19zm1-8h2.5v6.5H6V11zm4.75-5h2.5v11.5h-2.5V6zM15.5 9H18v8.5h-2.5V9z');

const cfg = window.DocumentEngineCommands || {};

function useDocumentSearch({ search: term }) {
	const { records, isLoading } = useSelect(
		(select) => {
			if (!term) {
				return { records: [], isLoading: false };
			}
			const query = { search: term, per_page: 10, status: 'publish,draft,pending,private', _fields: 'id,title' };
			return {
				records: select('core').getEntityRecords('postType', 'dengine_document', query) || [],
				isLoading: !select('core').hasFinishedResolution('getEntityRecords', ['postType', 'dengine_document', query]),
			};
		},
		[term]
	);
	const commands = useMemo(
		() =>
			records.map((record) => ({
				name: 'document-engine/document-' + record.id,
				label: sprintf(__('Edit document: %s', 'document-engine'), record.title?.rendered || '#' + record.id),
				icon: file,
				callback: ({ close }) => {
					close();
					document.location = addQueryArgs(cfg.postUrl, { post: record.id, action: 'edit' });
				},
			})),
		[records]
	);
	return { commands, isLoading };
}

const go = (url) => ({ close }) => {
	close();
	document.location = url;
};

const { registerCommand, registerCommandLoader } = dispatch(commandsStore);
[
	{ name: 'document-engine/add', label: __('Add document', 'document-engine'), icon: plus, callback: go(cfg.newUrl) },
	{ name: 'document-engine/list', label: __('Open documents', 'document-engine'), icon: file, callback: go(cfg.listUrl) },
	{ name: 'document-engine/dashboard', label: __('Document Engine dashboard', 'document-engine'), icon: chartBar, callback: go(cfg.dashboardUrl) },
	{ name: 'document-engine/settings', label: __('Document Engine settings', 'document-engine'), icon: cog, callback: go(cfg.settingsUrl) },
	...(cfg.extra || []).map((c) => ({ name: 'document-engine/' + c.name, label: c.label, icon: search, callback: go(c.url) })),
].forEach((command) => registerCommand(command));
registerCommandLoader({ name: 'document-engine/find-document', hook: useDocumentSearch });
