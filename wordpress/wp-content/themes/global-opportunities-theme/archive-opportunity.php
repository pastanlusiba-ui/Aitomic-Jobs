<?php get_header(); ?>
<?php $showing_expired = (bool) get_query_var('expired_opportunities'); ?>

<section class="page-heading">
    <div class="container">
        <p class="eyebrow"><?php echo $showing_expired ? 'Archive' : 'Browse'; ?></p>
        <h1><?php echo $showing_expired ? 'Expired Opportunities' : 'Opportunities'; ?></h1>
        <p class="archive-intro">
            <?php echo $showing_expired ? 'These listings are kept for reference because their application deadlines have passed.' : 'Browse currently open opportunities. Listings without a confirmed deadline remain visible until reviewed.'; ?>
        </p>
        <div class="archive-switcher">
            <a class="<?php echo $showing_expired ? '' : 'is-active'; ?>" href="<?php echo esc_url(get_post_type_archive_link('opportunity') ?: home_url('/opportunities/')); ?>">Open opportunities</a>
            <a class="<?php echo $showing_expired ? 'is-active' : ''; ?>" href="<?php echo esc_url(home_url('/opportunities/expired/')); ?>">Expired archive</a>
        </div>
        <?php if (!$showing_expired) : ?>
            <?php echo do_shortcode('[opportunity_search]'); ?>
        <?php endif; ?>
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
        <p><?php echo $showing_expired ? 'No expired opportunities found.' : 'No matching opportunities found.'; ?></p>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
