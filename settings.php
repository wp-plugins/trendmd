<?php
if (!class_exists('TrendMD_Settings')) {
    class TrendMD_Settings
    {
        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // register actions
            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_menu', array(&$this, 'add_menu'));
        } // END public function __construct

        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {
            // register your plugin's settings
            register_setting('trendmd-group', 'trendmd_journal_id');

            // add your settings section
            add_settings_section(
                'trendmd-section',
                '',
                array(&$this, 'settings_section_trendmd'),
                'trendmd'
            );

            // add your setting's fields
            add_settings_field(
                'trendmd-journal_id',
                '',
                array(&$this, 'settings_field_input_text'),
                'trendmd',
                'trendmd-section',
                array(
                    'field' => 'trendmd_journal_id'
                )
            );


            // Possibly do additional admin_init tasks
        } // END public static function activate

        public function settings_section_trendmd()
        {
            update_option('trendmd_journal_id', TrendMD::trendmd_get_journal_id());
            // Think of this as help text for the section.
            if (TrendMD::is_set_journal_id()) {
                $count_posts = wp_count_posts();
                $published_posts = $count_posts->publish;
                $chunk = round(400 / $published_posts);

                echo '<div style="color: #333; background-color: #f2f7ec;border-color: #d6e9c6; padding: 30px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 6px; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px; max-width: 750px;"><div style="display: inline;" class="trendmd-message"><h3>TrendMD is indexing <span class="articles-indexed">1</span> of ' . number_format($published_posts) . ' articles from ' . parse_url(get_bloginfo("url"), PHP_URL_HOST) . '</h3></div><br /><br /><div class="trendmd-progress-container" style="border-radius: 4px; background: #fff; border: 1px solid #ccc; width: 400px; height: 30px; box-shadow: inset 0 1px 1px 0 rgba(0,0,0,0.2); position: relative;"><div class="trendmd-progress" style="position: absolute; top: -1px; left: 0; height: 30px; border-radius: 4px; border: 1px solid #2b6ede; background-color: #3e81ef; border-top-right-radius: 0; border-bottom-right-radius: 0; width: 0px;"></div></div></div>';

            } else {
                $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $href = TrendMD::TRENDMD_URL . '/journals/new?redirect_to=' . urlencode($url) . '&new=1&journal[url]=' . get_bloginfo('url') . '&journal[short_name]=' . get_bloginfo('name') . '&journal[open_access]=1&journal[peer_reviewed]=1';

                echo '<div style="color: #333; background-color: #ecf3fe; 	border-color: #ecf3fe; padding: 30px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 6px; max-width: 750px; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px;"><h3 style="margin: 0 0 20px 0; font-size: 18px;">Almost done!</h3>Click \'Continue\' to register ' . parse_url(get_bloginfo("url"), PHP_URL_HOST) . ' with TrendMD:<br /><a href="' . $href . '"><button style="margin: 20px 0 0 0; background-color: #427ef5; border: 1px solid #2e69e1; border-radius: 4px; color: #fff; padding: 12px 23px; font-size: 14px; letter-spacing: 1px; box-shadow: 0 1px 1px 0 rgba(0,0,0,0.2);">Continue</button></a></div>';
            }
        }

        /**
         * This function provides text inputs for settings fields
         */
        public function settings_field_input_text($args)
        {
            // Get the field name from the $args array
            $field = $args['field'];
            // Get the value of this setting
            // $value = get_option($field);
            $value = get_option($field);
            // echo a proper input type="text"
            echo sprintf('<input type="hidden" name="%s" id="%s" value="%s" />', $field, $field, $value);
            //echo TrendMD::trendmd_get_journal_id();
        } // END public function settings_field_input_text($args)

        /**
         * add a menu
         */
        public function add_menu()
        {
            // Add a page to manage this plugin's settings
            add_options_page(
                'TrendMD settings',
                'TrendMD',
                'manage_options',
                'trendmd',
                array(&$this, 'plugin_settings_page')
            );
        } // END public function add_menu()

        /**
         * Menu Callback
         */
        public function plugin_settings_page()
        {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Render the settings template
            include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
        } // END public function plugin_settings_page()
    } // END class TrendMD_Settings
} // END if(!class_exists('TrendMD_Settings'))

add_action('wp_ajax_my_action', array('TrendMD', 'index_posts'));



?>
