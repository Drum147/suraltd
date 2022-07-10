<?php
class Footer_Putter_Credits  {

	const CSS_CLASS = 'footer-putter-credits';
	const CODE = 'footer-credits'; //shortcode prefix
	const OPTIONS_NAME = 'footer_credits_options';
	const SIDEBAR_ID = 'last-footer';

	protected $defaults = array(
        'terms' => array(
        'site' => '',
        'owner' => '',
        'address' => '',
        'street_address' => '',
        'locality' => '',
        'region' => '',
        'postal_code' => '',
        'country' => '',
        'latitude' => '',
        'longitude' => '',
        'map' => '',
        'email' => '',
        'telephone' => '',
        'copyright' => '',
        'copyright_start_year' => '',
        'copyright_preamble' => '',
        'courts' => '',
        'updated' => '',
        'privacy_contact' => '',
        'terms_contact' => ''),
		'nav_menu' => 0,
		'center' => true,
		'layout' => false,
		'separator' => '&nbsp;&middot;&nbsp;',		
		'show_copyright' => true,
		'show_telephone' => true,
		'show_email' => false,
		'show_address' => true,
		'show_return' => true,
		'return_text' => 'Return To Top',
		'return_class' => '',
		'footer_class' => '',
		'footer_hook' => '',
		'footer_remove' => true,
 		'footer_filter_hook' => '',
 		'visibility' => '' ,
        'use_microdata' => false,
        'hide_wordpress' => false
	);

    private $layouts = array(
        'single' => 'Single line: Menu + copyright + contact', 
        'single-alt' => 'Single line: Menu + contact + copyright', 
	    'contact-below' => '2 lines: Menu + copyright | Contact',
	    'copyright-below' => '2 lines: Menu + contact | Copyright', 
	    'menu-above' => '2 lines: Menu | Copyright + contact', 
	    'menu-above-alt' => '2 lines: Menu | Contact + copyright', 
	    'stacked' => '3 lines : Menu | Copyright | Contact',
	    'stacked-alt' => '3 lines : Menu | Contact | Copyright');
	
    protected $is_landing = false;
    private $plugin;
    private $utils;
    private $options;

   function __construct(){
      $this->plugin = Footer_Putter_Plugin::get_instance();
      $this->utils = $this->plugin->get_utils();
      $this->init();     
   }

	public function init() {
		$defaults = $this->theme_specific_defaults($this->defaults);
		$this->options = new Footer_Putter_Options(self::OPTIONS_NAME, $defaults);
		add_action('widgets_init',array($this,'register'),20);
		add_filter( 'wp_nav_menu_items', array($this, 'fix_home_link'), 10, 2 );
		if (!is_admin()) add_action('wp',array($this,'prepare'));
	}

	function register() {
		$this->register_sidebars();
		$this->register_widgets();
	}

    private function register_sidebars() {
    	if ($this->get_option('footer_hook')) {
			$tag = $this->is_html5() ? 'section' : 'div';
			register_sidebar( array(
				'id' => self::SIDEBAR_ID,
				'name'	=> __( 'Credibility Footer' ),
				'description' => __( 'Custom footer section for copyright, trademarks, etc.'),
				'before_widget' => '<'.$tag.' id="%1$s" class="widget %2$s"><div class="widget-wrap">',
				'after_widget'  => '</div></'.$tag.'>'				
			) );
		}
    }
	
	private function register_widgets() {
		if (class_exists('Footer_Putter_Copyright_Widget')) register_widget('Footer_Putter_Copyright_Widget');
		if (class_exists('Footer_Putter_Trademark_Widget')) register_widget('Footer_Putter_Trademark_Widget');
	}	
	
