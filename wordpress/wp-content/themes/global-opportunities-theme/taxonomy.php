<?php get_header(); ?>

<section class="page-heading">
    <div class="container">
        <p class="eyebrow">Browse</p>
        <h1><?php single_term_title(); ?></h1>
        <?php echo do_shortcode('[opportunity_search]'); ?>
    </div>
</section>

<section class="container section">
    <?php if (have_posts()) : ?>
        <div class="opportunity-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/opportunity-card'); ?>
            <?php endwhile; ?>
        </div>
        <?php gotheme_render_pagination(); ?>
    <?php else : ?>
        <p>No matching opportunities found.</p>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
