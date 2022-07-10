function footer_putter_news_ajax(url) {
	var data = { action: footer_putter_news.ajaxaction, security: footer_putter_news.ajaxnonce, url: url };     
	jQuery.post( footer_putter_news.ajaxurl, data, function( response ) {
   	var ele = jQuery(footer_putter_news.ajaxresults);
      if( response.success ) 
      	ele.append( response.data );
/*      else if ( response.data.error )
      	ele.append( response.data.error );
*/
   });
}    

jQuery(document).ready(function($) {
	if (typeof footer_putter_news0 != 'undefined') footer_putter_news_ajax(footer_putter_news0.feedurl );
	if (typeof footer_putter_news1 != 'undefined') footer_putter_news_ajax(footer_putter_news1.feedurl );   
	if (typeof footer_putter_news2 != 'undefined') footer_putter_news_ajax(footer_putter_news2.feedurl );
});