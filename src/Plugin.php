<?php

namespace WPKB;

final class Plugin {

	/**
	 * @const Slug of the post type
	 */
	const POST_TYPE_NAME = 'wpkb-article';

	/**
	 * @const Slug of category taxonomy
	 */
	const TAXONOMY_CATEGORY_NAME = 'wpkb-category';

	/**
	 * @const Slug of keyword taxonomy
	 */
	const TAXONOMY_KEYWORD_NAME = 'wpkb-keyword';

	/**
	 * @var string
	 */
	private $version = '1.0';

	/**
	 * @var
	 */
	private $file;

	/**
	 * @var
	 */
	private $dir;

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @var string
	 */
	protected $post_type_slug = 'kb';

	/**
	 * @var Categories
	 */
	public $categories;

	/**
	 * Constructor
	 */
	public function __construct( $version, $file, $dir ) {
		$this->version = $version;
		$this->file = $file;
		$this->dir = $dir;

		if( defined( 'WPKB_POST_TYPE_SLUG' ) ) {
			$this->post_type_slug = WPKB_POST_TYPE_SLUG;
		}

		$this->options = $this->load_options();

		// init categories
		$this->categories = new Categories( self::POST_TYPE_NAME, $this->post_type_slug );
		$this->categories->add_hooks();

		if( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$admin = new Admin();
			$admin->add_hooks();
		}
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		// add actions
		add_action( 'init', array( $this, 'init' ) );

		// register (de)activation hooks
		register_activation_hook( $this->file, array( $this, 'on_plugin_activation' ) );
		register_deactivation_hook( $this->file, array( $this, 'on_plugin_deactivation' ) );
	}

	/**
	 * Registers all terms, taxonomy's and post types.
	 */
	public function init() {
		$this->register_taxonomies();
		$this->register_post_type();
	}

	/**
	 * Register KB taxonomies
	 */
	protected function register_taxonomies() {
		$labels = array(
			'name'              => __( 'KB Keywords', 'wp-knowledge-base' ),
			'singular_name'     => __( 'KB Keyword', 'wp-knowledge-base' ),
			'menu_name'         => __( 'KB Keywords' )
		);

		// register docs taxonomy: keyword
		register_taxonomy(
			self::TAXONOMY_KEYWORD_NAME,
			self::POST_TYPE_NAME,
			array(
				'labels' => $labels,
				'rewrite' => array( 'with_front' => false, 'slug' => $this->post_type_slug . '/keyword' ),
				'hierarchical' => false,
				'query_var' => true
			)
		);
	}

	/**
	 * Register KB post type
	 */
	protected function register_post_type() {

		$labels = array(
			'name'               => _x( 'KB Articles', 'post type general name', 'wp-knowledge-base' ),
			'singular_name'      => _x( 'KB Article', 'post type singular name', 'wp-knowledge-base' ),
			'new_item'           => __( 'New KB Article', 'wp-knowledge-base' ),
			'update_item'        => __( 'Update KB Article', 'wp-knowledge-base' ),
			'edit_item'          => __( 'Edit KB Article', 'wp-knowledge-base' ),
			'add_new_item'       => __( 'Add new KB Article', 'wp-knowledge-base' )
		);

		// register docs post type
		register_post_type(
			self::POST_TYPE_NAME,
			array(
				'public' => true,
				'labels' => $labels,
				'hierarchical' => true,
				'rewrite' => array( 'with_front' => false, 'slug' => $this->post_type_slug ),
				'taxonomies' => array( $this->categories->taxonomy_name, self::TAXONOMY_KEYWORD_NAME ),
				'has_archive' => ( Plugin::get_option( 'custom_archive_page_id' ) === 0 ),
				'menu_icon'   => 'dashicons-info',
				//'supports' => array( 'comments' ) //todo: finish migration to comments API & use that interface
			)
		);
	}

	/**
	 * Make sure rewrite rules are flushed on plugin activation
	 */
	public function on_plugin_activation() {
		add_action( 'shutdown', 'flush_rewrite_rules' );
	}

	/**
	 * Make sure rewrite rules are flushed again on plugin deactivation
	 */
	public function on_plugin_deactivation() {
		add_action( 'shutdown', 'flush_rewrite_rules' );
	}

	/**
	 * @param $index
	 *
	 * @return mixed
	 */
	public function get_option( $index ) {

		// does the option exist?
		if( isset( $this->options[ $index ] ) ) {
			return $this->options[ $index ];
		}

		// return queried option
		return null;
	}

	/**
	 * Loads the options, makes sure defaults are taken into considerations
	 */
	private function load_options() {

		$defaults = array(
			'custom_archive_page_id' => 42540
		);

		$options = get_option( 'wpkb', array() );

		// merge options with defaults
		$options = array_merge( $defaults, $options );

		return $options;
	}

	/**
	 * Return al WPKB extensions
	 *
	 * @return Plugin
	 */
	public function extensions() {
		$extensions = apply_filters( 'wpkb_extensions', array() );
		return (array) $extensions;
	}

	/**
	 * Return al WPKB extensions
	 *
	 * @return Plugin
	 */
	public function extension( $slug ) {
		$extensions = $this->extensions();

		if( isset( $extensions[ $slug ] ) ) {
			return $extensions[ $slug ];
		}

		return null;
	}


	/**
	 * @return string
	 */
	public function version() {
		return $this->version;
	}

	/**
	 * @return mixed
	 */
	public function file() {
		return $this->file();
	}

	/**
	 * @return mixed
	 */
	public function dir() {
		return $this->dir;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function url( $path = '' ) {
		return plugins_url( $path, $this->file );
	}

	/**
	 * @param $instance
	 * @param $name
	 */
	public function attach( $instance, $variable_name ) {
		$this->$variable_name = $instance;
	}
}