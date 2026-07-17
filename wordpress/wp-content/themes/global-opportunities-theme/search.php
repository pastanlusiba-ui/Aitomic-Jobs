<?php get_header(); ?>

<section class="page-heading">
    <div class="container">
        <p class="eyebrow">Search</p>
        <h1>Opportunity results</h1>
        <?php echo do_shortcode('[opportunity_search]'); ?>
    </div>
</section>

<section class="container section">
    <?php if (have_posts()) : ?>
        <div class="opportunity-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php if (get_post_type() === 'opportunity') : ?>
                    <?php get_template_part('template-parts/opportunity-card'); ?>
                <?php else : ?>
                    <article <?php post_class('post-summary'); ?>>
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <?php the_excerpt(); ?>
                    </article>
                <?php endif; ?>
            <?php endwhile; ?>
        </div>
        <?php gotheme_render_pagination(); ?>
    <?php else : ?>
        <p>No matching opportunities found.</p>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
