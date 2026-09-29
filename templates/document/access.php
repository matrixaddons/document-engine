<?php
/**
 * "You can't open this" card. Override at document_engine/document/access.php.
 *
 * @var \MatrixAddons\DocumentEngine\Documents\Document $document
 * @var string $reason login|denied
 * @var string $return URL to come back to after logging in
 */

defined('ABSPATH') || exit;

$login = $reason === 'login';
$heading = $login ? __('Log in to open this document', 'document-engine') : __('You don\'t have access to this document', 'document-engine');
$text = $login
    ? __('This document is only available to signed-in members. Log in and you\'ll be brought straight back.', 'document-engine')
    : __('Your account doesn\'t have permission to open it. If you think you should, contact the site owner.', 'document-engine');
$heading = apply_filters('document_engine_access_heading', $heading, $reason, $document);
$text = apply_filters('document_engine_access_text', $text, $reason, $document);
$back = wp_get_referer();
?>
<div class="dengine-access" role="region" aria-label="<?php echo esc_attr($heading); ?>">
    <span class="dengine-access__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26"><rect x="5" y="11" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V7a4 4 0 0 1 8 0v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
    <p class="dengine-access__doc"><?php echo document_engine_file_icon($document->get_extension(), 'dengine-icon--inline'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html($document->get_title()); ?></p>
    <h2 class="dengine-access__title"><?php echo esc_html($heading); ?></h2>
    <p class="dengine-access__text"><?php echo esc_html($text); ?></p>
    <p class="dengine-access__actions">
        <?php if ($login) : ?>
            <a class="dengine-button" href="<?php echo esc_url(wp_login_url($return)); ?>"><?php esc_html_e('Log in', 'document-engine'); ?></a>
            <?php if (get_option('users_can_register')) : ?>
                <a class="dengine-button dengine-button--ghost" href="<?php echo esc_url(wp_registration_url()); ?>"><?php esc_html_e('Create an account', 'document-engine'); ?></a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($back && strpos($back, (string)wp_parse_url(home_url(), PHP_URL_HOST)) !== false) : ?>
            <a class="dengine-button dengine-button--ghost" href="<?php echo esc_url($back); ?>"><?php esc_html_e('Go back', 'document-engine'); ?></a>
        <?php endif; ?>
    </p>
    <?php do_action('document_engine_access_card_after', $document, $reason); ?>
</div>
