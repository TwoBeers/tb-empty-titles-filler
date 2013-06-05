<?php
/*
Plugin Name: TB Empty Titles Filler
Plugin URI: https://github.com/TwoBeers/tb-empty-titles-filler
Description: The plugin will fill every posts/pages empty title with a defined text. Uses the_title filter
Author: Jimo
Author URI: http://jimo.twbrs.net/
Version: 1.0
License: GNU General Public License, version 2
License URI: http: //www.gnu.org/licenses/gpl-2.0.html
*/

class TB_Empty_Titles_Filler {

	var $slug = 'tb_etf';

	var $option_key = '_options';

	var $options = array();

	/**
	 * Constructor
	 */
	function __construct() {
		$this->option_key = $this->slug . '_options';


		load_plugin_textdomain( 'tb_etf', '', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action( 'admin_init',				array( $this, 'options_init' )        );
		add_action( 'admin_menu',				array( $this, 'add_page'     )        );
		add_filter( 'the_title',				array( $this, 'fill_title'   ), 10, 2 );
	}

	/**
	 * Register the form setting for our options array.
	 *
	 * This function is attached to the admin_init action hook.
	 *
	 * This call to register_setting() registers a validation callback, validate(),
	 * which is used when the option is saved, to ensure that our option values are properly
	 * formatted, and safe.
	 */
	function options_init() {
		// Load our options for use in any method.
		$this->options = $this->get_plugin_options();

		// Register our option group.
		register_setting(
			$this->option_key,    // Options group, see settings_fields() call in render_page()
			$this->option_key,         // Database option, see get_plugin_options()
			array( $this, 'validate' ) // The sanitization callback, see validate()
		);

		// Register our settings field group.
		add_settings_section(
			'general',        // Unique identifier for the settings section
			'',               // Section title (we don't want one)
			'__return_false', // Section callback (we don't want anything)
			'plugin_options'   // Menu slug, used to uniquely identify the page; see add_page()
		);

		// Register our individual settings fields.
		add_settings_field(
			'title_format',										// Unique identifier for the field for this section
			__( 'Custom Title Format', 'tb_etf' ),					// Setting field label
			array( $this, 'settings_field_title_format' ),		// Function that renders the settings field
			'plugin_options',									// Menu slug, used to uniquely identify the page; see add_page()
			'general'											// Settings section. Same as the first argument in the add_settings_section() above
		);

		add_settings_field(
			'not_empty_titles',									// Unique identifier for the field for this section
			'',													// Setting field label
			array( $this, 'settings_field_not_empty_titles' ),	// Function that renders the settings field
			'plugin_options',									// Menu slug, used to uniquely identify the page; see add_page()
			'general'											// Settings section. Same as the first argument in the add_settings_section() above
		);
	}

	/**
	 * Add our plugin options page to the admin menu.
	 *
	 * This function is attached to the admin_menu action hook.
	 */
	function add_page() {
		$plugin_page = add_options_page(
			'TB Empty Titles Filler',		// Name of page
			'TB Empty Titles Filler',		// Label in menu
			'manage_options',					// Capability required
			'tb_etf_options',						// Menu slug, used to uniquely identify the page
			array(&$this, 'options_page')	// Function that renders the options page
		);
	}

	/**
	 * Returns the default options.
	 */
	function get_default_options() {
		$default_options = array(
			'title_format' => __( '(Set a Title Format)', 'tb_etf' ),
			'not_empty_titles' => false,
		);

		return apply_filters( 'tb_etf_default_options', $default_options );
	}

	/**
	 * Returns the options array.
	 */
	function get_plugin_options() {
		return get_option( $this->option_key, $this->get_default_options() );
	}

	/**
	 * Renders the setting field.
	 */
	function settings_field_title_format() {
		$options = $this->options;
		?>
		<label for="title-format">
			<input style="width: 400px;" type="text" name="<?php echo $this->option_key; ?>[title_format]" id="title-format" value="<?php echo esc_attr( $options['title_format'] ); ?>" />
		</label>
		<p>
			<?php _e( 'you may use these codes:', 'tb_etf' ); ?>
			<br><code>%d</code> <?php _e( 'date', 'tb_etf' ); ?>
			<br><code>%f</code> <?php _e( 'format (if any)', 'tb_etf' ); ?>
			<br><code>%n</code> <?php _e( 'id', 'tb_etf' ); ?>
			<br><code>%c</code> <?php _e( 'first category', 'tb_etf' ); ?>
		</p>
		<p>
			<?php _e( 'HTML tags allowed:', 'tb_etf'); ?> <code>em</code> <code>strong</code> <code>del</code> <code>ins</code> <code>img</code> <code>sub</code> <code>sup</code>
		</p>
		<?php
	}

	/**
	 * Renders the setting field.
	 */
	function settings_field_not_empty_titles() {
		$options = $this->options;
		?>
		<label for="not-empty-titles">
			<input type="checkbox" name="<?php echo $this->option_key; ?>[not_empty_titles]" id="not-empty-titles" <?php checked( $options['not_empty_titles'] ); ?> />
			<?php _e( 'Use the format even for not-empty titles. (highly not recommended)', 'tb_etf' );  ?>
		</label>
		<?php
	}

	/**
	 * Returns the options array.
	 */
	function options_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( 'Empty Titles Filler Options', 'tb_etf' ); ?></h2>

			<form method="post" action="options.php">
				<?php
					settings_fields( $this->option_key );
					do_settings_sections( 'plugin_options' );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize and validate form input. Accepts an array, return a sanitized array.
	 */
	function validate( $input ) {

		$input['title_format'] = wp_kses( $input['title_format'], array( 'em' => array(), 'strong' => array(), 'del' => array(), 'ins' => array(), 'sub' => array(), 'sup' => array(), 'img' => array( 'src' => array(), 'alt' => array() ) ) );
		if ( ! isset( $input['not_empty_titles'] ) )
			$input['not_empty_titles'] = false;
		$input['not_empty_titles'] = ( false != $input['not_empty_titles'] ? true : false );

		return $input;
	}

	/**
	 * Create the title.
	 */
	public function fill_title( $title, $id = null ) {
		global $post;

		$options = $this->get_plugin_options();

		if ( is_admin() ) return $title;

		if ( in_the_loop() ) $id = $post->ID;

		if ( $id == null ) return $title;

		if ( empty( $title ) || $options['not_empty_titles'] == true ) {
			$category = get_the_category( $id );
			$obj = get_post_type_object( get_post_type() );
			$postdata = array( get_post_format( $id )? get_post_format_string( get_post_format( $id ) ): $obj->labels->singular_name, get_the_time( get_option( 'date_format' ), $id ), $id, isset( $category[0] ) ? $category[0]->cat_name : '' );
			$codes = array( '%f', '%d', '%n', '%c' );
			return str_replace( $codes, $postdata, $options['title_format'] );
		} else
			return $title;
	}

}

new TB_Empty_Titles_Filler;
