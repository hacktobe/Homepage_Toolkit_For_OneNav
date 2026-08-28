<?php
/**
 * Core plugin class.
 *
 * @package Homepage_Toolkit_For_OneNav
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HTFO_Plugin {

	/** @var HTFO_Plugin|null */
	private static $instance = null;

	/** @var string */
	private const OPTION_CONTAINER = 'io_get_option';

	/** @var string[] */
	private const OPTION_KEYS = array(
		'htfo_enabled',
		'htfo_menus',
		'htfo_dock_enabled',
		'htfo_dock_buttons',
	);

	/**
	 * Return the single plugin instance.
	 *
	 * @return HTFO_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register lifecycle hooks.
	 */
	private function __construct() {
		register_activation_hook( HTFO_FILE, array( $this, 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Set defaults and migrate settings retained from the private predecessor.
	 */
	public function activate() {
		$this->set_default_options();
	}

	/**
	 * Start the plugin when a compatible OneNav theme is active.
	 */
	public function init() {
		if ( ! $this->is_onenav_theme_active() ) {
			add_action( 'admin_notices', array( $this, 'render_theme_notice' ) );
			return;
		}

		$this->set_default_options();
		$this->register_features();
	}

	/**
	 * Detect OneNav and OneNav child themes without relying on a display name alone.
	 *
	 * @return bool
	 */
	private function is_onenav_theme_active() {
		$theme      = wp_get_theme();
		$candidates = array(
			$theme->get( 'Name' ),
			$theme->get_template(),
			$theme->get_stylesheet(),
		);
		$parent     = $theme->parent();

		if ( $parent ) {
			$candidates[] = $parent->get( 'Name' );
			$candidates[] = $parent->get_template();
		}

		foreach ( $candidates as $candidate ) {
			$normalized = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $candidate ) );
			if ( 'onenav' === $normalized ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Explain why the plugin is inactive without blocking WordPress activation.
	 */
	public function render_theme_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'YWDJDH Homepage Toolkit for OneNav is active, but its features are paused because the OneNav theme (or a OneNav child theme) is not active.', 'ywdjdh-homepage-toolkit-for-onenav' )
		);
	}

	/**
	 * Read the shared theme option safely.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_theme_option( $key, $default = null ) {
		if ( function_exists( 'io_get_option' ) ) {
			return io_get_option( $key, $default );
		}

		$options = get_option( self::OPTION_CONTAINER, array() );
		return is_array( $options ) && array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Add defaults and migrate only retained features from the original plugin.
	 */
	private function set_default_options() {
		$options = get_option( self::OPTION_CONTAINER, array() );
		$options = is_array( $options ) ? $options : array();
		$changed = false;

		$migrations = array(
			'htfo_enabled'      => 'opd_enabled',
			'htfo_menus'        => 'opd_menus',
			'htfo_dock_enabled' => 'opd_dock_enabled',
			'htfo_dock_buttons' => 'opd_dock_buttons',
		);

		foreach ( $migrations as $new_key => $old_key ) {
			if ( ! array_key_exists( $new_key, $options ) && array_key_exists( $old_key, $options ) ) {
				$options[ $new_key ] = $options[ $old_key ];
				$changed             = true;
			}
		}

		if ( ! array_key_exists( 'htfo_enabled', $options ) ) {
			$options['htfo_enabled'] = '1';
			$changed                 = true;
		}

		if ( ! isset( $options['htfo_menus'] ) || ! is_array( $options['htfo_menus'] ) ) {
			$options['htfo_menus'] = array(
				array(
					'title'     => __( 'Email', 'ywdjdh-homepage-toolkit-for-onenav' ),
					'icon_type' => 'theme',
					'icon'      => 'iconfont icon-xiaoxi',
					'items'     => array(
						array(
							'title'   => 'Gmail',
							'url'     => 'https://mail.google.com/',
							'target'  => '_blank',
							'tooltip' => __( 'Open Gmail', 'ywdjdh-homepage-toolkit-for-onenav' ),
						),
					),
				),
			);
			$changed = true;
		}

		if ( ! array_key_exists( 'htfo_dock_enabled', $options ) ) {
			$options['htfo_dock_enabled'] = '';
			$changed                      = true;
		}

		if ( ! isset( $options['htfo_dock_buttons'] ) || ! is_array( $options['htfo_dock_buttons'] ) ) {
			$options['htfo_dock_buttons'] = array(
				array(
					'title'     => __( 'Home', 'ywdjdh-homepage-toolkit-for-onenav' ),
					'url'       => home_url( '/' ),
					'target'    => '_self',
					'icon_type' => 'theme',
					'icon'      => 'iconfont icon-home',
				),
			);
			$changed = true;
		}

		if ( $changed ) {
			update_option( self::OPTION_CONTAINER, $options );
		}
	}

	/**
	 * Register theme integration, assets, and protected AJAX endpoints.
	 */
	private function register_features() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'io_header_after_code', array( $this, 'render_dropdown_menu' ), 15 );
		add_action( 'wp_footer', array( $this, 'render_dock' ), 99 );
		add_action( 'io_setting_option_after_code', array( $this, 'add_theme_settings' ), 20, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_htfo_export_settings', array( $this, 'ajax_export_settings' ) );
		add_action( 'wp_ajax_htfo_import_settings', array( $this, 'ajax_import_settings' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( HTFO_FILE ), array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Add a direct link to the OneNav settings screen on the Plugins page.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'admin.php?page=theme_settings' ) ),
			esc_html__( 'Settings', 'ywdjdh-homepage-toolkit-for-onenav' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Suggest accurate disclosure text in WordPress's privacy policy helper.
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p>' . esc_html__( 'YWDJDH Homepage Toolkit for OneNav stores visitor-created dock links in that visitor\'s browser localStorage. This data is not sent to WordPress or to the plugin author.', 'ywdjdh-homepage-toolkit-for-onenav' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If a remote icon URL is explicitly configured, the visitor\'s browser requests that image directly from the specified host. That request is subject to the host\'s privacy policy.', 'ywdjdh-homepage-toolkit-for-onenav' ) . '</p>';

		wp_add_privacy_policy_content( 'YWDJDH Homepage Toolkit for OneNav', wp_kses_post( $content ) );
	}

	/**
	 * Enqueue local frontend assets only when a feature can render.
	 */
	public function enqueue_frontend_assets() {
		$dropdown_enabled = $this->get_theme_option( 'htfo_enabled', true );
		$dock_enabled     = $this->get_theme_option( 'htfo_dock_enabled', false );

		if ( ! $dropdown_enabled && ! $dock_enabled ) {
			return;
		}

		wp_enqueue_style(
			'homepage-toolkit-for-onenav',
			HTFO_URL . 'assets/css/frontend.css',
			array(),
			HTFO_VERSION
		);

		if ( $dock_enabled ) {
			wp_enqueue_script(
				'homepage-toolkit-for-onenav',
				HTFO_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				HTFO_VERSION,
				true
			);
			wp_localize_script(
				'homepage-toolkit-for-onenav',
				'htfoDock',
				array(
					'storageKey'       => 'htfo_dock_custom_links',
					'legacyStorageKey' => 'ywdjdh_dock_custom_links',
					'invalidUrl'       => __( 'Enter a valid HTTP or HTTPS URL.', 'ywdjdh-homepage-toolkit-for-onenav' ),
				)
			);
		}
	}

	/**
	 * Enqueue backup UI assets on the OneNav theme settings page only.
	 *
	 * @param string $hook_suffix Admin hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( false === strpos( (string) $hook_suffix, 'theme_settings' ) ) {
			return;
		}

		wp_enqueue_style(
			'homepage-toolkit-for-onenav-admin',
			HTFO_URL . 'assets/css/admin.css',
			array(),
			HTFO_VERSION
		);
		wp_enqueue_script(
			'homepage-toolkit-for-onenav-admin',
			HTFO_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			HTFO_VERSION,
			true
		);
		wp_localize_script(
			'homepage-toolkit-for-onenav-admin',
			'htfoBackup',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'loading'       => __( 'Loading current settings...', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'loadFailed'    => __( 'Could not load settings. Try again.', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'networkFailed' => __( 'The request failed. Check your connection and try again.', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'copied'        => __( 'Copied!', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'copy'          => __( 'Copy to clipboard', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'fetching'      => __( 'Loading...', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'download'      => __( 'Download JSON file', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'emptyImport'   => __( 'Paste exported JSON before importing.', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'invalidJson'   => __( 'The JSON is invalid.', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'importing'     => __( 'Importing...', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'imported'      => __( 'Settings imported. Reloading...', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'importFailed'  => __( 'Import failed.', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'import'        => __( 'Import settings', 'ywdjdh-homepage-toolkit-for-onenav' ),
			)
		);
	}

	/**
	 * Export settings belonging to this plugin.
	 */
	public function ajax_export_settings() {
		check_ajax_referer( 'htfo_backup', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to export these settings.', 'ywdjdh-homepage-toolkit-for-onenav' ), 403 );
		}

		$options  = get_option( self::OPTION_CONTAINER, array() );
		$options  = is_array( $options ) ? $options : array();
		$settings = array_intersect_key( $options, array_flip( self::OPTION_KEYS ) );

		wp_send_json_success(
			array(
				'plugin'   => 'homepage-toolkit-for-onenav',
				'version'  => HTFO_VERSION,
				'settings' => $settings,
			)
		);
	}

	/**
	 * Import a validated settings backup.
	 */
	public function ajax_import_settings() {
		check_ajax_referer( 'htfo_backup', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to import these settings.', 'ywdjdh-homepage-toolkit-for-onenav' ), 403 );
		}

		$json = isset( $_POST['data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['data'] ) ) : '';
		$data = json_decode( (string) $json, true );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( __( 'The backup is not valid JSON.', 'ywdjdh-homepage-toolkit-for-onenav' ), 400 );
		}

		$settings = isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : $data;
		$settings = $this->sanitize_imported_settings( $settings );

		if ( empty( $settings ) ) {
			wp_send_json_error( __( 'The backup does not contain supported settings.', 'ywdjdh-homepage-toolkit-for-onenav' ), 400 );
		}

		$options = get_option( self::OPTION_CONTAINER, array() );
		$options = is_array( $options ) ? $options : array();

		foreach ( $settings as $key => $value ) {
			$options[ $key ] = $value;
		}

		update_option( self::OPTION_CONTAINER, $options );
		wp_send_json_success( __( 'Settings imported successfully.', 'ywdjdh-homepage-toolkit-for-onenav' ) );
	}

	/**
	 * Sanitize supported current and legacy backup fields.
	 *
	 * @param array $settings Untrusted settings.
	 * @return array
	 */
	private function sanitize_imported_settings( array $settings ) {
		$legacy_map = array(
			'opd_enabled'      => 'htfo_enabled',
			'opd_menus'        => 'htfo_menus',
			'opd_dock_enabled' => 'htfo_dock_enabled',
			'opd_dock_buttons' => 'htfo_dock_buttons',
		);

		foreach ( $legacy_map as $old_key => $new_key ) {
			if ( ! array_key_exists( $new_key, $settings ) && array_key_exists( $old_key, $settings ) ) {
				$settings[ $new_key ] = $settings[ $old_key ];
			}
		}

		$clean = array();

		if ( array_key_exists( 'htfo_enabled', $settings ) ) {
			$clean['htfo_enabled'] = $settings['htfo_enabled'] ? '1' : '';
		}
		if ( array_key_exists( 'htfo_dock_enabled', $settings ) ) {
			$clean['htfo_dock_enabled'] = $settings['htfo_dock_enabled'] ? '1' : '';
		}
		if ( isset( $settings['htfo_menus'] ) && is_array( $settings['htfo_menus'] ) ) {
			$clean['htfo_menus'] = array_values( array_filter( array_map( array( $this, 'sanitize_menu' ), $settings['htfo_menus'] ) ) );
		}
		if ( isset( $settings['htfo_dock_buttons'] ) && is_array( $settings['htfo_dock_buttons'] ) ) {
			$buttons = array_slice( $settings['htfo_dock_buttons'], 0, 10 );
			$clean['htfo_dock_buttons'] = array_values( array_filter( array_map( array( $this, 'sanitize_dock_button' ), $buttons ) ) );
		}

		return $clean;
	}

	/**
	 * Sanitize a dropdown menu and its links.
	 *
	 * @param mixed $menu Untrusted menu.
	 * @return array|null
	 */
	private function sanitize_menu( $menu ) {
		if ( ! is_array( $menu ) ) {
			return null;
		}

		$title = isset( $menu['title'] ) ? sanitize_text_field( $menu['title'] ) : '';
		if ( '' === $title ) {
			return null;
		}

		$clean = array(
			'title'       => $title,
			'icon_type'   => isset( $menu['icon_type'] ) && 'custom' === $menu['icon_type'] ? 'custom' : 'theme',
			'icon'        => isset( $menu['icon'] ) ? sanitize_text_field( $menu['icon'] ) : '',
			'icon_custom' => isset( $menu['icon_custom'] ) ? esc_url_raw( $menu['icon_custom'] ) : '',
			'items'       => array(),
		);

		if ( isset( $menu['items'] ) && is_array( $menu['items'] ) ) {
			foreach ( $menu['items'] as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$item_title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
				$item_url   = isset( $item['url'] ) ? esc_url_raw( $item['url'], array( 'http', 'https' ) ) : '';
				if ( '' === $item_title || '' === $item_url ) {
					continue;
				}
				$clean['items'][] = array(
					'title'   => $item_title,
					'url'     => $item_url,
					'target'  => isset( $item['target'] ) && '_self' === $item['target'] ? '_self' : '_blank',
					'tooltip' => isset( $item['tooltip'] ) ? sanitize_text_field( $item['tooltip'] ) : '',
				);
			}
		}

		return $clean;
	}

	/**
	 * Sanitize an administrator-configured dock button.
	 *
	 * @param mixed $button Untrusted button.
	 * @return array|null
	 */
	private function sanitize_dock_button( $button ) {
		if ( ! is_array( $button ) ) {
			return null;
		}

		$title = isset( $button['title'] ) ? sanitize_text_field( $button['title'] ) : '';
		$url   = isset( $button['url'] ) ? esc_url_raw( $button['url'], array( 'http', 'https' ) ) : '';
		if ( '' === $title || '' === $url ) {
			return null;
		}

		return array(
			'title'       => $title,
			'url'         => $url,
			'target'      => isset( $button['target'] ) && '_self' === $button['target'] ? '_self' : '_blank',
			'icon_type'   => isset( $button['icon_type'] ) && 'custom' === $button['icon_type'] ? 'custom' : 'theme',
			'icon'        => isset( $button['icon'] ) ? sanitize_text_field( $button['icon'] ) : '',
			'icon_custom' => isset( $button['icon_custom'] ) ? esc_url_raw( $button['icon_custom'] ) : '',
		);
	}

	/**
	 * Register fields inside the theme's existing settings panel.
	 *
	 * @param string $prefix Theme option prefix.
	 */
	public function add_theme_settings( $prefix = self::OPTION_CONTAINER ) {
		if ( ! class_exists( 'IOCF' ) ) {
			return;
		}

		$prefix = is_string( $prefix ) && '' !== $prefix ? $prefix : self::OPTION_CONTAINER;

		IOCF::createSection(
			$prefix,
			array(
				'title'  => __( 'YWDJDH Homepage Toolkit', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'icon'   => 'fas fa-list-ul',
				'fields' => array(
					array(
						'id'      => 'htfo_enabled',
						'type'    => 'switcher',
						'title'   => __( 'Enable homepage dropdown menus', 'ywdjdh-homepage-toolkit-for-onenav' ),
						'default' => true,
					),
					array(
						'id'           => 'htfo_menus',
						'type'         => 'group',
						'title'        => __( 'Dropdown menus', 'ywdjdh-homepage-toolkit-for-onenav' ),
						'dependency'   => array( 'htfo_enabled', '==', 'true' ),
						'button_title' => __( 'Add menu', 'ywdjdh-homepage-toolkit-for-onenav' ),
						'fields'       => array(
							array(
								'id'    => 'title',
								'type'  => 'text',
								'title' => __( 'Menu title', 'ywdjdh-homepage-toolkit-for-onenav' ),
							),
							array(
								'id'      => 'icon_type',
								'type'    => 'select',
								'title'   => __( 'Icon type', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'options' => array(
									'theme'  => __( 'Theme icon', 'ywdjdh-homepage-toolkit-for-onenav' ),
									'custom' => __( 'Uploaded image', 'ywdjdh-homepage-toolkit-for-onenav' ),
								),
								'default' => 'theme',
							),
							array(
								'id'         => 'icon',
								'type'       => 'icon',
								'title'      => __( 'Theme icon', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'dependency' => array( 'icon_type', '==', 'theme' ),
								'default'    => 'iconfont icon-version',
							),
							array(
								'id'         => 'icon_custom',
								'type'       => 'upload',
								'title'      => __( 'Custom icon', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'dependency' => array( 'icon_type', '==', 'custom' ),
								'library'    => array( 'image' ),
							),
							array(
								'id'           => 'items',
								'type'         => 'group',
								'title'        => __( 'Menu links', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'button_title' => __( 'Add link', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'fields'       => array(
									array(
										'id'    => 'title',
										'type'  => 'text',
										'title' => __( 'Link label', 'ywdjdh-homepage-toolkit-for-onenav' ),
									),
									array(
										'id'    => 'url',
										'type'  => 'text',
										'title' => __( 'URL', 'ywdjdh-homepage-toolkit-for-onenav' ),
									),
									array(
										'id'      => 'target',
										'type'    => 'select',
										'title'   => __( 'Open link in', 'ywdjdh-homepage-toolkit-for-onenav' ),
										'options' => array(
											'_blank' => __( 'New tab', 'ywdjdh-homepage-toolkit-for-onenav' ),
											'_self'  => __( 'Same tab', 'ywdjdh-homepage-toolkit-for-onenav' ),
										),
										'default' => '_blank',
									),
									array(
										'id'    => 'tooltip',
										'type'  => 'text',
										'title' => __( 'Tooltip', 'ywdjdh-homepage-toolkit-for-onenav' ),
									),
								),
							),
						),
					),
					array(
						'id'      => 'htfo_dock_enabled',
						'type'    => 'switcher',
						'title'   => __( 'Enable desktop dock', 'ywdjdh-homepage-toolkit-for-onenav' ),
						'default' => false,
					),
					array(
						'id'           => 'htfo_dock_buttons',
						'type'         => 'group',
						'title'        => __( 'Dock buttons', 'ywdjdh-homepage-toolkit-for-onenav' ),
						'dependency'   => array( 'htfo_dock_enabled', '==', 'true' ),
						'button_title' => __( 'Add button', 'ywdjdh-homepage-toolkit-for-onenav' ),
						'max'          => 10,
						'fields'       => array(
							array(
								'id'    => 'title',
								'type'  => 'text',
								'title' => __( 'Button label', 'ywdjdh-homepage-toolkit-for-onenav' ),
							),
							array(
								'id'    => 'url',
								'type'  => 'text',
								'title' => __( 'URL', 'ywdjdh-homepage-toolkit-for-onenav' ),
							),
							array(
								'id'      => 'target',
								'type'    => 'select',
								'title'   => __( 'Open link in', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'options' => array(
									'_blank' => __( 'New tab', 'ywdjdh-homepage-toolkit-for-onenav' ),
									'_self'  => __( 'Same tab', 'ywdjdh-homepage-toolkit-for-onenav' ),
								),
								'default' => '_blank',
							),
							array(
								'id'      => 'icon_type',
								'type'    => 'select',
								'title'   => __( 'Icon type', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'options' => array(
									'theme'  => __( 'Theme icon', 'ywdjdh-homepage-toolkit-for-onenav' ),
									'custom' => __( 'Uploaded image', 'ywdjdh-homepage-toolkit-for-onenav' ),
								),
								'default' => 'theme',
							),
							array(
								'id'         => 'icon',
								'type'       => 'icon',
								'title'      => __( 'Theme icon', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'dependency' => array( 'icon_type', '==', 'theme' ),
								'default'    => 'iconfont icon-home',
							),
							array(
								'id'         => 'icon_custom',
								'type'       => 'upload',
								'title'      => __( 'Custom icon', 'ywdjdh-homepage-toolkit-for-onenav' ),
								'dependency' => array( 'icon_type', '==', 'custom' ),
								'library'    => array( 'image' ),
							),
						),
					),
				),
			)
		);

		IOCF::createSection(
			$prefix,
			array(
				'title'  => __( 'YWDJDH Homepage Toolkit Backup', 'ywdjdh-homepage-toolkit-for-onenav' ),
				'icon'   => 'fas fa-save',
				'fields' => array(
					array(
						'type'    => 'notice',
						'style'   => 'info',
						'content' => __( 'This backup includes only this plugin\'s dropdown and dock settings.', 'ywdjdh-homepage-toolkit-for-onenav' ),
					),
					array(
						'type'     => 'callback',
						'function' => array( $this, 'render_backup_ui' ),
					),
				),
			)
		);
	}

	/**
	 * Render the nonce-protected backup interface.
	 */
	public function render_backup_ui() {
		?>
		<div class="htfo-backup" data-nonce="<?php echo esc_attr( wp_create_nonce( 'htfo_backup' ) ); ?>">
			<h4><?php esc_html_e( 'Export settings', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></h4>
			<p class="htfo-description"><?php esc_html_e( 'The export is refreshed directly from the database.', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></p>
			<textarea class="htfo-export-data" readonly placeholder="<?php esc_attr_e( 'Loading...', 'ywdjdh-homepage-toolkit-for-onenav' ); ?>"></textarea>
			<p class="htfo-export-status" aria-live="polite"></p>
			<div class="htfo-backup-actions">
				<button type="button" class="button htfo-refresh"><?php esc_html_e( 'Refresh', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></button>
				<button type="button" class="button button-primary htfo-copy"><?php esc_html_e( 'Copy to clipboard', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></button>
				<button type="button" class="button htfo-download"><?php esc_html_e( 'Download JSON file', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></button>
			</div>
			<hr>
			<h4><?php esc_html_e( 'Import settings', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></h4>
			<p class="htfo-description"><?php esc_html_e( 'Importing replaces only settings owned by this plugin. Legacy backups are also accepted.', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></p>
			<textarea class="htfo-import-data" placeholder="<?php esc_attr_e( 'Paste exported JSON here...', 'ywdjdh-homepage-toolkit-for-onenav' ); ?>"></textarea>
			<div class="htfo-backup-actions">
				<button type="button" class="button button-primary htfo-import"><?php esc_html_e( 'Import settings', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></button>
			</div>
			<div class="htfo-backup-message" aria-live="polite"></div>
		</div>
		<?php
	}

	/**
	 * Render configured dropdown menus on the homepage header.
	 */
	public function render_dropdown_menu() {
		if ( ! $this->get_theme_option( 'htfo_enabled', true ) || ( ! is_front_page() && ! is_home() ) ) {
			return;
		}

		$menus = $this->get_theme_option( 'htfo_menus', array() );
		if ( ! is_array( $menus ) ) {
			return;
		}

		echo '<nav class="htfo-menu-container" aria-label="' . esc_attr__( 'Homepage quick links', 'ywdjdh-homepage-toolkit-for-onenav' ) . '">';
		foreach ( $menus as $menu ) {
			if ( ! is_array( $menu ) || empty( $menu['title'] ) ) {
				continue;
			}

			$title       = sanitize_text_field( $menu['title'] );
			$icon_type   = isset( $menu['icon_type'] ) ? $menu['icon_type'] : 'theme';
			$icon         = isset( $menu['icon'] ) ? $this->sanitize_icon_classes( $menu['icon'], 'iconfont icon-version' ) : 'iconfont icon-version';
			$icon_custom  = isset( $menu['icon_custom'] ) ? $menu['icon_custom'] : '';
			$items        = isset( $menu['items'] ) && is_array( $menu['items'] ) ? $menu['items'] : array();
			$has_children = ! empty( $items );

			echo '<div class="htfo-dropdown">';
			echo '<button type="button" class="htfo-drop-button"' . ( $has_children ? ' aria-haspopup="true"' : '' ) . '>';
			if ( 'custom' === $icon_type && $icon_custom ) {
				echo '<img src="' . esc_url( $icon_custom, array( 'http', 'https' ) ) . '" alt="" class="htfo-custom-icon">';
			} else {
				echo '<i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i>';
			}
			echo '<span>' . esc_html( $title ) . '</span></button>';

			if ( $has_children ) {
				echo '<ul class="htfo-sub-menu">';
				foreach ( $items as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$item_title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
					$item_url   = isset( $item['url'] ) ? esc_url( $item['url'], array( 'http', 'https' ) ) : '';
					if ( '' === $item_title || '' === $item_url ) {
						continue;
					}
					$target  = isset( $item['target'] ) && '_self' === $item['target'] ? '_self' : '_blank';
					$tooltip = ! empty( $item['tooltip'] ) ? sanitize_text_field( $item['tooltip'] ) : $item_title;
					echo '<li><a href="' . esc_url( $item_url, array( 'http', 'https' ) ) . '" target="' . esc_attr( $target ) . '" title="' . esc_attr( $tooltip ) . '"';
					if ( '_blank' === $target ) {
						echo ' rel="noopener noreferrer"';
					}
					echo '>' . esc_html( $item_title ) . '</a></li>';
				}
				echo '</ul>';
			}
			echo '</div>';
		}
		echo '</nav>';
	}

	/**
	 * Render the optional desktop dock and accessible dialogs.
	 */
	public function render_dock() {
		if ( ! $this->get_theme_option( 'htfo_dock_enabled', false ) ) {
			return;
		}

		$buttons = $this->get_theme_option( 'htfo_dock_buttons', array() );
		$buttons = is_array( $buttons ) ? array_slice( $buttons, 0, 10 ) : array();
		?>
		<div class="htfo-dock-container">
			<div class="htfo-dock">
				<ul class="htfo-dock-icons">
					<?php foreach ( $buttons as $button ) : ?>
						<?php
						if ( ! is_array( $button ) ) {
							continue;
						}
						$title = isset( $button['title'] ) ? sanitize_text_field( $button['title'] ) : '';
						$url   = isset( $button['url'] ) ? esc_url( $button['url'], array( 'http', 'https' ) ) : '';
						if ( '' === $title || '' === $url ) {
							continue;
						}
						$target      = isset( $button['target'] ) && '_self' === $button['target'] ? '_self' : '_blank';
						$icon_type   = isset( $button['icon_type'] ) ? $button['icon_type'] : 'theme';
						$icon         = isset( $button['icon'] ) ? $this->sanitize_icon_classes( $button['icon'], 'iconfont icon-home' ) : 'iconfont icon-home';
						$icon_custom  = isset( $button['icon_custom'] ) ? $button['icon_custom'] : '';
						?>
						<li class="htfo-dock-icon htfo-system-icon" data-title="<?php echo esc_attr( $title ); ?>">
							<a href="<?php echo esc_url( $url, array( 'http', 'https' ) ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php echo '_blank' === $target ? ' rel="noopener noreferrer"' : ''; ?>>
								<?php if ( 'custom' === $icon_type && $icon_custom ) : ?>
									<img src="<?php echo esc_url( $icon_custom, array( 'http', 'https' ) ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
								<?php else : ?>
									<i class="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i><span class="screen-reader-text"><?php echo esc_html( $title ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
					<li class="htfo-dock-divider" aria-hidden="true"></li>
					<li class="htfo-dock-icon" data-title="<?php esc_attr_e( 'Add link', 'ywdjdh-homepage-toolkit-for-onenav' ); ?>">
						<button type="button" id="htfo-add-button" aria-haspopup="dialog"><i class="fa fa-plus" aria-hidden="true"></i><span class="screen-reader-text"><?php esc_html_e( 'Add link', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></span></button>
					</li>
					<li class="htfo-dock-icon" data-title="<?php esc_attr_e( 'Collapse dock', 'ywdjdh-homepage-toolkit-for-onenav' ); ?>">
						<button type="button" id="htfo-collapse-button"><i class="fa fa-compress" aria-hidden="true"></i><span class="screen-reader-text"><?php esc_html_e( 'Collapse dock', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></span></button>
					</li>
				</ul>
			</div>
		</div>
		<button type="button" class="htfo-expand-dock" id="htfo-expand-dock" aria-label="<?php esc_attr_e( 'Expand dock', 'ywdjdh-homepage-toolkit-for-onenav' ); ?>"><i class="fa fa-angle-right" aria-hidden="true"></i></button>

		<div class="htfo-modal-overlay" id="htfo-add-overlay" role="dialog" aria-modal="true" aria-labelledby="htfo-add-title" hidden>
			<div class="htfo-modal-content">
				<button type="button" class="htfo-modal-close" id="htfo-modal-close" aria-label="<?php esc_attr_e( 'Close', 'ywdjdh-homepage-toolkit-for-onenav' ); ?>">&times;</button>
				<h2 id="htfo-add-title"><?php esc_html_e( 'Add dock link', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></h2>
				<p><?php esc_html_e( 'Custom links are stored only in this browser. Right-click a custom icon to remove it.', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></p>
				<form id="htfo-link-form">
					<label for="htfo-add-url"><?php esc_html_e( 'Website URL', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></label>
					<input type="url" id="htfo-add-url" placeholder="https://example.com/" required>
					<label for="htfo-add-name"><?php esc_html_e( 'Website name', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></label>
					<input type="text" id="htfo-add-name" maxlength="100" required>
					<label for="htfo-add-icon"><?php esc_html_e( 'Icon URL (optional)', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></label>
					<input type="url" id="htfo-add-icon" placeholder="https://example.com/icon.png">
					<p class="htfo-form-error" aria-live="polite"></p>
					<button type="submit" class="htfo-submit-button"><?php esc_html_e( 'Add link', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></button>
				</form>
			</div>
		</div>

		<div class="htfo-modal-overlay" id="htfo-delete-overlay" role="dialog" aria-modal="true" aria-labelledby="htfo-delete-title" hidden>
			<div class="htfo-delete-dialog">
				<p id="htfo-delete-title"><?php esc_html_e( 'Remove this custom dock link?', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></p>
				<div>
					<button type="button" id="htfo-confirm-delete"><?php esc_html_e( 'Remove', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></button>
					<button type="button" id="htfo-cancel-delete"><?php esc_html_e( 'Cancel', 'ywdjdh-homepage-toolkit-for-onenav' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Keep only characters expected in OneNav and Font Awesome icon class lists.
	 *
	 * @param string $classes  Untrusted classes.
	 * @param string $fallback Fallback classes.
	 * @return string
	 */
	private function sanitize_icon_classes( $classes, $fallback ) {
		$classes = preg_replace( '/[^A-Za-z0-9_\- ]/', '', (string) $classes );
		$classes = trim( preg_replace( '/\s+/', ' ', $classes ) );

		return '' !== $classes ? $classes : $fallback;
	}
}
