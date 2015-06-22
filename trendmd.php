<?php
/*
Plugin Name: TrendMD
Plugin URI: http://www.trendmd.com
Description: This plugin will add the TrendMD recommendations widget to your WordPress website. The TrendMD recommendations widget is used by scholarly publishers to increase their readership and revenue.
Version: 1.0
*/


if (!class_exists('TrendMD')) {
    class TrendMD
    {
        const TRENDMD_URL = 'http://www.trendmd.com';

        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // Initialize Settings
            require_once(sprintf("%s/settings.php", dirname(__FILE__)));
            $TrendMD_Settings = new TrendMD_Settings();

            $plugin = plugin_basename(__FILE__);
            add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'));
        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            update_option('trendmd_journal_id', self::trendmd_get_journal_id());
            self::init_db();

        } // END public static function activate

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            update_option('trendmd_journal_id', '');
            self::deactivate_db();
        } // END public static function deactivate

        // Add the settings link to the plugins page
        function plugin_settings_link($links)
        {
            $settings_link = '<a href="options-general.php?page=trendmd">Settings</a>';
            array_unshift($links, $settings_link);

            return $links;
        }

        public static function trendmd_get_journal_id()
        {
            $site_address = get_bloginfo('url');
            $trendMD_endpoint = TrendMD::TRENDMD_URL . '/journals/search?term=' . $site_address;
            $r = json_decode(file_get_contents($trendMD_endpoint));
            $journal_id = 0;
            if (count($r->results) > 0) {
                $journal_id = (int)$r->results[0]->id;
            }

            return (int)$journal_id;
        }

        public static function trendmd_add_js()
        {
            if (self::show_widget()) {
                wp_enqueue_script(
                    'newscript',
                    '//trendmd.s3.amazonaws.com/trendmd.min.js'
                );
            }
        }

        public static function trendmd_add_html($content)
        {
            if (self::show_widget()) {
                $content .= "<div id='trendmd-suggestions'></div>";
            }
            return $content;
        }

        public static function trendmd_add_widget_js()
        {
            if (self::show_widget()) {
                $content = "<script type='text/javascript'>
TrendMD.register({journal_id: '" . self::get_journal_id() . "', element: '#trendmd-suggestions', authors: '" . self::prepare_string(get_the_author()) . "', url: window.location.href, title: '" . self::prepare_string(get_the_title()) . "', abstract: '" . self::prepare_string(get_the_content()) . "', publication_year: '" . (int)get_the_date('Y') . "', publication_month: '" . (int)get_the_date('m') . "' });</script>";
                echo $content;
            }

        }

        public static function show_widget()
        {
            return (self::is_set_journal_id() && is_singular() && !is_preview());
        }

        public static function is_set_journal_id()
        {
            $j_id = self::get_journal_id();
            return (is_numeric($j_id) && $j_id > 0);
        }

        public static function get_journal_id()
        {
            return get_option('trendmd_journal_id', array());
        }

        static function prepare_string($string)
        {
            return trim(json_encode(html_entity_decode(strip_tags($string), ENT_NOQUOTES, 'UTF-8'), JSON_HEX_APOS), '"');
        }

        static function submit_post($post)
        {
            global $wpdb;
            $wpdb->query('REPLACE INTO ' . $wpdb->prefix . 'trendmd_indexed_articles values(' . $post->ID . ');');
            $d = array(
                'body' => array(
                    'abstract' => self::prepare_string($post->post_content),
                    'authors' => get_userdata($post->post_author)->user_nicename,
                    'publication_month' => date('m', strtotime($post->post_date)),
                    'publication_year' => date('Y', strtotime($post->post_date)),
                    'title' => $post->post_title,
                    'force_update' => 1,
                    'url' => get_permalink($post->ID)));

            wp_remote_post(TrendMD::TRENDMD_URL . '/journals/' . TrendMD::trendmd_get_journal_id() . '/articles', $d);
        }

        public static function index_posts()
        {
            $count_posts = wp_count_posts();
            $published_posts = $count_posts->publish;
            $offset = (int)$_POST['trendmd_offset'];

            if ($offset >= $published_posts) {
                echo 'done';
                update_option('trendmd_fetch_articles_at', date('Y-m-d- H:i:s'));
            } else {
                $args = array(
                    'posts_per_page' => 1,
                    'offset' => $offset,
                    'category_name' => '',
                    'orderby' => 'post_date',
                    'order' => 'DESC',
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'suppress_filters' => true
                );
                $posts_array = get_posts($args);

                foreach ($posts_array as $post) {
                    self::submit_post($post);

                    echo ++$offset;

                }
            }

            //Don't forget to always exit in the ajax function.
            exit();

        }

        public static function save_post_callback($post_id, $post)
        {
            if ($post->post_type != 'post' || $post->post_status != 'publish') {
                return;
            }
            self::submit_post($post);
        }

        public static function init_db()
        {
            global $wpdb;
            $sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'trendmd_indexed_articles' . '(id mediumint(9) PRIMARY KEY);';
            $wpdb->query($sql);
        }

        public static function deactivate_db()
        {
            global $wpdb;
            $sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'trendmd_indexed_articles;';
            $wpdb->query($sql);
        }

        public static function offset()
        {
            global $wpdb;
            $ids = 'SELECT id FROM ' . $wpdb->prefix . 'posts WHERE post_type="post" AND post_status="publish"';
            return $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'trendmd_indexed_articles WHERE id IN (' . $ids . ');');
        }

    } // END class TrendMD
} // END if(!class_exists('TrendMD'))

if (class_exists('TrendMD')) {
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('TrendMD', 'activate'));
    register_deactivation_hook(__FILE__, array('TrendMD', 'deactivate'));

    add_filter('the_content', array('TrendMD', 'trendmd_add_html'), -1);
    add_filter('wp_enqueue_scripts', array('TrendMD', 'trendmd_add_js'));
    add_action('wp_footer', array('TrendMD', 'trendmd_add_widget_js'));
    add_action('save_post', array('TrendMD', 'save_post_callback'), '99', 2);

    // instantiate the plugin class
    $trendmd = new TrendMD();

}
