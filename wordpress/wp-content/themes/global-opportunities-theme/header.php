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
            <ul>
                <li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                <li><a href="<?php echo esc_url(get_post_type_archive_link('opportunity') ?: home_url('/opportunities/')); ?>">Opportunities</a></li>
                <li><a href="<?php echo esc_url(home_url('/opportunities/expired/')); ?>">Expired</a></li>
            </ul>
        </nav>
        <a class="header-cta" href="<?php echo esc_url(get_post_type_archive_link('opportunity') ?: home_url('/')); ?>">Postings</a>
    </div>
    <div class="opportunity-type-ribbon" aria-label="Opportunity type navigation">
        <div class="container ribbon-inner">
            <span class="ribbon-label">Browse:</span>
            <nav class="ribbon-links" aria-label="Opportunity types">
                <a href="<?php echo esc_url(home_url('/opportunity-type/job/')); ?>">Jobs</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/internship/')); ?>">Internships</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/tender-consultancy/')); ?>">Tenders / Consultancies</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/volunteer/')); ?>">Volunteer</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/remote-work/')); ?>">Remote Work</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/training-short-course/')); ?>">Training</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/call-for-applications/')); ?>">Calls</a>
            </nav>
        </div>
    </div>
</header>
<main>
