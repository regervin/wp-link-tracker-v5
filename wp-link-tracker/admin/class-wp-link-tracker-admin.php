<?php
/**
 * Admin functionality for the plugin
 */
class WP_Link_Tracker_Admin {
    /**
     * Initialize the class.
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('manage_wplinktracker_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_wplinktracker_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-wplinktracker_sortable_columns', array($this, 'set_sortable_columns'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('wp_ajax_wp_link_tracker_get_dashboard_stats', array($this, 'get_dashboard_stats_ajax'));
        add_action('wp_ajax_wp_link_tracker_debug_date_range', array($this, 'debug_date_range_ajax'));
        add_action('wp_ajax_wp_link_tracker_view_data_count', array($this, 'view_data_count_ajax'));
        add_action('wp_ajax_wp_link_tracker_reset_stats', array($this, 'reset_stats_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_clicks_over_time', array($this, 'get_clicks_over_time_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_top_links', array($this, 'get_top_links_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_top_referrers', array($this, 'get_top_referrers_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_device_data', array($this, 'get_device_data_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_browser_data', array($this, 'get_browser_data_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_os_data', array($this, 'get_os_data_ajax'));
        add_action('wp_ajax_wp_link_tracker_validate_all_data', array($this, 'validate_all_data_ajax'));
    }

    /**
     * Add meta boxes for the tracked link post type.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wplinktracker_link_details',
            __('Link Details', 'wp-link-tracker'),
            array($this, 'render_link_details_meta_box'),
            'wplinktracker',
            'normal',
            'high'
        );
        
        add_meta_box(
            'wplinktracker_link_stats',
            __('Link Statistics', 'wp-link-tracker'),
            array($this, 'render_link_stats_meta_box'),
            'wplinktracker',
            'side',
            'default'
        );
    }

    /**
     * Render the link details meta box.
     */
    public function render_link_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('wplinktracker_meta_box_nonce', 'wplinktracker_meta_box_nonce');
        
