<?php
/**
 * Plugin Name: Simple Lightweight Preloader
 * Description: A stable, lightweight, and customizable page preloader without external dependencies.
 * Version: 1.0.0
 * Author: Amanullah Khan
 * License: MIT
 * Text Domain: simple-preloader
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Simple_Preloader {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_footer', array($this, 'output_preloader_html'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles_scripts'));
    }

    /**
     * Add Admin Menu Page
     */
    public function add_admin_menu() {
        add_options_page(
            __('Simple Preloader Settings', 'simple-preloader'),
            __('Simple Preloader', 'simple-preloader'),
            'manage_options',
            'simple-preloader',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Register Settings
     */
    public function register_settings() {
        register_setting('simple_preloader_group', 'simple_preloader_enabled');
        register_setting('simple_preloader_group', 'simple_preloader_bg_color');
        register_setting('simple_preloader_group', 'simple_preloader_spinner_color');
    }

    /**
     * Render Admin Page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('simple_preloader_group');
                do_settings_sections('simple_preloader_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Preloader', 'simple-preloader'); ?></th>
                        <td>
                            <input type="checkbox" name="simple_preloader_enabled" value="1" <?php checked(1, get_option('simple_preloader_enabled'), true); ?> />
                            <label for="simple_preloader_enabled"><?php _e('Check to enable the preloader', 'simple-preloader'); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Background Color', 'simple-preloader'); ?></th>
                        <td>
                            <input type="color" name="simple_preloader_bg_color" value="<?php echo esc_attr(get_option('simple_preloader_bg_color', '#ffffff')); ?>" />
                            <p class="description"><?php _e('Choose the background color of the preloader screen.', 'simple-preloader'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Spinner Color', 'simple-preloader'); ?></th>
                        <td>
                            <input type="color" name="simple_preloader_spinner_color" value="<?php echo esc_attr(get_option('simple_preloader_spinner_color', '#333333')); ?>" />
                            <p class="description"><?php _e('Choose the color of the spinning loader.', 'simple-preloader'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Output HTML Structure in Footer
     */
    public function output_preloader_html() {
        if (!get_option('simple_preloader_enabled')) {
            return;
        }
        ?>
        <div id="sl-preloader">
            <div class="sl-spinner"></div>
        </div>
        <?php
    }

    /**
     * Enqueue CSS and JS
     */
    public function enqueue_styles_scripts() {
        if (!get_option('simple_preloader_enabled')) {
            return;
        }

        $bg_color = get_option('simple_preloader_bg_color', '#ffffff');
        $spinner_color = get_option('simple_preloader_spinner_color', '#333333');

        // Inline CSS for performance (no extra HTTP request)
        $css = "
            #sl-preloader {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: {$bg_color};
                z-index: 999999;
                display: flex;
                justify-content: center;
                align-items: center;
                transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
            }
            
            #sl-preloader.sl-hidden {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }

            .sl-spinner {
                width: 50px;
                height: 50px;
                border: 5px solid rgba(0,0,0,0.1);
                border-radius: 50%;
                border-top-color: {$spinner_color};
                animation: sl-spin 1s ease-in-out infinite;
            }

            @keyframes sl-spin {
                to { transform: rotate(360deg); }
            }

            /* Accessibility: Reduce motion if user prefers */
            @media (prefers-reduced-motion: reduce) {
                .sl-spinner {
                    animation: none;
                    border: 5px solid {$spinner_color};
                }
            }
        ";

        wp_add_inline_style('wp-block-library', $css); // Hook into existing style queue

        // Inline JS for performance
        $js = "
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('load', function() {
                    var preloader = document.getElementById('sl-preloader');
                    if (preloader) {
                        // Small delay to ensure smooth transition even on fast connections
                        setTimeout(function() {
                            preloader.classList.add('sl-hidden');
                            // Remove from DOM after transition completes to free memory
                            setTimeout(function() {
                                preloader.style.display = 'none';
                            }, 500);
                        }, 300);
                    }
                });
            });
        ";

        wp_add_inline_script('jquery-core', $js); // Hook into existing script queue
    }
}

// Initialize the plugin
Simple_Preloader::get_instance();
