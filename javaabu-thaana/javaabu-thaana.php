<?php

/*
Plugin Name: Javaabu Thaana
Plugin URI: http://wordpress.org/extend/plugins/javaabu-thaana/
Description: Adds Dhivehi language to WordPress and Thaana editing support to WordPress backend
Version: 1.0.0
Author: Javaabu Pvt. Ltd.
Author URI: https://javaabu.com
Text Domain: javaabu-thaana
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('JAVAABU_THAANA_PATH', plugin_dir_path( __FILE__ ));
define( 'JAVAABU_THAANA_META_PREFIX', '_javaabuthaana_' );

//load CMB2
if ( file_exists( JAVAABU_THAANA_PATH . '/cmb2/init.php' ) ) {
    require_once JAVAABU_THAANA_PATH . '/cmb2/init.php';
} elseif ( file_exists( JAVAABU_THAANA_PATH . '/CMB2/init.php' ) ) {
    require_once JAVAABU_THAANA_PATH . '/CMB2/init.php';
}

class Javaabu_Thaana {

    const CURRENT_VER = '0.1';

    /**
     * Setup the environment for the plugin
     */
    public function bootstrap() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        
        add_action( 'plugins_loaded', array( $this, 'check_language_files_installed' ));
        add_action( 'init', array( $this, 'register_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'editor_styles' ) );
        add_filter( 'tiny_mce_before_init', array( $this, 'add_tinymce_jtk' ), 1000, 1 );
        add_action( 'wp_enqueue_scripts', array( $this, 'embed_font_styles' ) );
        add_filter( 'body_class', array( $this, 'add_body_class' ) );
        add_action( 'admin_print_styles', array( $this, 'add_custom_admin_css' ) );
        add_action( 'admin_print_footer_scripts', array( $this, 'add_custom_admin_js' ) );
        add_action( 'wp_print_footer_scripts', array( $this, 'add_front_end_jtk' ) );
	    add_filter( 'locale' , array( $this, 'redefine_locale' ) );
        add_action( 'cmb2_admin_init', array( $this, 'register_thaana_metabox' ) );
        add_filter( 'javaabuthaana_latin_title' , array( $this, 'get_latin_title' ) );
        add_shortcode( 'latin_title', array( $this, 'latin_title_shortcode' ) );
    }

    /**
     * Runs activation functions
     */
    public function activate() {
        $this->init_options();
        $this->install_language_files();
    }

    /**
     * Initialise options
     */
    public function init_options() {
        update_option( 'javaabuthaana_ver', self::CURRENT_VER );
    }

    /**
     * Install the Dhivehi language files
     */
    public function install_language_files() {
        //check if language files already installed
        if ( file_exists(WP_LANG_DIR . '/dv_MV.mo')) {
            return;
        }
        $access_type = get_filesystem_method();
        if($access_type === 'direct') {
            // you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, array());

            // initialize the API
            if ( ! WP_Filesystem($creds) ) {
                // any problems and we exit
                return false;
            }

            global $wp_filesystem;
            // do our file manipulations below

            //check language dir exists
            if( !$wp_filesystem->is_dir($wp_filesystem->wp_lang_dir()) ) {
                //create language dir
                $wp_filesystem->mkdir($wp_filesystem->wp_lang_dir());
            }

            //copy the files
            $plugin_path = str_replace(ABSPATH, $wp_filesystem->abspath(), JAVAABU_THAANA_PATH);
            $wp_filesystem->copy($plugin_path . '/res/languages/dv_MV.mo', $wp_filesystem->wp_lang_dir() . '/dv_MV.mo');
        } else {
            // don't have direct write access. Prompt user with our notice
            add_action('admin_notices', array( $this, 'language_install_error_notice' ));
        }
    }

    /**
     * Check language files installed
     */
    public function check_language_files_installed() {
        if ( !file_exists(WP_LANG_DIR . '/dv_MV.mo') ) {
            add_action('admin_notices', array( $this, 'language_install_error_notice' ));
        }
    }

    /**
     * Display an error message when unable to copy language files
     */
    public function language_install_error_notice() {
        echo '<div class="error"><p>'.wp_kses(__('<strong>Javaabu Thaana:</strong> Dhivehi language files not installed. Please manually copy all the files inside this plugin\'s <strong>res/languages</strong> directory to <strong>wp-content/languages</strong>', 'javaabu-thaana'), array('strong' => array()) ).'</p></div>';
    }

    /**
     * Registers scripts
     */
    public function register_scripts() {
        wp_register_style('javaabuthaana_admin_css', plugins_url('/res/css/admin.css', __FILE__), false, '1.0.0');
        wp_register_script('jtk', plugins_url('/res/js/jtk-4.2.1.pack.js', __FILE__), array(), '4.2.1', true);
        wp_register_script('jquery-thaana', plugins_url('/res/js/jquery.thaana.min.js', __FILE__), array(), '1.4', true);
        wp_register_script('jtk-admin', plugins_url('/res/js/jtk-admin.js', __FILE__), array('jtk', 'jquery-thaana', 'jquery'), '1.0.0', true);
    }

    /**
     * Add JTK scripts and css to admin
     */
    public function admin_scripts() {
        wp_enqueue_style('javaabuthaana_admin_css');

        if ( apply_filters('javaabuthaana_enable_admin_jtk', true) ) {
            wp_enqueue_script('jtk'); // Enqueue it!
            wp_enqueue_script('jtk-admin'); // Enqueue it!
        }
    }

    /**
     * Add CSS to TinyMCE
     */
    public function editor_styles() {
        add_editor_style(plugins_url('/res/css/editor-style.css', __FILE__));
    }

    /**
     * Add JTK to TinyMCE
     *
     * Credits to @reallynattu https://wordpress.org/plugins/thaana-wp
     * and Jaa https://github.com/jawish/jtk
     */
    public function add_tinymce_jtk($init_array) {
        $init_array['directionality'] = 'RTL';

        if ( apply_filters( 'javaabuthaana_enable_admin_jtk', true ) ) {
            //overriding WPML setup function
            $init_array['setup'] = 'function(ed) {                    
                    ed.on(\'keypress\', function (e) {
                        thaanaKeyboard.value = \'\';
                        thaanaKeyboard.handleKey(e);
                        ed.insertContent(thaanaKeyboard.value);
                    });
                }';
        }

        return $init_array;
    }

    /**
     * Embed Thaana fonts on the front end
     */
    public function embed_font_styles() {
        if ( apply_filters( 'javaabuthaana_enable_styles', true ) ) {
            wp_register_style( 'javaabu-thaana', plugins_url( '/res/css/style.css', __FILE__ ), array(), '0.1', 'all' );
            wp_enqueue_style( 'javaabu-thaana' );
        }

        if ( apply_filters( 'javaabuthaana_enable_jtk', true ) ) {
            wp_enqueue_script( 'jtk' ); // Enqueue it!
        }

        //add custom css under this hook
        do_action( 'javaabuthaana_enqueue_scripts' );
    }

    /**
     * Allows user to define custom fields for JTK
     */
    public function add_custom_admin_css() {
        $ids = apply_filters( 'javaabuthaana_custom_metabox_ids', array() );

        //print css if any ids specified
        if ( ! empty( $ids ) && is_array( $ids ) ) {
            $ids_string = implode( ',', $ids ); ?>
            <style type="text/css">
                <?php echo $ids_string; ?>
                {
                    font: 300 14px "MV Faseyha", "MV Waheed", Faruma, "mv iyyu nala", "mv elaaf normal", "MV Waheed", "MV Boli";
                    direction: rtl;
                    line-height: 26px;
                    unicode-bidi: embed;
                }
            </style>
        <?php }
    }

    /**
     * Allows users to define custom fields for JTK
     */
    public function add_custom_admin_js() {
        $ids = apply_filters( 'javaabuthaana_custom_metabox_ids', array() );

        //print js if any ids specified
        if ( ! empty( $ids ) && is_array( $ids ) ) {
            $ids_string = implode( ',', $ids ); ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('<?php echo $ids_string; ?>').addClass('thaanaKeyboardInput');
                });
            </script>
        <?php }
    }

    /**
     * Allows users to define front end fields for JTK
     */
    public function add_front_end_jtk() {

        $ids = apply_filters( 'javaabuthaana_front_end_ids', array() );

        //print js if any ids specified
        if ( apply_filters( 'javaabuthaana_enable_jtk', true ) && ! empty( $ids ) && is_array( $ids ) ) {
            $ids_string = implode( ',', $ids ); ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('<?php echo $ids_string; ?>').addClass('thaanaKeyboardInput');
                });
            </script>
        <?php }

    }

    /**
     * Add body class to front end
     */
    public function add_body_class( $classes ) {
        $classes[] = 'dv';
        return $classes;
    }

    /**
     * Change the front end locale
     */
	public function redefine_locale( $locale ) {
		if ( !( is_admin() || $GLOBALS['pagenow'] === 'wp-login.php' ) ) {
			$locale = 'dv_MV';
		}
		return $locale;
	}

    /**
     * Returns a custom meta value
     */
    public function get_meta($meta_id,  $single=true, $post_id=null, $echo=false) {
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }
        $meta_value = get_post_meta($post_id, JAVAABU_THAANA_META_PREFIX.$meta_id, $single);
        if ($echo && $single)
            echo $meta_value;
        return $meta_value;
    }

	/**
     * Add latin title metabox
     */
	public function register_thaana_metabox() {
        $prefix = JAVAABU_THAANA_META_PREFIX;

        $meta_box = new_cmb2_box(array(
                'id' => 'javaabu_thaana_metabox',
                'title' => __('Thaana Options', 'javaabu-blog'),
                'object_types' => get_post_types( array('public' => true), 'names' ), // Post type
                'context' => 'normal',
                'priority' => 'high',
                'show_names' => true, // Show field names on the left
            )
        );

        $meta_box->add_field(array(
            'name' => __('Latin Title', 'javaabu-blog'),
            'description' => __('Post title in latin', 'javaabu-blog'),
            'id' => $prefix . 'latin_title',
            'type' => 'text',
            'attributes' => array(
                'style' => 'width:90%;',
            ),
        ));
    }

    /**
     * Returns the latin title if exists, otherwise return normal title
     */
    public function get_latin_title($latin_title = '', $post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }

        //first get latin title
        $latin_title = $this->get_meta('latin_title', true, $post_id);
        if ( trim($latin_title) ) {
            return $latin_title;
        } else {
            return get_the_title($post_id);
        }
    }

    /**
     * Displays latin title shortcode
     */
    public function latin_title_shortcode($atts = array(), $content = null) {
        global $post;
        $atts = shortcode_atts(array(
            'id' => $post->ID,
        ), $atts);

        return apply_filters('javaabuthaana_latin_title', '', $atts['id']);
    }
}

//initialize the plugin
global $javaabu_thaana;
$javaabu_thaana = new Javaabu_Thaana();
$javaabu_thaana->bootstrap();