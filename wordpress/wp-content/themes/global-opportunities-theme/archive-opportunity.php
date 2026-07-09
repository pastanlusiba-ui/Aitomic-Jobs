<?php get_header(); ?>

<section class="page-heading">
    <div class="container">
        <p class="eyebrow">Browse</p>
        <h1>Opportunities</h1>
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
        <div class="pagination">
            <?php the_posts_pagination(); ?>
        </div>
    <?php else : ?>
        <p>No matching opportunities found.</p>
    <?php endif; ?>
</section>

<?php get_footer(); ?>

