<?php
/**
 * Typography control class.
 *
 * @since  1.0.0
 * @access public
 */

class Industrial_Lite_Control_Typography extends WP_Customize_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'typography';

	/**
	 * Array 
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $l10n = array();

	/**
	 * Set up our control.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $manager
	 * @param  string  $id
	 * @param  array   $args
	 * @return void
	 */
	public function __construct( $manager, $id, $args = array() ) {

		// Let the parent class do its thing.
		parent::__construct( $manager, $id, $args );

		// Make sure we have labels.
		$this->l10n = wp_parse_args(
			$this->l10n,
			array(
				'color'       => esc_html__( 'Font Color', 'industrial-lite' ),
				'family'      => esc_html__( 'Font Family', 'industrial-lite' ),
				'size'        => esc_html__( 'Font Size',   'industrial-lite' ),
				'weight'      => esc_html__( 'Font Weight', 'industrial-lite' ),
				'style'       => esc_html__( 'Font Style',  'industrial-lite' ),
				'line_height' => esc_html__( 'Line Height', 'industrial-lite' ),
				'letter_spacing' => esc_html__( 'Letter Spacing', 'industrial-lite' ),
			)
		);
	}

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script( 'industrial-lite-ctypo-customize-controls' );
		wp_enqueue_style(  'industrial-lite-ctypo-customize-controls' );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function to_json() {
		parent::to_json();

		// Loop through each of the settings and set up the data for it.
		foreach ( $this->settings as $setting_key => $setting_id ) {

			$this->json[ $setting_key ] = array(
				'link'  => $this->get_link( $setting_key ),
				'value' => $this->value( $setting_key ),
				'label' => isset( $this->l10n[ $setting_key ] ) ? $this->l10n[ $setting_key ] : ''
			);

			if ( 'family' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_families();

			elseif ( 'weight' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_weight_choices();

			elseif ( 'style' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_style_choices();
		}
	}

	/**
	 * Underscore JS template to handle the control's output.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function content_template() { ?>

		<# if ( data.label ) { #>
			<span class="customize-control-title">{{ data.label }}</span>
		<# } #>

		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>

		<ul>

		<# if ( data.family && data.family.choices ) { #>

			<li class="typography-font-family">

				<# if ( data.family.label ) { #>
					<span class="customize-control-title">{{ data.family.label }}</span>
				<# } #>

				<select {{{ data.family.link }}}>

					<# _.each( data.family.choices, function( label, choice ) { #>
						<option value="{{ choice }}" <# if ( choice === data.family.value ) { #> selected="selected" <# } #>>{{ label }}</option>
					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.weight && data.weight.choices ) { #>

			<li class="typography-font-weight">

				<# if ( data.weight.label ) { #>
					<span class="customize-control-title">{{ data.weight.label }}</span>
				<# } #>

				<select {{{ data.weight.link }}}>

					<# _.each( data.weight.choices, function( label, choice ) { #>

						<option value="{{ choice }}" <# if ( choice === data.weight.value ) { #> selected="selected" <# } #>>{{ label }}</option>

					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.style && data.style.choices ) { #>

			<li class="typography-font-style">

				<# if ( data.style.label ) { #>
					<span class="customize-control-title">{{ data.style.label }}</span>
				<# } #>

				<select {{{ data.style.link }}}>

					<# _.each( data.style.choices, function( label, choice ) { #>

						<option value="{{ choice }}" <# if ( choice === data.style.value ) { #> selected="selected" <# } #>>{{ label }}</option>

					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.size ) { #>

			<li class="typography-font-size">

				<# if ( data.size.label ) { #>
					<span class="customize-control-title">{{ data.size.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.size.link }}} value="{{ data.size.value }}" />

			</li>
		<# } #>

		<# if ( data.line_height ) { #>

			<li class="typography-line-height">

				<# if ( data.line_height.label ) { #>
					<span class="customize-control-title">{{ data.line_height.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.line_height.link }}} value="{{ data.line_height.value }}" />

			</li>
		<# } #>

		<# if ( data.letter_spacing ) { #>

			<li class="typography-letter-spacing">

				<# if ( data.letter_spacing.label ) { #>
					<span class="customize-control-title">{{ data.letter_spacing.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.letter_spacing.link }}} value="{{ data.letter_spacing.value }}" />

			</li>
		<# } #>

		</ul>
	<?php }

	/**
	 * Returns the available fonts.  Fonts should have available weights, styles, and subsets.
	 *
	 * @todo Integrate with Google fonts.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_fonts() { return array(); }

	/**
	 * Returns the available font families.
	 *
	 * @todo Pull families from `get_fonts()`.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	function get_font_families() {

		return array(
			'' => __( 'No Fonts', 'industrial-lite' ),
        'Abril Fatface' => __( 'Abril Fatface', 'industrial-lite' ),
        'Acme' => __( 'Acme', 'industrial-lite' ),
        'Anton' => __( 'Anton', 'industrial-lite' ),
        'Architects Daughter' => __( 'Architects Daughter', 'industrial-lite' ),
        'Arimo' => __( 'Arimo', 'industrial-lite' ),
        'Arsenal' => __( 'Arsenal', 'industrial-lite' ),
        'Arvo' => __( 'Arvo', 'industrial-lite' ),
        'Alegreya' => __( 'Alegreya', 'industrial-lite' ),
        'Alfa Slab One' => __( 'Alfa Slab One', 'industrial-lite' ),
        'Averia Serif Libre' => __( 'Averia Serif Libre', 'industrial-lite' ),
        'Bangers' => __( 'Bangers', 'industrial-lite' ),
        'Boogaloo' => __( 'Boogaloo', 'industrial-lite' ),
        'Bad Script' => __( 'Bad Script', 'industrial-lite' ),
        'Bitter' => __( 'Bitter', 'industrial-lite' ),
        'Bree Serif' => __( 'Bree Serif', 'industrial-lite' ),
        'BenchNine' => __( 'BenchNine', 'industrial-lite' ),
        'Cabin' => __( 'Cabin', 'industrial-lite' ),
        'Cardo' => __( 'Cardo', 'industrial-lite' ),
        'Courgette' => __( 'Courgette', 'industrial-lite' ),
        'Cherry Swash' => __( 'Cherry Swash', 'industrial-lite' ),
        'Cormorant Garamond' => __( 'Cormorant Garamond', 'industrial-lite' ),
        'Crimson Text' => __( 'Crimson Text', 'industrial-lite' ),
        'Cuprum' => __( 'Cuprum', 'industrial-lite' ),
        'Cookie' => __( 'Cookie', 'industrial-lite' ),
        'Chewy' => __( 'Chewy', 'industrial-lite' ),
        'Days One' => __( 'Days One', 'industrial-lite' ),
        'Dosis' => __( 'Dosis', 'industrial-lite' ),
        'Droid Sans' => __( 'Droid Sans', 'industrial-lite' ),
        'Economica' => __( 'Economica', 'industrial-lite' ),
        'Fredoka One' => __( 'Fredoka One', 'industrial-lite' ),
        'Fjalla One' => __( 'Fjalla One', 'industrial-lite' ),
        'Francois One' => __( 'Francois One', 'industrial-lite' ),
        'Frank Ruhl Libre' => __( 'Frank Ruhl Libre', 'industrial-lite' ),
        'Gloria Hallelujah' => __( 'Gloria Hallelujah', 'industrial-lite' ),
        'Great Vibes' => __( 'Great Vibes', 'industrial-lite' ),
        'Handlee' => __( 'Handlee', 'industrial-lite' ),
        'Hammersmith One' => __( 'Hammersmith One', 'industrial-lite' ),
        'Inconsolata' => __( 'Inconsolata', 'industrial-lite' ),
        'Indie Flower' => __( 'Indie Flower', 'industrial-lite' ),
        'IM Fell English SC' => __( 'IM Fell English SC', 'industrial-lite' ),
        'Julius Sans One' => __( 'Julius Sans One', 'industrial-lite' ),
        'Josefin Slab' => __( 'Josefin Slab', 'industrial-lite' ),
        'Josefin Sans' => __( 'Josefin Sans', 'industrial-lite' ),
        'Kanit' => __( 'Kanit', 'industrial-lite' ),
        'Lobster' => __( 'Lobster', 'industrial-lite' ),
        'Lato' => __( 'Lato', 'industrial-lite' ),
        'Lora' => __( 'Lora', 'industrial-lite' ),
        'Libre Baskerville' => __( 'Libre Baskerville', 'industrial-lite' ),
        'Lobster Two' => __( 'Lobster Two', 'industrial-lite' ),
        'Merriweather' => __( 'Merriweather', 'industrial-lite' ),
        'Monda' => __( 'Monda', 'industrial-lite' ),
        'Montserrat' => __( 'Montserrat', 'industrial-lite' ),
        'Muli' => __( 'Muli', 'industrial-lite' ),
        'Marck Script' => __( 'Marck Script', 'industrial-lite' ),
        'Noto Serif' => __( 'Noto Serif', 'industrial-lite' ),
        'Open Sans' => __( 'Open Sans', 'industrial-lite' ),
        'Overpass' => __( 'Overpass', 'industrial-lite' ),
        'Overpass Mono' => __( 'Overpass Mono', 'industrial-lite' ),
        'Oxygen' => __( 'Oxygen', 'industrial-lite' ),
        'Orbitron' => __( 'Orbitron', 'industrial-lite' ),
        'Patua One' => __( 'Patua One', 'industrial-lite' ),
        'Pacifico' => __( 'Pacifico', 'industrial-lite' ),
        'Padauk' => __( 'Padauk', 'industrial-lite' ),
        'Playball' => __( 'Playball', 'industrial-lite' ),
        'Playfair Display' => __( 'Playfair Display', 'industrial-lite' ),
        'PT Sans' => __( 'PT Sans', 'industrial-lite' ),
        'Philosopher' => __( 'Philosopher', 'industrial-lite' ),
        'Permanent Marker' => __( 'Permanent Marker', 'industrial-lite' ),
        'Poiret One' => __( 'Poiret One', 'industrial-lite' ),
        'Quicksand' => __( 'Quicksand', 'industrial-lite' ),
        'Quattrocento Sans' => __( 'Quattrocento Sans', 'industrial-lite' ),
        'Raleway' => __( 'Raleway', 'industrial-lite' ),
        'Rubik' => __( 'Rubik', 'industrial-lite' ),
        'Rokkitt' => __( 'Rokkitt', 'industrial-lite' ),
        'Russo One' => __( 'Russo One', 'industrial-lite' ),
        'Righteous' => __( 'Righteous', 'industrial-lite' ),
        'Slabo' => __( 'Slabo', 'industrial-lite' ),
        'Source Sans Pro' => __( 'Source Sans Pro', 'industrial-lite' ),
        'Shadows Into Light Two' => __( 'Shadows Into Light Two', 'industrial-lite'),
        'Shadows Into Light' => __( 'Shadows Into Light', 'industrial-lite' ),
        'Sacramento' => __( 'Sacramento', 'industrial-lite' ),
        'Shrikhand' => __( 'Shrikhand', 'industrial-lite' ),
        'Tangerine' => __( 'Tangerine', 'industrial-lite' ),
        'Ubuntu' => __( 'Ubuntu', 'industrial-lite' ),
        'VT323' => __( 'VT323', 'industrial-lite' ),
        'Varela Round' => __( 'Varela Round', 'industrial-lite' ),
        'Vampiro One' => __( 'Vampiro One', 'industrial-lite' ),
        'Vollkorn' => __( 'Vollkorn', 'industrial-lite' ),
        'Volkhov' => __( 'Volkhov', 'industrial-lite' ),
        'Yanone Kaffeesatz' => __( 'Yanone Kaffeesatz', 'industrial-lite' )
		);
	}

	/**
	 * Returns the available font weights.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_font_weight_choices() {

		return array(
			'' => esc_html__( 'No Fonts weight', 'industrial-lite' ),
			'100' => esc_html__( 'Thin',       'industrial-lite' ),
			'300' => esc_html__( 'Light',      'industrial-lite' ),
			'400' => esc_html__( 'Normal',     'industrial-lite' ),
			'500' => esc_html__( 'Medium',     'industrial-lite' ),
			'700' => esc_html__( 'Bold',       'industrial-lite' ),
			'900' => esc_html__( 'Ultra Bold', 'industrial-lite' ),
		);
	}

	/**
	 * Returns the available font styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_font_style_choices() {

		return array(
			'' => esc_html__( 'No Fonts Style', 'industrial-lite' ),
			'normal'  => esc_html__( 'Normal', 'industrial-lite' ),
			'italic'  => esc_html__( 'Italic', 'industrial-lite' ),
			'oblique' => esc_html__( 'Oblique', 'industrial-lite' )
		);
	}
}
