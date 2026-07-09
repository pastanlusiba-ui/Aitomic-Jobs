<?php get_header(); ?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy-block">
            <p class="eyebrow">Worldwide vacancies and career calls</p>
            <h1>Find credible work opportunities by category, country, and work mode.</h1>
            <p class="hero-copy">Browse jobs, internships, tenders, consultancies, remote roles, training, volunteer openings, and calls for applications from one clean directory.</p>
            <div class="quick-links" aria-label="Popular opportunity categories">
                <a href="<?php echo esc_url(home_url('/opportunity-type/job/')); ?>">Jobs</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/internship/')); ?>">Internships</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/remote-work/')); ?>">Remote</a>
                <a href="<?php echo esc_url(home_url('/opportunity-type/tender-consultancy/')); ?>">Consultancies</a>
            </div>
        </div>
        <div class="hero-panel">
            <h2>Search the directory</h2>
            <?php echo do_shortcode('[opportunity_search]'); ?>
        </div>
    </div>
</section>

<section id="opportunity-types" class="category-band">
    <div class="container">
        <div class="section-heading split">
            <div>
                <p class="eyebrow">Browse by type</p>
                <h2>Focused on practical career movement</h2>
            </div>
            <a class="text-link" href="<?php echo esc_url(get_post_type_archive_link('opportunity') ?: home_url('/')); ?>">Open full directory</a>
        </div>
        <div class="category-grid">
            <a href="<?php echo esc_url(home_url('/opportunity-type/job/')); ?>"><span>01</span>Jobs</a>
            <a href="<?php echo esc_url(home_url('/opportunity-type/internship/')); ?>"><span>02</span>Internships</a>
            <a href="<?php echo esc_url(home_url('/opportunity-type/tender-consultancy/')); ?>"><span>03</span>Tenders / Consultancies</a>
            <a href="<?php echo esc_url(home_url('/opportunity-type/volunteer/')); ?>"><span>04</span>Volunteer opportunities</a>
            <a href="<?php echo esc_url(home_url('/opportunity-type/remote-work/')); ?>"><span>05</span>Remote work opportunities</a>
            <a href="<?php echo esc_url(home_url('/opportunity-type/training-short-course/')); ?>"><span>06</span>Training / short courses</a>
            <a href="<?php echo esc_url(home_url('/opportunity-type/call-for-applications/')); ?>"><span>07</span>Calls for applications</a>
        </div>
    </div>
</section>

<section id="latest" class="container section">
    <div class="section-heading split">
        <div>
            <p class="eyebrow">Latest postings</p>
            <h2>Recently added opportunities</h2>
        </div>
        <a class="text-link" href="<?php echo esc_url(get_post_type_archive_link('opportunity') ?: home_url('/')); ?>">Browse archive</a>
    </div>
    <div class="opportunity-grid">
        <?php
        $latest = new WP_Query([
            'post_type' => 'opportunity',
            'posts_per_page' => 6,
        ]);
        ?>
        <?php if ($latest->have_posts()) : ?>
            <?php while ($latest->have_posts()) : $latest->the_post(); ?>
                <?php get_template_part('template-parts/opportunity-card'); ?>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <?php $sample_opportunities = gotheme_sample_opportunities(); ?>
            <?php if (!empty($sample_opportunities)) : ?>
                <p class="sample-note">Previewing current sample opportunities. Publish WordPress opportunities to replace these rows.</p>
                <?php foreach ($sample_opportunities as $sample_opportunity) : ?>
                    <?php gotheme_render_sample_opportunity_card($sample_opportunity); ?>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No opportunities have been posted yet.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
