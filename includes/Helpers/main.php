<?php

if (!defined('ABSPATH')) exit;

function document_engine_generate_pdf($query)
{
    

}

add_action('wp', 'document_engine_generate_pdf');


function document_engine_get_post_types()
{

    $args = array(
        'public' => true,
        '_builtin' => false
    );

    $post_types = get_post_types($args);

    $post_types_updated = array(
        array('id' => 'post', 'title' => __('post', 'document-engine')),
        array('id' => 'page', 'title' => __('page', 'document-engine')),
        array('id' => 'attachment', 'title' => __('attachment', 'document-engine')),
    );

    foreach ($post_types as $post_type) {

        $post_types_updated[] = array('id' => $post_type, 'title' => $post_type);


    }

    return $post_types_updated;

}

function document_engine_get_pdf_permissions()
{
    return array(
        array('id' => 'copy', 'title' => 'Copy'),
        array('id' => 'print', 'title' => 'Print'),
        array('id' => 'print-highres', 'title' => 'Print Highres'),
        array('id' => 'modify', 'title' => 'Modify'),
        array('id' => 'annot-forms', 'title' => 'Annot Forms'),
        array('id' => 'fill-forms', 'title' => 'Fill Forms'),
        array('id' => 'extract', 'title' => 'Extract'),
        array('id' => 'assemble', 'title' => 'Assemble')
    );

}
