<?php
/**
 * Industrial Lite Theme Customizer
 *
 * @package Industrial Lite
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */

function industrial_lite_custom_controls() {
	load_template( trailingslashit( get_template_directory() ) . '/inc/custom-controls.php' );
}
add_action( 'customize_register', 'industrial_lite_custom_controls' );

function industrial_lite_customize_register( $wp_customize ) {

	load_template( trailingslashit( get_template_directory() ) . 'inc/customize-homepage/class-customize-homepage.php' );

	load_template( trailingslashit( get_template_directory() ) . '/inc/icon-picker.php' );

	$wp_customize->get_setting( 'blogname' )->transport = 'postMessage'; 
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial( 'blogname', array( 
		'selector' => '.logo .site-title a', 
	 	'render_callback' => 'industrial_lite_customize_partial_blogname', 
	)); 

	$wp_customize->selective_refresh->add_partial( 'blogdescription', array( 
		'selector' => 'p.site-description', 
		'render_callback' => 'industrial_lite_customize_partial_blogdescription', 
	));

	//add home page setting pannel
	$IndustrialLiteParentPanel = new Industrial_Lite_WP_Customize_Panel( $wp_customize, 'industrial_lite_panel_id', array(
		'capability' => 'edit_theme_options',
		'theme_supports' => '',
		'title' => esc_html__( 'VW Settings', 'industrial-lite' ),
		'priority' => 10,
	));

	// Layout
	$wp_customize->add_section( 'industrial_lite_left_right', array(
    	'title'      => esc_html__( 'General Settings', 'industrial-lite' ),
		'panel' => 'industrial_lite_panel_id'
	) );

	$wp_customize->add_setting('industrial_lite_width_option',array(
        'default' => 'Full Width',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control(new Industrial_Lite_Image_Radio_Control($wp_customize, 'industrial_lite_width_option', array(
        'type' => 'select',
        'label' => __('Width Layouts','industrial-lite'),
        'description' => __('Here you can change the width layout of Website.','industrial-lite'),
        'section' => 'industrial_lite_left_right',
        'choices' => array(
            'Full Width' => get_template_directory_uri().'/assets/images/full-width.png',
            'Wide Width' => get_template_directory_uri().'/assets/images/wide-width.png',
            'Boxed' => get_template_directory_uri().'/assets/images/boxed-width.png',
    ))));

	$wp_customize->add_setting('industrial_lite_theme_options',array(
        'default' => 'Right Sidebar',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control('industrial_lite_theme_options',array(
        'type' => 'select',
        'label' => __('Post Sidebar Layout','industrial-lite'),
        'description' => __('Here you can change the sidebar layout for posts. ','industrial-lite'),
        'section' => 'industrial_lite_left_right',
        'choices' => array(
            'Left Sidebar' => __('Left Sidebar','industrial-lite'),
            'Right Sidebar' => __('Right Sidebar','industrial-lite'),
            'One Column' => __('One Column','industrial-lite'),
            'Three Columns' => __('Three Columns','industrial-lite'),
            'Four Columns' => __('Four Columns','industrial-lite'),
            'Grid Layout' => __('Grid Layout','industrial-lite')
        ),
	) );

	$wp_customize->add_setting('industrial_lite_page_layout',array(
        'default' => 'One Column',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control('industrial_lite_page_layout',array(
        'type' => 'select',
        'label' => __('Page Sidebar Layout','industrial-lite'),
        'description' => __('Here you can change the sidebar layout for pages. ','industrial-lite'),
        'section' => 'industrial_lite_left_right',
        'choices' => array(
            'Left Sidebar' => __('Left Sidebar','industrial-lite'),
            'Right Sidebar' => __('Right Sidebar','industrial-lite'),
            'One Column' => __('One Column','industrial-lite')
        ),
	) );

	//Pre-Loader
	$wp_customize->add_setting( 'industrial_lite_loader_enable',array(
        'default' => 1,
        'transport' => 'refresh',
        'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ) );
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_loader_enable',array(
        'label' => esc_html__( 'Pre-Loader','industrial-lite' ),
        'section' => 'industrial_lite_left_right'
    )));

	$wp_customize->add_setting('industrial_lite_loader_icon',array(
        'default' => 'Two Way',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control('industrial_lite_loader_icon',array(
        'type' => 'select',
        'label' => __('Pre-Loader Type','industrial-lite'),
        'section' => 'industrial_lite_left_right',
        'choices' => array(
            'Two Way' => __('Two Way','industrial-lite'),
            'Dots' => __('Dots','industrial-lite'),
            'Rotate' => __('Rotate','industrial-lite')
        ),
	) );

	//Top Bar
	$wp_customize->add_section( 'industrial_lite_topbar', array(
    	'title'      => __( 'Top Bar Settings', 'industrial-lite' ),
		'panel' => 'industrial_lite_panel_id'
	) );

	$wp_customize->add_setting( 'industrial_lite_topbar_hide_show',array(
      'default' => 0,
      'transport' => 'refresh',
      'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_topbar_hide_show',
       array(
      'label' => esc_html__( 'Show / Hide Topbar','industrial-lite' ),
      'section' => 'industrial_lite_topbar'
    )));

    $wp_customize->add_setting('industrial_lite_topbar_padding_top_bottom',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_topbar_padding_top_bottom',array(
		'label'	=> __('Topbar Padding Top Bottom','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_topbar',
		'type'=> 'text'
	));

    //Sticky Header
	$wp_customize->add_setting( 'industrial_lite_sticky_header',array(
        'default' => 0,
        'transport' => 'refresh',
        'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ) );
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_sticky_header',array(
        'label' => esc_html__( 'Sticky Header','industrial-lite' ),
        'section' => 'industrial_lite_topbar'
    )));

    $wp_customize->add_setting('industrial_lite_sticky_header_padding',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_sticky_header_padding',array(
		'label'	=> __('Sticky Header Padding','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_topbar',
		'type'=> 'text'
	));

	$wp_customize->add_setting( 'industrial_lite_header_search',array(
    	'default' => 1,
      	'transport' => 'refresh',
      	'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_header_search',array(
      	'label' => esc_html__( 'Show / Hide Search','industrial-lite' ),
      	'section' => 'industrial_lite_topbar'
    )));

    $wp_customize->add_setting('industrial_lite_search_font_size',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_search_font_size',array(
		'label'	=> __('Search Font Size','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_topbar',
		'type'=> 'text'
	));

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('industrial_lite_location', array( 
		'selector' => '.top-bar span', 
		'render_callback' => 'industrial_lite_customize_partial_industrial_lite_location', 
	));

    $wp_customize->add_setting('industrial_lite_location_icon',array(
		'default'	=> 'fas fa-map-marker-alt',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control(new Industrial_Lite_Fontawesome_Icon_Chooser(
        $wp_customize,'industrial_lite_location_icon',array(
		'label'	=> __('Add Location Icon','industrial-lite'),
		'transport' => 'refresh',
		'section'	=> 'industrial_lite_topbar',
		'setting'	=> 'industrial_lite_location_icon',
		'type'		=> 'icon'
	)));

	$wp_customize->add_setting('industrial_lite_location',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_location',array(
		'label'	=> __('Add Location','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '123 lorem ipsum is a dummy text, USA', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_topbar',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_phone_icon',array(
		'default'	=> 'fas fa-phone',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control(new Industrial_Lite_Fontawesome_Icon_Chooser(
        $wp_customize,'industrial_lite_phone_icon',array(
		'label'	=> __('Add Phone Icon','industrial-lite'),
		'transport' => 'refresh',
		'section'	=> 'industrial_lite_topbar',
		'setting'	=> 'industrial_lite_phone_icon',
		'type'		=> 'icon'
	)));

	$wp_customize->add_setting('industrial_lite_phone_number',array(
		'default'=> '',
		'sanitize_callback'	=> 'industrial_lite_sanitize_phone_number'
	));
	$wp_customize->add_control('industrial_lite_phone_number',array(
		'label'	=> __('Add Phone Number','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '+00 1234 567 890', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_topbar',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_email_address_icon',array(
		'default'	=> 'fas fa-envelope',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control(new Industrial_Lite_Fontawesome_Icon_Chooser(
        $wp_customize,'industrial_lite_email_address_icon',array(
		'label'	=> __('Add Email Icon','industrial-lite'),
		'transport' => 'refresh',
		'section'	=> 'industrial_lite_topbar',
		'setting'	=> 'industrial_lite_email_address_icon',
		'type'		=> 'icon'
	)));

	$wp_customize->add_setting('industrial_lite_email_address',array(
		'default'=> '',
		'sanitize_callback'	=> 'industrial_lite_sanitize_email'
	));
	$wp_customize->add_control('industrial_lite_email_address',array(
		'label'	=> __('Add Email Address','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'example@gmail.com', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_topbar',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_top_btn_url',array(
		'default'=> '',
		'sanitize_callback'	=> 'esc_url_raw'
	));
	$wp_customize->add_control('industrial_lite_top_btn_url',array(
		'label'	=> __('Add Button URL','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'https://example.com/', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_topbar',
		'type'=> 'url'
	));

	$wp_customize->add_setting('industrial_lite_top_btn_text',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_top_btn_text',array(
		'label'	=> __('Add Button Text','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'GET A QUOTE', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_topbar',
		'type'=> 'text'
	));
    
	//Slider
	$wp_customize->add_section( 'industrial_lite_slidersettings' , array(
    	'title'      => __( 'Slider Settings', 'industrial-lite' ),
		'panel' => 'industrial_lite_panel_id'
	) );

	$wp_customize->add_setting( 'industrial_lite_slider_arrows',array(
    	'default' => 0,
      	'transport' => 'refresh',
      	'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_slider_arrows',array(
      	'label' => esc_html__( 'Show / Hide Slider','industrial-lite' ),
      	'section' => 'industrial_lite_slidersettings'
    )));

    //Selective Refresh
    $wp_customize->selective_refresh->add_partial('industrial_lite_slider_arrows',array(
		'selector'        => '#slider .inner_carousel h1',
		'render_callback' => 'industrial_lite_customize_partial_industrial_lite_slider_arrows',
	));

	for ( $count = 1; $count <= 4; $count++ ) {
		$wp_customize->add_setting( 'industrial_lite_slider_page' . $count, array(
			'default'           => '',
			'sanitize_callback' => 'industrial_lite_sanitize_dropdown_pages'
		) );
		$wp_customize->add_control( 'industrial_lite_slider_page' . $count, array(
			'label'    => __( 'Select Slider Page', 'industrial-lite' ),
			'description' => __('Slider image size (1600 x 900)','industrial-lite'),
			'section'  => 'industrial_lite_slidersettings',
			'type'     => 'dropdown-pages'
		) );
	}

	$wp_customize->add_setting('industrial_lite_slider_button_text',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_slider_button_text',array(
		'label'	=> __('Add Slider Button Text','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'READ MORE', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_slidersettings',
		'type'=> 'text'
	));

	//content layout
	$wp_customize->add_setting('industrial_lite_slider_content_option',array(
        'default' => 'Left',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control(new Industrial_Lite_Image_Radio_Control($wp_customize, 'industrial_lite_slider_content_option', array(
        'type' => 'select',
        'label' => __('Slider Content Layouts','industrial-lite'),
        'section' => 'industrial_lite_slidersettings',
        'choices' => array(
            'Left' => get_template_directory_uri().'/assets/images/slider-content1.png',
            'Center' => get_template_directory_uri().'/assets/images/slider-content2.png',
            'Right' => get_template_directory_uri().'/assets/images/slider-content3.png',
    ))));

    //Slider excerpt
	$wp_customize->add_setting( 'industrial_lite_slider_excerpt_number', array(
		'default'              => 30,
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'industrial_lite_sanitize_number_range'
	) );
	$wp_customize->add_control( 'industrial_lite_slider_excerpt_number', array(
		'label'       => esc_html__( 'Slider Excerpt length','industrial-lite' ),
		'section'     => 'industrial_lite_slidersettings',
		'type'        => 'range',
		'settings'    => 'industrial_lite_slider_excerpt_number',
		'input_attrs' => array(
			'step'             => 2,
			'min'              => 0,
			'max'              => 50,
		),
	) );

	//Opacity
	$wp_customize->add_setting('industrial_lite_slider_opacity_color',array(
      'default'              => 0.4,
      'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));

	$wp_customize->add_control( 'industrial_lite_slider_opacity_color', array(
	'label'       => esc_html__( 'Slider Image Opacity','industrial-lite' ),
	'section'     => 'industrial_lite_slidersettings',
	'type'        => 'select',
	'settings'    => 'industrial_lite_slider_opacity_color',
	'choices' => array(
      '0' =>  esc_attr('0','industrial-lite'),
      '0.1' =>  esc_attr('0.1','industrial-lite'),
      '0.2' =>  esc_attr('0.2','industrial-lite'),
      '0.3' =>  esc_attr('0.3','industrial-lite'),
      '0.4' =>  esc_attr('0.4','industrial-lite'),
      '0.5' =>  esc_attr('0.5','industrial-lite'),
      '0.6' =>  esc_attr('0.6','industrial-lite'),
      '0.7' =>  esc_attr('0.7','industrial-lite'),
      '0.8' =>  esc_attr('0.8','industrial-lite'),
      '0.9' =>  esc_attr('0.9','industrial-lite')
	),
	));

	//Slider height
	$wp_customize->add_setting('industrial_lite_slider_height',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_slider_height',array(
		'label'	=> __('Slider Height','industrial-lite'),
		'description'	=> __('Specify the slider height (px).','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '500px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_slidersettings',
		'type'=> 'text'
	));

	$wp_customize->add_setting( 'industrial_lite_slider_speed', array(
		'default'  => 3000,
		'sanitize_callback'	=> 'industrial_lite_sanitize_float'
	) );
	$wp_customize->add_control( 'industrial_lite_slider_speed', array(
		'label' => esc_html__('Slider Transition Speed','industrial-lite'),
		'section' => 'industrial_lite_slidersettings',
		'type'  => 'number',
	) );
 
	//Our Services section
	$wp_customize->add_section( 'industrial_lite_services_section' , array(
    	'title'      => __( 'Services Settings', 'industrial-lite' ),
		'priority'   => null,
		'panel' => 'industrial_lite_panel_id'
	) );

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial( 'industrial_lite_section_title', array( 
		'selector' => '#serv-section h2', 
		'render_callback' => 'industrial_lite_customize_partial_industrial_lite_section_title',
	));

	$wp_customize->add_setting('industrial_lite_services_title_icon',array(
		'default'	=> 'fas fa-industry',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control(new Industrial_Lite_Fontawesome_Icon_Chooser(
        $wp_customize,'industrial_lite_services_title_icon',array(
		'label'	=> __('Add Title Icon','industrial-lite'),
		'transport' => 'refresh',
		'section'	=> 'industrial_lite_services_section',
		'setting'	=> 'industrial_lite_services_title_icon',
		'type'		=> 'icon'
	)));

	$wp_customize->add_setting('industrial_lite_section_title',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_section_title',array(
		'label'	=> __('Add Section Title','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'SERVICES WE PROVIDE', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_services_section',
		'type'=> 'text'
	));

	$categories = get_categories();
	$cat_post = array();
	$cat_post[]= 'select';
	$i = 0;	
	foreach($categories as $category){
		if($i==0){
			$default = $category->slug;
			$i++;
		}
		$cat_post[$category->slug] = $category->name;
	}

	$wp_customize->add_setting('industrial_lite_our_services',array(
		'default'	=> 'select',
		'sanitize_callback' => 'industrial_lite_sanitize_choices',
	));
	$wp_customize->add_control('industrial_lite_our_services',array(
		'type'    => 'select',
		'choices' => $cat_post,
		'label' => __('Select Category to display Services','industrial-lite'),
		'description' => __('Image Size (370 x 270)','industrial-lite'),
		'section' => 'industrial_lite_services_section',
	));

	//Services excerpt
	$wp_customize->add_setting( 'industrial_lite_services_excerpt_number', array(
		'default'              => 30,
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'industrial_lite_sanitize_number_range'
	) );
	$wp_customize->add_control( 'industrial_lite_services_excerpt_number', array(
		'label'       => esc_html__( 'Services Excerpt length','industrial-lite' ),
		'section'     => 'industrial_lite_services_section',
		'type'        => 'range',
		'settings'    => 'industrial_lite_services_excerpt_number',
		'input_attrs' => array(
			'step'             => 2,
			'min'              => 0,
			'max'              => 50,
		),
	) );

	//Blog Post
	$wp_customize->add_panel( $IndustrialLiteParentPanel );

	$BlogPostParentPanel = new Industrial_Lite_WP_Customize_Panel( $wp_customize, 'blog_post_parent_panel', array(
		'title' => __( 'Blog Post Settings', 'industrial-lite' ),
		'panel' => 'industrial_lite_panel_id',
	));

	$wp_customize->add_panel( $BlogPostParentPanel );

	// Add example section and controls to the middle (second) panel
	$wp_customize->add_section( 'industrial_lite_post_settings', array(
		'title' => __( 'Post Settings', 'industrial-lite' ),
		'panel' => 'blog_post_parent_panel',
	));

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('industrial_lite_toggle_postdate', array( 
		'selector' => '.post-main-box h2 a', 
		'render_callback' => 'industrial_lite_customize_partial_industrial_lite_toggle_postdate', 
	));

	$wp_customize->add_setting( 'industrial_lite_toggle_postdate',array(
        'default' => 1,
        'transport' => 'refresh',
        'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ) );
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_toggle_postdate',array(
        'label' => esc_html__( 'Post Date','industrial-lite' ),
        'section' => 'industrial_lite_post_settings'
    )));

    $wp_customize->add_setting( 'industrial_lite_toggle_author',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ) );
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_toggle_author',array(
		'label' => esc_html__( 'Author','industrial-lite' ),
		'section' => 'industrial_lite_post_settings'
    )));

    $wp_customize->add_setting( 'industrial_lite_toggle_comments',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ) );
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_toggle_comments',array(
		'label' => esc_html__( 'Comments','industrial-lite' ),
		'section' => 'industrial_lite_post_settings'
    )));

    $wp_customize->add_setting( 'industrial_lite_toggle_tags',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'industrial_lite_switch_sanitization'
	));
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_toggle_tags', array(
		'label' => esc_html__( 'Tags','industrial-lite' ),
		'section' => 'industrial_lite_post_settings'
    )));

    $wp_customize->add_setting( 'industrial_lite_excerpt_number', array(
		'default'              => 30,
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'industrial_lite_sanitize_number_range'
	) );
	$wp_customize->add_control( 'industrial_lite_excerpt_number', array(
		'label'       => esc_html__( 'Excerpt length','industrial-lite' ),
		'section'     => 'industrial_lite_post_settings',
		'type'        => 'range',
		'settings'    => 'industrial_lite_excerpt_number',
		'input_attrs' => array(
			'step'             => 2,
			'min'              => 0,
			'max'              => 50,
		),
	) );

	//Blog layout
    $wp_customize->add_setting('industrial_lite_blog_layout_option',array(
        'default' => 'Default',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
    ));
    $wp_customize->add_control(new Industrial_Lite_Image_Radio_Control($wp_customize, 'industrial_lite_blog_layout_option', array(
        'type' => 'select',
        'label' => __('Blog Layouts','industrial-lite'),
        'section' => 'industrial_lite_post_settings',
        'choices' => array(
            'Default' => get_template_directory_uri().'/assets/images/blog-layout1.png',
            'Center' => get_template_directory_uri().'/assets/images/blog-layout2.png',
            'Left' => get_template_directory_uri().'/assets/images/blog-layout3.png',
    ))));

    $wp_customize->add_setting('industrial_lite_excerpt_settings',array(
        'default' => 'Excerpt',
        'transport' => 'refresh',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control('industrial_lite_excerpt_settings',array(
        'type' => 'select',
        'label' => __('Post Content','industrial-lite'),
        'section' => 'industrial_lite_post_settings',
        'choices' => array(
        	'Content' => __('Content','industrial-lite'),
            'Excerpt' => __('Excerpt','industrial-lite'),
            'No Content' => __('No Content','industrial-lite')
        ),
	) );

	$wp_customize->add_setting('industrial_lite_excerpt_suffix',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_excerpt_suffix',array(
		'label'	=> __('Add Excerpt Suffix','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '[...]', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_post_settings',
		'type'=> 'text'
	));

	$wp_customize->add_setting( 'industrial_lite_blog_pagination_hide_show',array(
      'default' => 1,
      'transport' => 'refresh',
      'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_blog_pagination_hide_show',array(
      'label' => esc_html__( 'Show / Hide Blog Pagination','industrial-lite' ),
      'section' => 'industrial_lite_post_settings'
    )));

	$wp_customize->add_setting( 'industrial_lite_blog_pagination_type', array(
        'default'			=> 'blog-page-numbers',
        'sanitize_callback'	=> 'industrial_lite_sanitize_choices'
    ));
    $wp_customize->add_control( 'industrial_lite_blog_pagination_type', array(
        'section' => 'industrial_lite_post_settings',
        'type' => 'select',
        'label' => __( 'Blog Pagination', 'industrial-lite' ),
        'choices'		=> array(
            'blog-page-numbers'  => __( 'Numeric', 'industrial-lite' ),
            'next-prev' => __( 'Older Posts/Newer Posts', 'industrial-lite' ),
    )));

    // Button Settings
	$wp_customize->add_section( 'industrial_lite_button_settings', array(
		'title' => __( 'Button Settings', 'industrial-lite' ),
		'panel' => 'blog_post_parent_panel',
	));

	$wp_customize->add_setting('industrial_lite_button_padding_top_bottom',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_button_padding_top_bottom',array(
		'label'	=> __('Padding Top Bottom','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_button_settings',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_button_padding_left_right',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_button_padding_left_right',array(
		'label'	=> __('Padding Left Right','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_button_settings',
		'type'=> 'text'
	));

	$wp_customize->add_setting( 'industrial_lite_button_border_radius', array(
		'default'              => '',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'industrial_lite_sanitize_number_range'
	) );
	$wp_customize->add_control( 'industrial_lite_button_border_radius', array(
		'label'       => esc_html__( 'Button Border Radius','industrial-lite' ),
		'section'     => 'industrial_lite_button_settings',
		'type'        => 'range',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 1,
			'max'              => 50,
		),
	) );

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('industrial_lite_button_text', array( 
		'selector' => '.post-main-box .more-btn a', 
		'render_callback' => 'industrial_lite_customize_partial_industrial_lite_button_text', 
	));

	$wp_customize->add_setting('industrial_lite_button_text',array(
		'default'=> esc_html__( 'READ MORE', 'industrial-lite' ),
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_button_text',array(
		'label'	=> __('Add Button Text','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'READ MORE', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_button_settings',
		'type'=> 'text'
	));

	// Related Post Settings
	$wp_customize->add_section( 'industrial_lite_related_posts_settings', array(
		'title' => __( 'Related Posts Settings', 'industrial-lite' ),
		'panel' => 'blog_post_parent_panel',
	));

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('industrial_lite_related_post_title', array( 
		'selector' => '.related-post h3', 
		'render_callback' => 'industrial_lite_customize_partial_industrial_lite_related_post_title', 
	));

    $wp_customize->add_setting( 'industrial_lite_related_post',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ) );
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_related_post',array(
		'label' => esc_html__( 'Related Post','industrial-lite' ),
		'section' => 'industrial_lite_related_posts_settings'
    )));

    $wp_customize->add_setting('industrial_lite_related_post_title',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_related_post_title',array(
		'label'	=> __('Add Related Post Title','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'Related Post', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_related_posts_settings',
		'type'=> 'text'
	));

   	$wp_customize->add_setting('industrial_lite_related_posts_count',array(
		'default'=> '3',
		'sanitize_callback'	=> 'industrial_lite_sanitize_float'
	));
	$wp_customize->add_control('industrial_lite_related_posts_count',array(
		'label'	=> __('Add Related Post Count','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '3', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_related_posts_settings',
		'type'=> 'number'
	));

	// Single Posts Settings
	$wp_customize->add_section( 'industrial_lite_single_blog_settings', array(
		'title' => __( 'Single Post Settings', 'industrial-lite' ),
		'panel' => 'blog_post_parent_panel',
	));

	$wp_customize->add_setting( 'industrial_lite_single_blog_post_navigation_show_hide',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'industrial_lite_switch_sanitization'
	));
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_single_blog_post_navigation_show_hide', array(
		'label' => esc_html__( 'Post Navigation','industrial-lite' ),
		'section' => 'industrial_lite_single_blog_settings'
    )));

	//navigation text
	$wp_customize->add_setting('industrial_lite_single_blog_prev_navigation_text',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_single_blog_prev_navigation_text',array(
		'label'	=> __('Post Navigation Text','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'PREVIOUS', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_single_blog_settings',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_single_blog_next_navigation_text',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_single_blog_next_navigation_text',array(
		'label'	=> __('Post Navigation Text','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'NEXT', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_single_blog_settings',
		'type'=> 'text'
	));

    //404 Page Setting
	$wp_customize->add_section('industrial_lite_404_page',array(
		'title'	=> __('404 Page Settings','industrial-lite'),
		'panel' => 'industrial_lite_panel_id',
	));	

	$wp_customize->add_setting('industrial_lite_404_page_title',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));

	$wp_customize->add_control('industrial_lite_404_page_title',array(
		'label'	=> __('Add Title','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '404 Not Found', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_404_page',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_404_page_content',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));

	$wp_customize->add_control('industrial_lite_404_page_content',array(
		'label'	=> __('Add Text','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'Looks like you have taken a wrong turn, Dont worry, it happens to the best of us.', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_404_page',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_404_page_button_text',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_404_page_button_text',array(
		'label'	=> __('Add Button Text','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'Go Back', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_404_page',
		'type'=> 'text'
	));

	//Social Icon Setting
	$wp_customize->add_section('industrial_lite_social_icon_settings',array(
		'title'	=> __('Social Icons Settings','industrial-lite'),
		'panel' => 'industrial_lite_panel_id',
	));	

	$wp_customize->add_setting('industrial_lite_social_icon_font_size',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_social_icon_font_size',array(
		'label'	=> __('Icon Font Size','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_social_icon_settings',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_social_icon_padding',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_social_icon_padding',array(
		'label'	=> __('Icon Padding','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_social_icon_settings',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_social_icon_width',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_social_icon_width',array(
		'label'	=> __('Icon Width','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_social_icon_settings',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_social_icon_height',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_social_icon_height',array(
		'label'	=> __('Icon Height','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_social_icon_settings',
		'type'=> 'text'
	));

	$wp_customize->add_setting( 'industrial_lite_social_icon_border_radius', array(
		'default'              => '',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'industrial_lite_sanitize_number_range'
	) );
	$wp_customize->add_control( 'industrial_lite_social_icon_border_radius', array(
		'label'       => esc_html__( 'Icon Border Radius','industrial-lite' ),
		'section'     => 'industrial_lite_social_icon_settings',
		'type'        => 'range',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 1,
			'max'              => 50,
		),
	) );

	//Responsive Media Settings
	$wp_customize->add_section('industrial_lite_responsive_media',array(
		'title'	=> __('Responsive Media','industrial-lite'),
		'panel' => 'industrial_lite_panel_id',
	));

	$wp_customize->add_setting( 'industrial_lite_resp_topbar_hide_show',array(
      'default' => 0,
      'transport' => 'refresh',
      'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_resp_topbar_hide_show',array(
      'label' => esc_html__( 'Show / Hide Topbar','industrial-lite' ),
      'section' => 'industrial_lite_responsive_media'
    )));

    $wp_customize->add_setting( 'industrial_lite_stickyheader_hide_show',array(
      'default' => 0,
      'transport' => 'refresh',
      'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_stickyheader_hide_show',array(
      'label' => esc_html__( 'Sticky Header','industrial-lite' ),
      'section' => 'industrial_lite_responsive_media'
    )));

    $wp_customize->add_setting( 'industrial_lite_resp_slider_hide_show',array(
      'default' => 0,
      'transport' => 'refresh',
      'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_resp_slider_hide_show',array(
      'label' => esc_html__( 'Show / Hide Slider','industrial-lite' ),
      'section' => 'industrial_lite_responsive_media'
    )));

	$wp_customize->add_setting( 'industrial_lite_metabox_hide_show',array(
      'default' => 1,
      'transport' => 'refresh',
      'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_metabox_hide_show',array(
      'label' => esc_html__( 'Show / Hide Metabox','industrial-lite' ),
      'section' => 'industrial_lite_responsive_media'
    )));

    $wp_customize->add_setting( 'industrial_lite_sidebar_hide_show',array(
      'default' => 1,
      'transport' => 'refresh',
      'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_sidebar_hide_show',array(
      'label' => esc_html__( 'Show / Hide Sidebar','industrial-lite' ),
      'section' => 'industrial_lite_responsive_media'
    )));

    $wp_customize->add_setting( 'industrial_lite_resp_scroll_top_hide_show',array(
      'default' => 1,
      'transport' => 'refresh',
      'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_resp_scroll_top_hide_show',array(
      'label' => esc_html__( 'Show / Hide Scroll To Top','industrial-lite' ),
      'section' => 'industrial_lite_responsive_media'
    )));

    $wp_customize->add_setting('industrial_lite_res_open_menu_icon',array(
		'default'	=> 'fas fa-bars',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control(new Industrial_Lite_Fontawesome_Icon_Chooser(
        $wp_customize,'industrial_lite_res_open_menu_icon',array(
		'label'	=> __('Add Open Menu Icon','industrial-lite'),
		'transport' => 'refresh',
		'section'	=> 'industrial_lite_responsive_media',
		'setting'	=> 'industrial_lite_res_open_menu_icon',
		'type'		=> 'icon'
	)));

	$wp_customize->add_setting('industrial_lite_res_close_menus_icon',array(
		'default'	=> 'fas fa-times',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control(new Industrial_Lite_Fontawesome_Icon_Chooser(
        $wp_customize,'industrial_lite_res_close_menus_icon',array(
		'label'	=> __('Add Close Menu Icon','industrial-lite'),
		'transport' => 'refresh',
		'section'	=> 'industrial_lite_responsive_media',
		'setting'	=> 'industrial_lite_res_close_menus_icon',
		'type'		=> 'icon'
	)));

	//Content Creation
	$wp_customize->add_section( 'industrial_lite_content_section' , array(
    	'title' => __( 'Customize Home Page Settings', 'industrial-lite' ),
		'priority' => null,
		'panel' => 'industrial_lite_panel_id'
	) );

	$wp_customize->add_setting('industrial_lite_content_creation_main_control', array(
		'sanitize_callback' => 'esc_html',
	) );

	$homepage= get_option( 'page_on_front' );

	$wp_customize->add_control(	new Industrial_Lite_Content_Creation( $wp_customize, 'industrial_lite_content_creation_main_control', array(
		'options' => array(
			esc_html__( 'First select static page in homepage setting for front page.Below given edit button is to customize Home Page. Just click on the edit option, add whatever elements you want to include in the homepage, save the changes and you are good to go.','industrial-lite' ),
		),
		'section' => 'industrial_lite_content_section',
		'button_url'  => admin_url( 'post.php?post='.$homepage.'&action=edit'),
		'button_text' => esc_html__( 'Edit', 'industrial-lite' ),
	) ) );

	//Footer Text
	$wp_customize->add_section('industrial_lite_footer',array(
		'title'	=> __('Footer Settings','industrial-lite'),
		'panel' => 'industrial_lite_panel_id',
	));	

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('industrial_lite_footer_text', array( 
		'selector' => '#footer-2 .copyright p', 
		'render_callback' => 'industrial_lite_customize_partial_industrial_lite_footer_text', 
	));
	
	$wp_customize->add_setting('industrial_lite_footer_text',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control('industrial_lite_footer_text',array(
		'label'	=> __('Copyright Text','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( 'Copyright 2019, .....', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_footer',
		'type'=> 'text'
	));	

	$wp_customize->add_setting('industrial_lite_copyright_alingment',array(
        'default' => 'center',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control(new Industrial_Lite_Image_Radio_Control($wp_customize, 'industrial_lite_copyright_alingment', array(
        'type' => 'select',
        'label' => __('Copyright Alignment','industrial-lite'),
        'section' => 'industrial_lite_footer',
        'settings' => 'industrial_lite_copyright_alingment',
        'choices' => array(
            'left' => get_template_directory_uri().'/assets/images/copyright1.png',
            'center' => get_template_directory_uri().'/assets/images/copyright2.png',
            'right' => get_template_directory_uri().'/assets/images/copyright3.png'
    ))));

    $wp_customize->add_setting('industrial_lite_copyright_padding_top_bottom',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_copyright_padding_top_bottom',array(
		'label'	=> __('Copyright Padding Top Bottom','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_footer',
		'type'=> 'text'
	));

	$wp_customize->add_setting( 'industrial_lite_hide_show_scroll',array(
    	'default' => 1,
      	'transport' => 'refresh',
      	'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ));  
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_hide_show_scroll',array(
      	'label' => esc_html__( 'Show / Hide Scroll To Top','industrial-lite' ),
      	'section' => 'industrial_lite_footer'
    )));

    //Selective Refresh
	$wp_customize->selective_refresh->add_partial('industrial_lite_scroll_to_top_icon', array( 
		'selector' => '.scrollup i', 
		'render_callback' => 'industrial_lite_customize_partial_industrial_lite_scroll_to_top_icon', 
	));

    $wp_customize->add_setting('industrial_lite_scroll_to_top_icon',array(
		'default'	=> 'fas fa-long-arrow-alt-up',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control(new Industrial_Lite_Fontawesome_Icon_Chooser(
        $wp_customize,'industrial_lite_scroll_to_top_icon',array(
		'label'	=> __('Add Scroll to Top Icon','industrial-lite'),
		'transport' => 'refresh',
		'section'	=> 'industrial_lite_footer',
		'setting'	=> 'industrial_lite_scroll_to_top_icon',
		'type'		=> 'icon'
	)));

	$wp_customize->add_setting('industrial_lite_scroll_to_top_font_size',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_scroll_to_top_font_size',array(
		'label'	=> __('Icon Font Size','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_footer',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_scroll_to_top_padding',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_scroll_to_top_padding',array(
		'label'	=> __('Icon Top Bottom Padding','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_footer',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_scroll_to_top_width',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_scroll_to_top_width',array(
		'label'	=> __('Icon Width','industrial-lite'),
		'description'	=> __('Enter a value in pixels Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_footer',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_scroll_to_top_height',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_scroll_to_top_height',array(
		'label'	=> __('Icon Height','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_footer',
		'type'=> 'text'
	));

	$wp_customize->add_setting( 'industrial_lite_scroll_to_top_border_radius', array(
		'default'              => '',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'industrial_lite_sanitize_number_range'
	) );
	$wp_customize->add_control( 'industrial_lite_scroll_to_top_border_radius', array(
		'label'       => esc_html__( 'Icon Border Radius','industrial-lite' ),
		'section'     => 'industrial_lite_footer',
		'type'        => 'range',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 1,
			'max'              => 50,
		),
	) );

	$wp_customize->add_setting('industrial_lite_scroll_top_alignment',array(
        'default' => 'Right',
        'sanitize_callback' => 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control(new Industrial_Lite_Image_Radio_Control($wp_customize, 'industrial_lite_scroll_top_alignment', array(
        'type' => 'select',
        'label' => __('Scroll To Top','industrial-lite'),
        'section' => 'industrial_lite_footer',
        'settings' => 'industrial_lite_scroll_top_alignment',
        'choices' => array(
            'Left' => get_template_directory_uri().'/assets/images/layout1.png',
            'Center' => get_template_directory_uri().'/assets/images/layout2.png',
            'Right' => get_template_directory_uri().'/assets/images/layout3.png'
    ))));

    //Woocommerce settings
	$wp_customize->add_section('industrial_lite_woocommerce_section', array(
		'title'    => __('WooCommerce Layout', 'industrial-lite'),
		'priority' => null,
		'panel'    => 'woocommerce',
	));

    //Woocommerce Shop Page Sidebar
	$wp_customize->add_setting( 'industrial_lite_woocommerce_shop_page_sidebar',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ) );
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_woocommerce_shop_page_sidebar',array(
		'label' => esc_html__( 'Shop Page Sidebar','industrial-lite' ),
		'section' => 'industrial_lite_woocommerce_section'
    )));

    //Woocommerce Single Product page Sidebar
	$wp_customize->add_setting( 'industrial_lite_woocommerce_single_product_page_sidebar',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'industrial_lite_switch_sanitization'
    ) );
    $wp_customize->add_control( new Industrial_Lite_Toggle_Switch_Custom_Control( $wp_customize, 'industrial_lite_woocommerce_single_product_page_sidebar',array(
		'label' => esc_html__( 'Single Product Sidebar','industrial-lite' ),
		'section' => 'industrial_lite_woocommerce_section'
    )));

    //Products per page
    $wp_customize->add_setting('industrial_lite_products_per_page',array(
		'default'=> '9',
		'sanitize_callback'	=> 'industrial_lite_sanitize_float'
	));
	$wp_customize->add_control('industrial_lite_products_per_page',array(
		'label'	=> __('Products Per Page','industrial-lite'),
		'description' => __('Display on shop page','industrial-lite'),
		'input_attrs' => array(
            'step'             => 1,
			'min'              => 0,
			'max'              => 50,
        ),
		'section'=> 'industrial_lite_woocommerce_section',
		'type'=> 'number',
	));

    //Products per row
    $wp_customize->add_setting('industrial_lite_products_per_row',array(
		'default'=> '3',
		'sanitize_callback'	=> 'industrial_lite_sanitize_choices'
	));
	$wp_customize->add_control('industrial_lite_products_per_row',array(
		'label'	=> __('Products Per Row','industrial-lite'),
		'description' => __('Display on shop page','industrial-lite'),
		'choices' => array(
            '2' => '2',
			'3' => '3',
			'4' => '4',
        ),
		'section'=> 'industrial_lite_woocommerce_section',
		'type'=> 'select',
	));

	//Products padding
	$wp_customize->add_setting('industrial_lite_products_padding_top_bottom',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_products_padding_top_bottom',array(
		'label'	=> __('Products Padding Top Bottom','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_woocommerce_section',
		'type'=> 'text'
	));

	$wp_customize->add_setting('industrial_lite_products_padding_left_right',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('industrial_lite_products_padding_left_right',array(
		'label'	=> __('Products Padding Left Right','industrial-lite'),
		'description'	=> __('Enter a value in pixels. Example:20px','industrial-lite'),
		'input_attrs' => array(
            'placeholder' => __( '10px', 'industrial-lite' ),
        ),
		'section'=> 'industrial_lite_woocommerce_section',
		'type'=> 'text'
	));

	//Products box shadow
	$wp_customize->add_setting( 'industrial_lite_products_box_shadow', array(
		'default'              => '',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'industrial_lite_sanitize_number_range'
	) );
	$wp_customize->add_control( 'industrial_lite_products_box_shadow', array(
		'label'       => esc_html__( 'Products Box Shadow','industrial-lite' ),
		'section'     => 'industrial_lite_woocommerce_section',
		'type'        => 'range',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 1,
			'max'              => 50,
		),
	) );

	//Products border radius
    $wp_customize->add_setting( 'industrial_lite_products_border_radius', array(
		'default'              => '',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'industrial_lite_sanitize_number_range'
	) );
	$wp_customize->add_control( 'industrial_lite_products_border_radius', array(
		'label'       => esc_html__( 'Products Border Radius','industrial-lite' ),
		'section'     => 'industrial_lite_woocommerce_section',
		'type'        => 'range',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 1,
			'max'              => 50,
		),
	) );

    // Has to be at the top
	$wp_customize->register_panel_type( 'Industrial_Lite_WP_Customize_Panel' );
	$wp_customize->register_section_type( 'Industrial_Lite_WP_Customize_Section' );
}

add_action( 'customize_register', 'industrial_lite_customize_register' );

load_template( trailingslashit( get_template_directory() ) . '/inc/logo/logo-resizer.php' );

if ( class_exists( 'WP_Customize_Panel' ) ) {
  	class Industrial_Lite_WP_Customize_Panel extends WP_Customize_Panel {
	    public $panel;
	    public $type = 'industrial_lite_panel';
	    public function json() {

	      $array = wp_array_slice_assoc( (array) $this, array( 'id', 'description', 'priority', 'type', 'panel', ) );
	      $array['title'] = html_entity_decode( $this->title, ENT_QUOTES, get_bloginfo( 'charset' ) );
	      $array['content'] = $this->get_content();
	      $array['active'] = $this->active();
	      $array['instanceNumber'] = $this->instance_number;
	      return $array;
    	}
  	}
}

if ( class_exists( 'WP_Customize_Section' ) ) {
  	class Industrial_Lite_WP_Customize_Section extends WP_Customize_Section {	
	    public $section;
	    public $type = 'industrial_lite_section';
	    public function json() {

	      $array = wp_array_slice_assoc( (array) $this, array( 'id', 'description', 'priority', 'panel', 'type', 'description_hidden', 'section', ) );
	      $array['title'] = html_entity_decode( $this->title, ENT_QUOTES, get_bloginfo( 'charset' ) );
	      $array['content'] = $this->get_content();
	      $array['active'] = $this->active();
	      $array['instanceNumber'] = $this->instance_number;

	      if ( $this->panel ) {
	        $array['customizeAction'] = sprintf( 'Customizing &#9656; %s', esc_html( $this->manager->get_panel( $this->panel )->title ) );
	      } else {
	        $array['customizeAction'] = 'Customizing';
	      }
	      return $array;
    	}
  	}
}

// Enqueue our scripts and styles
function industrial_lite_customize_controls_scripts() {
  wp_enqueue_script( 'customizer-controls', get_theme_file_uri( '/assets/js/customizer-controls.js' ), array(), '1.0', true );
}
add_action( 'customize_controls_enqueue_scripts', 'industrial_lite_customize_controls_scripts' );

/**
 * Singleton class for handling the theme's customizer integration.
 *
 * @since  1.0.0
 * @access public
 */
final class Industrial_Lite_Customize {

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Sets up initial actions.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function setup_actions() {

		// Register panels, sections, settings, controls, and partials.
		add_action( 'customize_register', array( $this, 'sections' ) );

		// Register scripts and styles for the controls.
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_control_scripts' ), 0 );
	}

	/**
	 * Sets up the customizer sections.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $manager
	 * @return void
	*/
	public function sections( $manager ) {

		// Load custom sections.
		load_template( trailingslashit( get_template_directory() ) . '/inc/section-pro.php' );

		// Register custom section types.
		$manager->register_section_type( 'Industrial_Lite_Customize_Section_Pro' );

		// Register sections.
		$manager->add_section( new Industrial_Lite_Customize_Section_Pro( $manager,'example_1', array(
			'priority'   => 1,
			'title'    => esc_html__( 'INDUSTRIAL PRO', 'industrial-lite' ),
			'pro_text' => esc_html__( 'UPGRADE PRO', 'industrial-lite' ),
			'pro_url'  => esc_url('https://www.vwthemes.com/themes/industrial-wordpress-theme/'),
		) )	);

		$manager->add_section(new Industrial_Lite_Customize_Section_Pro($manager,'example_2',array(
			'priority'   => 1,
			'title'    => esc_html__( 'DOCUMENTATION', 'industrial-lite' ),
			'pro_text' => esc_html__( 'DOCS', 'industrial-lite' ),
			'pro_url'  => admin_url('themes.php?page=industrial_lite_guide'),
		)));
	}

	/**
	 * Loads theme customizer CSS.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue_control_scripts() {

		wp_enqueue_script( 'industrial-lite-customize-controls', trailingslashit( get_template_directory_uri() ) . '/assets/js/customize-controls.js', array( 'customize-controls' ) );

		wp_enqueue_style( 'industrial-lite-customize-controls', trailingslashit( get_template_directory_uri() ) . '/assets/css/customize-controls.css' );
	}
}

// Doing this customizer thang!
Industrial_Lite_Customize::get_instance();