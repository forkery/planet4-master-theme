<?php

use P4EN\Controllers\Ensapi_Controller as ENS_API;

if ( ! class_exists( 'P4_Control_Panel' ) ) {

	/**
	 * Class P4_Control_Panel
	 */
	class P4_Control_Panel {

		/**
		 * P4_Control_Panel constructor.
		 */
		public function __construct() {
			$this->hooks();
		}

		/**
		 * Hooks actions and filters.
		 */
		public function hooks() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			add_action( 'wp_dashboard_setup',           array( $this, 'add_dashboard_widgets' ), 9 );
			add_action( 'wp_ajax_flush_cache',          array( $this, 'flush_cache' ) );
			add_action( 'wp_ajax_check_cache',          array( $this, 'check_cache' ) );
			add_action( 'wp_ajax_check_en',             array( $this, 'check_en' ) );
			add_action( 'wp_ajax_check_search_indexer', array( $this, 'check_search_indexer' ) );
			add_action( 'admin_enqueue_scripts',        array( $this, 'enqueue_admin_assets' ) );
		}

		/**
		 * .
		 */
		public function add_dashboard_widgets() {
			wp_add_dashboard_widget(
				'planet4_control_panel',
				__( 'Planet 4 Control Panel', 'planet4-master-theme' ),
				array( $this, 'add_control_panel' )
			);
		}

		/**
		 * Function that outputs the contents of the dashboard widget.
		 *
		 * @param $post
		 * @param $callback_args
		 */
		public function add_control_panel( $post, $callback_args ) {
			wp_nonce_field( 'cp-action' );
			$this->add_item_cache();
			$this->add_item_engagingnetworks();
			$this->add_item_searchwp();
		}

		/**
		 * .
		 */
		public function add_item_cache() {
			echo '<div id="control_panel_item_cache" class="cp-item">
					<div class="welcome-panel"><span><strong>' . esc_html__( 'Cache', 'planet4-master-theme' ) . '</strong></span>
						<div>
							<a href="#" class="btn btn-cp-action btn-flush-cache-async" data-action="flush_cache">' . esc_html__( 'Flush Object Cache', 'planet4-master-theme' ) . '</a>
							<span class="cp-response"></span>
							<div class="cp-fix-container">
								<button class="button cp-fix">' . esc_html__( 'Fix it!', 'planet4-master-theme' ) . '</button>
							</div>
						</div>
						<div>
							<a href="#" class="btn btn-cp-action btn-check-cache-async" data-action="check_cache">' . esc_html__( 'Check Object Cache', 'planet4-master-theme' ) . '</a>
							<span class="cp-response"></span>
							<div class="cp-fix-container">
								<button class="button cp-fix">' . esc_html__( 'Fix it!', 'planet4-master-theme' ) . '</button>
							</div>
						</div>
					</div>
				</div>';
		}

		/**
		 * .
		 */
		public function add_item_engagingnetworks() {
			echo '<div id="control_panel_item_en" class="cp-item">
					<div class="welcome-panel"><span><strong>' . esc_html__( 'Engaging Networks', 'planet4-master-theme' ) . '</strong></span>
						<div>
							<a href="#" class="btn btn-cp-action btn-check-en-async" data-action="check_en">' . esc_html__( 'Check Engaging Networks', 'planet4-master-theme' ) . '</a>
							<span class="cp-response"></span>
							<div class="cp-fix-container">
								<button class="button cp-fix">' . esc_html__( 'Fix it!', 'planet4-master-theme' ) . '</button>
							</div>
						</div>
					</div>
				</div>';
		}

		/**
		 * .
		 */
		public function add_item_searchwp() {
			echo '<div id="control_panel_item_en" class="cp-item">
					<div class="welcome-panel"><span><strong>' . esc_html__( 'SearchWP', 'planet4-master-theme' ) . '</strong></span>
						<div>
							<a href="#" class="btn btn-cp-action btn-check-en-async" data-action="check_search_indexer">' . esc_html__( 'Check Search Indexer', 'planet4-master-theme' ) . '</a>
							<span class="cp-response"></span>
							<div class="cp-fix-container">
								<button class="button cp-fix">' . esc_html__( 'Fix it!', 'planet4-master-theme' ) . '</button>
							</div>
						</div>
					</div>
				</div>';
		}

		/**
		 * Adds a flush cache button to delete all keys in Redis database.
		 */
		public function flush_cache() {
			if ( wp_doing_ajax() ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				if ( wp_verify_nonce( $_GET['_wpnonce'], 'cp-action' )  // CSRF protection.
				     && isset( $_GET['cp-action'] )
				     && 'flush_cache' === $_GET['cp-action']
				) {
					if ( wp_cache_flush() ) {
						$messages[] = __( 'Object Cache flushed', 'planet4-master-theme' );
						$class = 'cp-success';
					} else {
						$messages[] = __( 'Object Cache did not flush', 'planet4-master-theme' );
						$class = 'cp-error';
					}
					if ( $messages ) {
						echo wp_json_encode( [ $messages, $class ] );
					}
				}
				wp_die();
			}
		}

		/**
		 * Adds a check cache button to check connectivity to the Redis server.
		 */
		public function check_cache() {
			if ( wp_doing_ajax() ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				if ( wp_verify_nonce( $_GET['_wpnonce'], 'cp-action' )  // CSRF protection.
				     && isset( $_GET['cp-action'] )
				     && 'check_cache' === $_GET['cp-action']
				) {
					$messages = [];
					$info     = wp_redis_get_info();

					if ( $info instanceof WP_Error ) {
						if ( $info->errors['wp-redis'] ) {
							foreach ( $info->errors['wp-redis'] as $index => $error ) {
								$messages[] = $error;
							}
							$class = 'cp-error';
						}
					} elseif ( 'connected' === $info['status'] ) {
						$messages[] = __( 'Planet 4 is connected to Redis.', 'planet4-master-theme' );
						$class      = 'cp-success';
					}

					if ( $messages ) {
						echo wp_json_encode( [ $messages, $class ] );
					}
				}
				wp_die();
			}
		}

		/**
		 * Adds a check cache button to check the ENS API.
		 */
		public function check_en() {
			if ( wp_doing_ajax() ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				if ( wp_verify_nonce( $_GET['_wpnonce'], 'cp-action' )     // CSRF protection.
				     && isset( $_GET['cp-action'] )
				     && 'check_en' === $_GET['cp-action']
				) {
					$messages = [];
					$main_settings = get_option( 'p4en_main_settings' );

					if ( isset( $main_settings['p4en_private_api'] ) && $main_settings['p4en_private_api'] ) {
						$ens_api           = new ENS_API();
						$ens_private_token = $main_settings['p4en_private_api'];
						$response          = $ens_api->authenticate( $ens_private_token );

						if ( is_array( $response ) && $response['body'] ) {
							$messages[] = __( 'Success', 'planet4-master-theme' );
							$class      = 'cp-success';
						} elseif ( is_string( $response ) ) {
							$messages[] = $response;
							$class      = 'cp-error';
						}
					}

					if ( $messages ) {
						echo wp_json_encode( [ $messages, $class ] );
					}
				}
				wp_die();
			}
		}

		/**
		 * Adds a check button to check the Indexer of the SearchWP plugin.
		 */
		public function check_search_indexer() {
			if ( wp_doing_ajax() ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				if ( wp_verify_nonce( $_GET['_wpnonce'], 'cp-action' )     // CSRF protection.
				     && isset( $_GET['cp-action'] )
				     && 'check_search_indexer' === $_GET['cp-action']
				) {
					$threshold = 180;
					$messages  = [];

					$last_activity  = searchwp_get_setting( 'last_activity', 'stats' );
					$running        = searchwp_get_setting( 'running' );
					$doing_delta    = searchwp_get_option( 'doing_delta' );
					$busy           = searchwp_get_option( 'busy' );

					if ( ! is_null( $last_activity ) && false !== $last_activity ) {
						if (
							( current_time( 'timestamp' ) > $last_activity + absint( $threshold ) )
							&& ! $running && ! $doing_delta && ! $busy
						) {
							$messages[] = __( 'Indexer has stalled', 'planet4-master-theme' );
							$class = 'cp-error';
						} else {
							$messages[] = __( 'Indexer is awake', 'planet4-master-theme' );
							$class      = 'cp-success';
						}
					}

					if ( $messages ) {
						echo wp_json_encode( [ $messages, $class ] );
					}
				}
				wp_die();
			}
		}

		/**
		 * Load assets.
		 */
		public function enqueue_admin_assets() {
			if ( ! is_admin() || 'dashboard' !== get_current_screen()->base ) {
				return;
			}
			wp_enqueue_style( 'dashboard-style', get_template_directory_uri() . '/assets/css/dashboard.css', array(), '0.1.0' );
			wp_enqueue_script( 'dashboard-script', get_template_directory_uri() . '/assets/js/dashboard.js', array( 'jquery' ), '0.1.0', true );
		}
	}
}
