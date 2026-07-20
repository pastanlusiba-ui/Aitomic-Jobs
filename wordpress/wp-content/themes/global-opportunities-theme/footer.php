</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <p>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?>.</p>
            <p>Jobs, internships, tenders, training, remote work, and calls for applications.</p>
        </div>
        <nav class="footer-links" aria-label="<?php esc_attr_e('Site information', 'global-opportunities-theme'); ?>">
            <a href="<?php echo esc_url(home_url('/about/')); ?>">About</a>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
            <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a>
            <a href="<?php echo esc_url(home_url('/cookie-policy/')); ?>">Cookie Policy</a>
            <a href="<?php echo esc_url(home_url('/terms-of-use/')); ?>">Terms</a>
            <a href="<?php echo esc_url(home_url('/editorial-policy-disclaimer/')); ?>">Editorial Policy</a>
        </nav>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
