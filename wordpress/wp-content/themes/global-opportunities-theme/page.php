<?php get_header(); ?>

<?php while (have_posts()) : the_post(); ?>
    <article <?php post_class('site-page'); ?>>
        <header class="page-heading">
            <div class="container narrow">
                <p class="eyebrow"><?php bloginfo('name'); ?></p>
                <h1><?php the_title(); ?></h1>
            </div>
        </header>

        <section class="container narrow section">
            <div class="content-body legal-page">
                <?php the_content(); ?>
            </div>
        </section>
    </article>
<?php endwhile; ?>

<?php get_footer(); ?>
