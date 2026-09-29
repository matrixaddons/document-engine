<?php
/**
 * PDF viewer shell. viewer.js renders the pages. Override at document_engine/viewer/viewer.php.
 *
 * @var array $config
 * @var array $classes
 * @var string $height
 * @var string $width
 * @var string $open_url
 */

defined('ABSPATH') || exit;
?>
<figure class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="--dengine-viewer-height: <?php echo esc_attr($height); ?>;<?php echo $width !== '100%' ? ' max-width: ' . esc_attr($width) . ';' : ''; ?>" data-dengine-viewer="<?php echo esc_attr(wp_json_encode($config)); ?>">
    <?php if ($config['toolbar']) : ?>
        <div class="dengine-viewer__toolbar" role="toolbar" aria-label="<?php esc_attr_e('PDF controls', 'document-engine'); ?>" title="<?php esc_attr_e('PDF controls', 'document-engine'); ?>">
            <div class="dengine-viewer__group">
                <?php if (!empty($config['sidebar'])) : ?>
                    <button type="button" class="dengine-viewer__btn" data-action="sidebar" aria-label="<?php esc_attr_e('Pages and outline', 'document-engine'); ?>" title="<?php esc_attr_e('Pages and outline', 'document-engine'); ?>" aria-pressed="false" aria-controls="<?php echo esc_attr($side_id = wp_unique_id('dengine-side-')); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM9 4v16"/></svg>
                    </button>
                <?php endif; ?>
                <button type="button" class="dengine-viewer__btn" data-action="prev" aria-label="<?php esc_attr_e('Previous page', 'document-engine'); ?>" title="<?php esc_attr_e('Previous page', 'document-engine'); ?>" disabled>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <span class="dengine-viewer__pageinfo">
                    <input type="number" class="dengine-viewer__page" min="1" value="1" inputmode="numeric" aria-label="<?php esc_attr_e('Page number', 'document-engine'); ?>" title="<?php esc_attr_e('Page number', 'document-engine'); ?>">
                    <span class="dengine-viewer__total">/ <span data-total>–</span></span>
                </span>
                <button type="button" class="dengine-viewer__btn" data-action="next" aria-label="<?php esc_attr_e('Next page', 'document-engine'); ?>" title="<?php esc_attr_e('Next page', 'document-engine'); ?>" disabled>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
            <div class="dengine-viewer__group">
                <button type="button" class="dengine-viewer__btn" data-action="zoom-out" aria-label="<?php esc_attr_e('Zoom out', 'document-engine'); ?>" title="<?php esc_attr_e('Zoom out', 'document-engine'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/></svg>
                </button>
                <label class="screen-reader-text" for="<?php echo esc_attr($zoom_id = wp_unique_id('dengine-zoom-')); ?>"><?php esc_html_e('Zoom', 'document-engine'); ?></label>
                <select class="dengine-viewer__zoomselect" id="<?php echo esc_attr($zoom_id); ?>" data-zoom-select>
                    <option value="page-width"><?php esc_html_e('Fit width', 'document-engine'); ?></option>
                    <option value="page-fit"><?php esc_html_e('Fit page', 'document-engine'); ?></option>
                    <option value="custom" data-custom hidden>100%</option>
                    <?php foreach (array(50, 75, 100, 125, 150, 200) as $pct) : ?><option value="<?php echo esc_attr($pct); ?>"><?php echo esc_html($pct . '%'); ?></option><?php endforeach; ?>
                </select>
                <button type="button" class="dengine-viewer__btn" data-action="zoom-in" aria-label="<?php esc_attr_e('Zoom in', 'document-engine'); ?>" title="<?php esc_attr_e('Zoom in', 'document-engine'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                </button>
            </div>
            <div class="dengine-viewer__group dengine-viewer__group--end">
                <?php if (!empty($config['search'])) : ?>
                    <button type="button" class="dengine-viewer__btn" data-action="search" aria-label="<?php esc_attr_e('Search in document', 'document-engine'); ?>" title="<?php esc_attr_e('Search in document', 'document-engine'); ?>" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg>
                    </button>
                <?php endif; ?>
                <?php if (!empty($config['print'])) : ?>
                    <button type="button" class="dengine-viewer__btn" data-action="print" aria-label="<?php esc_attr_e('Print', 'document-engine'); ?>" title="<?php esc_attr_e('Print', 'document-engine'); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4v-7h16v7h-2M8 14h8v7H8z"/></svg>
                    </button>
                <?php endif; ?>
                <?php if (empty($config['secure']) && $open_url) : ?>
                    <a class="dengine-viewer__btn" href="<?php echo esc_url($config['src']); ?>" target="_blank" rel="noopener" data-action="open" aria-label="<?php esc_attr_e('Open in a new tab', 'document-engine'); ?>" title="<?php esc_attr_e('Open in a new tab', 'document-engine'); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($config['download'])) : ?>
                    <a class="dengine-viewer__btn" href="<?php echo esc_url($config['download']); ?>" data-action="download" aria-label="<?php esc_attr_e('Download', 'document-engine'); ?>" title="<?php esc_attr_e('Download', 'document-engine'); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v11m0 0l-4-4m4 4l4-4M5 19h14"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($config['fullscreen'])) : ?>
                    <button type="button" class="dengine-viewer__btn" data-action="fullscreen" aria-label="<?php esc_attr_e('Full screen', 'document-engine'); ?>" title="<?php esc_attr_e('Full screen', 'document-engine'); ?>" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($config['search'])) : ?>
            <div class="dengine-viewer__search" role="search" hidden>
                <label class="screen-reader-text" for="<?php echo esc_attr($search_id = wp_unique_id('dengine-find-')); ?>"><?php esc_html_e('Find in document', 'document-engine'); ?></label>
                <input type="search" id="<?php echo esc_attr($search_id); ?>" class="dengine-viewer__find" placeholder="<?php esc_attr_e('Find in document…', 'document-engine'); ?>" autocomplete="off">
                <span class="dengine-viewer__matches" aria-live="polite"></span>
                <button type="button" class="dengine-viewer__btn" data-action="find-prev" aria-label="<?php esc_attr_e('Previous match', 'document-engine'); ?>" title="<?php esc_attr_e('Previous match', 'document-engine'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg></button>
                <button type="button" class="dengine-viewer__btn" data-action="find-next" aria-label="<?php esc_attr_e('Next match', 'document-engine'); ?>" title="<?php esc_attr_e('Next match', 'document-engine'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></button>
                <button type="button" class="dengine-viewer__btn" data-action="search-close" aria-label="<?php esc_attr_e('Close search', 'document-engine'); ?>" title="<?php esc_attr_e('Close search', 'document-engine'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <div class="dengine-viewer__body">
    <?php if (!empty($config['toolbar']) && !empty($config['sidebar'])) : ?>
        <aside class="dengine-viewer__side" id="<?php echo esc_attr($side_id); ?>" hidden>
            <div class="dengine-viewer__tabs" role="tablist" aria-label="<?php esc_attr_e('Navigation', 'document-engine'); ?>">
                <button type="button" role="tab" class="dengine-viewer__tab" aria-selected="true" data-side-tab="thumbs"><?php esc_html_e('Pages', 'document-engine'); ?></button>
                <button type="button" role="tab" class="dengine-viewer__tab" aria-selected="false" data-side-tab="outline" hidden><?php esc_html_e('Outline', 'document-engine'); ?></button>
            </div>
            <div class="dengine-viewer__thumbs" data-side-panel="thumbs"></div>
            <nav class="dengine-viewer__outline" data-side-panel="outline" hidden aria-label="<?php esc_attr_e('Document outline', 'document-engine'); ?>"></nav>
        </aside>
    <?php endif; ?>
    <div class="dengine-viewer__pages" tabindex="0" role="region" aria-label="<?php echo esc_attr($config['title'] !== '' ? $config['title'] : __('PDF document', 'document-engine')); ?>">
        <div class="dengine-viewer__status" role="status"><span class="dengine-viewer__skeleton" aria-hidden="true"><span></span><span></span><span></span><span></span></span><span class="dengine-viewer__loading"><span class="dengine-viewer__spinner" aria-hidden="true"></span><?php esc_html_e('Loading document…', 'document-engine'); ?></span></div>
    </div>
    </div>
    <div class="dengine-viewer__fallback" hidden>
        <p class="dengine-viewer__fallback-title"><?php esc_html_e('This PDF can\'t be shown here', 'document-engine'); ?></p>
        <p><?php esc_html_e('It may be hosted on a site that doesn\'t allow previews, or your browser blocked it.', 'document-engine'); ?></p>
        <?php if ($open_url) : ?>
            <p><a class="dengine-button" href="<?php echo esc_url($open_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Open the PDF', 'document-engine'); ?></a></p>
        <?php endif; ?>
    </div>
    <noscript>
        <?php if ($open_url) : ?>
            <p><a href="<?php echo esc_url($open_url); ?>"><?php echo esc_html($config['title'] !== '' ? $config['title'] : __('Open the PDF', 'document-engine')); ?></a></p>
        <?php endif; ?>
    </noscript>
</figure>
