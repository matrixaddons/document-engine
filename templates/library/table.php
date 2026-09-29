<?php
/**
 * Library table layout. Override in your theme at document_engine/library/table.php.
 *
 * @var array $settings
 * @var \MatrixAddons\DocumentEngine\Documents\Document[] $documents
 */

use MatrixAddons\DocumentEngine\Library\Library;
use MatrixAddons\DocumentEngine\Library\Query;

defined('ABSPATH') || exit;

$labels = Query::columns();
?>
<div class="dengine-table-wrap">
    <table class="dengine-table">
        <thead>
        <tr>
            <?php foreach ($settings['columns'] as $column) : ?>
                <?php $hidden_label = in_array($column, array('actions', 'select'), true); ?>
                <th scope="col" class="dengine-col--<?php echo esc_attr($column); ?>"><?php echo esc_html($hidden_label ? '' : $labels[$column]); ?><?php if ($hidden_label) : ?><span class="screen-reader-text"><?php echo esc_html($labels[$column]); ?></span><?php endif; ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($documents as $document) : ?>
            <tr>
                <?php foreach ($settings['columns'] as $column) : ?>
                    <td class="dengine-col--<?php echo esc_attr($column); ?>" data-label="<?php echo esc_attr(in_array($column, array('actions', 'select'), true) ? '' : $labels[$column]); ?>"><?php echo Library::cell($column, $document, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
