=== Document Engine – Document Library, PDF Viewer & Post to PDF ===
Contributors: MatrixAddons
Tags: document library, pdf viewer, embed pdf, document management, post to pdf
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Searchable document library, private PDF viewer that never calls Google, and one-click post-to-PDF downloads. For schools, councils and intranets.

== Description ==

**Document Engine** turns WordPress into a document hub. Publish policies, minutes, reports and forms in a searchable library. Show PDFs with a fast built-in viewer. Let visitors download any post or page as a branded PDF.

It is built for the block editor, and nothing is sent to Google or any other service.

= Document library (free) =

* **Documents** with categories (nested), tags, description and single pages. Titles are filled in from the file name.
* **Document Library block** with table, grid and folder layouts, live preview in the editor, and a `[document_engine_library]` shortcode.
* **Instant search and filters** by category (with counts), tag, file type, year and author, with sorting and pagination. Filtered views have their own URL, so you can link to "all 2026 minutes".
* **Document Search block**: put a search box in your header or home page that shows results in your library.
* **Works without JavaScript** and on phones (tables turn into cards).
* **Documents appear in your site search** (with file type and size).
* **Choose per document**: download the file, or open it in the browser.
* **Download counter** that ignores bots and link previews.
* **Media Library → Documents**: select files and choose *Create documents*.
* Files from Google Drive, Dropbox, OneDrive or any URL can be listed too.
* **Document List block**: newest, recently updated, most downloaded or related documents, plus optional "Related documents" under each document page.
* **Move from another plugin in one click**: Document Library (Barn2), Download Monitor and WordPress Download Manager. Files, categories, tags, download counts and passwords come across, and the old shortcodes keep working.
* **Elementor widgets** for the library, viewer, download card and document list.
* **Multilingual ready**: WPML and Polylang configuration included (a different file per language).
* **"Request an accessible version"** form on document pages (optional), with a list of requests to answer.
* **QR codes** for any document, to print on notices, agendas and handouts.
* **Command palette** (Ctrl/Cmd + K): add a document or jump to any document from anywhere in the admin.
* **Ready for AI assistants**: the WordPress Abilities API lets assistants search and read your library, only ever showing what the user may open.

= PDF viewer (free) =

* **Self-hosted PDF.js viewer**: page navigation, zoom, fit to width, full screen, download and print. Everything can be switched off.
* **Search inside the PDF**, **page thumbnails and outline** sidebar, **clickable links** in the PDF, and **links to a page** (`#page=4`). Features other viewers charge for.
* **Private by design**: no Google Docs viewer and no third-party requests, so it works with files on intranets and staging sites and helps with GDPR.
* **Fast**: the viewer loads only when it scrolls into view and renders pages as you read. Text stays selectable and searchable.
* Show a document, a Media Library PDF or any PDF URL. The **PDF Viewer block** and `[document_engine_viewer]` shortcode are included.

= Post to PDF (free) =

* A **"Download PDF" button** on any post type, placed before or after the content, or as a block or `[document_engine_pdf_button]`.
* Header logo, title and page numbers, footer text, page size, orientation, margins, custom CSS and your theme's styles.
* PDF shortcodes: page break, columns, and content hidden from the PDF.
* **PDF protection** (copy, print and modify permissions).
* **Fast and safe**: generated PDFs are cached and there's a per-visitor limit. Only public content can be exported.

= Document Engine Pro =

Pro adds control, proof and scale for organisations:

* **Access control**: restrict documents or whole categories to logged-in users, roles or named people.
* **Protected storage**: files leave the public uploads folder. **Share links** expire.
* **Secure viewer**: no download, print or copy, with the viewer's name, email and date on every page. **Stamped PDF downloads**.
* **Audit log and analytics**: who viewed or downloaded what and when, with CSV export and retention settings.
* **Versions** (same link, full history, restore). **Expiry and review dates** with reminder emails.
* **Email gate** (lead capture) with webhooks.
* **Search inside PDF, Word, Excel and PowerPoint files**. **Bulk import** (drag and drop or CSV) and **ZIP downloads**.
* **Front-end submissions** and **Handbook PDFs** (many posts in one PDF with contents). **WP-CLI**.
* **Policy acknowledgements**: "I have read and understood", with deadlines, reminders, typed signatures, rounds after a policy change, a Required Reading block and CSV evidence export.
* **Search insights**: what visitors search for, and what they cannot find.
* **Notifications**: signed webhooks, Slack or Microsoft Teams messages, and category email subscriptions.
* **AI assistant**: suggested titles, summaries, categories and tags using the AI provider connected to WordPress (no keys stored in the plugin).
* **PDF accessibility checks**: flags untagged, scanned, untitled or language-less PDFs in the documents list.

