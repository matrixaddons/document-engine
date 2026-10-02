<?php
/**
 * Top of a single document page: summary card with actions, then the PDF viewer.
 * Override in your theme at document_engine/document/single.php.
 *
 * @var \MatrixAddons\DocumentEngine\Documents\Document $document
 */

use MatrixAddons\DocumentEngine\Documents\FileServer;
use MatrixAddons\DocumentEngine\Viewer\Viewer;

defined('ABSPATH') || exit;

// Password-protected documents: WordPress shows its own password form in the content.
if (post_password_required($document->get_post())) {
    return;
}

if (!FileServer::can_access($document, null, 'view')) {
    do_action('document_engine_single_restricted', $document);
    document_engine_get_template('document/access.php', array(
        'document' => $document,
        'reason' => is_user_logged_in() ? 'denied' : 'login',
        'return' => get_permalink($document->get_post()),
    ));
    return;
}

/**
 * Add-ons can replace the page body (e.g. an email gate form).
 */
if (apply_filters('document_engine_single_takeover', false, $document)) {
    do_action('document_engine_single_takeover_render', $document);
    return;
}

// Sites can hide the summary card; access and add-on screens above still apply.
if (get_option('document_engine_single_card', 'yes') !== 'yes') {
    return;
}

$terms = get_the_terms($document->get_post(), 'dengine_category');
$show_viewer = $document->is_pdf() && get_option('document_engine_single_viewer', 'yes') === 'yes';
$meta = array_filter(array(
    $document->get_type_label(),
    $document->get_size_label(),
    /* translators: %s: date */
    sprintf(__('Updated %s', 'document-engine'), get_the_modified_date('', $document->get_post())),
));
?>
<div class="dengine-single">
    <div class="dengine-doc-head">
        <span class="dengine-doc-head__icon"><?php echo document_engine_file_icon($document->get_extension()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        <div class="dengine-doc-head__body">
            <p class="dengine-doc-head__meta">
                <?php foreach ($meta as $item) : ?><span><?php echo esc_html($item); ?></span><?php endforeach; ?>
            </p>
            <?php if (is_array($terms) && $terms) : ?>
                <p class="dengine-doc-head__terms">
                    <?php foreach ($terms as $term) : ?><span class="dengine-chip"><?php echo esc_html($term->name); ?></span><?php endforeach; ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="dengine-doc-head__actions">
            <?php echo \MatrixAddons\DocumentEngine\Library\Library::actions($document, true, false); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
    <?php
    /**
     * Below the summary card (e.g. custom field details).
     */
    do_action('document_engine_single_after_head', $document);
    ?>
    <?php if ($show_viewer) :
        echo Viewer::render(array('documentId' => $document->get_id())); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    elseif (get_option('document_engine_single_viewer', 'yes') === 'yes' && ($office = document_engine_office_embed_url($document)) !== '') : ?>
        <iframe class="dengine-office" src="<?php echo esc_url($office); ?>" title="<?php echo esc_attr($document->get_title()); ?>" loading="lazy" referrerpolicy="no-referrer"></iframe>
    <?php endif; ?>
</div>
