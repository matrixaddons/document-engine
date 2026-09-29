<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * The in-plugin documentation (Documents → Docs).
 *
 * Every article describes behavior that exists in this version; update it with the code.
 * Blocks: array('p', text) · array('ul', items) · array('ol', items) · array('code', text)
 * · array('table', rows, header) · array('note', text) · array('links', array(label => url)).
 * Text may contain <code>, <strong> and <em>.
 */
class DocsContent
{
    /**
     * @return array[] section id => array(title, icon, intro, articles => array(id => array(title, pro, blocks)))
     */
    public static function sections()
    {
        $docs = admin_url('edit.php?post_type=' . PostType::POST_TYPE);
        $settings = admin_url('admin.php?page=document-engine-settings');
        $new = admin_url('post-new.php?post_type=' . PostType::POST_TYPE);

        $sections = array(
            'start' => array(
                'title' => __('Getting started', 'document-engine'),
                'icon' => 'home',
                'intro' => __('Set up a document library in a few minutes.', 'document-engine'),
                'articles' => array(
                    'what' => array(__('What Document Engine does', 'document-engine'), false, array(
                        array('p', __('Document Engine turns WordPress into a document library. Each file (PDF, Word, Excel, PowerPoint, images, audio, video, archives) becomes a <strong>document</strong> with its own title, description, categories, tags and page. You publish documents in searchable libraries, show PDFs in a built-in viewer, and let visitors download them through one file server that counts downloads and applies access rules.', 'document-engine')),
                        array('p', __('It also includes Post to PDF: a "Download PDF" button that turns posts and pages into PDFs.', 'document-engine')),
                        array('ul', array(
                            __('<strong>Free</strong>: documents, libraries (table, grid, folders) with instant search and filters, the PDF viewer, preview popups, lists, search box, QR codes, Post to PDF, format requests, migration from other plugins, Elementor widgets.', 'document-engine'),
                            __('<strong>Pro</strong>: access control and private files, share links, secure viewer, email gate, activity and reading analytics, versions, review and expiry dates, read-and-confirm, notifications, import, custom fields, client portal, handbook PDFs, AI assistant, abuse protection.', 'document-engine'),
                        )),
                    )),
                    'install' => array(__('Install and activate', 'document-engine'), false, array(
                        array('ol', array(
                            __('In Plugins → Add New, search for "Document Engine", then install and activate it. Or upload the ZIP under Plugins → Add New → Upload Plugin.', 'document-engine'),
                            __('A <strong>Documents</strong> menu appears in the admin sidebar.', 'document-engine'),
                            __('Requirements: WordPress 6.6 or newer and PHP 7.4 or newer. Post to PDF needs the PHP mbstring and gd extensions.', 'document-engine'),
                        )),
                        array('p', __('Pro is a separate add-on plugin that needs the free plugin. See "Install Pro" in the Pro features section.', 'document-engine')),
                    )),
                    'setup' => array(__('Recommended first setup', 'document-engine'), false, array(
                        array('ol', array(
                            __('Open Documents → Settings → Documents. Keep <strong>Pages</strong> on so every document has its own page, and choose what the <strong>Download button</strong> does (download or open in the browser).', 'document-engine'),
                            __('Under Documents → Settings → Documents → Library, pick the default layout and documents per page.', 'document-engine'),
                            __('Create a few categories under Documents → Categories (for example Policies, Minutes, Forms).', 'document-engine'),
                            __('Under Documents → Settings → Advanced → Permissions, choose which roles manage all documents and which can publish their own.', 'document-engine'),
                            __('Add your first documents, then publish a library page (next articles).', 'document-engine'),
                        )),
                        array('links', array(__('Open settings', 'document-engine') => $settings)),
                    )),
                    'first-document' => array(__('Add your first document', 'document-engine'), false, array(
                        array('ol', array(
                            __('Go to Documents → Add New Document.', 'document-engine'),
                            __('Enter a title. In the sidebar\'s <strong>Document file</strong> panel, upload a file, pick one from the Media Library, or link to a file on another site.', 'document-engine'),
                            __('Optionally write a description in the editor, and choose categories and tags.', 'document-engine'),
                            __('Publish. The document gets a page (for example <code>/documents/annual-report/</code>) with a download button and, for PDFs, the viewer.', 'document-engine'),
                        )),
                        array('note', __('To turn many existing Media Library files into documents at once, select them in Media → Library (list view) and use the "Create documents" bulk action. With Pro you can also import a CSV file.', 'document-engine')),
                        array('links', array(__('Add a document', 'document-engine') => $new)),
                    )),
                    'first-library' => array(__('Publish a document library', 'document-engine'), false, array(
                        array('ol', array(
                            __('Create a page, for example "Documents".', 'document-engine'),
                            __('Add the <strong>Document Library</strong> block. In the block sidebar choose the layout (table, grid or folders), columns, which categories to include, and which filters visitors see.', 'document-engine'),
                            __('Publish. Visitors can search instantly, filter, sort and page through the documents. The address updates as they filter, so filtered views can be shared.', 'document-engine'),
                        )),
                        array('p', __('Not using the block editor? Use the shortcode <code>[document_engine_library]</code> (see Developers → Shortcodes) or the Elementor "Document Library" widget.', 'document-engine')),
                    )),
                ),
            ),

            'free' => array(
                'title' => __('Free features', 'document-engine'),
                'icon' => 'documents',
                'intro' => __('Everything included in the free plugin.', 'document-engine'),
                'articles' => array(
                    'documents' => array(__('Documents and document pages', 'document-engine'), false, array(
                        array('p', __('Documents are a post type with categories and tags. Each has a file (uploaded, from the Media Library, or an external link), a download counter and, when <strong>Pages</strong> is on, its own page showing the file type, size, last update, categories, a download button and, for PDFs, the viewer.', 'document-engine')),
                        array('ul', array(
                            __('<strong>Where</strong>: Documents → All Documents and Add New Document.', 'document-engine'),
                            __('<strong>Download or open</strong>: the site default is set in Settings → Documents → Download button; each document can override it in the Document file panel.', 'document-engine'),
                            __('<strong>File links</strong> go through <code>/?dengine_download=ID</code>, so counts and (with Pro) access rules always apply. Replacing a file keeps the same link.', 'document-engine'),
                            __('<strong>Site search</strong> includes documents unless you switch it off in Settings → Documents.', 'document-engine'),
                            __('The documents list shows the file, category, downloads and last update, with filters for category and file type.', 'document-engine'),
                        )),
                        array('note', __('Limitation: the free plugin does not restrict who can download a published document. Anyone with the link can download it. Use Pro access control for private documents.', 'document-engine')),
                    )),
                    'library' => array(__('Document Library: search, filters and layouts', 'document-engine'), false, array(
                        array('p', __('The Document Library block (and shortcode, and Elementor widget) lists documents as a table, a grid of cards, or folders by category.', 'document-engine')),
                        array('ul', array(
                            __('<strong>Search</strong> updates results as visitors type, and also works without JavaScript.', 'document-engine'),
                            __('<strong>Filters</strong>: category (with document counts), tag, file type, year, author and sort order. Turn on <strong>Let visitors pick several</strong> to replace the category, tag and file type dropdowns with checkboxes so visitors can combine choices. Picks within one filter widen the results; different filters narrow them.', 'document-engine'),
                            __('<strong>Active filters</strong> show as removable chips above the results, with "Clear all".', 'document-engine'),
                            __('<strong>Shareable</strong>: the page address carries the search and filters (for example <code>?dl_cat=policies,forms</code>).', 'document-engine'),
                            __('<strong>Columns</strong> (table) or card details (grid): thumbnail, title, description, category, tags, type, size, date, updated, author, downloads, download button.', 'document-engine'),
                            __('Several libraries on one page each keep their own search and filters.', 'document-engine'),
                        )),
                        array('note', __('Folder view lists each category as a folder. When a visitor searches or filters, it switches to a flat result list.', 'document-engine')),
                    )),
                    'viewer' => array(__('PDF viewer', 'document-engine'), false, array(
                        array('p', __('A built-in viewer based on PDF.js, served from your own site (no third-party viewer). It is used on document pages, in the PDF Viewer block and <code>[document_engine_viewer]</code>, and in preview popups.', 'document-engine')),
                        array('ul', array(
                            __('Page navigation, zoom (fit width, fit page, percentages), print, download, full screen.', 'document-engine'),
                            __('Search inside the PDF (Ctrl/Cmd+F while the viewer has focus) with highlighted matches.', 'document-engine'),
                            __('A sidebar with page thumbnails and the PDF\'s outline (bookmarks).', 'document-engine'),
                            __('Clickable links inside PDFs, including links to other pages of the same PDF.', 'document-engine'),
                            __('Deep links: add <code>#page=5</code> to a document page address to open the PDF at page 5.', 'document-engine'),
                        )),
                        array('p', __('Defaults are in Settings → PDF Viewer. Each block can override height, zoom, starting page and the toolbar buttons.', 'document-engine')),
                        array('note', __('PDFs linked from another website only display if that site allows it (CORS). Upload the file, or use a document with an uploaded file, for reliable viewing.', 'document-engine')),
                    )),
                    'preview' => array(__('Preview popup', 'document-engine'), false, array(
                        array('p', __('With Settings → Documents → Library → <strong>View opens</strong> set to "A preview popup", the View button in libraries opens the document in a popup instead of going to its page: PDFs in the viewer, images, audio and video natively. The popup has Open page and Download buttons and closes with Esc.', 'document-engine')),
                        array('p', __('The popup only shows documents the visitor may open. Other file types (Word, Excel…) have no View button.', 'document-engine')),
                    )),
                    'blocks' => array(__('Blocks and widgets', 'document-engine'), false, array(
                        array('table', array(
                            array(__('Document Library', 'document-engine'), __('Searchable, filterable list of documents (table, grid, folders).', 'document-engine')),
                            array(__('Document List', 'document-engine'), __('A short list: newest, recently updated, most downloaded, or related to a document.', 'document-engine')),
                            array(__('Document Download', 'document-engine'), __('One document as a card or a button.', 'document-engine')),
                            array(__('Document Search', 'document-engine'), __('A search box that sends visitors to a library page with their search applied.', 'document-engine')),
                            array(__('PDF Viewer', 'document-engine'), __('Show a PDF from a document, the Media Library or a URL.', 'document-engine')),
                            array(__('Save as PDF', 'document-engine'), __('The Post to PDF button, placed where you want it.', 'document-engine')),
                        ), array(__('Block', 'document-engine'), __('What it does', 'document-engine'))),
                        array('p', __('Elementor users get Document Library, Document Download and PDF Viewer widgets with the same options.', 'document-engine')),
                    )),
                    'lists' => array(__('Document lists and related documents', 'document-engine'), false, array(
                        array('p', __('The Document List block and <code>[document_engine_documents]</code> show a few documents in a compact list: newest, recently updated, most downloaded, or related to a document (sharing its categories or tags).', 'document-engine')),
                        array('p', __('Settings → Documents → <strong>Related documents</strong> adds a related list under each document page automatically.', 'document-engine')),
                    )),
                    'search-box' => array(__('Search box for your header or sidebar', 'document-engine'), false, array(
                        array('p', __('The Document Search block (or <code>[document_engine_search page="123"]</code>) shows a search field anywhere. Choose the page that holds your library; searches land there with results already filtered. It finds the library on that page automatically.', 'document-engine')),
                    )),
                    'qr' => array(__('QR codes', 'document-engine'), false, array(
                        array('p', __('Every published document has a QR code in the editor sidebar (Document file panel → QR code → Show). Download it as PNG or SVG for printed notices, posters or meeting packs. The code points to the document\'s page.', 'document-engine')),
                    )),
                    'post-to-pdf' => array(__('Post to PDF', 'document-engine'), false, array(
                        array('p', __('Adds a "Download PDF" button to posts, pages or other post types that turns the content into a PDF, generated on your server.', 'document-engine')),
                        array('ul', array(
                            __('Choose where the button appears in Settings → Post to PDF → Button (post types, text, placement, alignment, download or open).', 'document-engine'),
                            __('Header and footer (logo, title, page numbers), page size, margins and orientation, theme styles or custom CSS, text and image watermarks.', 'document-engine'),
                            __('Protection: optionally stop readers printing, copying or editing the PDF (PDF permissions).', 'document-engine'),
                            __('Content shortcodes: <code>[document_engine_pdf_remove]</code> hides content from the PDF, <code>[document_engine_pdf_page_break]</code> starts a new page, <code>[document_engine_pdf_columns]</code> and <code>[document_engine_pdf_column_break]</code> lay out columns.', 'document-engine'),
                        )),
                        array('note', __('PDFs are made only for published, public posts of the post types you ticked (or posts containing a Save as PDF block), never for private or password-protected posts. Images from internal network addresses are left out. Generated PDFs are cached, and each visitor can create a limited number per minute (Settings → Advanced → Performance).', 'document-engine')),
                    )),
                    'format-requests' => array(__('Accessible format requests', 'document-engine'), false, array(
                        array('p', __('When switched on (Settings → Documents → Accessibility → Format requests), document pages show a "Need this in another format?" link. Visitors choose a format (for example large print or plain text), leave their name and email, and the request is emailed to you.', 'document-engine')),
                        array('p', __('Requests are listed in the Documents menu under Format requests (Reports → Format requests with Pro), with a count of open requests. Reply to the email with the alternative version, then mark the request done. Only editors and administrators can see requests.', 'document-engine')),
                        array('note', __('The form has spam protection (a time check, a hidden field and limits per visitor, per document and per day). Requests contain names and emails; they are included in WordPress\'s personal data export and erase tools.', 'document-engine')),
                    )),
                    'migrate' => array(__('Migrate from another plugin', 'document-engine'), false, array(
                        array('p', __('Documents → Migrate (Documents → Tools → Migrate with Pro) copies documents from Download Monitor, WordPress Download Manager and Barn2 Document Library into Document Engine: titles, descriptions, categories, tags, files, download counts and dates.', 'document-engine')),
                        array('ul', array(
                            __('Run a preview first: it shows how many items will be copied and flags locked items (paid, email- or captcha-gated) that need attention.', 'document-engine'),
                            __('The original plugin\'s data is not changed. Running it again skips items already copied.', 'document-engine'),
                            __('The old plugin\'s shortcodes keep working and its download links redirect to the new documents, so you can deactivate it afterwards.', 'document-engine'),
                            __('Members-only items become drafts in the free plugin (Pro moves their files to private storage and applies the access rule).', 'document-engine'),
                        )),
                    )),
                    'palette' => array(__('Command palette and AI agents', 'document-engine'), false, array(
                        array('p', __('Press Ctrl/Cmd+K anywhere in the admin to add a document, open the library, dashboard or settings, or jump to a document by typing its title.', 'document-engine')),
                        array('p', __('On WordPress 6.9+, the plugin registers two read-only abilities (Abilities API): search documents and get a document. AI assistants connected to your site (for example through the MCP adapter) can use them; they only ever return documents the signed-in user may open.', 'document-engine')),
                    )),
                    'permissions' => array(__('Who can manage documents', 'document-engine'), false, array(
                        array('p', __('Documents have their own permissions, separate from posts. In Settings → Advanced → Permissions choose which roles:', 'document-engine')),
                        array('ul', array(
                            __('<strong>Manage all documents</strong> (default: Administrator, Editor): edit and publish any document, manage categories, see requests.', 'document-engine'),
                            __('<strong>Publish their own documents</strong> (default: Author).', 'document-engine'),
                            __('<strong>Draft their own documents</strong> (default: Contributor): an editor publishes them.', 'document-engine'),
                        )),
                        array('p', __('Settings and migration need the Administrator\'s "manage options" permission.', 'document-engine')),
                    )),
                ),
            ),

            'pro' => array(
                'title' => __('Pro features', 'document-engine'),
                'icon' => 'star',
                'intro' => __('What the Pro add-on adds, and where to find it.', 'document-engine'),
                'articles' => array(
                    'install-pro' => array(__('Install Pro and activate your licence', 'document-engine'), true, array(
                        array('ol', array(
                            __('Buy Pro, then either download the Pro ZIP from your account and upload it under Plugins → Add New → Upload Plugin, or enter your licence key at the bottom of Documents → Free vs Pro to install it in one step.', 'document-engine'),
                            __('Keep the free plugin active; Pro builds on it.', 'document-engine'),
                            __('Enter the licence key in Documents → Settings → License. An active licence gives you updates from the Plugins screen.', 'document-engine'),
                        )),
                        array('note', __('Deactivating Pro returns the site to the free features. Pro data (access rules, logs, acknowledgements) stays in the database unless you delete it on uninstall.', 'document-engine')),
                    )),
                    'access' => array(__('Access control and private files', 'document-engine'), true, array(
                        array('p', __('Decide who can open each document: everyone, logged-in users, users with chosen roles, or named people. Set it per document (Access & security panel) or for a whole category (on the category screen); documents follow their categories unless they have their own rule.', 'document-engine')),
                        array('ul', array(
                            __('Restricted documents are shown with a lock, or hidden from people who cannot open them (Settings → Access & Pro → Access).', 'document-engine'),
                            __('<strong>Protected storage</strong>: files of restricted documents move to a private uploads folder, so the file address alone cannot be used to download them. A Site Health test checks that the folder really is private.', 'document-engine'),
                            __('Logged-out visitors are asked to log in; logged-in people without access see a clear message.', 'document-engine'),
                            __('Restricted documents are left out of site search, feeds, library results (when set to hide) and file-content search for people who cannot open them.', 'document-engine'),
                        )),
                    )),
                    'share' => array(__('Share links', 'document-engine'), true, array(
                        array('p', __('Give someone without an account access to one document for a limited time (Access & security panel → Share link). Name the link after the recipient to see when they opened it; optionally get an email on the first open. Revoke a link at any time. Links default to Settings → Access & Pro → Share links last (7 days).', 'document-engine')),
                    )),
                    'secure' => array(__('Secure viewer and stamped downloads', 'document-engine'), true, array(
                        array('p', __('Secure mode shows a document in the viewer only: no download, print or copy buttons, and each page carries a watermark with the reader\'s name, email and date (pattern in Settings → Access & Pro → Secure viewer). Downloads of secure documents are blocked. Optionally, downloaded PDFs of other documents can be stamped with the reader\'s details.', 'document-engine')),
                        array('note', __('A watermark discourages sharing and identifies leaks; no viewer can stop a determined person photographing a screen.', 'document-engine')),
                    )),
                    'gate' => array(__('Email gate and leads', 'document-engine'), true, array(
                        array('p', __('Ask for a name and email (and optionally organization) before a download, with your consent text. Switch it on per document or for all documents. Logged-in users and visitors who already filled the form (for a number of days) go straight through. Leads are listed under Reports → Leads, can be exported, sent to a webhook or emailed to you, and deleted after a retention period.', 'document-engine')),
                    )),
                    'analytics' => array(__('Activity, reading analytics and searches', 'document-engine'), true, array(
                        array('ul', array(
                            __('<strong>Reports → Activity</strong>: views, downloads, blocked attempts, leads and share-link opens, with a chart, top documents and a filterable audit log (CSV export).', 'document-engine'),
                            __('<strong>Reading analytics</strong>: for PDFs in the viewer, how long people spend on each page and how far they read. Filter the Activity page to one document to see it.', 'document-engine'),
                            __('<strong>Reports → Searches</strong>: what visitors search for in libraries, and which searches found nothing.', 'document-engine'),
                            __('The documents list shows views in the last 30 days next to downloads.', 'document-engine'),
                        )),
                        array('note', __('No IP addresses are stored in these logs: visitors who are not logged in are identified by a hash that changes daily. Entries older than the retention period (default 365 days) are deleted automatically.', 'document-engine')),
                    )),
                    'versions' => array(__('Versions, review and expiry dates', 'document-engine'), true, array(
                        array('ul', array(
                            __('Replace a document\'s file and the old one is kept in its version history; links do not change. Restore any version.', 'document-engine'),
                            __('From the documents list, use the "Upload new version" row action, or drop a file onto a row.', 'document-engine'),
                            __('Give documents a review-by date and an expiry date. Expired documents are unpublished automatically; a daily email lists documents due for review.', 'document-engine'),
                        )),
                    )),
                    'acks' => array(__('Read and confirm (acknowledgements)', 'document-engine'), true, array(
                        array('p', __('Ask people to confirm they have read a document ("I have read and understood…"). Choose the roles who must confirm and a deadline; they see it on the document page and in the Required Reading block. Reports → Acknowledgements shows who has and has not confirmed, sends reminders, and exports evidence (name, time, statement, a fingerprint of the file).', 'document-engine')),
                        array('p', __('After an important change, start a new round from the Acknowledgements screen so everyone confirms again.', 'document-engine')),
                    )),
                    'notify' => array(__('Notifications, webhooks and subscriptions', 'document-engine'), true, array(
                        array('ul', array(
                            __('Signed webhooks (HMAC) for events such as document published, new version, acknowledged, submitted and new lead.', 'document-engine'),
                            __('Posts to a Slack or Microsoft Teams channel.', 'document-engine'),
                            __('Email subscriptions: logged-in people follow categories (Subscribe block) and get an email when a document is published there, with one-click unsubscribe.', 'document-engine'),
                        )),
                    )),
                    'submit' => array(__('Front-end submissions', 'document-engine'), true, array(
                        array('p', __('The Submit Document block lets chosen roles upload documents from the front end. Submissions are saved as pending (or published) with allowed file types and a size limit (Settings → Access & Pro → Review & submissions).', 'document-engine')),
                    )),
                    'import' => array(__('Bulk and CSV import', 'document-engine'), true, array(
                        array('p', __('Documents → Tools → Import: drop many files at once to create one document per file, or import a CSV spreadsheet (title, file_url or attachment_id, category, tags, description, status, id, and cf:key columns for custom fields). Rows with an id update that document. Also available as <code>wp dengine import</code>.', 'document-engine')),
                    )),
                    'fields' => array(__('Custom fields', 'document-engine'), true, array(
                        array('p', __('Settings → Fields: add your own details to documents, such as a reference number, department or review date. Types: text, long text, number, date, choice list, yes/no, link, email.', 'document-engine')),
                        array('ul', array(
                            __('Edit them in the document sidebar\'s Details panel.', 'document-engine'),
                            __('Show them as library columns, filters (with counts) and sort options, on document pages, and in search.', 'document-engine'),
                            __('Import them from CSV with <code>cf:key</code> columns (write "-" to clear a value).', 'document-engine'),
                            __('Use fields you already have in Advanced Custom Fields (ACF): add them from the Fields tab.', 'document-engine'),
                        )),
                    )),
                    'portal' => array(__('My documents, required reading and handbooks', 'document-engine'), true, array(
                        array('ul', array(
                            __('<strong>My Documents</strong> block: a client or staff area listing the documents shared with the logged-in person, with "New" since their last visit.', 'document-engine'),
                            __('<strong>Required Reading</strong> block: each person\'s documents to confirm, with deadlines.', 'document-engine'),
                            __('<strong>Handbook PDF</strong> (Documents → Tools → Handbook PDF, or <code>[document_engine_handbook]</code>): many posts in one PDF with a cover page and table of contents.', 'document-engine'),
                            __('<strong>ZIP download</strong>: a Select column in library tables lets visitors download several documents as one ZIP.', 'document-engine'),
                        )),
                    )),
                    'ai' => array(__('AI assistant and file-content search', 'document-engine'), true, array(
                        array('p', __('Search finds words inside PDF, Word, Excel and PowerPoint files, in libraries and site search. The AI assistant (Suggest details, in the editor) proposes a title, a short summary, categories and tags from the file\'s text, using the AI provider connected to your site in WordPress (no separate key). Restricted, private and password-protected documents are not sent to AI unless you allow it.', 'document-engine')),
                    )),
                    'protection' => array(__('Abuse protection', 'document-engine'), true, array(
                        array('p', __('Limit how many different documents one person can download or view per day (ZIP downloads included), and add Cloudflare Turnstile to the email gate, format request and submission forms (Settings → Access & Pro → Abuse protection).', 'document-engine')),
                    )),
                    'cli' => array(__('WP-CLI', 'document-engine'), true, array(
                        array('code', "wp dengine list [--category=<slug>] [--format=table|csv|json|ids]\nwp dengine import <file.csv> [--link-only]\nwp dengine stats [--days=30] [--document=<id>]\nwp dengine reindex [<id>...]\nwp dengine protect\nwp dengine share <id> [--days=<days>]"),
                    )),
                ),
            ),

            'config' => array(
                'title' => __('Configuration', 'document-engine'),
                'icon' => 'advanced',
                'intro' => __('What each settings tab controls, and the defaults.', 'document-engine'),
                'articles' => array(
                    'settings-documents' => array(__('Settings → Documents', 'document-engine'), false, array(
                        array('table', array(
                            array(__('Pages', 'document-engine'), __('On', 'document-engine'), __('Each document gets a page. Off: titles link straight to the file.', 'document-engine')),
                            array(__('URL prefix', 'document-engine'), '<code>documents</code>', __('Address of document pages. Changing it changes all document page addresses (file links stay the same).', 'document-engine')),
                            array(__('PDF preview', 'document-engine'), __('On', 'document-engine'), __('Shows the viewer on PDF document pages.', 'document-engine')),
                            array(__('Related documents', 'document-engine'), __('Off', 'document-engine'), __('Adds a related list to document pages.', 'document-engine')),
                            array(__('Site search', 'document-engine'), __('On', 'document-engine'), __('Includes documents in WordPress search results.', 'document-engine')),
                            array(__('Download button', 'document-engine'), __('Download', 'document-engine'), __('Download the file, or open it in the browser.', 'document-engine')),
                            array(__('Download counter', 'document-engine'), __('On', 'document-engine'), __('Counts downloads (known bots are ignored).', 'document-engine')),
                            array(__('Format requests', 'document-engine'), __('Off', 'document-engine'), __('Accessible format request form; requests go to the email you set.', 'document-engine')),
                            array(__('Library: layout, per page, View button, View opens', 'document-engine'), __('Table, 20, on, page', 'document-engine'), __('Defaults for new libraries.', 'document-engine')),
                        ), array(__('Setting', 'document-engine'), __('Default', 'document-engine'), __('Effect', 'document-engine'))),
                    )),
                    'settings-viewer' => array(__('Settings → PDF Viewer', 'document-engine'), false, array(
                        array('p', __('Default height (800px), initial zoom (fit width), and which toolbar buttons show: toolbar, download, print, full screen, search, sidebar. "Version 1 blocks" shows PDF blocks made with version 1 in the built-in viewer instead of Google\'s viewer. It is on for new installs; sites upgraded from version 1 keep Google\'s viewer until you switch it on (recommended: faster, and nothing is sent to Google).', 'document-engine')),
                    )),
                    'settings-pdf' => array(__('Settings → Post to PDF', 'document-engine'), false, array(
                        array('p', __('Button (post types, text, placement, alignment, download or open), Header & footer, Page & protection (orientation, text size, margins, PDF permissions), Style (theme styles, custom CSS) and Watermark. The button appears only on the post types you tick.', 'document-engine')),
                    )),
                    'settings-advanced' => array(__('Settings → Advanced', 'document-engine'), false, array(
                        array('ul', array(
                            __('<strong>Permissions</strong>: roles that manage, publish or draft documents.', 'document-engine'),
                            __('<strong>PDF cache</strong> (on) and <strong>Generation limit</strong> (20 PDFs per visitor per minute) for Post to PDF.', 'document-engine'),
                            __('<strong>When deleting the plugin</strong> (off): also delete documents, categories, settings and (with Pro) logs. Leave off unless you are removing the plugin for good.', 'document-engine'),
                        )),
                    )),
                    'settings-pro' => array(__('Settings → Access & Pro, Fields, License', 'document-engine'), true, array(
                        array('table', array(
                            array(__('Access', 'document-engine'), __('Restricted documents in lists (lock or hide), protect files automatically (on), share link lifetime (7 days).', 'document-engine')),
                            array(__('Secure viewer', 'document-engine'), __('Which documents use it (off / restricted / all), watermark pattern, block downloads, stamp downloads.', 'document-engine')),
                            array(__('Activity log', 'document-engine'), __('Log activity, reading time, searches, logged-out visitors; keep entries for 365 days.', 'document-engine')),
                            array(__('Email gate', 'document-engine'), __('Default (off), organization field, heading, consent text, remember visitors (30 days), webhook, lead retention, new-lead email.', 'document-engine')),
                            array(__('Review & submissions', 'document-engine'), __('Review reminder emails and recipients; who can submit, new submission status, file types and size.', 'document-engine')),
                            array(__('Acknowledgements', 'document-engine'), __('Default statement; reminder emails.', 'document-engine')),
                            array(__('Notifications', 'document-engine'), __('Webhook URLs, events and signing secret; Slack/Teams webhook and events; email subscriptions.', 'document-engine')),
                            array(__('AI assistant', 'document-engine'), __('On/off; allow restricted documents.', 'document-engine')),
                            array(__('Abuse protection', 'document-engine'), __('Documents per person per day (0 = no limit); Cloudflare Turnstile keys.', 'document-engine')),
                            array(__('Fields', 'document-engine'), __('Custom fields for documents.', 'document-engine')),
                            array(__('License', 'document-engine'), __('Licence key for updates.', 'document-engine')),
                        ), array(__('Section', 'document-engine'), __('What it controls', 'document-engine'))),
                    )),
                ),
            ),

            'howto' => array(
                'title' => __('How-to guides', 'document-engine'),
                'icon' => 'book',
                'intro' => __('Step-by-step answers to common tasks.', 'document-engine'),
                'articles' => array(
                    'howto-policy-library' => array(__('Publish a policy library with filters', 'document-engine'), false, array(
                        array('ol', array(
                            __('Create a "Policies" category and add your policies to it.', 'document-engine'),
                            __('On a new page, add a Document Library block. Under Documents to include, choose the Policies category.', 'document-engine'),
                            __('Under Search and filters tick Tag, Year and Sort order; turn on Let visitors pick several if visitors should combine tags.', 'document-engine'),
                            __('Add a Document Search block to your header (Appearance → Editor) pointing to this page.', 'document-engine'),
                        )),
                    )),
                    'howto-replace' => array(__('Replace a file without breaking links', 'document-engine'), false, array(
                        array('p', __('Open the document and use Replace in the Document file panel. The document keeps its page and download address, so links in emails and other pages keep working. With Pro, the previous file is kept in the version history.', 'document-engine')),
                    )),
                    'howto-page-link' => array(__('Link to a specific page of a PDF', 'document-engine'), false, array(
                        array('p', __('Add <code>#page=</code> and the page number to the document page address, for example <code>/documents/annual-report/#page=12</code>. In a PDF Viewer block, set the starting page.', 'document-engine')),
                    )),
                    'howto-members' => array(__('Make documents members-only', 'document-engine'), true, array(
                        array('ol', array(
                            __('Edit the category (or document) and set "Who can open these documents" to logged-in users or chosen roles.', 'document-engine'),
                            __('Leave "Protect files automatically" on so the files move to private storage.', 'document-engine'),
                            __('Check Tools → Site Health: the Document Engine protection test must pass. On nginx, add the rule it shows.', 'document-engine'),
                        )),
                    )),
                    'howto-external' => array(__('Share a document with someone outside your site', 'document-engine'), true, array(
                        array('p', __('Open the document, go to Access & security → Share link, type the recipient\'s name, tick "Email me when it is first opened", and create the link. Send it; it expires after the set number of days. Revoke it from the same panel.', 'document-engine')),
                    )),
                    'howto-confirm' => array(__('Make staff confirm they read a policy', 'document-engine'), true, array(
                        array('ol', array(
                            __('Open the policy, go to Read & confirm, switch it on, choose the roles and a deadline.', 'document-engine'),
                            __('Add a Required Reading block to your staff page.', 'document-engine'),
                            __('Follow progress in Reports → Acknowledgements, send reminders, and export the evidence CSV.', 'document-engine'),
                        )),
                    )),
                    'howto-register' => array(__('Build a register with reference numbers', 'document-engine'), true, array(
                        array('ol', array(
                            __('In Settings → Fields add "Reference" (text, Column and Sort) and "Department" (choice list, Column and Filter).', 'document-engine'),
                            __('Fill them in the Details panel, or import a CSV with cf:reference and cf:department columns.', 'document-engine'),
                            __('In a Document Library block, tick the Reference and Department columns and the Department filter.', 'document-engine'),
                        )),
                    )),
                    'howto-migrate' => array(__('Move from Download Monitor, WPDM or Barn2', 'document-engine'), false, array(
                        array('ol', array(
                            __('Keep the old plugin active. Open Migrate, pick the source and run the preview.', 'document-engine'),
                            __('Run the migration. Check a few documents and pages that used the old shortcodes.', 'document-engine'),
                            __('Deactivate the old plugin. Its shortcodes and download links keep working through Document Engine.', 'document-engine'),
                        )),
                    )),
                ),
            ),

            'troubleshooting' => array(
                'title' => __('Troubleshooting', 'document-engine'),
                'icon' => 'help',
                'intro' => __('Common problems and how to fix them.', 'document-engine'),
                'articles' => array(
                    'ts-404' => array(__('Document pages show "Page not found"', 'document-engine'), false, array(
                        array('p', __('Go to Settings → Permalinks and click Save (this refreshes the site\'s addresses). Also check that another page or post type does not use the same address as the URL prefix in Settings → Documents.', 'document-engine')),
                    )),
                    'ts-menu' => array(__('Someone cannot see the Documents menu or a screen', 'document-engine'), false, array(
                        array('p', __('Check their role in Settings → Advanced → Permissions. Reports and Tools only appear for roles allowed to use them; Settings needs an administrator. Related screens are grouped: Categories holds Tags, Reports holds Activity, Searches, Leads, Acknowledgements and Format requests, Tools holds Import, Migrate and Handbook PDF.', 'document-engine')),
                    )),
                    'ts-viewer' => array(__('The PDF viewer is blank or shows an error', 'document-engine'), false, array(
                        array('ul', array(
                            __('External PDFs: the other site must allow cross-site loading (CORS). Upload the file instead.', 'document-engine'),
                            __('Mixed content: a site on https cannot load a PDF from http.', 'document-engine'),
                            __('Security or caching plugins that combine or delay JavaScript can break the viewer; exclude the Document Engine viewer script.', 'document-engine'),
                        )),
                    )),
                    'ts-counts' => array(__('Download counts do not go up', 'document-engine'), false, array(
                        array('p', __('Downloads are counted when the file is requested through the document link. Check that Download counter is on, and that links point to the document (not directly to the file in /wp-content/uploads/). Requests from known bots are not counted.', 'document-engine')),
                    )),
                    'ts-pdf' => array(__('Post to PDF fails or looks wrong', 'document-engine'), false, array(
                        array('ul', array(
                            __('Blank or error: raise PHP\'s memory limit (256 MB recommended) and check that the mbstring and gd extensions are installed.', 'document-engine'),
                            __('"Too many PDF requests": the per-visitor limit in Settings → Advanced was reached; wait a minute or raise the limit.', 'document-engine'),
                            __('Styling: try "Theme styles" off and add custom CSS in Settings → Post to PDF → Style.', 'document-engine'),
                        )),
                    )),
                    'ts-email' => array(__('Emails do not arrive', 'document-engine'), false, array(
                        array('p', __('Format requests, reminders and notifications use WordPress\'s email. If other WordPress emails (like password resets) also fail, install an SMTP plugin to send through a real mail service.', 'document-engine')),
                    )),
                    'ts-protection' => array(__('Site Health says protected files can be downloaded directly', 'document-engine'), true, array(
                        array('p', __('Your web server ignores .htaccess files (common on nginx). Add the rule shown in the Site Health test to the site\'s nginx configuration and reload nginx, or ask your host. Until then, anyone who learns a protected file\'s exact address could download it.', 'document-engine')),
                        array('code', 'location ^~ /wp-content/uploads/document-engine-private- { deny all; return 403; }'),
                    )),
                    'ts-cache' => array(__('Members see the wrong list, or a page cache shows private content', 'document-engine'), true, array(
                        array('p', __('Pages with personal lists (My Documents, Required Reading) tell caching plugins not to cache them. For library pages that show restricted documents differently to members, exclude logged-in users from your page cache (most caching plugins do this by default).', 'document-engine')),
                    )),
                    'ts-search' => array(__('Search does not find words inside a file', 'document-engine'), true, array(
                        array('p', __('File text is indexed in the background after upload; large files can take a minute. Run <code>wp dengine reindex</code> to rebuild it. Scanned PDFs (images of text) contain no text to index.', 'document-engine')),
                    )),
                    'ts-license' => array(__('Pro licence or updates do not work', 'document-engine'), true, array(
                        array('p', __('Check the key in Settings → License (it shows the status and renewal date). If it says the key "has reached its site limit", deactivate it on another site or upgrade the licence. Your server must be able to reach the store over https. Pro keeps working without an active licence; only updates need one.', 'document-engine')),
                    )),
                ),
            ),

            'faq' => array(
                'title' => __('FAQ', 'document-engine'),
                'icon' => 'help',
                'intro' => __('Short answers to frequent questions.', 'document-engine'),
                'articles' => array(
                    'faq-free' => array(__('Is the free version limited in time or in number of documents?', 'document-engine'), false, array(
                        array('p', __('No. The free plugin has no time limit and no document limit.', 'document-engine')),
                    )),
                    'faq-compat' => array(__('Does it work with my theme and page builder?', 'document-engine'), false, array(
                        array('p', __('Libraries use your theme\'s fonts and colors and adapt to the space they get. Blocks work in the block editor and site editor; Elementor has its own widgets; shortcodes work anywhere else. Templates can be overridden in your theme (see Developers).', 'document-engine')),
                    )),
                    'faq-old-shortcodes' => array(__('Will my version 1 shortcodes and blocks keep working?', 'document-engine'), false, array(
                        array('p', __('Yes. Version 1 shortcodes, the PDF block, settings and the settings page address are kept.', 'document-engine')),
                    )),
                    'faq-private' => array(__('Can I keep documents private with the free plugin?', 'document-engine'), false, array(
                        array('p', __('You can keep documents as drafts or private posts (only editors see them), but a published document can be downloaded by anyone who has the link. Member-only access, private file storage and share links are Pro features.', 'document-engine')),
                    )),
                    'faq-uninstall' => array(__('What happens when I uninstall?', 'document-engine'), false, array(
                        array('p', __('By default nothing is deleted, so you can reinstall without losing documents. To remove everything, turn on Settings → Advanced → When deleting the plugin before deleting it. Uploaded files in the Media Library are never deleted.', 'document-engine')),
                    )),
                    'faq-pro-off' => array(__('What happens if my Pro licence expires?', 'document-engine'), true, array(
                        array('p', __('Pro keeps working; you stop receiving updates and support until you renew.', 'document-engine')),
                    )),
                    'faq-multisite' => array(__('Does it support multisite?', 'document-engine'), false, array(
                        array('p', __('The plugin works per site: activate it on each site that needs it; every site keeps its own documents and settings.', 'document-engine')),
                    )),
                ),
            ),

            'privacy' => array(
                'title' => __('Security & privacy', 'document-engine'),
                'icon' => 'shield',
                'intro' => __('What is stored, what leaves your site, and who can see it.', 'document-engine'),
                'articles' => array(
                    'data-stored' => array(__('Data the plugin stores', 'document-engine'), false, array(
                        array('table', array(
                            array(__('Documents, categories, tags', 'document-engine'), __('WordPress posts, terms and post meta (file, type, size, download count).', 'document-engine'), __('Free', 'document-engine')),
                            array(__('Format requests', 'document-engine'), __('Name, email, requested format, message. Visible to editors and administrators.', 'document-engine'), __('Free', 'document-engine')),
                            array(__('Activity log, reading sessions, searches', 'document-engine'), __('Document, event, time, user ID for logged-in users; a daily-rotating hash for visitors. No IP addresses. Deleted after the retention period.', 'document-engine'), __('Pro', 'document-engine')),
                            array(__('Leads', 'document-engine'), __('Name, email, organization, document, time. Deleted after the lead retention period if set.', 'document-engine'), __('Pro', 'document-engine')),
                            array(__('Acknowledgements', 'document-engine'), __('User, name, time, statement, file fingerprint, IP address and browser (kept as evidence).', 'document-engine'), __('Pro', 'document-engine')),
                        ), array(__('Data', 'document-engine'), __('What', 'document-engine'), __('Edition', 'document-engine'))),
                        array('p', __('Format requests, and with Pro leads, activity, searches, reading sessions and acknowledgements, are included in Tools → Export Personal Data and Erase Personal Data.', 'document-engine')),
                    )),
                    'external' => array(__('External services', 'document-engine'), false, array(
                        array('p', __('The free plugin does not contact external services on its own. The viewer, PDF generation and QR codes run on your site. Exceptions you control:', 'document-engine')),
                        array('ul', array(
                            __('Version 1 PDF blocks on sites upgraded from version 1 keep using Google\'s document viewer (which loads the PDF from Google) until you switch on Settings → PDF Viewer → Version 1 blocks. New installs use the built-in viewer.', 'document-engine'),
                            __('Documents that link to external files load them from that site.', 'document-engine'),
                            __('Pro: the licence server (activation and updates), your webhook and Slack/Teams URLs, Cloudflare Turnstile when switched on, and the AI provider you connected in WordPress when you use the assistant.', 'document-engine'),
                        )),
                    )),
                    'security-model' => array(__('How access is enforced', 'document-engine'), false, array(
                        array('ul', array(
                            __('Every download and view goes through the document file server, which checks the document is published and (with Pro) that the person may open it.', 'document-engine'),
                            __('Admin actions check both a permission and a security token (nonce).', 'document-engine'),
                            __('Pro checks access on the server for every download, preview, REST request, search result and AI ability; hiding a button is never the only protection.', 'document-engine'),
                            __('With Pro, restricted files are moved to a private folder; on nginx you must add the server rule (see Troubleshooting).', 'document-engine'),
                        )),
                    )),
                ),
            ),

            'dev' => array(
                'title' => __('Developers', 'document-engine'),
                'icon' => 'list',
                'intro' => __('Shortcodes, blocks, templates, hooks, REST routes.', 'document-engine'),
                'articles' => array(
                    'dev-shortcodes' => array(__('Shortcodes', 'document-engine'), false, array(
                        array('table', array(
                            array('<code>[document_engine_library]</code>', __('id, layout (table|grid|folders), categories, tags, include, exclude, file_types, per_page, orderby (date|title|modified|downloads|menu_order|rand), order, columns, grid_columns, search, filters (category,tag,type,year,author,sort, cf_key with Pro), multi_filters, show_thumbnails, show_excerpt, link_to (document|file|none), pagination, folder_limit, open_folders, class', 'document-engine')),
                            array('<code>[document_engine_document]</code>', __('id, style (card|button), label, show_meta', 'document-engine')),
                            array('<code>[document_engine_documents]</code>', __('mode (recent|updated|popular|related), count, category, title, show_meta, document', 'document-engine')),
                            array('<code>[document_engine_search]</code>', __('page, placeholder, button', 'document-engine')),
                            array('<code>[document_engine_viewer]</code>', __('id (document), file (attachment), url, height, width, toolbar, download, print, fullscreen, page, zoom', 'document-engine')),
                            array('<code>[document_engine_pdf_button]</code>', __('Post to PDF button', 'document-engine')),
                            array(__('Pro', 'document-engine'), '<code>[document_engine_my_documents]</code> <code>[document_engine_required_reading]</code> <code>[document_engine_subscribe]</code> <code>[document_engine_submit]</code> <code>[document_engine_handbook]</code>'),
                        ), array(__('Shortcode', 'document-engine'), __('Attributes', 'document-engine'))),
                    )),
                    'dev-templates' => array(__('Template overrides', 'document-engine'), false, array(
                        array('p', __('Copy a file from the plugin\'s <code>templates/</code> folder to a <code>document_engine/</code> folder in your theme, keeping the sub-folder (for example <code>document_engine/document/single.php</code>). Filter the folder name with <code>document_engine_template_path</code>.', 'document-engine')),
                    )),
                    'dev-hooks' => array(__('Main hooks', 'document-engine'), false, array(
                        array('table', array(
                            array('<code>document_engine_can_access_document</code>', __('Filter: may this user view/download this document (Pro adds its rules here).', 'document-engine')),
                            array('<code>document_engine_before_serve_document</code>', __('Action before a file is sent.', 'document-engine')),
                            array('<code>document_engine_download_filename</code>', __('Filter the download file name.', 'document-engine')),
                            array('<code>document_engine_library_query_args</code>', __('Filter the WP_Query arguments of a library.', 'document-engine')),
                            array('<code>document_engine_library_columns</code>, <code>document_engine_library_cell</code>', __('Add library columns and their cell markup.', 'document-engine')),
                            array('<code>document_engine_library_filter_keys</code>, <code>document_engine_library_controls</code>, <code>document_engine_library_state</code>', __('Add library filters.', 'document-engine')),
                            array('<code>document_engine_library_searched</code>', __('Action when a visitor searches a library.', 'document-engine')),
                            array('<code>document_engine_viewer_config</code>', __('Filter the viewer\'s settings.', 'document-engine')),
                            array('<code>document_engine_single_after_head</code>', __('Action below the summary card on document pages.', 'document-engine')),
                            array('<code>document_engine_save_document</code>', __('Action after a document is saved.', 'document-engine')),
                            array('<code>document_engine_menu_groups</code>, <code>document_engine_menu_ranks</code>', __('Arrange the Documents menu.', 'document-engine')),
                            array('<code>document_engine_register_abilities</code>, <code>document_engine_ability_document</code>', __('Add abilities; change the document data they return.', 'document-engine')),
                        ), array(__('Hook', 'document-engine'), __('Use', 'document-engine'))),
                    )),
                    'dev-rest' => array(__('REST routes', 'document-engine'), false, array(
                        array('table', array(
                            array('<code>GET /document-engine/v1/library</code>', __('Rendered library results (used by the library script).', 'document-engine')),
                            array('<code>GET /document-engine/v1/preview/&lt;id&gt;</code>', __('Preview popup content; 403 if the visitor may not open the document.', 'document-engine')),
                            array('<code>GET /document-engine/v1/documents</code>', __('Document search for editor pickers (needs editing permission).', 'document-engine')),
                            array('<code>/wp/v2/dengine_document</code>', __('Standard WordPress REST for documents.', 'document-engine')),
                            array(__('Pro', 'document-engine'), '<code>/document-engine-pro/v1/documents/&lt;id&gt;/share</code>, <code>/documents/&lt;id&gt;/suggest</code>, <code>/read</code>'),
                        ), array(__('Route', 'document-engine'), __('Purpose', 'document-engine'))),
                    )),
                ),
            ),
        );

        /**
         * Add or change documentation sections and articles.
         */
        return apply_filters('document_engine_docs_sections', $sections);
    }
}
