<?php

namespace MatrixAddons\DocumentEngine\Hooks;

use MatrixAddons\DocumentEngine\Generate_PDF;

class Template
{
    public function __construct()
    {
        add_filter('the_content', array($this, 'button'));
        add_filter('query_vars', array($this, 'set_query_vars'));
        // Late on template_redirect so membership/redirect plugins run first.
        add_action('template_redirect', array($this, 'generate_pdf'), 99);


    }

    public function button($content)
    {
        if (!is_singular()) {
            return $content;
        }
        if (is_archive() || is_front_page() || is_home()) {
            return $content;
        }
        // Attachment pages had PDFs in 1.x; they can't be exported any more, so no button (1.x settings may still tick them).
        if (get_post_type() === 'attachment') {
            return $content;
        }

        if (document_engine_pdf_is_valid_post_type()) {

            remove_shortcode('document_engine_pdf_button');

            return str_replace("[document_engine_pdf_button]", "", $content);

        }

        global $post;

        $option_post_types = array_keys(document_engine_pdf_post_type());

        if (!in_array(get_post_type($post), $option_post_types)) {

            return $content;

        }

        $c = $content;

        $button_position = document_engine_pdf_button_position();


        $button_args = array(
            'button_text' => document_engine_pdf_button_text(),
            'button_alignment' => document_engine_pdf_button_alignment(),
            'button_icon' => 'fa fa-file-pdf'
        );

        if ($button_position == '') {
            return $c;
        }

        wp_enqueue_style('document-engine-frontend');

        if ($button_position == 'before') {

            ob_start();

            document_engine_get_template('pdf-button.php', $button_args);


            return ob_get_clean() . $c;


        } else if ($button_position == 'after') {

            ob_start();

            document_engine_get_template('pdf-button.php', $button_args);

            return $c . ob_get_clean();

        }

        return $content;

    }

    public function set_query_vars($query_vars)
    {
        $query_vars[] = DOCUMENT_ENGINE_QUERY_VAR_SLUG;

        return $query_vars;

    }

    public function generate_pdf($query)
    {

        if (!document_engine_pdf_is_valid_post_type()) {
            return;
        }

        // Only on the post's own address, so plugins that protect a post by checking the requested page
        // (membership, content restriction, maintenance) have run for this post first.
        $id = absint(get_query_var(DOCUMENT_ENGINE_QUERY_VAR_SLUG));
        if (!is_singular() || (int)get_queried_object_id() !== $id) {
            $permalink = get_permalink($id);
            // Once only: when the post's own address isn't a single-post view either (the Posts page, a shop
            // archive, a page another plugin takes over), show that page instead of redirecting forever.
            if ($permalink && empty($_GET['dengine_pdf_r'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                wp_safe_redirect(add_query_arg(array(DOCUMENT_ENGINE_QUERY_VAR_SLUG => $id, 'dengine_pdf_r' => 1), $permalink), 302);
                exit;
            }
            return;
        }

        Generate_PDF::generate();
    }
}