<?php
/**
 * Download card or button for a single document. Override at document_engine/document/card.php.
 *
 * @var \MatrixAddons\DocumentEngine\Documents\Document $document
 * @var string $style card|button
 * @var string $label
 * @var bool $show_meta
 */

defined('ABSPATH') || exit;

$meta = trim($document->get_type_label() . ' · ' . $document->get_size_label(), ' ·');
$target = $document->get_behavior() === 'inline' ? ' target="_blank" rel="noopener"' : '';

if ($style === 'button') : ?>
    <p class="dengine-download dengine-download--button">
        <a class="dengine-button" href="<?php echo esc_url($document->get_download_url()); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-dengine-download="<?php echo esc_attr($document->get_id()); ?>">
            <?php echo document_engine_ui_icon($document->is_external() ? 'external' : 'download'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span><?php echo esc_html($label); ?></span>
            <?php if ($show_meta && $meta) : ?><span class="dengine-button__meta">(<?php echo esc_html($meta); ?>)</span><?php endif; ?>
        </a>
    </p>
<?php else : ?>
    <div class="dengine-download dengine-download--card">
        <span class="dengine-download__icon"><?php echo document_engine_file_icon($document->get_extension()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        <span class="dengine-download__body">
            <span class="dengine-download__title"><?php echo esc_html($document->get_title()); ?></span>
            <?php if ($show_meta && $meta) : ?><span class="dengine-download__meta"><?php echo esc_html($meta); ?></span><?php endif; ?>
        </span>
        <a class="dengine-button" href="<?php echo esc_url($document->get_download_url()); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-dengine-download="<?php echo esc_attr($document->get_id()); ?>">
            <?php echo document_engine_ui_icon($document->is_external() ? 'external' : 'download'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span><?php echo esc_html($label); ?></span>
        </a>
    </div>
<?php endif;