= Free vs Pro, at a glance =

Free is a complete document library with no ads and no feature limits on what it includes: libraries (table, grid, folders), filters and search, the PDF viewer with search and thumbnails, document pages, download counts, Post to PDF, migration, blocks, Elementor widgets and translations.

Pro adds what organisations need to control, prove and scale: who can open what, private files, the secure viewer and watermarks, activity and reading analytics, email gate, versions, review and expiry dates, search inside files, ZIP downloads, submissions, policy acknowledgements, notifications, the AI assistant and priority support.

[See Document Engine Pro](https://matrixaddons.com/plugins/document-engine/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=document-engine)

== Installation ==

1. Install from **Plugins → Add New** (search "Document Engine") and activate.
2. Go to **Documents → Add New**, or select files in **Media → Library** (list view) and choose **Bulk actions → Create documents**.
3. Add the **Document Library** block to a page.
4. Optional: in **Documents → Settings → Post to PDF**, choose which post types get a "Download PDF" button.

== Frequently Asked Questions ==

= Does the PDF viewer use Google? =

No. Version 2 includes its own viewer (PDF.js) and never contacts Google or anyone else.

If your site used the PDF Viewer block from version 1, those blocks keep the Google viewer until you switch them in **Documents → Settings → PDF Viewer → Classic PDF Viewer blocks**. You can also convert each one to the new block (block toolbar → Transform).

= Can I show PDFs hosted on another website? =

Yes, if that website allows it (CORS). If it doesn't, visitors see an "Open the PDF" link instead.

= Where are my version 1 settings? =

Everything still works: settings, the five PDF shortcodes, the classic block and the settings page link. The PDF settings are now under **Documents → Settings → Post to PDF**.

= Can themes change the look? =

Yes. Copy any file from the plugin's `templates` folder to `your-theme/document_engine/`. Colors follow your theme and can be changed with CSS custom properties such as `--dengine-accent`.

= Is it accessible? =

The library, filters and viewer controls are keyboard and screen-reader friendly, with labelled controls, live regions and visible focus. PDFs keep a text layer, so browser search and screen readers work. You can also switch on a "Request an accessible version" form (Settings → General → Accessibility) so visitors can ask for another format.

== Shortcodes ==

* `[document_engine_library layout="table|grid|folders" categories="slug" file_types="pdf,word" per_page="20" filters="category,tag,type,sort"]`
* `[document_engine_viewer id="document ID" file="attachment ID" url="https://…" height="800px"]`
* `[document_engine_document id="12" style="card|button"]`
* `[document_engine_documents mode="recent|updated|popular|related" count="5" category="slug" title="Latest"]`
* `[document_engine_pdf_button]`, `[document_engine_pdf_remove]…[/document_engine_pdf_remove]`, `[document_engine_pdf_page_break]`, `[document_engine_pdf_columns]…[/document_engine_pdf_columns]`, `[document_engine_pdf_column_break]`

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
* Requirements - PHP 7.4+ and WordPress 6.6+.

= 1.3.1 - 2026-09-29 =
* Security - PDF export is limited to publicly viewable post types and respects post passwords.
* Fixed - Concurrent PDF downloads no longer delete each other's temporary files.
* Fixed - PDF viewer block encodes the document URL.

= 1.3 - 2025-08-26 =
* Fixed - Escaping issue fixed

= 1.2 - 2025-04-13 =
* Fixed - Setting page design

== Upgrade Notice ==

= 2.0.0 =
Adds a document library, a private built-in PDF viewer and a security fix. Existing shortcodes, settings and blocks keep working. Requires PHP 7.4 and WordPress 6.6.
