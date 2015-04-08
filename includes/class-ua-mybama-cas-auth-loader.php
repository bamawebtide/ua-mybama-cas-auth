<?php

/**
 * Register all actions, filters and shortcodes for the plugin
 *
 * @link       https://webtide.ua.edu
 * @since      1.0
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/includes
 */

/**
 * Register all actions, filters and shortcodes for the plugin.
 *
 * Maintain a list of all hooks and shortcodes that are registered
 * throughout the plugin, and register them with the WordPress API.
 * Call the run function to execute the list of actions and filters.
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/includes
 * @author     Rachel Carden <rmcarden@ur.ua.edu>
 */
class UA_myBama_CAS_Auth_Loader {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;
	
	/**
	 * The array of shortcodes registered with WordPress.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      array    $shortcodes    The shortcodes registered with WordPress to add when the plugin loads.
	 */
	protected $shortcodes;

	/**
	 * Initialize the collections used to maintain the actions, filters, and shortcodes.
	 *
	 * @since    1.0
	 */
	public function __construct() {

		$this->actions = array();
		$this->filters = array();
		$this->shortcodes = array();

	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    1.0
	 * @var      string               $hook             The name of the WordPress action that is being registered.
	 * @var      object               $component        A reference to the instance of the object on which the action is defined.
	 * @var      string               $callback         The name of the function definition on the $component.
	 * @var      int      Optional    $priority         The priority at which the function should be fired.
	 * @var      int      Optional    $accepted_args    The number of arguments that should be passed to the $callback.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since    1.0
	 * @var      string               $hook             The name of the WordPress filter that is being registered.
	 * @var      object               $component        A reference to the instance of the object on which the filter is defined.
	 * @var      string               $callback         The name of the function definition on the $component.
	 * @var      int      Optional    $priority         The priority at which the function should be fired.
	 * @var      int      Optional    $accepted_args    The number of arguments that should be passed to the $callback.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}
	
	/**
	 * Add a new shortcode to the collection to be registered with WordPress.
	 *
	 * @since    1.0
	 * @var      string               $tag             The name of the WordPress shortcode that is being registered.
	 * @var      object               $component       A reference to the instance of the object on which the action is defined.
	 * @var      string               $callback        The name of the function definition on the $component.
	 */
	public function add_shortcode( $tag, $component, $callback ) {
		
		$this->shortcodes[] = array(
			'tag'		=> $tag,
			'component'	=> $component,
			'callback'	=> $callback
			);
			
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      array                $hooks            The collection of hooks that is being registered (that is, actions or filters).
	 * @var      string               $hook             The name of the WordPress filter that is being registered.
	 * @var      object               $component        A reference to the instance of the object on which the filter is defined.
	 * @var      string               $callback         The name of the function definition on the $component.
	 * @var      int      Optional    $priority         The priority at which the function should be fired.
	 * @var      int      Optional    $accepted_args    The number of arguments that should be passed to the $callback.
	 * @return   type                                   The collection of actions and filters registered with WordPress.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args
		);

		return $hooks;

	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    1.0
	 */
	public function run() {

		foreach ( $this->filters as $hook ) {
			add_filter( $hook[ 'hook' ], array( $hook[ 'component' ], $hook[ 'callback' ] ), $hook[ 'priority' ], $hook[ 'accepted_args' ] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook[ 'hook' ], array( $hook[ 'component' ], $hook[ 'callback' ] ), $hook[ 'priority' ], $hook[ 'accepted_args' ] );
		}
		
		foreach ( $this->shortcodes as $shortcode ) {
			add_shortcode( $shortcode[ 'tag' ], array( $shortcode[ 'component' ], $shortcode[ 'callback' ] ) );
		}

	}

}