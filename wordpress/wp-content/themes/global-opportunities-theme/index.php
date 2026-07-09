<?php get_header(); ?>

<section class="page-heading">
    <div class="container">
        <h1><?php single_post_title(); ?></h1>
    </div>
</section>

<section class="container section">
    <?php if (have_posts()) : ?>
        <div class="post-list">
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('post-summary'); ?>>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <?php the_excerpt(); ?>
                </article>
            <?php endwhile; ?>
        </div>
        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p>No posts found.</p>
    <?php endif; ?>
</section>

<?php get_footer(); ?>

