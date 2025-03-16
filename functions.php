<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define("MARKSYN_VERSION", "2.1.0");

function marksyn_load_textdomain()
{
    load_theme_textdomain('marksyn', get_template_directory() . '/languages');
}

add_action('after_setup_theme', 'marksyn_load_textdomain');

function marksyn_init_assets()
{
    wp_enqueue_style('marksyn-style', get_stylesheet_uri(), array(), MARKSYN_VERSION, 'all');
    wp_enqueue_script('marksyn-script', get_template_directory_uri() . '/assets/js/script.js', array('jquery'), MARKSYN_VERSION, true);
}

add_action('wp_enqueue_scripts', 'marksyn_init_assets');

function marksyn_init_admin_assets($hook)
{
    global $post;

    if ($hook === 'post.php' || $hook === 'post-new.php') {
        if (isset($post) && $post->post_type === 'projects') {
            wp_enqueue_media();
            wp_enqueue_script('marksyn-projects', get_template_directory_uri() . '/assets/js/admin-projects.js', array('jquery'), MARKSYN_VERSION, true);
        }
    }
}

add_action('admin_enqueue_scripts', 'marksyn_init_admin_assets');

function marksyn_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('menus');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('editor-styles');
}

add_action('after_setup_theme', 'marksyn_theme_setup');

function marksyn_register_menus()
{
    register_nav_menus(
        array(
            'primary' => __('Primary Menu', 'marksyn'),
            'footer' => __('Footer Menu', 'marksyn')
        )
    );
}

add_action('init', 'marksyn_register_menus');

function marksyn_elementor_support()
{
    add_theme_support('elementor');
}

add_action('after_setup_theme', 'marksyn_elementor_support');

function marksyn_projects_post_type()
{
    $labels = array(
        'name' => __('Projects', 'marksyn'),
        'singular_name' => __('Project', 'marksyn'),
        'menu_name' => __('Projects', 'marksyn'),
        'name_admin_bar' => __('Project', 'marksyn'),
        'add_new' => __('Add New', 'marksyn'),
        'add_new_item' => __('Add New Project', 'marksyn'),
        'new_item' => __('New Project', 'marksyn'),
        'edit_item' => __('Edit Project', 'marksyn'),
        'view_item' => __('View Project', 'marksyn'),
        'all_items' => __('All Projects', 'marksyn'),
        'search_items' => __('Search Projects', 'marksyn'),
        'not_found' => __('No projects found.', 'marksyn'),
        'not_found_in_trash' => __('No projects found in Trash.', 'marksyn')
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'projects'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-portfolio',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions', 'author'),
        'taxonomies' => array('category', 'post_tag'),
        'show_in_rest' => true,
    );

    register_post_type('projects', $args);
}

add_action('init', 'marksyn_projects_post_type');

function marksyn_projects_logo_meta_box()
{
    add_meta_box(
        'ms_projects_logo_meta_box',
        __('Project Logo', 'marksyn'),
        'marksyn_projects_logo_callback',
        'projects',
        'side',
        'high'
    );
}

add_action('add_meta_boxes', 'marksyn_projects_logo_meta_box');

function marksyn_projects_logo_callback($post)
{
    wp_nonce_field('ms_projects_logo_save_meta_box', 'ms_projects_logo_meta_box_nonce');

    $logo_id = get_post_meta($post->ID, '_ms_projects_logo', true);
    $logo_url = !empty($logo_id) ? wp_get_attachment_url($logo_id) : '';

    echo '<p>';
    echo '<input type="hidden" id="projects_logo" name="ms_projects_logo" value="' . esc_attr($logo_id) . '">';
    echo '<img id="projects_logo_preview" src="' . esc_url($logo_url) . '" style="max-width:100%; height:auto; display:' . ($logo_url ? 'block' : 'none') . ';">';
    echo '<br><div class="buttons_container" style="display: flex; gap: 5px;"><button type="button" class="button projects_logo_upload">' . __('Upload Logo', 'marksyn') . '</button><button type="button" class="button projects_logo_remove" style="display:' . ($logo_url ? 'inline-block' : 'none') . ';">' . __('Remove', 'marksyn') . '</button></div>';
    echo '</p>';
}

