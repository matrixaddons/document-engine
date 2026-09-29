=== Document Engine – Document Library, PDF Viewer & Post to PDF ===
Contributors: MatrixAddons
Tags: document library, pdf viewer, embed pdf, download manager, document management
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Searchable document library, a private built-in PDF viewer and Post to PDF. Unlimited documents, no ads, nothing sent to Google.

== Description ==

**Document Engine** turns WordPress into a document library your visitors can actually use. Publish policies, meeting minutes, reports, forms and guides; let people search and filter them instantly; show PDFs in a fast viewer that runs on your own site; and let visitors save any post or page as a PDF.

It is built for the block editor, works with any theme, and the free plugin has **no document limits, no ads and no time limits**.

**Made for** councils and public bodies, schools and universities, housing and community associations, intranets and HR teams, nonprofits, law and accounting firms, and member sites.

= Why Document Engine =

* **A real document library, free.** Table, grid and folder layouts with instant search, filters with counts, multi-select filters, sorting and shareable links, all in the free plugin.
* **A private PDF viewer.** Built on PDF.js and served from your own site: no Google Docs viewer, no third-party requests, so it works on intranets and staging sites and helps with GDPR.
* **Viewer features other plugins charge for.** Search inside PDFs, page thumbnails and outline, clickable links, links to a page (`#page=4`), print, download and full screen.
* **One link per document, for good.** Every download goes through the document's own link: downloads are counted (bots and link previews are ignored), and replacing the file keeps the same link, so emails and printed QR codes never break.
* **Switch without losing anything.** Move from Document Library (Barn2), Download Monitor or WordPress Download Manager in a few clicks. Their shortcodes keep working and their old download links redirect.
* **Accessibility first.** Keyboard and screen-reader friendly libraries and viewer, checked with automated WCAG 2.1 AA tests, plus an optional "Request an accessible version" form.

= Document library =

* **Documents** with nested categories, tags, a description and their own page. Titles are filled in from the file name.
* **Document Library block** (and `[document_engine_library]` shortcode, and Elementor widget) with **table, grid and folder** layouts and a live preview in the editor.
* **Instant search** as visitors type, and it still works without JavaScript.
* **Filters** by category (with document counts), tag, file type, year and author, plus sorting and pagination. Turn on **"Let visitors pick several"** to let people combine categories, tags or file types.
* **Shareable filtered views**: every search and filter has its own address, so you can link straight to "all 2026 minutes".
* **Preview popup**: PDFs, images, audio and video open in a popup from the library, without leaving the page.
* **Document Search block**: put a search box in your header or home page that shows results in your library.
* **Document List block**: newest, recently updated, most downloaded or related documents, and optional related documents under each document page.
* **Document Download block**: one document as a card or a button, anywhere.
* **Documents in your site search**, with file type and size.
* **Download or open**: set it for the whole site, or per document.
* **Any file type**: PDF, Word, Excel, PowerPoint, images, audio, video, archives, or a link to a file on another site (for example a Google Drive, Dropbox or OneDrive share link).
* **Turn existing files into documents**: select them in Media → Library and choose *Create documents*.
* **Mobile friendly**: libraries adapt to phones and narrow columns and use your theme's fonts and colours.

= PDF viewer =

* **Self-hosted PDF.js viewer** with page navigation, zoom, fit to width or page, print, download and full screen. Every button can be switched off.
* **Search inside the PDF** with highlighted matches.
* **Page thumbnails and outline** (bookmarks) sidebar.
* **Clickable links** inside PDFs, including links to other pages of the same PDF.
* **Link to a page**: add `#page=12` to a document's address.
* **Fast**: the viewer loads when it scrolls into view and renders pages as you read. Text stays selectable.
* Show a document, a Media Library PDF or a PDF from another site with the **PDF Viewer block** or `[document_engine_viewer]`.

= Document pages =

Every document gets a clean page with its file type, size, last update, categories, a download button and, for PDFs, the viewer. Pages are indexed by search engines, and can be switched off if you only want the file links. Templates can be overridden in your theme.

= Post to PDF =

* A **"Download PDF" button** on the post types you choose, placed before or after the content, or anywhere with the **Save as PDF block** or `[document_engine_pdf_button]`.
* Header logo, title and page numbers, footer text, page orientation, margins, text size, custom CSS or your theme's styles.
* Text and image **watermarks**, and optional **PDF protection** (print, copy and edit permissions).
* PDF-only shortcodes for page breaks, columns and content hidden from the PDF.
* **Fast and safe**: generated PDFs are cached, each visitor has a per-minute limit, and only public content is exported (never private or password-protected posts).

