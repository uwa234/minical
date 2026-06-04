<?php
$js_files = isset($js_files) ? $js_files : array();
?>
<footer class="mc-footer">
    <div class="mc-container mc-footer-inner">
        <div class="mc-footer-brand">
            <?php if (!empty($website_logo_url)) { ?>
                <img class="mc-logo-img mc-logo-img--footer" src="<?php echo htmlspecialchars($website_logo_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(seo_brand_name(), ENT_QUOTES, 'UTF-8'); ?>" />
            <?php } else { ?>
                <span class="mc-logo-mark" aria-hidden="true"></span>
                <span><?php echo htmlspecialchars(seo_brand_name(), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php } ?>
        </div>
        <nav class="mc-footer-links" aria-label="Footer">
            <a href="<?php echo base_url('auth/login'); ?>">Sign in</a>
            <a href="<?php echo base_url('auth/register'); ?>">Start free trial</a>
            <a href="<?php echo base_url('auth/show_terms_of_service'); ?>">Terms of Service</a>
            <a href="<?php echo base_url('auth/show_privacy_policy'); ?>">Privacy Policy</a>
            <?php
            $support_email = getenv('SEO_ORG_EMAIL') ?: getenv('MAIL_REPLY_TO');
            if ($support_email) {
                echo '<a href="mailto:' . htmlspecialchars($support_email, ENT_QUOTES, 'UTF-8') . '">Contact us</a>';
            }
            ?>
        </nav>
        <p class="mc-footer-copy">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(seo_brand_name(), ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.</p>
    </div>
</footer>

<?php foreach ($js_files as $js) { ?>
    <script src="<?php echo $js; ?>"></script>
<?php } ?>
</body>
</html>
