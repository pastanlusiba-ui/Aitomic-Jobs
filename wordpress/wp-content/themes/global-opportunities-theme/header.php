<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="container header-inner">
        <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> home">
            <img class="site-logo" src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/aitomic-jobs-logo-horizontal.png'); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>
        <nav class="site-nav" aria-label="<?php esc_attr_e('Primary menu', 'global-opportunities-theme'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container' => false,
                'fallback_cb' => false,
                'depth' => 1,
            ]);
            ?>
        </nav>
        <a class="header-cta" href="<?php echo esc_url(get_post_type_archive_link('opportunity') ?: home_url('/')); ?>">Postings</a>
    </div>
    <div class="global-search-shell">
        <div class="container">
            <?php echo do_shortcode('[opportunity_search]'); ?>
        </div>
    </div>
</header>
<main>
