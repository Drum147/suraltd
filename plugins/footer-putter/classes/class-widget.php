<?php
if (!class_exists('Footer_Putter_Widget')) {
 abstract class Footer_Putter_Widget extends WP_Widget {

   const ALLOWED_TAGS = '<a>,<img>,<span>,<i>,<em>,<br>';
   
    protected $plugin;
    protected $utils;   
    protected $instance; 
    protected $key; 
    protected $widget_id; 
    private $tooltips;
    private $defaults = array('title' => '', 'html_title' => '', 'class' => '');
    private $tips = array('title' => array('heading' => 'Label', 'tip' => 'Label appears only in the Widget Dashboard to make widget identification easier'),
                        'html_title' => array('heading' => 'Widget Title', 'tip' => 'Enhanced widget title can contain some HTML such as links, spans and breaks'),
                        'class' => array('heading' => 'Widget Class', 'tip' => 'Class to place on widget instance to make CSS customization easier')
                        );

    function __construct( $id_base, $name, $widget_options = array(), $control_options = array()) {
        $this->plugin = Footer_Putter_Plugin::get_instance();
        $this->utils = $this->plugin->get_utils();
        parent::__construct($id_base, $name, $widget_options, $control_options);
    }

    function get_version() {
        return $this->plugin->get_version();
    }

	function get_defaults() {
		return $this->defaults;
	}

	function set_defaults($defaults) {
        if (is_array($defaults) && (count($defaults) > 0))
            $this->defaults = array_merge($this->defaults, $defaults);
	}

	public function override_args($args, &$instance) {	
        $this->instance = wp_parse_args( (array) $instance, $this->get_defaults() );
        $title = isset($instance['html_title']) ?  $instance['html_title'] : ''; 
        $instance = $this->instance;
        $class = isset($instance['class']) ?  $instance['class'] : ''; 
        if ( ! empty( $class ) ) $args['before_widget'] = str_replace('"widget ', '"widget '.$class.' ', $args['before_widget']);       
        if ( ! empty( $title ) ) $args['before_widget'] .= sprintf('%1$s%2$s%3$s',  $args['before_title'], $title, $args['after_title']);
        return $args;
   }

    function get_active_instances() {
        $active = array();
         if ($instances = $this->get_settings())
            foreach ($instances as $key => $instance) 
                if (is_array($instance) 
                && (count($instance) > 0) 
                && is_active_widget( false, $this->id_base.'-'.$key, $this->id_base, true )) {   
                    $inst = clone $this;
                    $inst->key = $key;    
                    $inst->widget_id = $this->id_base.'-'.$key;    
                    $inst->instance = wp_parse_args( (array) $instance, $this->get_defaults() );                   
                    $active[] = $inst;	
                }
         return $active;
    }

    function is_widget_instance_active() {
         if ($instances = $this->get_settings())
            foreach ($instances as $key => $instance) 
                if (is_array($instance) 
                && (count($instance) > 0) 
                && is_active_widget( false, $this->id_base.'-'.$key, $this->id_base, true )) {   
                    $this->key = $key;    
                    $this->widget_id = $this->id_base.'-'.$this->key;    
                    $this->instance = wp_parse_args( (array) $instance, $this->get_defaults() );                   
                    return true;		
                }
         return false;
    }

	public function update_instance($new_instance,  $old_instance) {
		$instance = wp_parse_args( (array) $old_instance, $this->get_defaults() );
		$instance['title'] = strip_tags($new_instance['title']);		
		$instance['html_title'] = strip_tags( $new_instance['html_title'],  self::ALLOWED_TAGS );	
		$instance['class'] = strip_tags( $new_instance['class'] );
      return $instance;
   }

	function form_init( $instance, $tips = false, $html_title = true) {
        if (is_array($tips) && (count($tips) > 0)) $this->tips = array_merge($this->tips, $tips);
        $this->tooltips = new Footer_Putter_Tooltip($this->tips);
        $this->instance = wp_parse_args( (array) $instance, $this->get_defaults() );      
 		print('<h4>Title</h4>');
        $this->print_form_field('title', 'text', array(), array('size' => 20));
        if ($html_title) $this->print_form_field('html_title', 'textarea', array(), array( 'class' => 'widefat' ));
        $this->print_form_field('class', 'text', array(), array( 'size' => 20 ));
        print ('<hr />');	  
	}
   
	public function print_form_field($fld, $type, $options = array(), $args = array()) {
		print $this->utils->form_field( 
			$this->get_field_id($fld), $this->get_field_name($fld), 
			$this->tooltips->tip($fld), 
			isset($this->instance[$fld]) ? $this->instance[$fld] : false,
			$type, $options, $args);
	}

	function print_text_field($fld, $value, $args = array()) {
 		$this->print_form_field($fld, $value, 'text', array(), $args);
 	}

	function taxonomy_options ($fld) {
        $selected = array_key_exists($fld, $this->instance) ? (array) $this->instance[$fld] : array();
		$s = sprintf('<option value="">%1$s</option>', __('All Taxonomies and Terms', WPMAGIQ_DOMAIN ));
		$taxonomies = get_taxonomies( array('public' => true ), 'objects');
		foreach ( $taxonomies as $taxonomy ) {
			if ($taxonomy->name !== 'nav_menu') {
				$query_label = $taxonomy->name;
				$s .= sprintf('optgroup label="%1$s">', esc_attr( $taxonomy->labels->name ));
				$s .= sprintf('<option style="margin-left: 5px; padding-right:10px;" %1$s value="%2$s">%3$s</option>',
					selected( in_array($query_label , $selected), true, false), 
					$query_label, $taxonomy->labels->all_items) ;
				$terms = get_terms( $taxonomy->name, 'orderby=name&hide_empty=1');
				foreach ( $terms as $term ) 
					$s .= sprintf('<option %1$s value="%2$s">%3$s</option>',
						selected(in_array($query_label. ',' . $term->slug, $selected), true, false),
						$query_label. ',' . $term->slug, '-' . esc_attr( $term->name )) ;
				$s .= '</optgroup>';
			}
		}
		return  $s;
	}

    function get_visibility_options(){
		return array(
			'' => 'Show on all pages', 
			'hide_landing' => 'Hide on landing pages', 
			'show_landing' => 'Show only on landing pages');
	}

	function hide_widget($visibility ) {
		$hide = false;
		$is_landing = $this->utils->is_landing_page();
		switch ($visibility) {
			case 'hide_landing' : $hide = $is_landing; break; //hide only on landing pages
			case 'show_landing' : $hide = ! $is_landing; break; //hide except on landing pages
		}
		return $hide;
	}

 } 
}