	function prepare() {
		add_shortcode(self::CODE, array($this, 'footer' ) );
		add_shortcode(self::CODE.'-contact', array($this, 'contact' ) );
		add_shortcode(self::CODE.'-copyright', array($this, 'copyright' ) );
		add_shortcode(self::CODE.'-menu', array($this, 'footer_menu' ) );
		add_shortcode(self::CODE.'-return', array($this, 'footer_return' ) );
		add_filter('widget_text', 'do_shortcode', 11);
		add_action('wp_enqueue_scripts',array($this, 'enqueue_styles' ));

		$this->is_landing = $this->utils->is_landing_page();
			
		//insert custom footer at specified hook
		if ($footer_hook = $this->get_option('footer_hook'))  {
			if ($this->get_option('footer_remove')) {
				remove_all_actions( $footer_hook); 
				if ($footer_hook =='wp_footer') {
					add_action( 'wp_footer', 'wp_print_footer_scripts', 20);  //put back the footer scripts             
					add_action( 'wp_footer', 'wp_admin_bar_render', 1000 ); //put back the admin bar
				}
			}
			add_action( $footer_hook, array($this, 'custom_footer')); 
		}
	
 		//suppress footer output
 		if ($ffs = $this->get_option('footer_filter_hook')) 
 			add_filter($ffs, array($this, 'no_footer'),100); 

		if (is_page('privacy') && $this->get_term('privacy_contact'))
			add_filter('the_content', array($this, 'add_privacy_footer'),9 );	

		if (is_page('terms') && $this->get_term('terms_contact'))
			add_filter('the_content', array($this, 'add_terms_footer'),9 );	

		if (is_page('terms') || is_page('privacy') || is_page('affiliates') || is_page('disclaimer'))
			add_filter('the_content', array($this, 'terms_filter') );	
				
	}
	
	function enqueue_styles() {
		wp_enqueue_style('footer-credits', plugins_url('styles/footer-credits.css',dirname(__FILE__)), array(), $this->plugin->get_version());
    }

	function get_option($option_name) {
    	$options = $this->get_options();
    	if ($option_name && $options && array_key_exists($option_name,$options))
        	return $options[$option_name];
    	else
        	return false;
    }

	function get_options() {
    	return $this->options->get_options();
    }
	
	function save_options($new_options) {
		$new_options['terms'] = $this->sanitize_terms($new_options['terms']);
		return $this->options->save_options( $new_options) ;
	}

	function get_layouts() { return $this->layouts; }	

	function is_html5() {
		return $this->utils->is_html5();
	}

	private function sanitize_terms($new_terms) {
		$new_terms = wp_parse_args($new_terms, $this->defaults['terms']); //ensure terms are complete		
		$new_terms['site'] = $this->get_default_site();
		$new_terms['copyright'] = $this->get_copyright($new_terms['copyright_start_year']); //generate copyright
		return $new_terms;
	}

	public function is_terms_key($key) {
		return array_key_exists($key, $this->defaults['terms']);
	}
	
 	public function get_terms() {
    	return $this->get_option('terms');
    }   
	
	public function get_term($term_name) {
    	$options = $this->get_options();
    	$terms = is_array($options) && array_key_exists('terms',$options) ? $options['terms'] : false;
    	if ($term_name && $terms && array_key_exists($term_name,$terms) && $terms[$term_name])
        	return $terms[$term_name];
    	else
        	return $this->get_default_term($term_name);    		
    }
	
    private function get_default_term($key) {
		$default='';
    	switch ($key) {
   			case 'owner' : $default = $this->get_term('site'); break;
   			case 'copyright' : $default = $this->get_copyright($this->get_term('copyright_start_year')); break;
   			case 'copyright_start_year': $default = date('Y'); break;
			case 'copyright_preamble': $default = 'Copyright &copy;'; break;
   			case 'country' : $default = 'The United States'; break;
   			case 'courts' : $default = ucwords(sprintf('the courts of %1$s',$this->get_term('country'))); break;
   			case 'email' : $default = 'privacy@'.strtolower($this->get_term('site')); break;
   			case 'site' : $default = $this->get_default_site(); break;
   			case 'updated' : $default = date('d M Y'); break;
 			default: $default='';  //default is blank for others
   		}
   		return $default;
    }
	
	private function get_default_site() { 
		$domain = strtolower(parse_url(site_url(),PHP_URL_HOST));
		$p = strpos($domain,'www.') ;
		if (($p !== FALSE) && ($p == 0)) $domain = substr($domain,4);
		return $domain; 
	}
	
	public function get_copyright($startyear=''){
  		$thisyear = date("Y");
		$format = (empty( $startyear) || ($startyear==$thisyear)) ? '%1$s %3$s' : '%1$s %2$s-%3$s';
  		return sprintf($format, $this->get_term('copyright_preamble'), $startyear, $thisyear);
 	}