        // Get current values
        $destination_url = get_post_meta($post->ID, '_wplinktracker_destination_url', true);
        $short_code = get_post_meta($post->ID, '_wplinktracker_short_code', true);
        $campaign = wp_get_object_terms($post->ID, 'wplinktracker_campaign', array('fields' => 'names'));
        $campaign_name = !empty($campaign) ? $campaign[0] : '';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wplinktracker_destination_url"><?php _e('Destination URL', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="url" id="wplinktracker_destination_url" name="wplinktracker_destination_url" 
                           value="<?php echo esc_attr($destination_url); ?>" class="regular-text" required />
                    <p class="description"><?php _e('The URL where users will be redirected when they click the short link.', 'wp-link-tracker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wplinktracker_short_code"><?php _e('Short Code', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="text" id="wplinktracker_short_code" name="wplinktracker_short_code" 
                           value="<?php echo esc_attr($short_code); ?>" class="regular-text" />
                    <p class="description">
                        <?php _e('Custom short code for the link. Leave blank to auto-generate.', 'wp-link-tracker'); ?>
                        <?php if (!empty($short_code)): ?>
                            <br><?php printf(__('Short URL: %s', 'wp-link-tracker'), '<strong>' . home_url('go/' . $short_code) . '</strong>'); ?>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wplinktracker_campaign"><?php _e('Campaign', 'wp-link-tracker'); ?></label>
                </th>
                <td>
                    <input type="text" id="wplinktracker_campaign" name="wplinktracker_campaign" 
                           value="<?php echo esc_attr($campaign_name); ?>" class="regular-text" />
                    <p class="description"><?php _e('Optional campaign name to group related links.', 'wp-link-tracker'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the link statistics meta box.
     */
    public function render_link_stats_meta_box($post) {
        $total_clicks = get_post_meta($post->ID, '_wplinktracker_total_clicks', true);
        $unique_visitors = get_post_meta($post->ID, '_wplinktracker_unique_visitors', true);
        $last_clicked = get_post_meta($post->ID, '_wplinktracker_last_clicked', true);
        
        ?>
        <div class="wplinktracker-stats">
            <div class="wplinktracker-stat-item">
                <strong><?php _e('Total Clicks:', 'wp-link-tracker'); ?></strong>
                <span><?php echo !empty($total_clicks) ? esc_html($total_clicks) : '0'; ?></span>
            </div>
            
            <div class="wplinktracker-stat-item">
                <strong><?php _e('Unique Visitors:', 'wp-link-tracker'); ?></strong>
                <span><?php echo !empty($unique_visitors) ? esc_html($unique_visitors) : '0'; ?></span>
            </div>
            
            <div class="wplinktracker-stat-item">
                <strong><?php _e('Conversion Rate:', 'wp-link-tracker'); ?></strong>
                <span>
                    <?php 
                    $total_clicks_int = (int) $total_clicks;
                    $unique_visitors_int = (int) $unique_visitors;
                    $conversion_rate = ($unique_visitors_int > 0) ? round(($total_clicks_int / $unique_visitors_int) * 100, 2) . '%' : '0%';
                    echo esc_html($conversion_rate);
                    ?>
                </span>
            </div>
            
            <?php if (!empty($last_clicked)): ?>
            <div class="wplinktracker-stat-item">
                <strong><?php _e('Last Clicked:', 'wp-link-tracker'); ?></strong>
                <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_clicked))); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
            .wplinktracker-stats {
                margin: 10px 0;
            }
            .wplinktracker-stat-item {
                margin-bottom: 10px;
                padding: 8px;
                background: #f9f9f9;
                border-left: 3px solid #0073aa;
            }
            .wplinktracker-stat-item strong {
                display: inline-block;
                width: 120px;
            }
        </style>
        <?php
    }

    /**
     * Save meta box data.
     */
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check if this is the correct post type
        if (get_post_type($post_id) !== 'wplinktracker') {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['wplinktracker_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['wplinktracker_meta_box_nonce'], 'wplinktracker_meta_box_nonce')) {
            return;
        }
        
        // Save destination URL
        if (isset($_POST['wplinktracker_destination_url'])) {
            $destination_url = esc_url_raw($_POST['wplinktracker_destination_url']);
            update_post_meta($post_id, '_wplinktracker_destination_url', $destination_url);
        }
        
        // Save or generate short code
        if (isset($_POST['wplinktracker_short_code'])) {
            $short_code = sanitize_text_field($_POST['wplinktracker_short_code']);
            
            // If no custom short code provided, generate one
            if (empty($short_code)) {
                $short_code = $this->generate_short_code();
            } else {
                // Check if custom short code is already in use
                $existing_post = $this->get_post_by_short_code($short_code);
                if ($existing_post && $existing_post->ID !== $post_id) {
                    // Short code already exists, generate a new one
                    $short_code = $this->generate_short_code();
                }
            }
            
            update_post_meta($post_id, '_wplinktracker_short_code', $short_code);
        }
        
        // Save campaign
        if (isset($_POST['wplinktracker_campaign'])) {
            $campaign = sanitize_text_field($_POST['wplinktracker_campaign']);
            if (!empty($campaign)) {
                wp_set_object_terms($post_id, $campaign, 'wplinktracker_campaign');
            } else {
                wp_delete_object_term_relationships($post_id, 'wplinktracker_campaign');
            }
        }
    }

    /**
     * Get post by short code.
     */
    private function get_post_by_short_code($short_code) {
        $args = array(
            'post_type' => 'wplinktracker',
            'meta_query' => array(
                array(
                    'key' => '_wplinktracker_short_code',
                    'value' => $short_code,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            return $query->posts[0];
        }
        
        return null;
    }

    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=wplinktracker',
            __('Dashboard', 'wp-link-tracker'),
            __('Dashboard', 'wp-link-tracker'),
            'manage_options',
            'wp-link-tracker-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=wplinktracker',
            __('Settings', 'wp-link-tracker'),
            __('Settings', 'wp-link-tracker'),
            'manage_options',
            'wp-link-tracker-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin scripts and styles - FORCE CACHE REFRESH.
     */
    public function enqueue_scripts($hook) {
        global $post_type;
        
        // Check if we're on any wplinktracker related page
        $is_plugin_page = (
            // Dashboard and settings pages
            strpos($hook, 'wp-link-tracker') !== false ||
            // Post edit pages
            ($hook === 'post.php' && $post_type === 'wplinktracker') ||
            ($hook === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'wplinktracker') ||
            // Post list page
            ($hook === 'edit.php' && $post_type === 'wplinktracker')
        );
        
        if (!$is_plugin_page) {
            return;
        }
        
        // Force enqueue jQuery if not already loaded
        wp_enqueue_script('jquery');
        
        // Enqueue Chart.js with proper dependency
        wp_enqueue_script(
            'chartjs', 
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js', 
            array(), 
            '3.7.1', 
            true
        );
        
        // FORCE CACHE REFRESH - Use current timestamp
        $cache_buster = time() . '-' . wp_rand(1000, 9999);
        
        // Enqueue our admin script with proper dependencies
        wp_enqueue_script(
            'wp-link-tracker-admin',
            WP_LINK_TRACKER_PLUGIN_URL . 'admin/js/wp-link-tracker-admin.js',
            array('jquery', 'chartjs'),
            $cache_buster, // Force cache refresh
            true
        );
        
        // Localize script with translations and AJAX data
        wp_localize_script('wp-link-tracker-admin', 'wpLinkTrackerAdmin', array(
            'noDataMessage' => __('No data available yet. Create some tracked links to see statistics here.', 'wp-link-tracker'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_link_tracker_admin_nonce'),
            'resetConfirmMessage' => __('Are you sure you want to reset all statistics? This action cannot be undone.', 'wp-link-tracker'),
            'resetSuccessMessage' => __('Statistics reset successfully!', 'wp-link-tracker'),
            'resetErrorMessage' => __('Reset failed. Please try again.', 'wp-link-tracker'),
            'debug' => true, // Enable debug mode
            'cacheBuster' => $cache_buster, // Add cache buster to localized data
            'productionMode' => true // Flag to indicate production mode
        ));
        
        // Enqueue our admin styles
        wp_enqueue_style(
            'wp-link-tracker-admin',
            WP_LINK_TRACKER_PLUGIN_URL . 'admin/css/wp-link-tracker-admin.css',
            array(),
            $cache_buster // Force cache refresh
        );
        
        // Add inline script to verify loading and force refresh
        wp_add_inline_script('wp-link-tracker-admin', '
            console.log("[WP Link Tracker] DATA VALIDATION MODE - Scripts loaded with cache buster: ' . $cache_buster . '");
            console.log("[WP Link Tracker] Hook: " + "' . $hook . '");
            console.log("[WP Link Tracker] Post Type: " + "' . $post_type . '");
            console.log("[WP Link Tracker] Is Plugin Page: " + ' . ($is_plugin_page ? 'true' : 'false') . ');
            console.log("[WP Link Tracker] DATA VALIDATION SYSTEM ACTIVE");
        ');
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('wp_link_tracker_settings', 'wp_link_tracker_settings');
        
        add_settings_section(
            'wp_link_tracker_general_settings',
            __('General Settings', 'wp-link-tracker'),
            array($this, 'render_general_settings_section'),
            'wp_link_tracker_settings'
        );
        
        add_settings_field(
            'link_prefix',
            __('Link Prefix', 'wp-link-tracker'),
            array($this, 'render_link_prefix_field'),
            'wp_link_tracker_settings',
            'wp_link_tracker_general_settings'
        );
    }

    /**
     * Render the general settings section.
     */
    public function render_general_settings_section() {
        echo '<p>' . __('Configure general settings for the WP Link Tracker plugin.', 'wp-link-tracker') . '</p>';
    }

    /**
     * Render the link prefix field.
     */
    public function render_link_prefix_field() {
        $options = get_option('wp_link_tracker_settings');
        $link_prefix = isset($options['link_prefix']) ? $options['link_prefix'] : 'go';
        
        echo '<input type="text" id="link_prefix" name="wp_link_tracker_settings[link_prefix]" value="' . esc_attr($link_prefix) . '" />';
        echo '<p class="description">' . __('The prefix for shortened links. Default is "go".', 'wp-link-tracker') . '</p>';
        echo '<p class="description">' . __('Example: yourdomain.com/go/abc123', 'wp-link-tracker') . '</p>';
    }

    /**
     * Set custom columns for the tracked links list.
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        
        // Add checkbox and title first
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        
        if (isset($columns['title'])) {
            $new_columns['title'] = $columns['title'];
        }
        
        // Add our custom columns
        $new_columns['destination'] = __('Destination URL', 'wp-link-tracker');
        $new_columns['short_url'] = __('Short URL', 'wp-link-tracker');
        $new_columns['clicks'] = __('Clicks', 'wp-link-tracker');
        $new_columns['unique_visitors'] = __('Unique Visitors', 'wp-link-tracker');
        $new_columns['conversion_rate'] = __('Conversion Rate', 'wp-link-tracker');
        $new_columns['date'] = __('Date', 'wp-link-tracker');
        
        return $new_columns;
    }

    /**
     * Display content for custom columns.
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'destination':
                $destination_url = get_post_meta($post_id, '_wplinktracker_destination_url', true);
                if (!empty($destination_url)) {
                    echo '<a href="' . esc_url($destination_url) . '" target="_blank">' . esc_url($destination_url) . '</a>';
                } else {
                    echo '—';
                }
                break;
                
            case 'short_url':
                $short_code = get_post_meta($post_id, '_wplinktracker_short_code', true);
                if (!empty($short_code)) {
                    $short_url = home_url('go/' . $short_code);
                    echo '<a href="' . esc_url($short_url) . '" target="_blank">' . esc_url($short_url) . '</a>';
                    echo '<br><button type="button" class="button button-small copy-to-clipboard" data-clipboard-text="' . esc_url($short_url) . '">' . __('Copy', 'wp-link-tracker') . '</button>';
                } else {
                    echo '—';
                }
                break;
                
            case 'clicks':
                $total_clicks = get_post_meta($post_id, '_wplinktracker_total_clicks', true);
                echo !empty($total_clicks) ? esc_html($total_clicks) : '0';
                break;
                
            case 'unique_visitors':
                $unique_visitors = get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
                echo !empty($unique_visitors) ? esc_html($unique_visitors) : '0';
                break;
                
            case 'conversion_rate':
                $total_clicks = (int) get_post_meta($post_id, '_wplinktracker_total_clicks', true);
                $unique_visitors = (int) get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
                $conversion_rate = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) . '%' : '0%';
                echo esc_html($conversion_rate);
                break;
        }
    }

    /**
     * Set sortable columns.
     */
    public function set_sortable_columns($columns) {
        $columns['clicks'] = 'clicks';
        $columns['unique_visitors'] = 'unique_visitors';
        $columns['conversion_rate'] = 'conversion_rate';
        return $columns;
    }

    /**
     * COMPREHENSIVE DATA VALIDATION - Validate all dashboard data integrity.
     */
    public function validate_all_data_ajax() {
        error_log('WP Link Tracker: [DATA VALIDATION] Starting comprehensive data validation');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        
        $validation_results = array(
            'validation_timestamp' => current_time('mysql'),
            'overall_status' => 'PASSED',
            'issues_found' => array(),
            'warnings' => array(),
            'data_sources' => array(),
            'panel_validations' => array()
        );
        
        // Get date range parameters
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // 1. VALIDATE DATA SOURCES
        $validation_results['data_sources'] = $this->validate_data_sources();
        
        // 2. VALIDATE SUMMARY STATISTICS
        $validation_results['panel_validations']['summary'] = $this->validate_summary_statistics($days, $date_from, $date_to);
        
        // 3. VALIDATE CLICKS OVER TIME
        $validation_results['panel_validations']['clicks_over_time'] = $this->validate_clicks_over_time($days, $date_from, $date_to);
        
        // 4. VALIDATE TOP LINKS
        $validation_results['panel_validations']['top_links'] = $this->validate_top_links($days, $date_from, $date_to);
        
        // 5. VALIDATE TOP REFERRERS
        $validation_results['panel_validations']['top_referrers'] = $this->validate_top_referrers($days, $date_from, $date_to);
        
        // 6. VALIDATE DEVICE DATA (THE PROBLEMATIC ONE)
        $validation_results['panel_validations']['device_data'] = $this->validate_device_data($days, $date_from, $date_to);
        
        // 7. VALIDATE BROWSER DATA
        $validation_results['panel_validations']['browser_data'] = $this->validate_browser_data($days, $date_from, $date_to);
        
        // 8. VALIDATE OS DATA
        $validation_results['panel_validations']['os_data'] = $this->validate_os_data($days, $date_from, $date_to);
        
        // 9. CROSS-VALIDATE DATA CONSISTENCY
        $validation_results['cross_validation'] = $this->cross_validate_data_consistency($days, $date_from, $date_to);
        
        // Determine overall status
        foreach ($validation_results['panel_validations'] as $panel => $result) {
            if ($result['status'] === 'FAILED') {
                $validation_results['overall_status'] = 'FAILED';
                $validation_results['issues_found'][] = "Panel '{$panel}' failed validation: " . $result['error'];
            } elseif ($result['status'] === 'WARNING') {
                $validation_results['warnings'][] = "Panel '{$panel}' has warnings: " . $result['warning'];
            }
        }
        
        if ($validation_results['cross_validation']['status'] === 'FAILED') {
            $validation_results['overall_status'] = 'FAILED';
            $validation_results['issues_found'][] = 'Cross-validation failed: ' . $validation_results['cross_validation']['error'];
        }
        
        error_log('WP Link Tracker: [DATA VALIDATION] Validation complete. Status: ' . $validation_results['overall_status']);
        error_log('WP Link Tracker: [DATA VALIDATION] Results: ' . print_r($validation_results, true));
        
        wp_send_json_success($validation_results);
    }

    /**
     * Validate data sources integrity.
     */
    private function validate_data_sources() {
        global $wpdb;
        
        $sources = array(
            'clicks_table' => array('exists' => false, 'records' => 0, 'structure_valid' => false),
            'post_meta' => array('exists' => false, 'records' => 0, 'structure_valid' => false),
            'posts_table' => array('exists' => false, 'records' => 0, 'structure_valid' => false)
        );
        
        // Check clicks table
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $sources['clicks_table']['exists'] = $table_exists;
        
        if ($table_exists) {
            $sources['clicks_table']['records'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            
            // Check required columns
            $columns = $wpdb->get_results("DESCRIBE $table_name");
            $required_columns = array('post_id', 'click_time', 'ip_address', 'device_type', 'browser', 'os', 'referrer');
            $existing_columns = array_column($columns, 'Field');
            $sources['clicks_table']['structure_valid'] = empty(array_diff($required_columns, $existing_columns));
            $sources['clicks_table']['columns'] = $existing_columns;
        }
        
        // Check post meta
        $meta_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wplinktracker_%'");
        $sources['post_meta']['exists'] = $meta_count > 0;
        $sources['post_meta']['records'] = (int) $meta_count;
        $sources['post_meta']['structure_valid'] = true; // Post meta is always valid structure
        
        // Check posts table
        $posts_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wplinktracker' AND post_status = 'publish'");
        $sources['posts_table']['exists'] = $posts_count > 0;
        $sources['posts_table']['records'] = (int) $posts_count;
        $sources['posts_table']['structure_valid'] = true; // Posts table is always valid
        
        return $sources;
    }

    /**
     * Validate summary statistics.
     */
    private function validate_summary_statistics($days, $date_from, $date_to) {
        $validation = array(
            'status' => 'PASSED',
            'data_source' => 'unknown',
            'calculations' => array(),
            'cross_check' => array()
        );
        
        try {
            // Get stats using the same method as dashboard
            $stats = $this->get_dashboard_statistics($days, $date_from, $date_to);
            
            // Validate each statistic
            $validation['calculations']['total_clicks'] = array(
                'value' => $stats['total_clicks'],
                'valid' => is_numeric($stats['total_clicks']) && $stats['total_clicks'] >= 0
            );
            
            $validation['calculations']['unique_visitors'] = array(
                'value' => $stats['unique_visitors'],
                'valid' => is_numeric($stats['unique_visitors']) && $stats['unique_visitors'] >= 0
            );
            
            $validation['calculations']['active_links'] = array(
                'value' => $stats['active_links'],
                'valid' => is_numeric($stats['active_links']) && $stats['active_links'] >= 0
            );
            
            // Cross-check: unique visitors should not exceed total clicks
            if ($stats['unique_visitors'] > $stats['total_clicks']) {
                $validation['status'] = 'FAILED';
                $validation['error'] = 'Unique visitors (' . $stats['unique_visitors'] . ') exceeds total clicks (' . $stats['total_clicks'] . ')';
            }
            
            // Determine data source
            global $wpdb;
            $table_name = $wpdb->prefix . 'wplinktracker_clicks';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            $validation['data_source'] = $table_exists && $stats['total_clicks'] > 0 ? 'clicks_table' : 'post_meta';
            
        } catch (Exception $e) {
            $validation['status'] = 'FAILED';
            $validation['error'] = 'Exception during validation: ' . $e->getMessage();
        }
        
        return $validation;
    }

    /**
     * Validate clicks over time data.
     */
    private function validate_clicks_over_time($days, $date_from, $date_to) {
        $validation = array(
            'status' => 'PASSED',
            'data_points' => 0,
            'date_range_valid' => false,
            'data_consistency' => array()
        );
        
        try {
            $clicks_data = $this->get_clicks_over_time_data($days, $date_from, $date_to);
            $validation['data_points'] = count($clicks_data);
            
            // Validate date range
            if (!empty($clicks_data)) {
                $first_date = $clicks_data[0]['date'];
                $last_date = end($clicks_data)['date'];
                $validation['date_range_valid'] = strtotime($first_date) <= strtotime($last_date);
                $validation['first_date'] = $first_date;
                $validation['last_date'] = $last_date;
            }
            
            // Validate data consistency
            $total_clicks_from_timeline = 0;
            foreach ($clicks_data as $point) {
                if (!is_numeric($point['clicks']) || $point['clicks'] < 0) {
                    $validation['status'] = 'FAILED';
                    $validation['error'] = 'Invalid click count on ' . $point['date'] . ': ' . $point['clicks'];
                    break;
                }
                $total_clicks_from_timeline += $point['clicks'];
            }
            
            $validation['total_clicks_calculated'] = $total_clicks_from_timeline;
            
        } catch (Exception $e) {
            $validation['status'] = 'FAILED';
            $validation['error'] = 'Exception during validation: ' . $e->getMessage();
        }
        
        return $validation;
    }

    /**
     * Validate top links data.
     */
    private function validate_top_links($days, $date_from, $date_to) {
        $validation = array(
            'status' => 'PASSED',
            'links_count' => 0,
            'data_integrity' => array()
        );
        
        try {
            $top_links = $this->get_top_links_data($days, $date_from, $date_to);
            $validation['links_count'] = count($top_links);
            
            foreach ($top_links as $index => $link) {
                $link_validation = array(
                    'id' => $link['id'],
                    'title_valid' => !empty($link['title']),
                    'url_valid' => !empty($link['short_url']) && filter_var($link['short_url'], FILTER_VALIDATE_URL),
                    'clicks_valid' => is_numeric($link['total_clicks']) && $link['total_clicks'] >= 0,
                    'visitors_valid' => is_numeric($link['unique_visitors']) && $link['unique_visitors'] >= 0,
                    'conversion_valid' => $link['unique_visitors'] <= $link['total_clicks']
                );
                
                if (!$link_validation['conversion_valid']) {
                    $validation['status'] = 'FAILED';
                    $validation['error'] = 'Link ID ' . $link['id'] . ' has more unique visitors than total clicks';
                }
                
                $validation['data_integrity'][] = $link_validation;
            }
            
        } catch (Exception $e) {
            $validation['status'] = 'FAILED';
            $validation['error'] = 'Exception during validation: ' . $e->getMessage();
        }
        
        return $validation;
    }

    /**
     * Validate top referrers data.
     */
    private function validate_top_referrers($days, $date_from, $date_to) {
        $validation = array(
            'status' => 'PASSED',
            'referrers_count' => 0,
            'url_validation' => array()
        );
        
        try {
            $top_referrers = $this->get_top_referrers_data($days, $date_from, $date_to);
            $validation['referrers_count'] = count($top_referrers);
            
            foreach ($top_referrers as $referrer) {
                $is_valid_url = filter_var($referrer['referrer'], FILTER_VALIDATE_URL) !== false;
                $has_valid_count = is_numeric($referrer['count']) && $referrer['count'] > 0;
                
                $validation['url_validation'][] = array(
                    'referrer' => $referrer['referrer'],
                    'domain' => $referrer['domain'],
                    'count' => $referrer['count'],
                    'url_valid' => $is_valid_url,
                    'count_valid' => $has_valid_count
                );
                
                if (!$has_valid_count) {
                    $validation['status'] = 'WARNING';
                    $validation['warning'] = 'Referrer has invalid count: ' . $referrer['referrer'];
                }
            }
            
        } catch (Exception $e) {
            $validation['status'] = 'FAILED';
            $validation['error'] = 'Exception during validation: ' . $e->getMessage();
        }
        
        return $validation;
    }

    /**
     * Validate device data - THE CRITICAL ONE.
     */
    private function validate_device_data($days, $date_from, $date_to) {
        global $wpdb;
        
        $validation = array(
            'status' => 'PASSED',
            'data_source_analysis' => array(),
            'column_content_analysis' => array(),
            'data_mapping_check' => array(),
            'expected_vs_actual' => array()
        );
        
        try {
            // 1. Analyze what's actually in the database
            $table_name = $wpdb->prefix . 'wplinktracker_clicks';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if ($table_exists) {
                // Check what's in device_type column vs os column
                $device_sample = $wpdb->get_results("SELECT device_type, os, browser FROM $table_name LIMIT 20");
                $validation['data_source_analysis']['sample_data'] = $device_sample;
                
                // Analyze device_type column content
                $device_types = $wpdb->get_results("SELECT device_type, COUNT(*) as count FROM $table_name GROUP BY device_type ORDER BY count DESC");
                $validation['column_content_analysis']['device_type_values'] = $device_types;
                
                // Analyze os column content
                $os_types = $wpdb->get_results("SELECT os, COUNT(*) as count FROM $table_name GROUP BY os ORDER BY count DESC");
                $validation['column_content_analysis']['os_values'] = $os_types;
                
                // Check if device_type contains OS data (the suspected issue)
                $device_type_values = array_column($device_types, 'device_type');
                $os_like_values = array('Linux', 'Windows', 'Mac OS', 'iOS', 'Android', 'Unknown');
                $device_like_values = array('Desktop', 'Mobile', 'Tablet');
                
                $device_contains_os = !empty(array_intersect($device_type_values, $os_like_values));
                $device_contains_devices = !empty(array_intersect($device_type_values, $device_like_values));
                
                $validation['data_mapping_check'] = array(
                    'device_type_contains_os_data' => $device_contains_os,
                    'device_type_contains_device_data' => $device_contains_devices,
                    'device_type_values' => $device_type_values,
                    'suspected_data_corruption' => $device_contains_os && !$device_contains_devices
                );
                
                // Get what the function actually returns
                $device_data = $this->get_device_breakdown_data($days, $date_from, $date_to);
                $validation['expected_vs_actual'] = array(
                    'function_returns' => $device_data,
                    'expected_device_types' => array('Desktop', 'Mobile', 'Tablet'),
                    'actual_returned_types' => array_column($device_data, 'device')
                );
                
                // Determine if there's a data integrity issue
                if ($validation['data_mapping_check']['suspected_data_corruption']) {
                    $validation['status'] = 'FAILED';
                    $validation['error'] = 'CRITICAL: device_type column contains OS data instead of device types. This explains why the device chart shows OS values.';
                }
                
            } else {
                $validation['status'] = 'WARNING';
                $validation['warning'] = 'Clicks table does not exist - no device data available';
            }
            
        } catch (Exception $e) {
            $validation['status'] = 'FAILED';
            $validation['error'] = 'Exception during validation: ' . $e->getMessage();
        }
        
        return $validation;
    }

    /**
     * Validate browser data.
     */
    private function validate_browser_data($days, $date_from, $date_to) {
        $validation = array(
            'status' => 'PASSED',
            'browsers_count' => 0,
            'browser_names' => array()
        );
        
        try {
            $browser_data = $this->get_browser_breakdown_data($days, $date_from, $date_to);
            $validation['browsers_count'] = count($browser_data);
            $validation['browser_names'] = array_column($browser_data, 'browser');
            
            // Validate browser names are reasonable
            $valid_browsers = array('Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Unknown');
            $invalid_browsers = array_diff($validation['browser_names'], $valid_browsers);
            
            if (!empty($invalid_browsers)) {
                $validation['status'] = 'WARNING';
                $validation['warning'] = 'Unexpected browser names found: ' . implode(', ', $invalid_browsers);
            }
            
        } catch (Exception $e) {
            $validation['status'] = 'FAILED';
            $validation['error'] = 'Exception during validation: ' . $e->getMessage();
        }
        
        return $validation;
    }

    /**
     * Validate OS data.
     */
    private function validate_os_data($days, $date_from, $date_to) {
        $validation = array(
            'status' => 'PASSED',
            'os_count' => 0,
            'os_names' => array()
        );
        
        try {
            $os_data = $this->get_os_breakdown_data($days, $date_from, $date_to);
            $validation['os_count'] = count($os_data);
            $validation['os_names'] = array_column($os_data, 'os');
            
            // Validate OS names are reasonable
            $valid_os = array('Windows', 'Mac OS', 'Linux', 'iOS', 'Android', 'Unknown');
            $invalid_os = array_diff($validation['os_names'], $valid_os);
            
            if (!empty($invalid_os)) {
                $validation['status'] = 'WARNING';
                $validation['warning'] = 'Unexpected OS names found: ' . implode(', ', $invalid_os);
            }
            
        } catch (Exception $e) {
            $validation['status'] = 'FAILED';
            $validation['error'] = 'Exception during validation: ' . $e->getMessage();
        }
        
        return $validation;
    }

    /**
     * Cross-validate data consistency across panels.
     */
    private function cross_validate_data_consistency($days, $date_from, $date_to) {
        $validation = array(
            'status' => 'PASSED',
            'consistency_checks' => array()
        );
        
        try {
            // Get data from all sources
            $summary_stats = $this->get_dashboard_statistics($days, $date_from, $date_to);
            $clicks_timeline = $this->get_clicks_over_time_data($days, $date_from, $date_to);
            $top_links = $this->get_top_links_data($days, $date_from, $date_to);
            
            // Calculate total clicks from timeline
            $timeline_total = array_sum(array_column($clicks_timeline, 'clicks'));
            
            // Calculate total clicks from top links
            $links_total = array_sum(array_column($top_links, 'total_clicks'));
            
            $validation['consistency_checks'] = array(
                'summary_total_clicks' => $summary_stats['total_clicks'],
                'timeline_total_clicks' => $timeline_total,
                'links_total_clicks' => $links_total,
                'summary_vs_timeline_match' => abs($summary_stats['total_clicks'] - $timeline_total) <= 1, // Allow 1 click difference for rounding
                'summary_vs_links_match' => abs($summary_stats['total_clicks'] - $links_total) <= 1
            );
            
            // Check for major discrepancies
            if (!$validation['consistency_checks']['summary_vs_timeline_match']) {
                $validation['status'] = 'WARNING';
                $validation['warning'] = 'Summary clicks (' . $summary_stats['total_clicks'] . ') does not match timeline total (' . $timeline_total . ')';
            }
            
        } catch (Exception $e) {
            $validation['status'] = 'FAILED';
            $validation['error'] = 'Exception during cross-validation: ' . $e->getMessage();
        }
        
        return $validation;
    }

    /**
     * Get dashboard statistics via AJAX - WITH VALIDATION INTEGRATION.
     */
    public function get_dashboard_stats_ajax() {
        error_log('WP Link Tracker: [DATA VALIDATION] Dashboard stats AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            error_log('WP Link Tracker: Invalid nonce for dashboard stats');
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range parameters
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        error_log('WP Link Tracker: [DATA VALIDATION] Getting stats for days=' . $days . ', from=' . $date_from . ', to=' . $date_to);
        
        // Get statistics with validation
        $stats = $this->get_dashboard_statistics($days, $date_from, $date_to);
        
        // Get all panel data
        $clicks_over_time = $this->get_clicks_over_time_data($days, $date_from, $date_to);
        $stats['clicks_over_time'] = $clicks_over_time;
        
        $top_links = $this->get_top_links_data($days, $date_from, $date_to);
        $stats['top_links'] = $top_links;
        
        $top_referrers = $this->get_top_referrers_data($days, $date_from, $date_to);
        $stats['top_referrers'] = $top_referrers;
        
        $device_data = $this->get_device_breakdown_data($days, $date_from, $date_to);
        $stats['device_data'] = $device_data;
        
        $browser_data = $this->get_browser_breakdown_data($days, $date_from, $date_to);
        $stats['browser_data'] = $browser_data;
        
        $os_data = $this->get_os_breakdown_data($days, $date_from, $date_to);
        $stats['os_data'] = $os_data;
        
        // Add validation flag
        $stats['validation_available'] = true;
        
        error_log('WP Link Tracker: [DATA VALIDATION] Returning stats with validation capability');
        
        wp_send_json_success($stats);
    }

    /**
     * Get top links data via AJAX.
     */
    public function get_top_links_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] Top links AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $top_links = $this->get_top_links_data($days, $date_from, $date_to);
        
        wp_send_json_success($top_links);
    }

    /**
     * Get top referrers data via AJAX.
     */
    public function get_top_referrers_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] Top referrers AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $top_referrers = $this->get_top_referrers_data($days, $date_from, $date_to);
        
        wp_send_json_success($top_referrers);
    }

    /**
     * Get device data via AJAX.
     */
    public function get_device_data_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] Device data AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $device_data = $this->get_device_breakdown_data($days, $date_from, $date_to);
        
        wp_send_json_success($device_data);
    }

    /**
     * Get browser data via AJAX.
     */
    public function get_browser_data_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] Browser data AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $browser_data = $this->get_browser_breakdown_data($days, $date_from, $date_to);
        
        wp_send_json_success($browser_data);
    }

    /**
     * Get OS data via AJAX.
     */
    public function get_os_data_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] OS data AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $os_data = $this->get_os_breakdown_data($days, $date_from, $date_to);
        
        wp_send_json_success($os_data);
    }

    /**
     * Get top links data - PRODUCTION VERSION (REAL DATA ONLY).
     */
    private function get_top_links_data($days = 30, $date_from = '', $date_to = '', $limit = 10) {
        error_log('WP Link Tracker: [PRODUCTION] Getting top links data - REAL DATA ONLY');
        
        // Get all tracked links with their meta data
        $posts = get_posts(array(
            'post_type' => 'wplinktracker',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $links_data = array();
        
        foreach ($posts as $post) {
            $total_clicks = get_post_meta($post->ID, '_wplinktracker_total_clicks', true);
            $unique_visitors = get_post_meta($post->ID, '_wplinktracker_unique_visitors', true);
            $short_code = get_post_meta($post->ID, '_wplinktracker_short_code', true);
            $destination_url = get_post_meta($post->ID, '_wplinktracker_destination_url', true);
            
            // Only include links with actual data or show all with 0 values
            $links_data[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'short_code' => $short_code,
                'short_url' => home_url('go/' . $short_code),
                'destination_url' => $destination_url,
                'total_clicks' => !empty($total_clicks) ? (int) $total_clicks : 0,
                'unique_visitors' => !empty($unique_visitors) ? (int) $unique_visitors : 0,
                'conversion_rate' => (!empty($unique_visitors) && $unique_visitors > 0) ? round(((int) $total_clicks / (int) $unique_visitors) * 100, 2) : 0
            );
        }
        
        // Sort by total clicks descending
        usort($links_data, function($a, $b) {
            return $b['total_clicks'] - $a['total_clicks'];
        });
        
        // Limit results
        $links_data = array_slice($links_data, 0, $limit);
        
        error_log('WP Link Tracker: [PRODUCTION] Top links data (REAL): ' . print_r($links_data, true));
        
        return $links_data;
    }

    /**
     * Get top referrers data - PRODUCTION VERSION (REAL DATA ONLY).
     */
    private function get_top_referrers_data($days = 30, $date_from = '', $date_to = '', $limit = 10) {
        global $wpdb;
        
        error_log('WP Link Tracker: [PRODUCTION] Getting top referrers data - REAL DATA ONLY');
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        $referrers_data = array();
        
        if ($table_exists) {
            // Build date condition
            if (!empty($date_from) && !empty($date_to)) {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT referrer, COUNT(*) as count
                    FROM $table_name
                    WHERE DATE(click_time) BETWEEN %s AND %s
                    AND referrer != '' AND referrer IS NOT NULL
                    GROUP BY referrer
                    ORDER BY count DESC
                    LIMIT %d",
                    $date_from, $date_to, $limit
                ));
            } else {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT referrer, COUNT(*) as count
                    FROM $table_name
                    WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
                    AND referrer != '' AND referrer IS NOT NULL
                    GROUP BY referrer
                    ORDER BY count DESC
                    LIMIT %d",
                    $days, $limit
                ));
            }
            
            foreach ($results as $row) {
                $domain = parse_url($row->referrer, PHP_URL_HOST);
                $referrers_data[] = array(
                    'referrer' => $row->referrer,
                    'domain' => $domain ?: $row->referrer,
                    'count' => (int) $row->count
                );
            }
        }
        
        error_log('WP Link Tracker: [PRODUCTION] Top referrers data (REAL): ' . print_r($referrers_data, true));
        
        return $referrers_data;
    }

    /**
     * Get device breakdown data - PRODUCTION VERSION (REAL DATA ONLY) - CRITICAL DEBUG VERSION.
     */
    private function get_device_breakdown_data($days = 30, $date_from = '', $date_to = '') {
        global $wpdb;
        
        error_log('WP Link Tracker: [CRITICAL DEBUG] Getting device breakdown data - REAL DATA ONLY');
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        error_log('WP Link Tracker: [CRITICAL DEBUG] Table name: ' . $table_name);
        error_log('WP Link Tracker: [CRITICAL DEBUG] Table exists: ' . ($table_exists ? 'YES' : 'NO'));
        
        $device_data = array();
        
        if ($table_exists) {
            // First, let's check what columns exist in the table
            $columns = $wpdb->get_results("DESCRIBE $table_name");
            error_log('WP Link Tracker: [CRITICAL DEBUG] Table columns: ' . print_r($columns, true));
            
            // Build date condition - EXPLICITLY USE device_type COLUMN
            if (!empty($date_from) && !empty($date_to)) {
                $sql = $wpdb->prepare(
                    "SELECT device_type, COUNT(*) as count
                    FROM $table_name
                    WHERE DATE(click_time) BETWEEN %s AND %s
                    GROUP BY device_type
                    ORDER BY count DESC",
                    $date_from, $date_to
                );
            } else {
                $sql = $wpdb->prepare(
                    "SELECT device_type, COUNT(*) as count
                    FROM $table_name
                    WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
                    GROUP BY device_type
                    ORDER BY count DESC",
                    $days
                );
            }
            
            error_log('WP Link Tracker: [CRITICAL DEBUG] Device SQL query: ' . $sql);
            
            $results = $wpdb->get_results($sql);
            
            error_log('WP Link Tracker: [CRITICAL DEBUG] Raw device query results: ' . print_r($results, true));
            
            foreach ($results as $row) {
                $device_data[] = array(
                    'device' => $row->device_type ?: 'Unknown',
                    'count' => (int) $row->count
                );
            }
            
            // Let's also check what's actually in the device_type column
            $sample_data = $wpdb->get_results("SELECT device_type, os, browser FROM $table_name LIMIT 10");
            error_log('WP Link Tracker: [CRITICAL DEBUG] Sample data from table: ' . print_r($sample_data, true));
        }
        
        error_log('WP Link Tracker: [CRITICAL DEBUG] Final device breakdown data: ' . print_r($device_data, true));
        
        return $device_data;
    }

    /**
     * Get browser breakdown data - PRODUCTION VERSION (REAL DATA ONLY).
     */
    private function get_browser_breakdown_data($days = 30, $date_from = '', $date_to = '') {
        global $wpdb;
        
        error_log('WP Link Tracker: [PRODUCTION] Getting browser breakdown data - REAL DATA ONLY');
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        $browser_data = array();
        
        if ($table_exists) {
            // Build date condition
            if (!empty($date_from) && !empty($date_to)) {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT browser, COUNT(*) as count
                    FROM $table_name
                    WHERE DATE(click_time) BETWEEN %s AND %s
                    GROUP BY browser
                    ORDER BY count DESC
                    LIMIT 10",
                    $date_from, $date_to
                ));
            } else {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT browser, COUNT(*) as count
                    FROM $table_name
                    WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
                    GROUP BY browser
                    ORDER BY count DESC
                    LIMIT 10",
                    $days
                ));
            }
            
            foreach ($results as $row) {
                $browser_data[] = array(
                    'browser' => $row->browser ?: 'Unknown',
                    'count' => (int) $row->count
                );
            }
        }
        
        error_log('WP Link Tracker: [PRODUCTION] Browser breakdown data (REAL): ' . print_r($browser_data, true));
        
        return $browser_data;
    }

    /**
     * Get OS breakdown data - PRODUCTION VERSION (REAL DATA ONLY).
     */
    private function get_os_breakdown_data($days = 30, $date_from = '', $date_to = '') {
        global $wpdb;
        
        error_log('WP Link Tracker: [PRODUCTION] Getting OS breakdown data - REAL DATA ONLY');
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        $os_data = array();
        
        if ($table_exists) {
            // Build date condition
            if (!empty($date_from) && !empty($date_to)) {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT os, COUNT(*) as count
                    FROM $table_name
                    WHERE DATE(click_time) BETWEEN %s AND %s
                    GROUP BY os
                    ORDER BY count DESC
                    LIMIT 10",
                    $date_from, $date_to
                ));
            } else {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT os, COUNT(*) as count
                    FROM $table_name
                    WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
                    GROUP BY os
                    ORDER BY count DESC
                    LIMIT 10",
                    $days
                ));
            }
            
            foreach ($results as $row) {
                $os_data[] = array(
                    'os' => $row->os ?: 'Unknown',
                    'count' => (int) $row->count
                );
            }
        }
        
        error_log('WP Link Tracker: [PRODUCTION] OS breakdown data (REAL): ' . print_r($os_data, true));
        
        return $os_data;
    }

    /**
     * Get clicks over time data via AJAX.
     */
    public function get_clicks_over_time_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] Clicks over time AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            error_log('WP Link Tracker: Invalid nonce for clicks over time');
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range parameters
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        error_log('WP Link Tracker: [PRODUCTION] Getting clicks over time for days=' . $days . ', from=' . $date_from . ', to=' . $date_to);
        
        // Get clicks over time data
        $clicks_over_time = $this->get_clicks_over_time_data($days, $date_from, $date_to);
        
        error_log('WP Link Tracker: [PRODUCTION] Returning clicks over time (REAL): ' . print_r($clicks_over_time, true));
        
        wp_send_json_success($clicks_over_time);
    }

    /**
     * Get clicks over time data - PRODUCTION VERSION (REAL DATA ONLY).
     */
    private function get_clicks_over_time_data($days = 30, $date_from = '', $date_to = '') {
        global $wpdb;
        
        error_log('WP Link Tracker: [PRODUCTION] Getting clicks over time data - REAL DATA ONLY');
        
        // Initialize empty data array
        $data = array();
        
        // Determine date range
        if (!empty($date_from) && !empty($date_to)) {
            $start_date = new DateTime($date_from);
            $end_date = new DateTime($date_to);
            $interval = $start_date->diff($end_date)->days + 1;
        } else {
            $start_date = new DateTime();
            $start_date->modify('-' . $days . ' days');
            $end_date = new DateTime();
            $interval = $days;
        }
        
        error_log('WP Link Tracker: [PRODUCTION] Date range from ' . $start_date->format('Y-m-d') . ' to ' . $end_date->format('Y-m-d') . ' (' . $interval . ' days)');
        
        // Initialize all dates with zero clicks
        $current_date = clone $start_date;
        for ($i = 0; $i < $interval; $i++) {
            $date_key = $current_date->format('Y-m-d');
            $data[$date_key] = 0;
            $current_date->modify('+1 day');
        }
        
        // Try to get data from clicks table
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if ($table_exists) {
            error_log('WP Link Tracker: [PRODUCTION] Getting data from clicks table');
            
            // Build date condition
            if (!empty($date_from) && !empty($date_to)) {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE(click_time) as date, COUNT(*) as clicks
                    FROM $table_name
                    WHERE DATE(click_time) BETWEEN %s AND %s
                    GROUP BY DATE(click_time)
                    ORDER BY date ASC",
                    $date_from, $date_to
                ));
            } else {
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE(click_time) as date, COUNT(*) as clicks
                    FROM $table_name
                    WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
                    GROUP BY DATE(click_time)
                    ORDER BY date ASC",
                    $days
                ));
            }
            
            error_log('WP Link Tracker: [PRODUCTION] Found ' . count($results) . ' date records from clicks table');
            
            // Update data array with actual clicks
            foreach ($results as $row) {
                if (isset($data[$row->date])) {
                    $data[$row->date] = (int) $row->clicks;
                }
            }
        } else {
            error_log('WP Link Tracker: [PRODUCTION] Clicks table does not exist - returning empty data');
        }
        
        // Format data for Chart.js
        $formatted_data = array();
        foreach ($data as $date => $clicks) {
            $formatted_data[] = array(
                'date' => $date,
                'clicks' => $clicks
            );
        }
        
        error_log('WP Link Tracker: [PRODUCTION] Formatted data (REAL): ' . print_r($formatted_data, true));
        
        return $formatted_data;
    }

    /**
     * Debug date range via AJAX.
     */
    public function debug_date_range_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] Debug date range AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $debug_info = array(
            'production_mode' => true,
            'current_time' => current_time('mysql'),
            'timezone' => get_option('timezone_string'),
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format'),
            'parameters' => array(
                'days' => $days,
                'date_from' => $date_from,
                'date_to' => $date_to
            )
        );
        
        // Check if clicks table exists
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $debug_info['clicks_table_exists'] = $table_exists;
        
        if ($table_exists) {
            $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $debug_info['total_click_records'] = $total_records;
            
            if ($total_records > 0) {
                $sample_records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY click_time DESC LIMIT 5");
                $debug_info['sample_records'] = $sample_records;
            }
        }
        
        // Check post meta statistics
        $debug_info['post_meta_stats'] = $this->get_post_meta_statistics();
        
        // Get clicks over time data for debugging
        $debug_info['clicks_over_time_data'] = $this->get_clicks_over_time_data($days, $date_from, $date_to);
        
        wp_send_json_success($debug_info);
    }

    /**
     * View data count via AJAX.
     */
    public function view_data_count_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] View data count AJAX called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Get tracked links count
        $links_count = wp_count_posts('wplinktracker');
        $published_links = isset($links_count->publish) ? $links_count->publish : 0;
        
        // Get clicks data
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        $data_count = array(
            'production_mode' => true,
            'tracked_links' => $published_links,
            'clicks_table_exists' => $table_exists,
            'total_click_records' => 0,
            'filtered_click_records' => 0,
            'date_range' => array(
                'days' => $days,
                'date_from' => $date_from,
                'date_to' => $date_to
            )
        );
        
        if ($table_exists) {
            // Total click records
            $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $data_count['total_click_records'] = $total_clicks;
            
            // Filtered click records based on date range
            if (!empty($date_from) && !empty($date_to)) {
                $filtered_clicks = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE DATE(click_time) BETWEEN %s AND %s",
                    $date_from, $date_to
                ));
            } else {
                $filtered_clicks = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $days
                ));
            }
            $data_count['filtered_click_records'] = $filtered_clicks;
        }
        
        // Add post meta statistics
        $data_count['post_meta_stats'] = $this->get_post_meta_statistics();
        
        wp_send_json_success($data_count);
    }

    /**
     * Reset statistics via AJAX.
     */
    public function reset_stats_ajax() {
        error_log('WP Link Tracker: [PRODUCTION] Reset stats AJAX called');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_admin_nonce')) {
            error_log('WP Link Tracker: Reset stats - Invalid nonce');
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            error_log('WP Link Tracker: Reset stats - Permission denied');
            wp_send_json_error('Permission denied');
            return;
        }
        
        global $wpdb;
        
        try {
            error_log('WP Link Tracker: [PRODUCTION] Starting reset process');
            
            // Clear clicks table if it exists
            $table_name = $wpdb->prefix . 'wplinktracker_clicks';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            $cleared_table = false;
            if ($table_exists) {
                $result = $wpdb->query("TRUNCATE TABLE $table_name");
                $cleared_table = ($result !== false);
                error_log('WP Link Tracker: [PRODUCTION] Truncate table result: ' . ($cleared_table ? 'success' : 'failed'));
            }
            
            // Reset all post meta statistics
            $posts = get_posts(array(
                'post_type' => 'wplinktracker',
                'post_status' => 'publish',
                'numberposts' => -1
            ));
            
            error_log('WP Link Tracker: [PRODUCTION] Found ' . count($posts) . ' posts to reset');
            
            foreach ($posts as $post) {
                update_post_meta($post->ID, '_wplinktracker_total_clicks', 0);
                update_post_meta($post->ID, '_wplinktracker_unique_visitors', 0);
                delete_post_meta($post->ID, '_wplinktracker_last_clicked');
            }
            
            error_log('WP Link Tracker: [PRODUCTION] Reset completed successfully');
            
            wp_send_json_success(array(
                'message' => __('All statistics have been reset successfully.', 'wp-link-tracker'),
                'cleared_table' => $cleared_table,
                'reset_posts' => count($posts),
                'table_exists' => $table_exists,
                'production_mode' => true
            ));
            
        } catch (Exception $e) {
            error_log('WP Link Tracker: [PRODUCTION] Reset failed with exception: ' . $e->getMessage());
            wp_send_json_error('Reset failed: ' . $e->getMessage());
        }
    }

    /**
     * Get post meta statistics for debugging.
     */
    private function get_post_meta_statistics() {
        // Get all tracked links with their meta data
        $posts = get_posts(array(
            'post_type' => 'wplinktracker',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $stats = array(
            'total_posts' => count($posts),
            'posts_with_clicks' => 0,
            'posts_with_unique_visitors' => 0,
            'total_clicks_from_meta' => 0,
            'total_unique_visitors_from_meta' => 0,
            'sample_posts' => array()
        );
        
        foreach ($posts as $post) {
            $total_clicks = get_post_meta($post->ID, '_wplinktracker_total_clicks', true);
            $unique_visitors = get_post_meta($post->ID, '_wplinktracker_unique_visitors', true);
            
            if (!empty($total_clicks) && $total_clicks > 0) {
                $stats['posts_with_clicks']++;
                $stats['total_clicks_from_meta'] += (int) $total_clicks;
            }
            
            if (!empty($unique_visitors) && $unique_visitors > 0) {
                $stats['posts_with_unique_visitors']++;
                $stats['total_unique_visitors_from_meta'] += (int) $unique_visitors;
            }
            
            // Add first 5 posts as samples
            if (count($stats['sample_posts']) < 5) {
                $stats['sample_posts'][] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'total_clicks' => $total_clicks ?: '0',
                    'unique_visitors' => $unique_visitors ?: '0'
                );
            }
        }
        
        return $stats;
    }

    /**
     * Get dashboard statistics - PRODUCTION VERSION (REAL DATA ONLY).
     */
    private function get_dashboard_statistics($days = 30, $date_from = '', $date_to = '') {
        global $wpdb;
        
        error_log('WP Link Tracker: [PRODUCTION] Getting dashboard statistics - REAL DATA ONLY');
        
        // Always start with zero values
        $total_clicks = 0;
        $unique_visitors = 0;
        
        // Method 1: Try to get from clicks table first
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        error_log('WP Link Tracker: [PRODUCTION] Clicks table exists: ' . ($table_exists ? 'yes' : 'no'));
        
        if ($table_exists) {
            // Build date condition
            $date_condition = '';
            $date_params = array();
            
            if (!empty($date_from) && !empty($date_to)) {
                $date_condition = "WHERE DATE(click_time) BETWEEN %s AND %s";
                $date_params = array($date_from, $date_to);
            } else {
                $date_condition = "WHERE click_time >= DATE_SUB(NOW(), INTERVAL %d DAY)";
                $date_params = array($days);
            }
            
            // Get total clicks for the date range
            $total_clicks = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name $date_condition",
                $date_params
            ));
            
            // Get unique visitors for the date range
            $unique_visitors = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT ip_address) FROM $table_name $date_condition",
                $date_params
            ));
            
            error_log('WP Link Tracker: [PRODUCTION] From clicks table - Total: ' . $total_clicks . ', Unique: ' . $unique_visitors);
        }
        
        // Method 2: If clicks table doesn't exist or has no data, aggregate from post meta
        if (!$table_exists || $total_clicks == 0) {
            error_log('WP Link Tracker: [PRODUCTION] Using post meta fallback');
            
            $posts = get_posts(array(
                'post_type' => 'wplinktracker',
                'post_status' => 'publish',
                'numberposts' => -1
            ));
            
            error_log('WP Link Tracker: [PRODUCTION] Found ' . count($posts) . ' tracked links');
            
            $total_clicks = 0;
            $unique_visitors = 0;
            
            foreach ($posts as $post) {
                $post_clicks = get_post_meta($post->ID, '_wplinktracker_total_clicks', true);
                $post_unique = get_post_meta($post->ID, '_wplinktracker_unique_visitors', true);
                
                $total_clicks += !empty($post_clicks) ? (int) $post_clicks : 0;
                $unique_visitors += !empty($post_unique) ? (int) $post_unique : 0;
            }
            
            error_log('WP Link Tracker: [PRODUCTION] From post meta - Total: ' . $total_clicks . ', Unique: ' . $unique_visitors);
        }
        
        // Get active links count
        $links_count = wp_count_posts('wplinktracker');
        $active_links = isset($links_count->publish) ? (int) $links_count->publish : 0;
        
        // Calculate average conversion rate
        $avg_conversion = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) : 0;
        
        $result = array(
            'total_clicks' => $total_clicks,
            'unique_visitors' => $unique_visitors,
            'active_links' => $active_links,
            'avg_conversion' => $avg_conversion . '%',
            'production_mode' => true
        );
        
        error_log('WP Link Tracker: [PRODUCTION] Final stats (REAL): ' . print_r($result, true));
        
        return $result;
    }

    /**
     * Render the dashboard page.
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wplinktracker-dashboard">
                <div class="wplinktracker-dashboard-header">
                    <div class="wplinktracker-date-range">
                        <label for="wplinktracker-date-range-select"><?php _e('Date Range:', 'wp-link-tracker'); ?></label>
                        <select id="wplinktracker-date-range-select">
                            <option value="7"><?php _e('Last 7 Days', 'wp-link-tracker'); ?></option>
                            <option value="30" selected><?php _e('Last 30 Days', 'wp-link-tracker'); ?></option>
                            <option value="90"><?php _e('Last 90 Days', 'wp-link-tracker'); ?></option>
                            <option value="365"><?php _e('Last Year', 'wp-link-tracker'); ?></option>
                            <option value="custom"><?php _e('Custom Range', 'wp-link-tracker'); ?></option>
                        </select>
                        
                        <div id="wplinktracker-custom-date-range" style="display: none;">
                            <input type="date" id="wplinktracker-date-from" />
                            <span>to</span>
                            <input type="date" id="wplinktracker-date-to" />
                            <button type="button" class="button" id="wplinktracker-apply-date-range"><?php _e('Apply', 'wp-link-tracker'); ?></button>
                        </div>
                        
                        <div class="wplinktracker-date-actions">
                            <button type="button" class="button button-primary" id="wplinktracker-validate-all-data">
                                <span class="dashicons dashicons-yes-alt"></span> <?php _e('Validate All Data', 'wp-link-tracker'); ?>
                            </button>
                            <button type="button" class="button" id="wplinktracker-refresh-data">
                                <span class="dashicons dashicons-update"></span> <?php _e('Refresh Data', 'wp-link-tracker'); ?>
                            </button>
                            <button type="button" class="button" id="wplinktracker-view-data-count">
                                <span class="dashicons dashicons-visibility"></span> <?php _e('View Data Count', 'wp-link-tracker'); ?>
                            </button>
                            <button type="button" class="button" id="wplinktracker-debug-date-range">
                                <span class="dashicons dashicons-admin-tools"></span> <?php _e('Debug Date Range', 'wp-link-tracker'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="wplinktracker-reset-stats">
                                <span class="dashicons dashicons-trash"></span> <?php _e('Reset Stats', 'wp-link-tracker'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-refresh">
                        <button type="button" class="button" id="wplinktracker-refresh-dashboard">
                            <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'wp-link-tracker'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-summary">
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Total Clicks', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-total-clicks">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Unique Visitors', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-unique-visitors">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Active Links', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-active-links">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Avg. Conversion Rate', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-avg-conversion">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                        </div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-charts">
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Clicks Over Time', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-clicks-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-tables">
                    <div class="wplinktracker-table-container">
                        <h3><?php _e('Top Performing Links', 'wp-link-tracker'); ?></h3>
                        <div id="wplinktracker-top-links-table" class="wplinktracker-table-content">
                            <p><?php _e('No data available yet. Create some tracked links to see statistics here.', 'wp-link-tracker'); ?></p>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-table-container">
                        <h3><?php _e('Top Referrers', 'wp-link-tracker'); ?></h3>
                        <div id="wplinktracker-top-referrers-table" class="wplinktracker-table-content">
                            <p><?php _e('No data available yet. Create some tracked links to see statistics here.', 'wp-link-tracker'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-devices">
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Device Types', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-devices-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Browsers', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-browsers-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Operating Systems', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-os-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_link_tracker_settings');
                do_settings_sections('wp_link_tracker_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Create a new link via AJAX.
     */
    public function create_link_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_create_link_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check if user has permission
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        // Check required fields
        if (!isset($_POST['title']) || !isset($_POST['destination_url'])) {
            wp_send_json_error('Missing required fields');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $destination_url = esc_url_raw($_POST['destination_url']);
        $campaign = isset($_POST['campaign']) ? sanitize_text_field($_POST['campaign']) : '';
        
        // Create the post
        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => 'wplinktracker',
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }
        
        // Set the destination URL
        update_post_meta($post_id, '_wplinktracker_destination_url', $destination_url);
        
        // Generate short code
        $short_code = $this->generate_short_code();
        update_post_meta($post_id, '_wplinktracker_short_code', $short_code);
        
        // Set campaign if provided
        if (!empty($campaign)) {
            wp_set_object_terms($post_id, $campaign, 'wplinktracker_campaign');
        }
        
        // Get the short URL
        $short_url = home_url('go/' . $short_code);
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'short_url' => $short_url,
            'shortcode' => '[tracked_link id="' . $post_id . '"]'
        ));
    }

    /**
     * Generate a unique short code.
     */
    private function generate_short_code($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $short_code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $short_code .= $characters[rand(0, $charactersLength - 1)];
        }
        
        // Check if the code already exists
        $args = array(
            'post_type' => 'wplinktracker',
            'meta_query' => array(
                array(
                    'key' => '_wplinktracker_short_code',
                    'value' => $short_code,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        
        // If the code exists, generate a new one
        if ($query->have_posts()) {
            return $this->generate_short_code($length);
        }
        
        return $short_code;
    }
}
