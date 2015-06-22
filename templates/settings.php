<div class="wrap">
    <?php @settings_fields('trendmd-group'); ?>
    <?php do_settings_sections('trendmd'); ?>
</div>
<script type="text/javascript">
<?php global $wpdb; $offset = TrendMD::offset() ?>
<?php $site_url = parse_url(get_bloginfo("url"), PHP_URL_HOST); ?>
trendmd_offset = <?php echo $offset; ?>;
indexed = trendmd_offset;
trendmdIndexArticles =    function (trendmd_chunk) {
    jQuery('.trendmd-progress-container').show();
    jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: { action: 'my_action' , trendmd_offset: trendmd_offset }
        }).done(function( msg ) {
            trendmd_offset = msg;
            jQuery('.trendmd-progress').css("width", parseInt(trendmd_offset)*parseInt(trendmd_chunk));
            if(parseInt(trendmd_offset) > 0 ) {
                indexed = parseInt(trendmd_offset);
                jQuery('.articles-indexed').html(trendmd_offset);
                trendmdIndexArticles(trendmd_chunk);
            }
        else
            {
                jQuery('.trendmd-progress-container').hide();
                jQuery('.trendmd-message').html('<h3>TrendMD recommendations will appear on <?php echo $site_url; ?> within 10 minutes</h3><a target="_blank" href="<?php echo TrendMD::TRENDMD_URL; ?>/faqs"><button style="margin: 20px 0 0 0; background-color: #427ef5; border: 1px solid #2e69e1; border-radius: 4px; color: #fff; padding: 12px 23px; font-size: 14px; letter-spacing: 1px; box-shadow: 0 1px 1px 0 rgba(0,0,0,0.2);">Contact support</button></a>');
            }
        });

    }
<?php
    $count_posts = wp_count_posts();
    $published_posts = $count_posts->publish;
    $chunk = round(400 / $published_posts);
    echo 'trendmdIndexArticles(' . $chunk . ');';
?>
</script>
