<?php
	/*------------------------------ Global First Color -----------*/

	$industrial_lite_first_color = get_theme_mod('industrial_lite_first_color');

	$industrial_lite_custom_css = '';

	if($industrial_lite_first_color != false){
		$industrial_lite_custom_css .='.top-bar-btn a, .more-btn a, #slider .carousel-control-prev-icon:hover, #slider .carousel-control-next-icon:hover, input[type="submit"], #footer .tagcloud a:hover, #sidebar .custom-social-icons i, #footer .custom-social-icons i, #footer-2, .home-page-header, #sidebar h3, .pagination .current, .pagination a:hover, #sidebar .tagcloud a:hover, #comments input[type="submit"], .woocommerce #respond input#submit, .woocommerce a.button, .woocommerce button.button, .woocommerce input.button, .woocommerce #respond input#submit.alt, .woocommerce a.button.alt, .woocommerce button.button.alt, .woocommerce input.button.alt, nav.woocommerce-MyAccount-navigation ul li, #sidebar .widget_price_filter .ui-slider .ui-slider-range, #sidebar .widget_price_filter .ui-slider .ui-slider-handle, #sidebar .woocommerce-product-search button, #footer .widget_price_filter .ui-slider .ui-slider-range, #footer .widget_price_filter .ui-slider .ui-slider-handle, #footer .woocommerce-product-search button, #footer a.custom_read_more, #sidebar a.custom_read_more, .nav-previous a:hover, .nav-next a:hover, .woocommerce nav.woocommerce-pagination ul li a:hover, .woocommerce nav.woocommerce-pagination ul li span.current, .wp-block-button__link{';
			$industrial_lite_custom_css .='background-color: '.esc_attr($industrial_lite_first_color).';';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_first_color != false){
		$industrial_lite_custom_css .='a, .top-bar i, #serv-section h2 i, #footer li a:hover, #footer .custom-social-icons i:hover, #footer caption, #sidebar caption, .scrollup i, .post-main-box:hover h2, #sidebar ul li a:hover, .post-navigation a:hover .post-title, .post-navigation a:focus .post-title, .entry-content a, .post-main-box:hover h2 a, .main-navigation a:hover, .main-navigation ul.sub-menu a:hover, .entry-content a, .sidebar .textwidget p a, .textwidget p a, #comments p a, .slider .inner_carousel p a, #footer a.custom_read_more:hover, #footer input[type="submit"]:hover{';
			$industrial_lite_custom_css .='color: '.esc_attr($industrial_lite_first_color).';';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_first_color != false){
		$industrial_lite_custom_css .='.scrollup i{';
			$industrial_lite_custom_css .='border-color: '.esc_attr($industrial_lite_first_color).';';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_first_color != false){
		$industrial_lite_custom_css .='.main-navigation ul ul{';
			$industrial_lite_custom_css .='border-top-color: '.esc_attr($industrial_lite_first_color).';';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_first_color != false){
		$industrial_lite_custom_css .='#footer h3:after, .main-navigation ul ul, .header-fixed{';
			$industrial_lite_custom_css .='border-bottom-color: '.esc_attr($industrial_lite_first_color).';';
		$industrial_lite_custom_css .='}';
	}

	/*------------------------------ Global Second Color -----------*/

	$industrial_lite_second_color = get_theme_mod('industrial_lite_second_color');

	if($industrial_lite_second_color != false){
		$industrial_lite_custom_css .='.main-header-box, #slider .carousel-control-prev-icon, #slider .carousel-control-next-icon, .more-btn a:hover, #footer, .pagination span, .pagination a, #sidebar .custom-social-icons i:hover, .woocommerce span.onsale, .woocommerce #respond input#submit:hover, .woocommerce a.button:hover, .woocommerce button.button:hover, .woocommerce input.button:hover, .woocommerce #respond input#submit.alt:hover, .woocommerce a.button.alt:hover, .woocommerce button.button.alt:hover, .woocommerce input.button.alt:hover, #comments a.comment-reply-link, #sidebar a.custom_read_more:hover, #sidebar input[type="submit"]:hover, .header-fixed, .nav-previous a, .nav-next a, .woocommerce nav.woocommerce-pagination ul li a, .wp-block-button .wp-block-button__link:hover{';
			$industrial_lite_custom_css .='background-color: '.esc_attr($industrial_lite_second_color).';';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_second_color != false){
		$industrial_lite_custom_css .='.top-bar-btn a, #slider .inner_carousel h1 a, .more-btn a, #slider .inner_carousel p, h1, h2, h3, h4, h5, h6, #footer input[type="submit"], #sidebar .custom-social-icons i, #footer .custom-social-icons i, .copyright p, .copyright a, #footer .tagcloud a:hover, .post-main-box h2, .pagination a:hover, .pagination .current, #sidebar input[type="submit"], #sidebar h3, #sidebar .tagcloud a:hover, #comments input[type="submit"], .post-navigation a, input[type="submit"], h2.woocommerce-loop-product__title, .woocommerce div.product .product_title, .woocommerce #respond input#submit, .woocommerce a.button, .woocommerce button.button, .woocommerce input.button, .woocommerce #respond input#submit.alt, .woocommerce a.button.alt, .woocommerce button.button.alt, .woocommerce input.button.alt, .woocommerce .quantity .qty, .woocommerce-message::before, .woocommerce-info::before, nav.woocommerce-MyAccount-navigation ul li a, .main-header-box .custom-social-icons i:hover, .post-main-box h2 a, #sidebar .woocommerce-product-search button, #footer .woocommerce-product-search button, #footer a.custom_read_more, #sidebar a.custom_read_more{';
			$industrial_lite_custom_css .='color: '.esc_attr($industrial_lite_second_color).';';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_second_color != false){
		$industrial_lite_custom_css .='.wp-block-button__link{';
			$industrial_lite_custom_css .='color: '.esc_attr($industrial_lite_second_color).'!important;';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_second_color != false){
		$industrial_lite_custom_css .='.woocommerce .quantity .qty{';
			$industrial_lite_custom_css .='border-color: '.esc_attr($industrial_lite_second_color).';';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_second_color != false){
		$industrial_lite_custom_css .='.woocommerce-message, .woocommerce-info{';
			$industrial_lite_custom_css .='border-top-color: '.esc_attr($industrial_lite_second_color).';';
		$industrial_lite_custom_css .='}';
	}
	if($industrial_lite_second_color != false){
		$industrial_lite_custom_css .='nav.woocommerce-MyAccount-navigation ul li{
		box-shadow: 2px 2px 0 0 '.esc_attr($industrial_lite_second_color).';
		}';
	}

	/*---------------------------Width Layout -------------------*/

	$industrial_lite_theme_lay = get_theme_mod( 'industrial_lite_width_option','Full Width');
    if($industrial_lite_theme_lay == 'Boxed'){
		$industrial_lite_custom_css .='body{';
			$industrial_lite_custom_css .='max-width: 1140px; width: 100%; padding-right: 15px; padding-left: 15px; margin-right: auto; margin-left: auto;';
		$industrial_lite_custom_css .='}';
		$industrial_lite_custom_css .='.page-template-custom-home-page .home-page-header{';
			$industrial_lite_custom_css .='width: 97%;';
		$industrial_lite_custom_css .='}';
	}else if($industrial_lite_theme_lay == 'Wide Width'){
		$industrial_lite_custom_css .='body{';
			$industrial_lite_custom_css .='width: 100%;padding-right: 15px;padding-left: 15px;margin-right: auto;margin-left: auto;';
		$industrial_lite_custom_css .='}';
	}else if($industrial_lite_theme_lay == 'Full Width'){
		$industrial_lite_custom_css .='body{';
			$industrial_lite_custom_css .='max-width: 100%;';
		$industrial_lite_custom_css .='}';
	}

	/*--------------------------- Slider Opacity -------------------*/

	$industrial_lite_theme_lay = get_theme_mod( 'industrial_lite_slider_opacity_color','0.4');
	if($industrial_lite_theme_lay == '0'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.1'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.1';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.2'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.2';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.3'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.3';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.4'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.4';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.5'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.5';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.6'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.6';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.7'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.7';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.8'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.8';
		$industrial_lite_custom_css .='}';
		}else if($industrial_lite_theme_lay == '0.9'){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='opacity:0.9';
		$industrial_lite_custom_css .='}';
		}

	/*---------------------------Slider Content Layout -------------------*/

	$industrial_lite_theme_lay = get_theme_mod( 'industrial_lite_slider_content_option','Left');
    if($industrial_lite_theme_lay == 'Left'){
		$industrial_lite_custom_css .='#slider .carousel-caption, #slider .inner_carousel, #slider .inner_carousel h1, #slider .inner_carousel p, #slider .more-btn{';
			$industrial_lite_custom_css .='text-align:left; left:15%; right:45%;';
		$industrial_lite_custom_css .='}';
	}else if($industrial_lite_theme_lay == 'Center'){
		$industrial_lite_custom_css .='#slider .carousel-caption, #slider .inner_carousel, #slider .inner_carousel h1, #slider .inner_carousel p, #slider .more-btn{';
			$industrial_lite_custom_css .='text-align:center; left:20%; right:20%;';
		$industrial_lite_custom_css .='}';
		$industrial_lite_custom_css .='#slider .inner_carousel h1{';
			$industrial_lite_custom_css .='padding: 0;';
		$industrial_lite_custom_css .='}';
	}else if($industrial_lite_theme_lay == 'Right'){
		$industrial_lite_custom_css .='#slider .carousel-caption, #slider .inner_carousel, #slider .inner_carousel h1, #slider .inner_carousel p, #slider .more-btn{';
			$industrial_lite_custom_css .='text-align:right; left:45%; right:15%;';
		$industrial_lite_custom_css .='}';
		$industrial_lite_custom_css .='#slider .inner_carousel h1, #slider .inner_carousel p{';
			$industrial_lite_custom_css .='padding: 0;';
		$industrial_lite_custom_css .='}';
	}

	/*---------------------------Slider Height ------------*/

	$industrial_lite_slider_height = get_theme_mod('industrial_lite_slider_height');
	if($industrial_lite_slider_height != false){
		$industrial_lite_custom_css .='#slider img{';
			$industrial_lite_custom_css .='height: '.esc_attr($industrial_lite_slider_height).';';
		$industrial_lite_custom_css .='}';
	}

	/*--------------------------- Slider -------------------*/

	$industrial_lite_slider = get_theme_mod('industrial_lite_slider_arrows');
	if($industrial_lite_slider == false){
		$industrial_lite_custom_css .='.page-template-custom-home-page .home-page-header{';
			$industrial_lite_custom_css .='position:static; background:#f7c02e; padding-bottom:25px;';
		$industrial_lite_custom_css .='}';
		$industrial_lite_custom_css .='#serv-section{';
			$industrial_lite_custom_css .='padding: 0;';
		$industrial_lite_custom_css .='}';
	}

	/*---------------------------Blog Layout -------------------*/

	$industrial_lite_theme_lay = get_theme_mod( 'industrial_lite_blog_layout_option','Default');
    if($industrial_lite_theme_lay == 'Default'){
		$industrial_lite_custom_css .='.post-main-box{';
			$industrial_lite_custom_css .='';
		$industrial_lite_custom_css .='}';
	}else if($industrial_lite_theme_lay == 'Center'){
		$industrial_lite_custom_css .='.post-main-box, .post-main-box h2, .post-info, .new-text p, .post-main-box .more-btn{';
			$industrial_lite_custom_css .='text-align:center;';
		$industrial_lite_custom_css .='}';
		$industrial_lite_custom_css .='.post-info{';
			$industrial_lite_custom_css .='margin-top:10px;';
		$industrial_lite_custom_css .='}';
		$industrial_lite_custom_css .='.post-info hr{';
			$industrial_lite_custom_css .='margin:10px auto;';
		$industrial_lite_custom_css .='}';
	}else if($industrial_lite_theme_lay == 'Left'){
		$industrial_lite_custom_css .='.post-main-box, .post-main-box h2, .post-info, .new-text p, .post-main-box .more-btn, #our-services p{';
			$industrial_lite_custom_css .='text-align:Left;';
		$industrial_lite_custom_css .='}';
		$industrial_lite_custom_css .='.post-info hr{';
			$industrial_lite_custom_css .='margin-bottom:10px;';
		$industrial_lite_custom_css .='}';
		$industrial_lite_custom_css .='.post-main-box h2{';
			$industrial_lite_custom_css .='margin-top:10px;';
		$industrial_lite_custom_css .='}';
	}

	/*------------------------------Responsive Media -----------------------*/

	$industrial_lite_resp_topbar = get_theme_mod( 'industrial_lite_resp_topbar_hide_show',false);
    if($industrial_lite_resp_topbar == true){
    	$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='.top-bar{';
			$industrial_lite_custom_css .='display:block;';
		$industrial_lite_custom_css .='} }';
	}else if($industrial_lite_resp_topbar == false){
		$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='.top-bar{';
			$industrial_lite_custom_css .='display:none;';
		$industrial_lite_custom_css .='} }';
	}

	$industrial_lite_resp_stickyheader = get_theme_mod( 'industrial_lite_stickyheader_hide_show',false);
    if($industrial_lite_resp_stickyheader == true){
    	$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='.header-fixed{';
			$industrial_lite_custom_css .='display:block;';
		$industrial_lite_custom_css .='} }';
	}else if($industrial_lite_resp_stickyheader == false){
		$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='.header-fixed{';
			$industrial_lite_custom_css .='display:none;';
		$industrial_lite_custom_css .='} }';
	}

	$industrial_lite_resp_slider = get_theme_mod( 'industrial_lite_resp_slider_hide_show',false);
    if($industrial_lite_resp_slider == true){
    	$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='#slider{';
			$industrial_lite_custom_css .='display:block;';
		$industrial_lite_custom_css .='} }';
	}else if($industrial_lite_resp_slider == false){
		$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='#slider{';
			$industrial_lite_custom_css .='display:none;';
		$industrial_lite_custom_css .='} }';
	}

	$industrial_lite_metabox = get_theme_mod( 'industrial_lite_metabox_hide_show',true);
    if($industrial_lite_metabox == true){
    	$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='.post-info{';
			$industrial_lite_custom_css .='display:block;';
		$industrial_lite_custom_css .='} }';
	}else if($industrial_lite_metabox == false){
		$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='.post-info{';
			$industrial_lite_custom_css .='display:none;';
		$industrial_lite_custom_css .='} }';
	}

	$industrial_lite_sidebar = get_theme_mod( 'industrial_lite_sidebar_hide_show',true);
    if($industrial_lite_sidebar == true){
    	$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='#sidebar{';
			$industrial_lite_custom_css .='display:block;';
		$industrial_lite_custom_css .='} }';
	}else if($industrial_lite_sidebar == false){
		$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='#sidebar{';
			$industrial_lite_custom_css .='display:none;';
		$industrial_lite_custom_css .='} }';
	}

	$industrial_lite_resp_scroll_top = get_theme_mod( 'industrial_lite_resp_scroll_top_hide_show',true);
    if($industrial_lite_resp_scroll_top == true){
    	$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='.scrollup i{';
			$industrial_lite_custom_css .='display:block;';
		$industrial_lite_custom_css .='} }';
	}else if($industrial_lite_resp_scroll_top == false){
		$industrial_lite_custom_css .='@media screen and (max-width:575px) {';
		$industrial_lite_custom_css .='.scrollup i{';
			$industrial_lite_custom_css .='display:none !important;';
		$industrial_lite_custom_css .='} }';
	}

	/*------------- Top Bar Settings ------------------*/

	$industrial_lite_topbar_padding_top_bottom = get_theme_mod('industrial_lite_topbar_padding_top_bottom');
	if($industrial_lite_topbar_padding_top_bottom != false){
		$industrial_lite_custom_css .='.top-bar{';
			$industrial_lite_custom_css .='padding-top: '.esc_attr($industrial_lite_topbar_padding_top_bottom).'; padding-bottom: '.esc_attr($industrial_lite_topbar_padding_top_bottom).';';
		$industrial_lite_custom_css .='}';
	}

	/*-------------- Sticky Header Padding ----------------*/

	$industrial_lite_sticky_header_padding = get_theme_mod('industrial_lite_sticky_header_padding');
	if($industrial_lite_sticky_header_padding != false){
		$industrial_lite_custom_css .='.header-fixed{';
			$industrial_lite_custom_css .='padding: '.esc_attr($industrial_lite_sticky_header_padding).';';
		$industrial_lite_custom_css .='}';
	}

	/*------------------ Search Settings -----------------*/
	
	$industrial_lite_search_font_size = get_theme_mod('industrial_lite_search_font_size');
	if($industrial_lite_search_font_size != false){
		$industrial_lite_custom_css .='.search-box i{';
			$industrial_lite_custom_css .='font-size: '.esc_attr($industrial_lite_search_font_size).';';
		$industrial_lite_custom_css .='}';
	}

	/*---------------- Button Settings ------------------*/

	$industrial_lite_button_padding_top_bottom = get_theme_mod('industrial_lite_button_padding_top_bottom');
	$industrial_lite_button_padding_left_right = get_theme_mod('industrial_lite_button_padding_left_right');
	if($industrial_lite_button_padding_top_bottom != false || $industrial_lite_button_padding_left_right != false){
		$industrial_lite_custom_css .='.post-main-box .more-btn a{';
			$industrial_lite_custom_css .='padding-top: '.esc_attr($industrial_lite_button_padding_top_bottom).'; padding-bottom: '.esc_attr($industrial_lite_button_padding_top_bottom).';padding-left: '.esc_attr($industrial_lite_button_padding_left_right).';padding-right: '.esc_attr($industrial_lite_button_padding_left_right).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_button_border_radius = get_theme_mod('industrial_lite_button_border_radius');
	if($industrial_lite_button_border_radius != false){
		$industrial_lite_custom_css .='.post-main-box .more-btn a{';
			$industrial_lite_custom_css .='border-radius: '.esc_attr($industrial_lite_button_border_radius).'px;';
		$industrial_lite_custom_css .='}';
	}

	/*------------- Single Blog Page------------------*/

	$industrial_lite_single_blog_post_navigation_show_hide = get_theme_mod('industrial_lite_single_blog_post_navigation_show_hide',true);
	if($industrial_lite_single_blog_post_navigation_show_hide != true){
		$industrial_lite_custom_css .='.post-navigation{';
			$industrial_lite_custom_css .='display: none;';
		$industrial_lite_custom_css .='}';
	}

	/*-------------- Copyright Alignment ----------------*/

	$industrial_lite_copyright_alingment = get_theme_mod('industrial_lite_copyright_alingment');
	if($industrial_lite_copyright_alingment != false){
		$industrial_lite_custom_css .='.copyright p{';
			$industrial_lite_custom_css .='text-align: '.esc_attr($industrial_lite_copyright_alingment).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_copyright_padding_top_bottom = get_theme_mod('industrial_lite_copyright_padding_top_bottom');
	if($industrial_lite_copyright_padding_top_bottom != false){
		$industrial_lite_custom_css .='#footer-2{';
			$industrial_lite_custom_css .='padding-top: '.esc_attr($industrial_lite_copyright_padding_top_bottom).'; padding-bottom: '.esc_attr($industrial_lite_copyright_padding_top_bottom).';';
		$industrial_lite_custom_css .='}';
	}

	/*----------------Sroll to top Settings ------------------*/

	$industrial_lite_scroll_to_top_font_size = get_theme_mod('industrial_lite_scroll_to_top_font_size');
	if($industrial_lite_scroll_to_top_font_size != false){
		$industrial_lite_custom_css .='.scrollup i{';
			$industrial_lite_custom_css .='font-size: '.esc_attr($industrial_lite_scroll_to_top_font_size).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_scroll_to_top_padding = get_theme_mod('industrial_lite_scroll_to_top_padding');
	$industrial_lite_scroll_to_top_padding = get_theme_mod('industrial_lite_scroll_to_top_padding');
	if($industrial_lite_scroll_to_top_padding != false){
		$industrial_lite_custom_css .='.scrollup i{';
			$industrial_lite_custom_css .='padding-top: '.esc_attr($industrial_lite_scroll_to_top_padding).';padding-bottom: '.esc_attr($industrial_lite_scroll_to_top_padding).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_scroll_to_top_width = get_theme_mod('industrial_lite_scroll_to_top_width');
	if($industrial_lite_scroll_to_top_width != false){
		$industrial_lite_custom_css .='.scrollup i{';
			$industrial_lite_custom_css .='width: '.esc_attr($industrial_lite_scroll_to_top_width).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_scroll_to_top_height = get_theme_mod('industrial_lite_scroll_to_top_height');
	if($industrial_lite_scroll_to_top_height != false){
		$industrial_lite_custom_css .='.scrollup i{';
			$industrial_lite_custom_css .='height: '.esc_attr($industrial_lite_scroll_to_top_height).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_scroll_to_top_border_radius = get_theme_mod('industrial_lite_scroll_to_top_border_radius');
	if($industrial_lite_scroll_to_top_border_radius != false){
		$industrial_lite_custom_css .='.scrollup i{';
			$industrial_lite_custom_css .='border-radius: '.esc_attr($industrial_lite_scroll_to_top_border_radius).'px;';
		$industrial_lite_custom_css .='}';
	}

	/*----------------Social Icons Settings ------------------*/

	$industrial_lite_social_icon_font_size = get_theme_mod('industrial_lite_social_icon_font_size');
	if($industrial_lite_social_icon_font_size != false){
		$industrial_lite_custom_css .='#sidebar .custom-social-icons i, #footer .custom-social-icons i{';
			$industrial_lite_custom_css .='font-size: '.esc_attr($industrial_lite_social_icon_font_size).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_social_icon_padding = get_theme_mod('industrial_lite_social_icon_padding');
	if($industrial_lite_social_icon_padding != false){
		$industrial_lite_custom_css .='#sidebar .custom-social-icons i, #footer .custom-social-icons i{';
			$industrial_lite_custom_css .='padding: '.esc_attr($industrial_lite_social_icon_padding).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_social_icon_width = get_theme_mod('industrial_lite_social_icon_width');
	if($industrial_lite_social_icon_width != false){
		$industrial_lite_custom_css .='#sidebar .custom-social-icons i, #footer .custom-social-icons i{';
			$industrial_lite_custom_css .='width: '.esc_attr($industrial_lite_social_icon_width).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_social_icon_height = get_theme_mod('industrial_lite_social_icon_height');
	if($industrial_lite_social_icon_height != false){
		$industrial_lite_custom_css .='#sidebar .custom-social-icons i, #footer .custom-social-icons i{';
			$industrial_lite_custom_css .='height: '.esc_attr($industrial_lite_social_icon_height).';';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_social_icon_border_radius = get_theme_mod('industrial_lite_social_icon_border_radius');
	if($industrial_lite_social_icon_border_radius != false){
		$industrial_lite_custom_css .='#sidebar .custom-social-icons i, #footer .custom-social-icons i{';
			$industrial_lite_custom_css .='border-radius: '.esc_attr($industrial_lite_social_icon_border_radius).'px;';
		$industrial_lite_custom_css .='}';
	}

	/*----------------Woocommerce Products Settings ------------------*/

	$industrial_lite_products_padding_top_bottom = get_theme_mod('industrial_lite_products_padding_top_bottom');
	if($industrial_lite_products_padding_top_bottom != false){
		$industrial_lite_custom_css .='.woocommerce ul.products li.product, .woocommerce-page ul.products li.product{';
			$industrial_lite_custom_css .='padding-top: '.esc_attr($industrial_lite_products_padding_top_bottom).'!important; padding-bottom: '.esc_attr($industrial_lite_products_padding_top_bottom).'!important;';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_products_padding_left_right = get_theme_mod('industrial_lite_products_padding_left_right');
	if($industrial_lite_products_padding_left_right != false){
		$industrial_lite_custom_css .='.woocommerce ul.products li.product, .woocommerce-page ul.products li.product{';
			$industrial_lite_custom_css .='padding-left: '.esc_attr($industrial_lite_products_padding_left_right).'!important; padding-right: '.esc_attr($industrial_lite_products_padding_left_right).'!important;';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_products_box_shadow = get_theme_mod('industrial_lite_products_box_shadow');
	if($industrial_lite_products_box_shadow != false){
		$industrial_lite_custom_css .='.woocommerce ul.products li.product, .woocommerce-page ul.products li.product{';
				$industrial_lite_custom_css .='box-shadow: '.esc_attr($industrial_lite_products_box_shadow).'px '.esc_attr($industrial_lite_products_box_shadow).'px '.esc_attr($industrial_lite_products_box_shadow).'px #ddd;';
		$industrial_lite_custom_css .='}';
	}

	$industrial_lite_products_border_radius = get_theme_mod('industrial_lite_products_border_radius');
	if($industrial_lite_products_border_radius != false){
		$industrial_lite_custom_css .='.woocommerce ul.products li.product, .woocommerce-page ul.products li.product{';
			$industrial_lite_custom_css .='border-radius: '.esc_attr($industrial_lite_products_border_radius).'px;';
		$industrial_lite_custom_css .='}';
	}