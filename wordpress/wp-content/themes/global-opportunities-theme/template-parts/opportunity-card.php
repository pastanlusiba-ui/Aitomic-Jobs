<article <?php post_class('opportunity-card'); ?>>
    <div class="card-kicker">
        <span class="pill"><?php echo gotheme_term_list('opportunity_type'); ?></span>
        <span><?php echo esc_html(gotheme_meta('deadline')); ?></span>
    </div>
    <div>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p class="card-org"><?php echo esc_html(gotheme_meta('organization')); ?></p>
    </div>
    <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
    <div class="card-footer">
        <span><?php echo gotheme_term_list('country'); ?></span>
        <a href="<?php the_permalink(); ?>">Details</a>
    </div>
</article>