	public function return_to_top( $text, $class) {
		return sprintf( '<div class="footer-return %1$s"><a rel="nofollow" href="#" onclick="window.scrollTo(0,0); return false;" >%2$s</a></div>', trim($class), $text);
	}

    private function contact_info($params) {
        $org ='';
        if ($address = $this->contact_address($params['show_address'], $params['use_microdata'], $params['separator'])) $org .= $address;
        if ($telephone = $this->contact_telephone($params['show_telephone'], $params['use_microdata'])) $org .= $telephone;
        if ($email = $this->contact_email($params['show_email'], $params['use_microdata'])) $org .= $email;
		$format = '<div class="footer-putter-contact %1$s" ' . ($params['use_microdata'] ? ' itemscope="itemscope" itemtype="http://schema.org/Organization"' : '') . '>%2$s</div>';
        return sprintf($format, $params['footer_class'], $org);
    }

    private function contact_telephone($show_telephone, $microdata ) {
      if  ($show_telephone && ($telephone = $this->get_term('telephone')))
        if ($microdata)
            return sprintf('<span itemprop="telephone" class="telephone">%1$s</span>', $telephone) ;
        else
            return sprintf('<span class="telephone">%1$s</span>', $telephone) ;
      else
            return '';
    }

    private function contact_email($show_email, $microdata) {
      if  ($show_email && ($email = $this->get_term('email')))
            return sprintf('<a href="mailto:%1$s" class="email"%2$s>%1$s</a>', $email, $microdata ? ' itemprop="email"' : '') ;
      else
            return '';
    }

    private function contact_address($show_address, $microdata, $separator) {
      if  ($show_address)
        if ($microdata) {
            return $this->org_location($separator);
        } elseif ($address = $this->get_term('address'))
            return sprintf('<span class="address">%1$s%2$s</span>', $this->format_address($address, $separator), $this->get_term('country'));
      return '';
    }

    private function format_address ($address, $separator) {
		$s='';
		$addlines = explode(',', trim($address));
		foreach ($addlines as $a) {
			$a = trim($a);
			if (!empty($a)) $s .= $a . $separator;
		}
		return $s;
    }	
	
    private function org_location($separator) {
        $location = '';
        if ($loc_address = $this->location_address( $separator)) $location .=  $loc_address;
        if ($loc_geo = $this->location_geo()) $location .= $loc_geo;
        if ($loc_map = $this->location_map()) $location .= $loc_map;
        if ($location)
            return sprintf('<span itemprop="location" itemscope="itemscope" itemtype="http://schema.org/Place">%1$s</span>', $location) ;
        else
            return '';
    }

    private function location_address($separator) {
        $address = '';
        if ( $street_address = $this->get_term('street_address'))
            $address .=  sprintf('<span itemprop="streetAddress">%1$s</span>', $this->format_address($street_address, $separator)) ;
        if ( $locality = $this->get_term('locality'))
                $address .=  sprintf('<span itemprop="addressLocality">%1$s</span>', $this->format_address($locality, $separator)) ;
        if ( $region = $this->get_term('region'))
                $address .=  sprintf('<span itemprop="addressRegion">%1$s</span>', $this->format_address($region, $separator)) ;
        if ( $postal_code = $this->get_term('postal_code'))
                $address .=  sprintf('<span itemprop="postalCode">%1$s</span>', $this->format_address($postal_code, $separator)) ;
        if ( $country = $this->get_term('country'))
                $address .=  sprintf('<span itemprop="addressCountry">%1$s</span>', $country) ;

        if ($address)
            return sprintf('<span class="address" itemprop="address" itemscope="itemscope" itemtype="http://schema.org/PostalAddress">%1$s</span>',$address) ;
        else
            return '';
    }

    private function location_geo() {
        $geo = '';
        if ( $latitude = $this->get_term('latitude')) $geo .=  sprintf('<meta itemprop="latitude" content="%1$s" />', $latitude) ;
        if ( $longitude = $this->get_term('longitude')) $geo .=  sprintf('<meta itemprop="longitude" content="%1$s" />', $longitude) ;
        return $geo ? sprintf('<span itemprop="geo" itemscope="itemscope" itemtype="http://schema.org/GeoCoordinates">%1$s</span>', $geo) : '';
    }

    private function location_map() {
        if ( $map = $this->get_term('map'))
            return sprintf('<a rel="nofollow external" target="_blank" class="map" itemprop="map" href="%1$s">%2$s</a>', $map, __('Map')) ;
        else
            return '';
    }

