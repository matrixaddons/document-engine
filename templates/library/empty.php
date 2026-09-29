<?php
/**
 * Shown when a library has no matching documents.
 *
 * @var array $settings
 * @var array $state
 */

use MatrixAddons\DocumentEngine\Library\Library;
use MatrixAddons\DocumentEngine\Library\Query;

defined('ABSPATH') || exit;
?>
<div class="dengine-empty">
    <svg class="dengine-empty__icon" viewBox="0 0 24 24" width="36" height="36" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z M14 3v5h5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="11.5" cy="14.5" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M13.5 16.5L16 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
    <?php if (Query::is_filtered($state)) : ?>
        <p class="dengine-empty__title"><?php esc_html_e('No documents match your search', 'document-engine'); ?></p>
        <p><?php esc_html_e('Try different words or remove a filter.', 'document-engine'); ?></p>
        <p><a href="<?php echo esc_url(Library::url_for($settings, array())); ?>" class="dengine-button dengine-button--ghost" data-dengine-nav><?php esc_html_e('Clear search and filters', 'document-engine'); ?></a></p>
    <?php else : ?>
        <p class="dengine-empty__title"><?php esc_html_e('No documents yet', 'document-engine'); ?></p>
        <p><?php esc_html_e('Documents will appear here once they are published.', 'document-engine'); ?></p>
    <?php endif; ?>
</div>
