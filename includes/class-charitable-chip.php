<?php

/**
 * The main Charitable Chip class.
 *
 * The responsibility of this class is to load all the plugin's functionality.
 *
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
if (! class_exists('Charitable_Chip')) :

	/**
	 * Charitable_Chip
	 * 
	 * @since   1.0.0
	 */
	class Charitable_Chip
	{

		/**
		 * @var string
		 */
		const VERSION = '1.0';

		/**
		 * @var string  A date in the format: YYYYMMDD
		 */
		const DB_VERSION = '20241029';

		/**
		 * @var string The product name.
		 */
		const NAME = 'Chip for WP Charitable';

		/**
		 * @var string The product author.
		 */
		const AUTHOR = 'CHIP IN';

		/**
		 * @var Charitable_Chip
		 */
		private static $instance = null;

		/**
		 * The root file of the plugin.
		 *
		 * @var     string
		 * @access  private
		 */
		private $plugin_file;

		/**
		 * The root directory of the plugin.
		 *
		 * @var     string
		 * @access  private
		 */
		private $directory_path;

		/**
		 * The root directory of the plugin as a URL.
		 *
		 * @var     string
		 * @access  private
		 */
		private $directory_url;

		/**
		 * @var     array       Store of registered objects.
		 * @access  private
		 */
		private $registry;

		/**
		 * Create class instance.
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function __construct($plugin_file)
		{
			$this->plugin_file      = $plugin_file;
			$this->directory_path   = plugin_dir_path($plugin_file);
			$this->directory_url    = plugin_dir_url($plugin_file);

			add_action('charitable_start', array($this, 'start'), 1);
		}

		/**
		 * Returns the original instance of this class.
		 *
		 * @return  Charitable
		 * @since   1.0.0
		 */
		public static function get_instance()
		{
			return self::$instance;
		}

		/**
		 * Run the startup sequence on the charitable_start hook.
		 *
		 * This is only ever executed once.
		 *
		 * @return  void
		 * @access  public
		 * @since   1.0.0
		 */
		public function start()
		{
			// If we've already started (i.e. run this function once before), do not pass go.
			if ($this->started()) {
				return;
			}

			// Set static instance
			self::$instance = $this;

			$this->load_dependencies();

			$this->attach_hooks_and_filters();

			// Hook in here to do something when the plugin is first loaded.
			do_action('charitable_chip_start', $this);
		}

		/**
		 * Include necessary files.
		 *
		 * @return  void
		 * @access  private
		 * @since   1.0.0
		 */
		private function load_dependencies()
		{
			require_once($this->get_path('includes') . 'gateway/class-charitable-gateway-chip.php');
			require_once($this->get_path('includes') . 'gateway/charitable-chip-gateway-hooks.php');
			require_once($this->get_path('includes') . 'gateway/class-charitable-gateway-chip-callback-listener.php');
			require_once($this->get_path('includes') . 'chip.php');
		}

		/**
		 * Store CHIP public keys
		 */
		public static function store_public_key()
		{

			if ($settings = get_option('charitable_settings')) {

				// $settings = get_option('charitable_settings');

				if (isset($settings['gateways_chip']['brand_id']) && isset($settings['gateways_chip']['secret_key'])) {
					error_log('Setting Public Key option');

					// Check if secret_key and brand_id set
					$brand_id = $settings['gateways_chip']['brand_id'];
					$secret_key = $settings['gateways_chip']['secret_key'];

					$credentials = array(
						$secret_key,
						$brand_id
					);

					$chip = new Chip($credentials);

					$settings['gateways_chip']['public_key'] = $chip->public_key();

					update_option('charitable_settings', $settings);
				}
			}
		}


		/**
		 * Set up callbacks.
		 *
		 * @return  void
		 * @access  private
		 * @since   1.0.0
		 */
		private function attach_hooks_and_filters()
		{

			add_filter('plugin_action_links_' . plugin_basename($this->get_path()), array($this, 'add_plugin_action_links'));
			add_filter('charitable_payment_gateways', array($this, 'register_gateway'));
		}

		/**
		 * Add a direct link to the Payment Gateways tab, or straight through to the Chip settings if it's enabled.
		 *
		 * @param   string[] $links
		 * @return  string[]
		 * @access  public
		 * @since   1.0.0
		 */
		public function add_plugin_action_links($links)
		{
			$link = add_query_arg(array(
				'page'  => 'charitable-settings',
				'tab'   => 'gateways',
			), admin_url('admin.php'));

			$link_text = __('Settings', 'charitable-chip');

			if (charitable_get_helper('gateways')->is_active_gateway('chip')) {

				$link = add_query_arg(array(
					'group' => 'gateways_chip',
				), $link);
			}

			$links[] = "<a href=\"$link\">$link_text</a>";

			return $links;
		}

		/**
		 * Register the Chip payment gateway class.
		 *
		 * @param   string[]
		 * @return  string[]
		 * @access  public
		 * @since   1.0.0
		 */
		public function register_gateway($gateways)
		{
			$gateways['chip'] = 'Charitable_Gateway_Chip';
			return $gateways;
		}

		/**
		 * Returns whether we are currently in the start phase of the plugin.
		 *
		 * @return  bool
		 * @access  public
		 * @since   1.0.0
		 */
		public function is_start()
		{
			return current_filter() == 'charitable_chip_start';
		}

		/**
		 * Returns whether the plugin has already started.
		 *
		 * @return  bool
		 * @access  public
		 * @since   1.0.0
		 */
		public function started()
		{
			return did_action('charitable_chip_start') || current_filter() == 'charitable_chip_start';
		}

		/**
		 * Returns the plugin's version number.
		 *
		 * @return  string
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_version()
		{
			return self::VERSION;
		}

		/**
		 * Returns plugin paths.
		 *
		 * @param   string $path            // If empty, returns the path to the plugin.
		 * @param   bool $absolute_path     // If true, returns the file system path. If false, returns it as a URL.
		 * @return  string
		 * @since   1.0.0
		 */
		public function get_path($type = '', $absolute_path = true)
		{
			$base = $absolute_path ? $this->directory_path : $this->directory_url;

			switch ($type) {
				case 'includes':
					$path = $base . 'includes/';
					break;

				case 'templates':
					$path = $base . 'templates/';
					break;

				case 'directory':
					$path = $base;
					break;

				default:
					$path = $this->plugin_file;
			}

			return $path;
		}

		/**
		 * Stores an object in the plugin's registry.
		 *
		 * @param   mixed       $object
		 * @return  void
		 * @access  public
		 * @since   1.0.0
		 */
		public function register_object($object)
		{
			if (! is_object($object)) {
				return;
			}

			$class = get_class($object);

			$this->registry[$class] = $object;
		}

		/**
		 * Returns a registered object.
		 *
		 * @param   string      $class  The type of class you want to retrieve.
		 * @return  mixed               The object if its registered. Otherwise false.
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_object($class)
		{
			return isset($this->registry[$class]) ? $this->registry[$class] : false;
		}

		/**
		 * Throw error on object clone.
		 *
		 * This class is specifically designed to be instantiated once. You can retrieve the instance using charitable()
		 *
		 * @since   1.0.0
		 * @access  public
		 * @return  void
		 */
		public function __clone()
		{
			_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'charitable-chip'), '1.0.0');
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since   1.0.0
		 * @access  public
		 * @return  void
		 */
		public function __wakeup()
		{
			_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'charitable-chip'), '1.0.0');
		}
	}

endif; // End if class_exists check