function marksyn_projects_logo_save_meta_box($post_id)
{
    if (!isset($_POST['ms_projects_logo_meta_box_nonce']) || !wp_verify_nonce($_POST['ms_projects_logo_meta_box_nonce'], 'ms_projects_logo_save_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!empty($_POST['ms_projects_logo']) && is_numeric($_POST['ms_projects_logo'])) {
        update_post_meta($post_id, '_ms_projects_logo', absint($_POST['ms_projects_logo']));
    } else {
        delete_post_meta($post_id, '_ms_projects_logo');
    }
}

add_action('save_post', 'marksyn_projects_logo_save_meta_box');

function marksyn_get_project_logo_shortcode()
{
    global $post;
    $post_id = isset($post->ID) ? $post->ID : get_the_ID();
    if (!$post_id) return '';

    $logo_id = get_post_meta($post_id, '_ms_projects_logo', true);

    if (!empty($logo_id)) {
        $logo_url = wp_get_attachment_url($logo_id);
        $mime_type = get_post_mime_type($logo_id);

        if (!empty($mime_type) && $mime_type === 'image/svg+xml') {
            $svg_file = get_attached_file($logo_id);
            if (!empty($svg_file) && file_exists($svg_file)) {
                return '<div class="ms_project_logo">' . file_get_contents($svg_file) . '</div>';
            }
        }

        return '<img class="ms_project_logo" src="' . esc_url($logo_url) . '" alt="Project Logo">';
    }

    return '';
}

add_shortcode('project_logo', 'marksyn_get_project_logo_shortcode');

function marksyn_projects_subtitle_meta_box()
{
    add_meta_box(
        'ms_projects_subtitle_meta_box',
        __('Subtitle', 'marksyn'),
        'marksyn_projects_subtitle_callback',
        'projects',
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'marksyn_projects_subtitle_meta_box');


function marksyn_projects_subtitle_callback($post)
{
    wp_nonce_field('ms_projects_subtitle_save_meta_box', 'ms_projects_subtitle_meta_box_nonce');

    $subtitle = get_post_meta($post->ID, '_ms_projects_subtitle', true);
    echo '<input type="text" name="ms_projects_subtitle" value="' . esc_attr($subtitle) . '" style="width:100%;" />';
}

function marksyn_projects_subtitle_save_meta_box($post_id)
{
    if (!isset($_POST['ms_projects_subtitle_meta_box_nonce']) || !wp_verify_nonce($_POST['ms_projects_subtitle_meta_box_nonce'], 'ms_projects_subtitle_save_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!empty($_POST['ms_projects_subtitle'])) {
        update_post_meta($post_id, '_ms_projects_subtitle', sanitize_text_field($_POST['ms_projects_subtitle']));
    } else {
        delete_post_meta($post_id, '_ms_projects_subtitle');
    }
}

add_action('save_post', 'marksyn_projects_subtitle_save_meta_box');

function marksyn_get_project_subtitle_shortcode()
{
    global $post;
    $post_id = isset($post->ID) ? $post->ID : get_the_ID();
    if (!$post_id) return '';

    $subtitle = get_post_meta($post_id, '_ms_projects_subtitle', true);

    if (!empty($subtitle)) {
        return '<span class="ms_project_subtitle">' . sanitize_text_field($subtitle) . '</span>';
    }

    return '';
}

add_shortcode('project_subtitle', 'marksyn_get_project_subtitle_shortcode');


function marksyn_team_post_type()
{
    $labels = array(
        'name' => __('Team', 'marksyn'),
        'singular_name' => __('Team Member', 'marksyn'),
        'menu_name' => __('Team', 'marksyn'),
        'name_admin_bar' => __('Team Member', 'marksyn'),
        'add_new' => __('Add New', 'marksyn'),
        'add_new_item' => __('Add New Team Member', 'marksyn'),
        'new_item' => __('New Team Member', 'marksyn'),
        'edit_item' => __('Edit Team Member', 'marksyn'),
        'view_item' => __('View Team Member', 'marksyn'),
        'all_items' => __('All Team Members', 'marksyn'),
        'search_items' => __('Search Team Members', 'marksyn'),
        'not_found' => __('No team member found.', 'marksyn'),
        'not_found_in_trash' => __('No team member found in Trash.', 'marksyn')
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'team'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true,
    );

    register_post_type('team', $args);
}

add_action('init', 'marksyn_team_post_type');

function marksyn_team_social_meta_box()
{
    add_meta_box(
        'ms_team_social_meta_box',
        __('Team Member Details', 'marksyn'),
        'marksyn_team_social_callback',
        'team',
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'marksyn_team_social_meta_box');

function marksyn_team_social_callback($post)
{
    wp_nonce_field('ms_team_social_save_meta_box', 'ms_team_social_meta_box_nonce');

    $fields = [
        'ms_team_position' => __('Position', 'marksyn'),
        'ms_team_facebook' => __('Facebook URL', 'marksyn'),
        'ms_team_twitter'  => __('Twitter URL', 'marksyn'),
        'ms_team_linkedin' => __('LinkedIn URL', 'marksyn'),
        'ms_team_instagram'=> __('Instagram URL', 'marksyn'),
        'ms_team_email'    => __('Email Address', 'marksyn'),
        'ms_team_phone'    => __('Phone Number', 'marksyn'),
    ];

    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, "_{$key}", true);
        echo "<p><label for='{$key}'><strong>{$label}:</strong></label></p>";
        echo "<input type='text' id='{$key}' name='{$key}' value='" . esc_attr($value) . "' style='width:100%;' />";
    }
}

function marksyn_team_social_save_meta_box($post_id)
{
    if (!isset($_POST['ms_team_social_meta_box_nonce']) || !wp_verify_nonce($_POST['ms_team_social_meta_box_nonce'], 'ms_team_social_save_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        'ms_team_position',
        'ms_team_facebook',
        'ms_team_twitter',
        'ms_team_linkedin',
        'ms_team_instagram',
        'ms_team_email',
        'ms_team_phone'
    ];

    foreach ($fields as $field) {
        if (!empty($_POST[$field])) {
            update_post_meta($post_id, "_{$field}", sanitize_text_field($_POST[$field]));
        } else {
            delete_post_meta($post_id, "_{$field}");
        }
    }
}

add_action('save_post', 'marksyn_team_social_save_meta_box');

function marksyn_get_team_member_details_shortcode($atts)
{
    global $post;
    $post_id = isset($post->ID) ? $post->ID : get_the_ID();
    if (!$post_id) return '';

    $atts = shortcode_atts([
        'field' => 'ms_team_position'
    ], $atts);

    $meta_value = get_post_meta($post_id, "_{$atts['field']}", true);

    return !empty($meta_value) ? esc_html($meta_value) : '';
}

add_shortcode('team_member_info', 'marksyn_get_team_member_details_shortcode');