= Moving from another plugin =

Document Engine copies your documents from **Document Library (Barn2)**, **Download Monitor** and **WordPress Download Manager**: titles, descriptions, categories, tags, files, download counts, dates and passwords. A preview shows what will be copied first, the original plugin's data is never changed, and running it again skips anything already moved. Afterwards the old shortcodes keep working and old download links redirect, so you can deactivate the old plugin.

= For editors and admins =

* **QR code** for every document (PNG or SVG) to print on notices, agendas and handouts.
* **Command palette** (Ctrl/Cmd + K): add a document or jump to any document from anywhere in the admin.
* **Documents list** with file type and size, category and download counts, and filters by category and file type.
* **Roles**: choose which roles manage all documents, publish their own, or only draft.
* **Documentation built in** under Documents → Docs.
* **Multilingual**: WPML and Polylang configuration included.
* **Ready for AI assistants**: the WordPress Abilities API lets assistants search and read your library, only ever returning documents the signed-in user may open.

= Document Engine Pro =

The free plugin publishes documents. **Pro controls who can open them, proves who read them, and keeps them up to date**, for councils, schools, HR teams, intranets, client portals and member sites.

Pro is an add-on: install it next to the free plugin and its features appear in the same screens. Your documents, pages and settings stay exactly as they are. [See Document Engine Pro](https://matrixaddons.com/plugins/document-engine/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=document-engine-pro&utm_content=intro#features)

**Pro is for you if you need to…**

* **Publish staff-only or members-only documents** without a membership plugin: restrict a document, or a whole category, to logged-in users, chosen roles or named people.
* **Stop files being shared by their address**: restricted files move out of the public uploads folder and are only served to people allowed to open them.
* **Send a document to someone outside your site**, such as an auditor, a board member or a client, with a link that expires and tells you when they opened it.
* **Make staff confirm they've read a policy**, with deadlines, reminders and evidence you can export for an inspection or audit.
* **See which documents people read**, for how long, and what they search for but can't find.
* **Collect leads** before a download (name, email, consent), for brochures, reports and whitepapers.
* **Keep a register of documents** with your own fields (reference number, department, review date) that visitors can filter and sort by.
* **Move hundreds of files in at once** from a spreadsheet, and keep them current with versions, review dates and automatic expiry.

= Everything Pro adds =

Screenshots and details of every feature are on the [Document Engine Pro page](https://matrixaddons.com/plugins/document-engine/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=document-engine-pro&utm_content=features#features).

**Access control**

* **Members-only documents** by logged-in users, roles or named people, per document or for a whole category (sub-categories follow).
* Restricted documents shown **with a lock** or **hidden** from people who can't open them, and left out of site search, feeds and file-content search.
* **Private file storage** for restricted files, with a **Site Health test** that checks the folder really is private (and shows the nginx rule when it isn't).
* **Share links** for people without an account: expiring, **named per recipient**, with **open tracking**, an optional **email on first open**, and one-click revoke.
* **"My documents" block**: a client or staff area listing the documents shared with the signed-in person, with what's new since their last visit.

**Secure viewing and downloads**

* **Secure viewer**: read-only, with no download, print or copy buttons, and the **reader's name, email and date watermarked on every page**.
* **Stamped PDF downloads**: every downloaded PDF carries the reader's details.
* **Email gate**: name and email (and optionally organisation) with your consent text before a download; returning visitors pass straight through. Leads are listed, exported to CSV, emailed to you or sent to a webhook, and deleted after a retention period you choose.
* **Abuse protection**: a daily limit on how many different documents one person can open, and **Cloudflare Turnstile** on the email gate, request and submission forms.

**Proof and insight**

* **Activity log** of views, downloads, blocked attempts, leads and share-link opens, with charts, top documents, filters and **CSV export**. No IP addresses are stored; entries are deleted after your retention period.
* **Reading analytics**: time spent on each page of a PDF and how far people read.
* **Search insights**: what visitors search for, and the searches that found nothing.
* **Read and confirm**: ask chosen roles to confirm "I have read and understood", with **deadlines, reminder emails, a Required Reading block**, new rounds after an important change, and an **evidence export** (who, when, the statement they confirmed and a fingerprint of the file).
* Views in the last 30 days and access status right in the documents list.

**Keeping documents current**

* **Versions**: replace a file without changing its link, see the history and restore any version. **Upload a new version from the documents list** (or drop a file onto a row).
* **Review-by and expiry dates**, with a daily email of documents due for review; expired documents are unpublished automatically.
* **Custom fields** (text, number, date, choice list, yes/no, link, email) shown as **library columns, filters with counts and sort options**, on document pages and in search. Works with **Advanced Custom Fields**.
* **PDF accessibility check** that flags untagged, scanned or untitled PDFs in the documents list.

**Scale and automation**

* **Search inside PDF, Word, Excel and PowerPoint files**, in libraries and site search.
* **Automatic PDF thumbnails** for grid layouts (when your server can make PDF previews).
* **Bulk upload** (drag and drop many files) and **CSV import**, including updates by ID and custom-field columns.
* **ZIP download**: visitors select several documents in a library and download them as one ZIP.
* **Front-end submissions**: chosen roles upload documents from a page, with allowed file types, a size limit and moderation.
* **Handbook PDF**: many posts in one PDF with a cover and table of contents, for admins or visitors (`[document_engine_handbook]`).
* **Notifications**: signed webhooks (for Zapier, Make or your own system), **Slack or Microsoft Teams** messages, and **email subscriptions** by category with a Subscribe block and one-click unsubscribe.
* **AI assistant** in the editor that suggests a title, summary, categories and tags from the file's text, using the AI provider connected to WordPress (no extra keys).
* **More for AI agents**: abilities to read a document's text (never for secure documents) and to list documents due for review.
* **WP-CLI**: list, import, stats, reindex, protect and share.
* Personal data **export and erase** for leads, activity, searches, reading sessions and acknowledgements, and **automatic updates** with an active licence.

**Plans** (every plan includes every Pro feature):

* **Personal**: 1 website, $79 a year or $199 lifetime.
* **Plus**: 5 websites, $149 a year or $379 lifetime.
* **Agency**: 25 websites and priority support, $249 a year or $599 lifetime.

Every plan has a 14-day money-back guarantee ([refund policy](https://mantrabrain.com/refund-policy/)). If a licence expires, Pro keeps working; only updates and support stop.

[See Pro plans and pricing](https://matrixaddons.com/plugins/document-engine/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=document-engine-pro&utm_content=plans#pricing)

= Free vs Pro =

**Free** is a complete document library: libraries, search and filters, the PDF viewer, document pages, Post to PDF, blocks, shortcodes, Elementor widgets and migration, with no limits.

**Pro** adds control, proof and scale: who can open each document, private files, the secure viewer, read-and-confirm, analytics, versions, custom fields and bulk import.

The full side-by-side list is in the plugin under **Documents → Free vs Pro** and on our website: [compare Free and Pro](https://matrixaddons.com/plugins/document-engine/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=document-engine-pro&utm_content=compare#compare).

= Privacy =

The free plugin stores documents, categories and tags as normal WordPress content, plus a download count per document. Accessible-format requests (when switched on) store the requester's name and email; they are included in WordPress's personal data export and erase tools. The plugin does not track visitors, set tracking cookies or send data to other services, apart from the optional cases listed under "External services".

= External services =

The free plugin works without any external service. It only contacts one in these cases:

* **Google Docs Viewer** (`docs.google.com`): only for PDF Viewer blocks created with version 1, on sites upgraded from version 1, until you switch on *Documents → Settings → PDF Viewer → Version 1 blocks*. The visitor's browser loads Google's viewer, which fetches the PDF's address. New installs never use it. [Google Terms of Service](https://policies.google.com/terms), [Google Privacy Policy](https://policies.google.com/privacy).
* **MatrixAddons licence server** (`store.mantrabrain.com`): only when an administrator enters a Pro licence key on *Documents → Free vs Pro* to install Pro. The licence key and your site's address are sent to check the licence and download Pro. [Terms and Conditions](https://mantrabrain.com/terms-and-conditions/), [Privacy Policy](https://mantrabrain.com/privacy-policy/).
* **Files you link to**: documents or viewers that point to a file on another website load that file from that website.
* **Images in Post to PDF**: when a post includes images or stylesheets from another website, your server fetches them from that website while it builds the PDF (local and private addresses are refused).

= Documentation and support =

* Built-in documentation: **Documents → Docs** in your WordPress admin.
* Questions about the free plugin: the [support forum](https://wordpress.org/support/plugin/document-engine/) here on WordPress.org.
* Document Engine Pro: [features, plans and FAQ](https://matrixaddons.com/plugins/document-engine/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=document-engine-pro&utm_content=support) and [pre-sales questions](https://matrixaddons.com/contact-us/). Pro customers get email support (priority support on the Agency plan).

== Installation ==

1. In **Plugins → Add New**, search for "Document Engine", then install and activate it.
2. Go to **Documents → Add New Document** and upload a file, or select existing files in **Media → Library** (list view) and choose **Bulk actions → Create documents**.
3. Create a page and add the **Document Library** block. Choose the layout, columns and filters in the block settings.
4. Optional: in **Documents → Settings → Post to PDF**, choose which post types get a "Download PDF" button.
5. Optional: moving from another document plugin? Open **Documents → Tools → Migrate**.

Requirements: WordPress 6.6 or newer and PHP 7.4 or newer. Post to PDF needs the PHP mbstring and gd extensions.

== Frequently Asked Questions ==

= Is the free plugin limited? =

No. There's no limit on documents, libraries, downloads or time, and no ads. Pro adds features for organisations; it doesn't unlock anything that was taken out of the free plugin.

= Does the PDF viewer use Google? =

No. Document Engine includes its own viewer (PDF.js) and never contacts Google. The only exception is PDF Viewer blocks created with version 1 on sites that upgraded: they keep Google's viewer until you switch on *Documents → Settings → PDF Viewer → Version 1 blocks* (see "External services").

= Do I need Pro? =

Not to publish documents. The free plugin is a complete library with the viewer, search, filters and Post to PDF. You need Pro when documents must be limited to certain people, when you need proof of who read something, or when you manage enough documents to want versions, review dates, custom fields, bulk import and analytics.

= How do I upgrade to Pro? =

Choose a plan on the [Document Engine Pro page](https://matrixaddons.com/plugins/document-engine/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=document-engine-pro&utm_content=faq#pricing), then either upload the Pro plugin from your account, or enter your licence key at the bottom of **Documents → Free vs Pro** and it installs in one step. Keep the free plugin active; Pro builds on it, and nothing needs to be moved or set up again.

= Can I keep documents private? =

With the free plugin you can keep documents as drafts, private posts or password-protected, and their download links respect that. Their files still live in the public uploads folder, so anyone who has a file's exact address could open it. Pro adds members-only documents by role, person or category and moves restricted files to private storage.

= Will my version 1 content keep working? =

Yes. Your settings, the five PDF shortcodes, the PDF Viewer block and the settings page link all keep working. The Post to PDF settings are now under **Documents → Settings → Post to PDF**.

= Can I move from Download Monitor, WordPress Download Manager or Barn2's Document Library? =

Yes, from **Documents → Tools → Migrate**. Files, categories, tags, dates, download counts and passwords come across; the old shortcodes keep working and old download links redirect. The original plugin's data isn't changed.

= Can I show PDFs hosted on another website? =

Yes, if that website allows it (CORS). If it doesn't, visitors see a link to open the PDF instead.

= Does it work with my theme and page builder? =

Libraries use your theme's fonts and colours and adapt to the space they get. Blocks work in the block and site editors, Elementor has its own widgets, and shortcodes work everywhere else. Copy any file from the plugin's `templates` folder to `your-theme/document_engine/` to change it, and adjust colours with CSS custom properties such as `--dengine-accent`.

= Is it accessible? =

The library, filters and viewer are keyboard and screen-reader friendly, with labelled controls, live regions and visible focus, and the admin screens pass automated WCAG 2.1 AA checks. PDFs keep a text layer, so browser search and screen readers work. You can also switch on a "Request an accessible version" form (**Documents → Settings → Documents → Accessibility**).

= Does it slow my site down? =

Styles and scripts load only on pages that use them, the viewer loads when it scrolls into view, and generated PDFs are cached. Libraries were tested with 10,000 documents.

= What happens when I uninstall? =

Nothing is deleted unless you choose to: turn on **Documents → Settings → Advanced → When deleting the plugin** first if you want documents and settings removed. Files in your Media Library are never deleted.

= Where is the documentation? =

In your admin under **Documents → Docs**: getting started, every feature, settings, how-to guides, troubleshooting and developer notes, with search.

= Which shortcodes are there? =

Every block has a shortcode for classic themes and page builders:

* `[document_engine_library layout="table|grid|folders" categories="slug" file_types="pdf,word" per_page="20" filters="category,tag,type,year,author,sort" multi_filters="yes"]`
* `[document_engine_search page="123"]`
* `[document_engine_viewer id="document ID" file="attachment ID" url="https://…" height="800px" page="1"]`
* `[document_engine_document id="12" style="card|button"]`
* `[document_engine_documents mode="recent|updated|popular|related" count="5" category="slug" title="Latest"]`
* `[document_engine_pdf_button]`, `[document_engine_pdf_remove]…[/document_engine_pdf_remove]`, `[document_engine_pdf_page_break]`, `[document_engine_pdf_columns]…[/document_engine_pdf_columns]`, `[document_engine_pdf_column_break]`

All attributes are listed in **Documents → Docs → Developers**.

== Screenshots ==

1. Document library with instant search, filters with counts and multi-select.
2. Table, grid and folder layouts.
3. The built-in PDF viewer: search inside PDFs, page thumbnails, outline and links.
4. Preview documents in a popup without leaving the library.
5. Every document gets its own page with file details, a download button and the viewer.
6. The Document Library block in the block editor.
7. Replace a document's file without breaking links, and print its QR code.
8. The documents list in the admin.
9. Libraries adapt to phones and narrow columns.
10. Step-by-step documentation inside WordPress (Documents → Docs).

== Changelog ==

= 2.0.0 - 2026-09-29 =
* New - Documents: post type with categories, tags, single pages, file type/size, download counter, open-or-download setting, and site search.
* New - Document Library block and shortcode: table, grid and folder layouts, instant search, filters, sorting, pagination, shareable URLs.
* New - Built-in PDF viewer (PDF.js) block and shortcode. It replaces the Google Docs viewer for new content.
* New - Library filters: let visitors pick several categories, tags or file types at once ("Let visitors pick several"), plus year and author filters with counts.
* Tweak - A shorter Documents menu: related screens share one item with tabs (Categories and Tags; Reports; Tools). Existing admin links keep working.
* New - Document Download and Save as PDF blocks.
* New - Media Library bulk action "Create documents", onboarding checklist, Site Health checks.
* New - Document List block and shortcode (newest, updated, popular, related) and optional related documents on document pages.
* New - Migrate from Document Library (Barn2), Download Monitor and WordPress Download Manager, with compatibility for their shortcodes.
* New - Elementor widgets, WPML/Polylang configuration, and an optional "Request an accessible version" form.
* New - PDF viewer: search inside the document, page thumbnails and outline, clickable links, links to a page.
* New - Year and author filters, category counts, Document Search block, QR codes, command palette and Abilities API support.
* New - Post to PDF cache and per-visitor generation limit.
* Security - PDF export is limited to publicly viewable content and respects post passwords.
* Fixed - Custom PDF CSS keeps characters such as ">" (child selectors).
* Fixed - The PDF header no longer renders empty space when nothing is enabled.
* Fixed - Concurrent PDF downloads no longer delete each other's temporary files.
* Fixed - Settings field attributes (min, step) were escaped twice.
* Improved - Styles and scripts load only on pages that use them.
* Improved - mPDF 8.3.1 is bundled under the plugin's own namespace, so it can't clash with other plugins.
* New - Documents → Docs: searchable documentation inside WordPress, with a "Help for this screen" link on every screen.
* New - Documents → Free vs Pro: what each edition includes.
* New - Preview popup for PDFs, images, audio and video from any library.
* Security - Files of draft, private and password-protected documents are always streamed, never redirected to, and are hidden from the media REST API for people who can't read them.
* Security - Unpublished documents no longer reveal their title on the download link.
* Security - Post to PDF only runs for the post types you enable (or posts with the Save as PDF block), never for attachments, and skips images from internal network addresses.
* Security - Accessible-format request limits are per visitor, so one person can't block requests for everyone.
* Improved - Faster admin and front end on large libraries (tested with 10,000 documents), and accessibility fixes in admin screens.
* Improved - A single bundled library folder (vendor-prefixed); Composer's vendor folder is no longer shipped.
* Requirements - PHP 7.4+ and WordPress 6.6+.

= 1.3 - 2025-08-26 =
* Fixed - Escaping issue fixed

= 1.2 - 2025-04-13 =
* Fixed - Setting page design

== Upgrade Notice ==

= 2.0.0 =
Adds a document library, a private built-in PDF viewer and a security fix. Existing shortcodes, settings and blocks keep working. Requires PHP 7.4 and WordPress 6.6.
