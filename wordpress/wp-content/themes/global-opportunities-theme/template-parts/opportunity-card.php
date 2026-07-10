<?php $is_expired = gotheme_is_expired_opportunity(); ?>
<article <?php post_class('opportunity-card' . ($is_expired ? ' is-expired' : '')); ?>>
    <div class="card-kicker">
        <span class="pill"><?php echo gotheme_term_list('opportunity_type'); ?></span>
        <span class="deadline-badge <?php echo $is_expired ? 'is-past' : ''; ?>"><?php echo esc_html(gotheme_deadline_label()); ?></span>
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
