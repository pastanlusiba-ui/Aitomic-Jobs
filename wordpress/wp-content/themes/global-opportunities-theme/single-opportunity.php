<?php get_header(); ?>

<?php while (have_posts()) : the_post(); ?>
    <article class="single-opportunity">
        <header class="single-hero">
            <div class="container narrow">
                <p class="eyebrow"><?php echo gotheme_term_list('opportunity_type'); ?></p>
                <h1><?php the_title(); ?></h1>
                <p class="single-meta">
                    <?php echo esc_html(gotheme_meta('organization')); ?>
                    <?php if (gotheme_term_list('country')) : ?>
                        <span><?php echo gotheme_term_list('country'); ?></span>
                    <?php endif; ?>
                </p>
                <p class="single-deadline <?php echo gotheme_is_expired_opportunity() ? 'is-past' : ''; ?>">
                    <?php echo esc_html(gotheme_deadline_label()); ?>
                    <?php if (gotheme_is_expired_opportunity()) : ?>
                        <span>Closed</span>
                    <?php endif; ?>
                </p>
            </div>
        </header>

        <div class="container single-layout">
            <div class="content-body">
                <?php the_content(); ?>
            </div>
            <aside class="details-panel">
                <h2>Opportunity details</h2>
                <?php echo gotheme_opportunity_meta_list(); ?>
                <?php if (gotheme_is_expired_opportunity()) : ?>
                    <p class="closed-note">This opportunity is archived because its application deadline has passed.</p>
                <?php endif; ?>
                <?php $application_link = gotheme_meta('application_link'); ?>
                <?php if ($application_link) : ?>
                    <a class="button primary" href="<?php echo esc_url($application_link); ?>" target="_blank" rel="noopener">Apply now</a>
                <?php endif; ?>
            </aside>
        </div>
    </article>
<?php endwhile; ?>

<?php get_footer(); ?>