	public function copyright_owner($params){
  		return sprintf('<span class="copyright">%1$s %2$s</span>', 
  			$this->get_copyright($params['copyright_start_year']), $params['owner']);
	}	
	
    public function contact($atts = array()) {
		$defaults = array();
		$defaults['show_telephone'] = $this->defaults['show_telephone'];
		$defaults['show_email'] = $this->defaults['show_email'];
		$defaults['show_address'] = $this->defaults['show_address'];
		$defaults['use_microdata'] = $this->defaults['use_microdata'];
		$defaults['separator'] = $this->defaults['separator'];
		$defaults['footer_class'] = '';	
  		$params = shortcode_atts( $defaults, $atts ); //apply plugin defaults  		
        return $this->contact_info($params);
    }

	public function copyright($atts = array()){
		$defaults = array();
		$defaults['owner'] = $this->get_term('owner');
		$defaults['copyright_start_year'] = $this->get_term('copyright_start_year');	
		$defaults['footer_class'] = '';	
  		$params = shortcode_atts( $defaults, $atts ); //apply plugin defaults  		
        return sprintf('<div class="footer-putter-copyright %1$s">%2$s</div>', $params['footer_class'],  $this->copyright_owner($params));
	}	

	public function footer_menu($atts = array()) {
 		$defaults = array('menu' => 'Footer Menu', 'echo' => false, 'container' => false, 'footer_class' => self::CSS_CLASS);
		if (isset($atts['nav_menu'])) $atts['menu'] = $atts['nav_menu']; 
   		$params = shortcode_atts( $defaults, $atts ); //apply plugin defaults	
        return sprintf ('<div class="footer-putter-menu %1$s">%2$s</div>', $params['footer_class'], wp_nav_menu($params));
	}

	public function footer($atts = array()) {
  		$params = shortcode_atts( $this->get_options(), $atts ); //apply plugin defaults

		if ($params['center']) {
			$return_class = 'return-center';
			$footer_class = 'footer-center';
			$clear = '';
		} else {
			$return_class = ' return-left';
			$footer_class = ' footer-right';
			$clear = '<div class="clear"></div>';
		}	
        $layout = isset($atts['layout']) ? $atts['layout'] : 'single';

		$format = '<div class="%4$s %5$s %6$s">'.$this->get_footer_content_order($layout).'</div>%7$s';
		return (empty($params['show_return']) ? '' :
			$this->return_to_top($params['return_text'], $return_class. ' ' . $params['return_class'])) . 
			sprintf($format,
				(empty($params['nav_menu']) ? '' : $this->footer_menu($params)),
				(empty($params['show_copyright']) ? '' : $this->copyright($params)),
				$this->contact_info($params),
				self::CSS_CLASS,
				$footer_class, 	
                $layout, 
				$clear
			);				
	}

    private function get_footer_content_order($layout) {
        switch ($layout) {
            case 'single-alt': 
            case 'copyright-below': 
            case 'menu-above-alt': 
            case 'stacked-alt': return '%1$s%3$s%2$s'; 
         }
         return  '%1$s%2$s%3$s';
    } 

	public function terms_filter($content) {
		if ($terms = $this->get_terms()) {
			$from = array();
			$to = array();
			foreach ($terms as $term => $value) {
				$from[] = '%%'.$term.'%%';
				$to[] = $value;
			}
			return str_replace($from,$to,$content);
		}
		return $content;
	}

	public function custom_footer() {
		if ( is_active_sidebar( self::SIDEBAR_ID) ) {
         	$class = 'custom-footer'. ($this->get_option('hide_wordpress') ? ' hide-wordpress' :'');
			if ($this->is_html5()) {
				printf('<footer class="%1$s" role="contentinfo" itemscope="itemscope" itemtype="http://schema.org/WPFooter">', $class);
				dynamic_sidebar( self::SIDEBAR_ID );
				echo '</footer><!-- end .custom-footer -->';
			} else {
				printf('<div class="%1$s">', $class);
				dynamic_sidebar( self::SIDEBAR_ID );
				echo '</div><!-- end .custom-footer -->';
			}
		}
	}

    public function no_footer($content) { return ''; }  		

