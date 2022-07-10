<?php
class Footer_Putter_Plugin {

  	const OPTIONS_NAME = 'footer_putter_options';
	private $help = FOOTER_PUTTER_HELP;
  	private $home = FOOTER_PUTTER_HOME;
  	private $icon = FOOTER_PUTTER_ICON;
 	private $name = FOOTER_PUTTER_NAME;
	private $newsfeeds = array(DIYWEBMASTERY_NEWS);
 	private $path = FOOTER_PUTTER_PATH;
 	private $slug = FOOTER_PUTTER_SLUG;
 	private $version = FOOTER_PUTTER_VERSION;
	private $news;
	private $options;
	private $tooltips;
	private $utils;
	private $defaults = array();
	private $links = array();
	private $credits;
	
	static function get_instance() {
        static $instance = null;
        if (null === $instance) {
            // $instance = new static(); //use self instead of static to support 5.2 - not the same but okay as the plugin class is not extended 
            $instance = new self(); 
            register_activation_hook($instance->path, array($instance, 'activate'));            
            add_action('init', array($instance, 'init'),0);
            if (is_admin()) add_action('init', array($instance, 'admin_init'),0);

        }
        return $instance;
	}
   
  	protected function __construct() {}

	private function __clone() {}

  	private function __wakeup() {}


 	function init() {
		$dir = dirname(__FILE__) . '/';
		require_once($dir . 'class-utils.php');
		require_once($dir . 'class-tooltip.php');
		require_once($dir . 'class-options.php');
		require_once($dir . 'class-widget.php');
		require_once($dir . 'class-credits.php');
		require_once($dir . 'class-credits-widgets.php');
		require_once($dir . 'class-trademarks-widgets.php');
		$this->utils = new Footer_Putter_Utils();
		$this->tooltips = new Footer_Putter_Tooltip();
		$this->options = new Footer_Putter_Options( self::OPTIONS_NAME, $this->defaults);
		$this->credits = new Footer_Putter_Credits();
	}
	
	function admin_init() {
		$dir = dirname(__FILE__) . '/';
		require_once($dir . 'class-tooltip.php');
		require_once($dir . 'class-admin.php');
		require_once($dir . 'class-news.php');
		require_once($dir . 'class-dashboard.php');
		require_once($dir . 'class-credits-admin.php');
		require_once($dir . 'class-trademarks-admin.php');		
		$this->news = new Footer_Putter_News($this->version);
		$intro = new Footer_Putter_Dashboard($this);
		$this->links['intro'] = $intro->get_url();
		$credits = new Footer_Putter_Credits_Admin($this, 'credits');	
		$this->links['credits'] = $credits->get_url();
		$trademarks = new Footer_Putter_Trademarks_Admin($this, 'trademarks');	
		$this->links['trademarks'] = $trademarks->get_url();
	}	

	public function get_help(){
		return $this->help;
	}
	
	public function get_home(){
		return $this->home;
	}

	public function get_icon(){
		return $this->icon;
	}

	public function get_name(){
		return $this->name;
	}

	public function get_news(){
		return $this->news;
	}

	public function get_newsfeeds(){
		return $this->newsfeeds;
	}

	public function get_options(){
		return $this->options;
	}


	public function get_path(){
		return $this->path;
	}

	public function get_slug(){
		return $this->slug;
	}

	public function get_tooltips(){
		return $this->tooltips;
	}

	public function get_utils(){
		return $this->utils;
	}

	public function get_version(){
		return $this->version;
	}

	function get_link_url($key) {
		if (array_key_exists($key, $this->links))
			return $this->links[$key];
		else
			return ('#');
	}

    function get_credits() {
         return $this->credits;
    }
}
