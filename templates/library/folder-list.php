<?php
/**
 * Documents inside one folder. Override in your theme at document_engine/library/folder-list.php.
 *
 * @var array $settings
 * @var \MatrixAddons\DocumentEngine\Documents\Document[] $documents
 */

use MatrixAddons\DocumentEngine\Library\Library;

defined('ABSPATH') || exit;
?>
<ul class="dengine-folder__list">
    <?php foreach ($documents as $document) : ?>
        <li class="dengine-folder__item">
            <?php echo Library::cell('title', $document, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span class="dengine-folder__meta"><?php echo esc_html(trim($document->get_type_label() . ' · ' . $document->get_size_label(), ' ·')); ?></span>
            <?php echo Library::actions($document); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </li>
    <?php endforeach; ?>
</ul>