	public function add_privacy_footer($content) {
		$email = $this->get_term('email');	
		$address = $this->get_term('address');
		$country = $this->get_term('country');
		$owner = $this->get_term('owner');
		$contact = <<< PRIVACY
<h2>How to Contact Us</h2> 
<p>Questions about this statement or about our handling of your information may be sent by email to <a href="mailto:{$email}">{$email}</a>, or by post to {$owner} Privacy Office, {$address} {$country}. </p>
PRIVACY;
		return (strpos($content,'%%') == FALSE) ? ($content . $contact) : $content;
	}

	public function add_terms_footer($content) {
		$email = $this->get_term('email');	
		$address = $this->get_term('address');
		$country = $this->get_term('country');
		$courts = $this->get_term('courts');
		$owner = $this->get_term('owner');
		$copyright = $this->get_term('copyright');
		$updated = $this->get_term('updated');
		$terms_contact = $this->get_term('terms_contact');
		$disputes = <<< DISPUTES
<h2>Dispute Resolution</h2>
<p>These terms, and any dispute arising from the use of this site, will be governed by {$courts} without regard to its conflicts of laws provisions.</p>
DISPUTES;
		$terms = <<< TERMS
<h2>Feedback And Information</h2> 
<p>Any feedback you provide at this site shall be deemed to be non-confidential. {$owner} shall be free to use such information on an unrestricted basis.</p>
<p>The terms and conditions for this web site are subject to change without notice.<p>
<p>{$copyright} {$owner} All rights reserved.<br/> {$owner}, {$address} {$country}</p>
<p>Updated by The {$owner} Legal Team on {$updated}</p>
TERMS;
		if (strpos($content,'%%') == FALSE) {
			$content .= $courts ? $disputes : '';
			$content .= $address ? $terms : '';
		}
		return $content ;
	}

	function fix_home_link( $content, $args) {
		$class =  is_front_page()? ' class="current_page_item"' : '';
		$home_linktexts = array('Home','<span>Home</span>');
		foreach ($home_linktexts as $home_linktext) {
			$home_link = sprintf('<a>%1$s</a>',$home_linktext);
			if (strpos($content, $home_link) !== FALSE) 
				$content = str_replace ($home_link,sprintf('<a href="%1$s"%2$s>%3$s</a>',home_url(),$class,$home_linktext),$content); 
		} 
		return $content;
	}

	function footer_return($atts = array()) {
 		$defaults = array('return_text' => $this->defaults['return_text'],  'return_class' => $this->defaults['return_class']);
   		$params = shortcode_atts( $defaults, $atts ); //apply plugin defaults	
        return $this->return_to_top($params['return_text'], $params['return_class']);
	}

	function theme_specific_defaults($defaults) {
		switch (basename( TEMPLATEPATH ) ) {  
			case 'twentyten': 
				$defaults['footer_hook'] = 'twentyten_credits'; break;
			case 'twentyeleven': 
				$defaults['footer_hook'] = 'twentyeleven_credits'; break;
			case 'twentytwelve': 
				$defaults['footer_hook'] = 'twentytwelve_credits'; break;
			case 'twentythirteen': 
				$defaults['footer_hook'] = 'twentythirteen_credits'; break;
			case 'twentyfourteen': 
				$defaults['footer_hook'] = 'twentyfourteen_credits'; break;
			case 'twentyfifteen': 
				$defaults['footer_hook'] = 'twentyfifteen_credits'; break;
			case 'twentysixteen': 
				$defaults['footer_hook'] = 'twentysixteen_credits'; break;
			case 'twentyseventeen': 
				$defaults['footer_hook'] = 'get_template_part_template-parts/footer/site'; break;
			case 'generatepress': 
				$defaults['footer_hook'] = 'generate_credits'; break;
			case 'delicate': 
				$defaults['footer_hook'] = 'get_footer'; break;
			case 'genesis': 
				$defaults['footer_hook'] = 'genesis_footer';
				$defaults['footer_filter_hook'] = 'genesis_footer_output';
				break;
			case 'graphene': 
				$defaults['footer_hook'] = 'graphene_footer'; break;
			case 'pagelines': 
				$defaults['footer_hook'] = 'pagelines_leaf'; break;
			default: 
				$defaults['footer_hook'] = 'wp_footer';
				$defaults['footer_remove'] = false;				
				break;
		}
	   return $defaults;
    }
}
