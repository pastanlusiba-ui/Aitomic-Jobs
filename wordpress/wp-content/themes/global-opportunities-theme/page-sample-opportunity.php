<?php get_header(); ?>

<?php
$sample_slug = isset($_GET['sample_opportunity']) ? sanitize_title(wp_unslash($_GET['sample_opportunity'])) : '';
$sample = $sample_slug ? gotheme_find_sample_opportunity($sample_slug) : [];
$responsibilities = !empty($sample) ? gotheme_sample_points($sample['summary'] ?? '', [
    'Deliver high-quality work aligned with the organization requirements.',
    'Collaborate with remote or distributed team members as needed.',
    'Communicate progress clearly and meet agreed timelines.',
]) : [];
$requirements = !empty($sample) ? gotheme_sample_points($sample['summary'] ?? '', [
    'Relevant professional experience for the role.',
    'Strong written communication and ability to work independently.',
    'Reliable internet access and comfort using remote work tools.',
]) : [];
$benefits = !empty($sample) ? array_filter([
    !empty($sample['compensation']) ? 'Compensation: ' . $sample['compensation'] : 'Compensation details are provided on the source listing where available.',
    'Remote work arrangement.',
    'Opportunity to work with an international or distributed team.',
]) : [];
?>

<?php if (!empty($sample)) : ?>
    <article class="single-opportunity">
        <header class="single-hero">
            <div class="container narrow">
                <p class="eyebrow"><?php echo esc_html($sample['opportunity_type'] ?? 'Opportunity'); ?></p>
                <h1><?php echo esc_html($sample['title'] ?? 'Opportunity'); ?></h1>
                <p class="single-meta">
                    <?php echo esc_html($sample['organization'] ?? ''); ?>
                    <?php if (!empty($sample['country'])) : ?>
                        <span><?php echo esc_html($sample['country']); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </header>

        <div class="container single-layout">
            <div class="content-body opportunity-template-body">
                <section>
                    <h2>Short Summary</h2>
                    <p><?php echo esc_html(wp_trim_words($sample['summary'] ?? '', 42)); ?></p>
                </section>

                <section>
                    <h2>Key Details</h2>
                    <dl class="content-details-grid">
                        <div><dt>Position</dt><dd><?php echo esc_html($sample['title'] ?? ''); ?></dd></div>
                        <div><dt>Organization</dt><dd><?php echo esc_html($sample['organization'] ?? ''); ?></dd></div>
                        <div><dt>Country</dt><dd><?php echo esc_html($sample['country'] ?? ''); ?></dd></div>
                        <div><dt>Location</dt><dd><?php echo esc_html($sample['location'] ?? ''); ?></dd></div>
                        <div><dt>Work arrangement</dt><dd><?php echo esc_html($sample['work_mode'] ?? ''); ?></dd></div>
                        <div><dt>Opportunity type</dt><dd><?php echo esc_html($sample['opportunity_type'] ?? ''); ?></dd></div>
                        <div><dt>Category</dt><dd><?php echo esc_html($sample['category'] ?? ''); ?></dd></div>
                        <div><dt>Compensation</dt><dd><?php echo esc_html($sample['compensation'] ?: 'Not specified'); ?></dd></div>
                        <div><dt>Posted date</dt><dd><?php echo esc_html($sample['posted_date'] ?? ''); ?></dd></div>
                    </dl>
                </section>

                <section>
                    <h2>Description</h2>
                    <p><?php echo esc_html($sample['summary'] ?? ''); ?></p>
                </section>

                <section>
                    <h2>Responsibilities</h2>
                    <?php gotheme_render_points($responsibilities); ?>
                </section>

                <section>
                    <h2>Requirements / Eligibility</h2>
                    <?php gotheme_render_points($requirements); ?>
                </section>

                <section>
                    <h2>Benefits</h2>
                    <?php gotheme_render_points($benefits); ?>
                </section>

                <section>
                    <h2>How To Apply</h2>
                    <p>Review the opportunity details on Aitomic Jobs first, then continue to the official source website to apply.</p>
                </section>

                <section>
                    <h2>Source</h2>
                    <p>Source: <?php echo esc_html($sample['source'] ?? ''); ?>. Aitomic Jobs links back to the original listing for application and verification.</p>
                </section>
            </div>
            <aside class="details-panel">
                <h2>Opportunity details</h2>
                <dl class="opportunity-meta">
                    <div><dt>Organization</dt><dd><?php echo esc_html($sample['organization'] ?? ''); ?></dd></div>
                    <div><dt>Category</dt><dd><?php echo esc_html($sample['category'] ?? ''); ?></dd></div>
                    <div><dt>Location</dt><dd><?php echo esc_html($sample['location'] ?? ''); ?></dd></div>
                    <div><dt>Work mode</dt><dd><?php echo esc_html($sample['work_mode'] ?? ''); ?></dd></div>
                    <div><dt>Posted</dt><dd><?php echo esc_html($sample['posted_date'] ?? ''); ?></dd></div>
                    <div><dt>Compensation</dt><dd><?php echo esc_html($sample['compensation'] ?: 'Not specified'); ?></dd></div>
                    <div><dt>Source</dt><dd><?php echo esc_html($sample['source'] ?? ''); ?></dd></div>
                </dl>
                <?php if (!empty($sample['application_link'])) : ?>
                    <a class="button primary" href="<?php echo esc_url($sample['application_link']); ?>" target="_blank" rel="noopener">Apply on source website</a>
                <?php endif; ?>
            </aside>
        </div>
    </article>
<?php else : ?>
    <section class="page-heading">
        <div class="container">
            <p class="eyebrow">Sample opportunity</p>
            <h1>Opportunity not found</h1>
        </div>
    </section>
<?php endif; ?>

<?php get_footer(); ?>
