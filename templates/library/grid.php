<?php
/**
 * Library grid layout. Override in your theme at document_engine/library/grid.php.
 *
 * @var array $settings
 * @var \MatrixAddons\DocumentEngine\Documents\Document[] $documents
 */

use MatrixAddons\DocumentEngine\Documents\FileServer;
use MatrixAddons\DocumentEngine\Library\Library;

defined('ABSPATH') || exit;
?>
<ul class="dengine-grid" style="--dengine-grid-columns: <?php echo esc_attr($settings['grid_columns']); ?>">
    <?php foreach ($documents as $document) :
        $url = Library::title_url($document, $settings);
        $group = document_engine_file_type_group($document->get_extension());
        $terms = in_array('category', $settings['columns'], true) ? get_the_terms($document->get_post(), 'dengine_category') : false;
        $meta = array_filter(array(
            in_array('size', $settings['columns'], true) ? $document->get_size_label() : '',
            in_array('date', $settings['columns'], true) ? Library::short_date($document) : '',
            in_array('modified', $settings['columns'], true) ? Library::short_date($document, true) : '',
            in_array('downloads', $settings['columns'], true) ? sprintf(
                /* translators: %s: download count */
                _n('%s download', '%s downloads', $document->get_download_count(), 'document-engine'),
                number_format_i18n($document->get_download_count())
            ) : '',
        ));
        $locked = !FileServer::can_access($document, null, 'list'); ?>
        <li class="dengine-card dengine-card--<?php echo esc_attr($group); ?>">
            <?php if ($settings['show_thumbnails']) : ?>
                <div class="dengine-card__media<?php echo has_post_thumbnail($document->get_post()) ? ' has-image' : ''; ?>">
                    <?php echo $document->get_thumbnail_html('medium'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php if ($document->get_type_label() !== '') : ?><span class="dengine-card__badge"><?php echo esc_html($document->get_type_label()); ?></span><?php endif; ?>
                    <?php if ($locked) : ?><span class="dengine-pill dengine-pill--locked dengine-card__lock"><?php echo document_engine_ui_icon('lock'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html(\MatrixAddons\DocumentEngine\Library\Library::lock_label($document)); ?></span><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="dengine-card__body">
                <h3 class="dengine-card__title"><?php if ($url) : ?><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($document->get_title()); ?></a><?php else : echo esc_html($document->get_title()); endif; ?></h3>
                <?php if ($settings['show_excerpt'] && has_excerpt($document->get_post())) : ?>
                    <p class="dengine-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt($document->get_post()), 24)); ?></p>
                <?php endif; ?>
                <?php if ($meta) : ?>
                    <p class="dengine-card__meta"><?php echo esc_html(implode(' · ', $meta)); ?></p>
                <?php endif; ?>
                <?php if (is_array($terms) && $terms) : ?>
                    <p class="dengine-card__terms"><?php echo Library::cell('category', $document, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                <?php endif; ?>
            </div>
            <div class="dengine-card__footer"><?php echo Library::actions($document, true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        </li>
    <?php endforeach; ?>
</ul>
