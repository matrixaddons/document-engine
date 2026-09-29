<?php
/**
 * Standalone page for file-server messages when documents have no page of their own
 * (access denied, missing file, add-on forms). Override at document_engine/notice-page.php.
 *
 * @var string $title
 * @var string $body HTML (already escaped)
 */

defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($title . ' – ' . get_bloginfo('name')); ?></title>
    <?php // Standalone page without wp_head(), so the stylesheet is linked directly. ?>
    <link rel="stylesheet" href="<?php echo esc_url(DOCUMENT_ENGINE_ASSETS_URI . 'build/documents.css?ver=' . DOCUMENT_ENGINE_VERSION); ?>"><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
    <?php if (has_site_icon()) : ?><link rel="icon" href="<?php echo esc_url(get_site_icon_url(64)); ?>"><?php endif; ?>
    <style>body{margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;padding:32px 16px;background:#f6f7f9;color:#1e1e1e;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;box-sizing:border-box}.dengine-notice-page__site{display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;font-weight:600}.dengine-notice-page__site img{width:32px;height:32px;border-radius:6px}.dengine-notice-page__card{width:100%;max-width:480px;background:#fff;border:1px solid #e3e5e8;border-radius:14px;box-shadow:0 10px 30px rgb(0 0 0 / .05);padding:8px}</style>
</head>
<body>
    <a class="dengine-notice-page__site" href="<?php echo esc_url(home_url('/')); ?>">
        <?php if (has_site_icon()) : ?><img src="<?php echo esc_url(get_site_icon_url(64)); ?>" alt=""><?php endif; ?>
        <span><?php echo esc_html(get_bloginfo('name')); ?></span>
    </a>
    <main class="dengine-notice-page__card"><?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></main>
</body>
</html>
