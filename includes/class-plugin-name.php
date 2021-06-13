<?php

use GuzzleHttp\Client;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class Plugin_Name {

    protected $old_themes = array();
    protected $old_plugins = array();

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'plugin-name';

		$this->load_dependencies();
		$this->set_locale();


		//add_action('wp_head', [$this, 'HookEvents']);

		//$this->writeToFile();

        //add_action( 'upgrader_process_complete', [$this, 'plugin_logger_updated'], 10, 2 );
        //add_action( 'activated_plugin', [$this, 'plugin_logger'], 10, 2 );
        //add_action( 'deactivated_plugin', [$this, 'plugin_logger'], 10, 2 );

        add_action( 'admin_init', array( $this, 'EventAdminInit' ) );
        add_action('shutdown', [$this, 'EventAdminShutdown']);
        //add_action( 'shutdown', array( $this, 'EventAdminShutdown' ) );


    }

    public function LogPluginUpdatedEvent( $pluginAction, $plugin_file, $old_plugins = '' ) {
        $plugin_file_full = WP_PLUGIN_DIR . '/' . $plugin_file;
        $plugin      = get_plugin_data( $plugin_file_full, false, true );

        $old_version = ( isset( $old_plugins[ $plugin_file ] ) ) ? $old_plugins[ $plugin_file ]['Version'] : false;
        $new_version = $plugin['Version'];

        if ( $old_version !== $new_version ) {

            $log[] = array (
                'website_name' => get_bloginfo('name'),
                'website_url' => get_site_url(),
                'plugin_name'  => $plugin['Name'],
                'plugin_version'  => $plugin['Version'],
                'status'  => $pluginAction,
                'user'    => esc_html( wp_get_current_user()->display_name ),
                'network_wide' => $plugin['Network'] ? true : '',
            );

            $this->logToAPI( $log );

        }
    }

    public function EventAdminInit() {
        $this->old_themes  = wp_get_themes();
        $this->old_plugins = get_plugins();
    }

    public function EventAdminShutdown() {

	    $log = array();

        // Filter global arrays for security.
        $post_array  = filter_input_array( INPUT_POST );
        $get_array   = filter_input_array( INPUT_GET );
        $script_name = isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : false;
        //var_dump($post_array);
        //print_r($get_array->success);



        if ($post_array) {
            $pluginAction = $post_array['action'];
        } else {
            $pluginAction = $get_array['action'];
        }

        //exit(print_r($pluginAction));

        $action = '';
        if ( isset( $get_array['action'] ) && '-1' != $get_array['action'] ) {
            $action = $get_array['action'];
        } elseif ( isset( $post_array['action'] ) && '-1' != $post_array['action'] ) {
            $action = $post_array['action'];
        }

        if ( isset( $get_array['action2'] ) && '-1' != $get_array['action2'] ) {
            $action = $get_array['action2'];
        } elseif ( isset( $post_array['action2'] ) && '-1' != $post_array['action2'] ) {
            $action = $post_array['action2'];
        }

        $actype = '';
        if ( ! empty( $script_name ) ) {
            $actype = basename( $script_name, '.php' );
        }

        $is_plugins = 'plugins' === $actype;

        // Install plugin.
        if ( in_array( $action, array( 'install-plugin', 'upload-plugin', 'run_addon_install' ) ) && current_user_can( 'install_plugins' ) ) {


            $plugin = array_values( array_diff( array_keys( get_plugins() ), array_keys( $this->old_plugins ) ) );




            /*if ( count( $plugin ) != 1 ) {
                return;
            }*/

            $plugin_path = $plugin[0];

            //exit(print_r($plugin_path));

            //$plugin      = get_plugins();
            //$plugin      = $plugin[ $plugin_path ];

            $plugin_path = $plugin[0];
            $plugin      = get_plugins();
            $plugin      = $plugin[ $plugin_path ];

            // Get plugin directory name.
            $plugin_dir = $this->get_plugin_dir( $plugin_path );

            $plugin_path = plugin_dir_path( WP_PLUGIN_DIR . '/' . $plugin_path[0] );



            /*$log[] = array(
                5000,
                array(
                    'Plugin' => (object) array(
                        'Name'            => $plugin['Name'],
                        'PluginURI'       => $plugin['PluginURI'],
                        'Version'         => $plugin['Version'],
                        'Author'          => $plugin['Author'],
                        'Network'         => $plugin['Network'] ? 'True' : 'False',
                        'plugin_dir_path' => $plugin_path,
                    ),
                )
            );*/

            $date_format = get_option( 'date_format' ) . ' · ' . get_option( 'time_format' );
            $log[] = array (
                'website_name' => get_bloginfo('name'),
                'website_url' => get_site_url(),
                'plugin_name'  => $plugin['Name'],
                'plugin_version'  => $plugin['Version'],
                'status'  => $pluginAction,
                'user'    => esc_html( wp_get_current_user()->display_name ),
                'network_wide' => $plugin['Network'] ? true : '',
            );

            //exit(print_r($log));

            $this->logToAPI( $log );

            //$this->run_addon_check( $plugin_dir );
        }

        // Activate plugin.
        if ( $is_plugins && in_array( $action, array( 'activate', 'activate-selected' ) ) && current_user_can( 'activate_plugins' ) ) {
            // Check $_GET array case.
            if ( isset( $get_array['plugin'] ) ) {
                if ( ! isset( $get_array['checked'] ) ) {
                    $get_array['checked'] = array();
                }
                $get_array['checked'][] = $get_array['plugin'];
            }

            // Check $_POST array case.
            if ( isset( $post_array['plugin'] ) ) {
                if ( ! isset( $post_array['checked'] ) ) {
                    $post_array['checked'] = array();
                }
                $post_array['checked'][] = $post_array['plugin'];
            }

            if ( isset( $get_array['checked'] ) && ! empty( $get_array['checked'] ) ) {
                //$latest_event = $this->plugin->alerts->get_latest_events();
                //$latest_event = isset( $latest_event[0] ) ? $latest_event[0] : false;
                //$event_meta   = $latest_event ? $latest_event->GetMetaArray() : false;

                foreach ( $get_array['checked'] as $plugin_file ) {
                    /*if ( $latest_event && 5001 === $latest_event->alert_id && $event_meta && isset( $event_meta['PluginFile'] ) ) {
                        if ( basename( WSAL_BASE_NAME ) === basename( $event_meta['PluginFile'] ) ) {
                            continue;
                        }
                    }*/

                    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_file;
                    $plugin = get_plugin_data( $plugin_file, false, true );

                    $log[] = array (
                        'website_name' => get_bloginfo('name'),
                        'website_url' => get_site_url(),
                        'plugin_name'  => $plugin['Name'],
                        'plugin_version'  => $plugin['Version'],
                        'status'  => $pluginAction,
                        'user'    => esc_html( wp_get_current_user()->display_name ),
                        'network_wide' => $plugin['Network'] ? true : '',
                    );

                    $this->logToAPI( $log );

                    //$this->run_addon_check( $plugin_file );
                }
            } elseif ( isset( $post_array['checked'] ) && ! empty( $post_array['checked'] ) ) {
                foreach ( $post_array['checked'] as $plugin_file ) {
                    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_file;
                    $plugin_data = get_plugin_data( $plugin_file, false, true );

                    $log[] = array (
                        'website_name' => get_bloginfo('name'),
                        'website_url' => get_site_url(),
                        'plugin_name'  => $plugin['Name'],
                        'plugin_version'  => $plugin['Version'],
                        'status'  => $pluginAction,
                        'user'    => esc_html( wp_get_current_user()->display_name ),
                        'network_wide' => $plugin['Network'] ? true : '',
                    );

                    $this->logToAPI( $log );

                    //$this->run_addon_check( $plugin_file );
                }
            }
        }

        // Deactivate plugin.
        if ( $is_plugins && in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) && current_user_can( 'activate_plugins' ) ) {
            // Check $_GET array case.
            if ( isset( $get_array['plugin'] ) ) {
                if ( ! isset( $get_array['checked'] ) ) {
                    $get_array['checked'] = array();
                }
                $get_array['checked'][] = $get_array['plugin'];
            }

            // Check $_POST array case.
            if ( isset( $post_array['plugin'] ) ) {
                if ( ! isset( $post_array['checked'] ) ) {
                    $post_array['checked'] = array();
                }
                $post_array['checked'][] = $post_array['plugin'];
            }

            if ( isset( $get_array['checked'] ) && ! empty( $get_array['checked'] ) ) {
                foreach ( $get_array['checked'] as $plugin_file ) {
                    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_file;
                    $plugin = get_plugin_data( $plugin_file, false, true );
                    $log[] = array (
                        'website_name' => get_bloginfo('name'),
                        'website_url' => get_site_url(),
                        'plugin_name'  => $plugin['Name'],
                        'plugin_version'  => $plugin['Version'],
                        'status'  => $pluginAction,
                        'user'    => esc_html( wp_get_current_user()->display_name ),
                        'network_wide' => $plugin['Network'] ? true : '',
                    );

                    $this->logToAPI( $log );
                }
            } elseif ( isset( $post_array['checked'] ) && ! empty( $post_array['checked'] ) ) {
                foreach ( $post_array['checked'] as $plugin_file ) {
                    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_file;
                    $plugin = get_plugin_data( $plugin_file, false, true );
                    $log[] = array (
                        'website_name' => get_bloginfo('name'),
                        'website_url' => get_site_url(),
                        'plugin_name'  => $plugin['Name'],
                        'plugin_version'  => $plugin['Version'],
                        'status'  => $pluginAction,
                        'user'    => esc_html( wp_get_current_user()->display_name ),
                        'network_wide' => $plugin['Network'] ? true : '',
                    );

                    $this->logToAPI( $log );
                    //WSAL_Sensors_PluginsThemes::run_addon_removal_check( $plugin_file );
                }
            }
        }

        // Uninstall plugin.
        if ( $is_plugins && in_array( $action, array( 'delete-selected' ) ) && current_user_can( 'delete_plugins' ) ) {
            if ( ! isset( $post_array['verify-delete'] ) ) {
                // First step, before user approves deletion
                // TODO store plugin data in session here.
            } else {
                // second step, after deletion approval
                // TODO use plugin data from session.
                foreach ( $post_array['checked'] as $plugin_file ) {
                    $plugin_name = basename( $plugin_file, '.php' );
                    $plugin_name = str_replace( array( '_', '-', '  ' ), ' ', $plugin_name );
                    $plugin_name = ucwords( $plugin_name );
                    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_file;
                    $plugin = get_plugin_data( $plugin_file, false, true );
                    $log[] = array (
                        'website_name' => get_bloginfo('name'),
                        'website_url' => get_site_url(),
                        'plugin_name'  => $plugin['Name'],
                        'plugin_version'  => $plugin['Version'],
                        'status'  => $pluginAction,
                        'user'    => esc_html( wp_get_current_user()->display_name ),
                        'network_wide' => $plugin['Network'] ? true : '',
                    );

                    $this->logToAPI( $log );
                }
            }
        }

        // Uninstall plugin for WordPress version 4.6.
        if ( in_array( $action, array( 'delete-plugin' ) ) && current_user_can( 'delete_plugins' ) ) {
            if ( isset( $post_array['plugin'] ) ) {
                $plugin_file = WP_PLUGIN_DIR . '/' . $post_array['plugin'];
                $plugin_name = basename( $plugin_file, '.php' );
                $plugin_name = str_replace( array( '_', '-', '  ' ), ' ', $plugin_name );
                $plugin_name = ucwords( $plugin_name );
                $plugin = $this->old_plugins[ $post_array['plugin'] ];

                $log[] = array (
                    'website_name' => get_bloginfo('name'),
                    'website_url' => get_site_url(),
                    'plugin_name'  => $plugin['Name'],
                    'plugin_version'  => $plugin['Version'],
                    'status'  => $pluginAction,
                    'user'    => esc_html( wp_get_current_user()->display_name ),
                    'network_wide' => $plugin['Network'] ? true : '',
                );

                $this->logToAPI( $log );
            }
        }

        // Upgrade plugin.
        if ( in_array( $action, array( 'upgrade-plugin', 'update-plugin', 'update-selected' ) ) && current_user_can( 'update_plugins' ) ) {
            $plugins = array();

            // Check $_GET array cases.
            if ( isset( $get_array['plugins'] ) ) {
                $plugins = explode( ',', $get_array['plugins'] );
            } elseif ( isset( $get_array['plugin'] ) ) {
                $plugins[] = $get_array['plugin'];
            }

            // Check $_POST array cases.
            if ( isset( $post_array['plugins'] ) ) {
                $plugins = explode( ',', $post_array['plugins'] );
            } elseif ( isset( $post_array['plugin'] ) ) {
                $plugins[] = $post_array['plugin'];
            }
            if ( isset( $plugins ) ) {
                foreach ( $plugins as $plugin_file ) {
                    $this->LogPluginUpdatedEvent( $pluginAction, $plugin_file, $this->old_plugins );
                }
            }
        }

        // Update theme.
        if ( in_array( $action, array( 'upgrade-theme', 'update-theme', 'update-selected-themes' ) ) && current_user_can( 'install_themes' ) ) {
            // Themes.
            $themes = array();

            // Check $_GET array cases.
            if ( isset( $get_array['slug'] ) || isset( $get_array['theme'] ) ) {
                $themes[] = isset( $get_array['slug'] ) ? $get_array['slug'] : $get_array['theme'];
            } elseif ( isset( $get_array['themes'] ) ) {
                $themes = explode( ',', $get_array['themes'] );
            }

            // Check $_POST array cases.
            if ( isset( $post_array['slug'] ) || isset( $post_array['theme'] ) ) {
                $themes[] = isset( $post_array['slug'] ) ? $post_array['slug'] : $post_array['theme'];
            } elseif ( isset( $post_array['themes'] ) ) {
                $themes = explode( ',', $post_array['themes'] );
            }
            if ( isset( $themes ) ) {
                foreach ( $themes as $theme_name ) {
                    WSAL_Sensors_PluginsThemes::LogThemeUpdatedEvent( $theme_name );
                }
            }
        }

        // Install theme.
        if ( in_array( $action, array( 'install-theme', 'upload-theme' ) ) && current_user_can( 'install_themes' ) ) {
            $themes = array_diff( wp_get_themes(), $this->old_themes );
            foreach ( $themes as $name => $theme ) {
                $this->plugin->alerts->Trigger(
                    5005,
                    array(
                        'Theme' => (object) array(
                            'Name'                   => $theme->Name,
                            'ThemeURI'               => $theme->ThemeURI,
                            'Description'            => $theme->Description,
                            'Author'                 => $theme->Author,
                            'Version'                => $theme->Version,
                            'get_template_directory' => $theme->get_template_directory(),
                        ),
                    )
                );
            }
        }

        // Uninstall theme.
        if ( in_array( $action, array( 'delete-theme' ) ) && current_user_can( 'install_themes' ) ) {
            foreach ( $this->GetRemovedThemes() as $index => $theme ) {
                $this->plugin->alerts->Trigger(
                    5007,
                    array(
                        'Theme' => (object) array(
                            'Name'                   => $theme->Name,
                            'ThemeURI'               => $theme->ThemeURI,
                            'Description'            => $theme->Description,
                            'Author'                 => $theme->Author,
                            'Version'                => $theme->Version,
                            'get_template_directory' => $theme->get_template_directory(),
                        ),
                    )
                );
            }
        }
    }

    public static function get_plugin_dir( $plugin ) {
        $position = strpos( $plugin, '/' );
        if ( false !== $position ) {
            $plugin = substr_replace( $plugin, '', $position );
        }
        return $plugin;
    }

	public function logToAPI( $log )
    {
        $client = new Client();

        $client->request('GET', 'http://wp-logger-dashboard.test/api/log', [
            'query' => [
                'website_name'      => $log[0]['website_name'],
                'website_url'       => $log[0]['website_url'],
                'plugin_name'       => $log[0]['plugin_name'],
                'plugin_version'    => $log[0]['plugin_version'],
                'user'              => $log[0]['user'],
                'status'            => $log[0]['status']
            ]
        ]);
    }

	public function plugin_logger_updated( $upgrader_object, $options ) {

        if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {

            foreach( $options['plugins'] as $plugin ) {

                if( $plugin ) {
                    $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
                    $this->writeToFile( $plugin_data );
                }
            }
        }
    }

    public function plugin_logger( $plugin, $network_wide )
    {
        //$this->writeToFile();
        $log_size = 20;
        //$log      = get_option( 't5_plugin_log', array () );

        // Remove the oldest entry.
        //sizeof( $log ) > $log_size and array_shift( $log );

        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

        $post_array  = filter_input_array( INPUT_POST );
        $get_array   = filter_input_array( INPUT_GET );

        //exit(print_r($get_array));

        $date_format = get_option( 'date_format' ) . ' · ' . get_option( 'time_format' );
        $log[] = array (
            'website_name' => get_bloginfo('name'),
            'website_url' => get_site_url(),
            'plugin_name'  => $plugin_data['Name'],
            'plugin_version'  => $plugin_data['Version'],
            'status'  => 'deactivated_plugin' === current_filter() ? 'Deactivated' : 'Activated',
            'user'    => esc_html( wp_get_current_user()->display_name ),
            'network_wide' => $network_wide ? true : '',
        );

        //exit(print_r($log));

        $this->logToAPI( $log );
        //$this->writeToFile( $log );
    }

	public function writeToFile( $log )
    {
        $myfile = fopen(plugin_dir_path( __FILE__ ) . "../log.txt", "a") or die("Unable to open file!");

        fwrite($myfile, $log);
        fclose($myfile);
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
	 * - Plugin_Name_i18n. Defines internationalization functionality.
	 * - Plugin_Name_Admin. Defines all hooks for the admin area.
	 * - Plugin_Name_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

        /**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-plugin-name-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-plugin-name-i18n.php';

		$this->loader = new Plugin_Name_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Plugin_Name_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
