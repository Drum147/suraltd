<?php
/**
 * The template part for header
 *
 * @package Industrial Lite 
 * @subpackage industrial-lite
 * @since industrial-lite 1.0
 */
?>

<div class="container">
	<div class="main-header-box">
		<div class="row">
			<div class="col-lg-3 col-md-3 logo-bdr">
		        <div class="logo">
		          	<?php if ( has_custom_logo() ) : ?>
		              <div class="site-logo"><?php the_custom_logo(); ?></div>
		            <?php endif; ?>
		            <?php $blog_info = get_bloginfo( 'name' ); ?>
		              <?php if ( ! empty( $blog_info ) ) : ?>
		                <?php if ( is_front_page() && is_home() ) : ?>
		                  <?php if( get_theme_mod('industrial_lite_logo_title_hide_show',true) != ''){ ?>
		                  	<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
		                  <?php } ?>
		                <?php else : ?>
		                  <?php if( get_theme_mod('industrial_lite_logo_title_hide_show',true) != ''){ ?>
		                  	<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
		                  <?php } ?>
		                <?php endif; ?>
		              <?php endif; ?>
		              <?php
		                $description = get_bloginfo( 'description', 'display' );
		                if ( $description || is_customize_preview() ) :
		              ?>
		              	<?php if( get_theme_mod('industrial_lite_tagline_hide_show',true) != ''){ ?>
			              <p class="site-description">
			                <?php echo esc_html($description); ?>
			              </p>
			            <?php } ?>
		            <?php endif; ?>
		        </div>
	      	</div>
	      	<div class="col-lg-9 col-md-9">
	      		<?php if( get_theme_mod('industrial_lite_topbar_hide_show') != ''){ ?>
		      		<div class="top-bar row mr-0">
		      			<div class="col-lg-9 col-md-9">
		      				<?php if( get_theme_mod( 'industrial_lite_location') != '') { ?>
			      				<span><i class="<?php echo esc_attr(get_theme_mod('industrial_lite_location_icon','fas fa-map-marker-alt')); ?>"></i><?php echo esc_html(get_theme_mod('industrial_lite_location',''));?></span>
			      			<?php }?>
			      			<?php if( get_theme_mod( 'industrial_lite_phone_number') != '') { ?>
			      				<span><i class="<?php echo esc_attr(get_theme_mod('industrial_lite_phone_icon','fas fa-phone')); ?>"></i><?php echo esc_html(get_theme_mod('industrial_lite_phone_number',''));?></span>
			      			<?php }?>
			      			<?php if( get_theme_mod( 'industrial_lite_email_address') != '') { ?>
			      				<span><i class="<?php echo esc_attr(get_theme_mod('industrial_lite_email_address_icon','fas fa-envelope')); ?>"></i><?php echo esc_html(get_theme_mod('industrial_lite_email_address',''));?></span>
			      			<?php }?>
			      		</div>
			      		<div class="col-lg-3 col-md-3">
			      			<?php if( get_theme_mod( 'industrial_lite_top_btn_url') != '' || get_theme_mod( 'industrial_lite_top_btn_text') != '') { ?>
				      			<div class="top-bar-btn">
				      				<a href="<?php echo esc_url(get_theme_mod('industrial_lite_top_btn_url','')) ?>"><?php echo esc_html(get_theme_mod('industrial_lite_top_btn_text','')) ?><span class="screen-reader-text"><?php esc_html_e( 'GET A QUOTE','industrial-lite' );?></span></a>
				      			</div>
				      		<?php }?>
			      		</div>
		      		</div>
		      	<?php } ?>
		      	<div class="header-menu <?php if( get_theme_mod( 'industrial_lite_sticky_header') != '') { ?> header-sticky"<?php } else { ?>close-sticky <?php } ?>">
		      		<div class="row">
		      			<div class="<?php if(get_theme_mod('industrial_lite_header_search',true)) { ?>col-lg-8 col-md-7 col-3 pr-lg-0" <?php } else { ?>col-lg-9 col-md-9" <?php } ?> >
				        	<?php get_template_part( 'template-parts/header/navigation' ); ?>
				      	</div>
				      	<?php if( get_theme_mod( 'industrial_lite_header_search',true) != '') { ?>
				        <div class="col-lg-1 col-md-2 col-3">
				          	<div class="search-box">
				            	<button type="button" data-toggle="modal" data-target="#myModal"><i class="fas fa-search"></i></button>
				          	</div>
				        </div>
				      	<?php }?>
				      	<div class="col-lg-3 col-md-3 col-6 pl-lg-0">
				      		<?php dynamic_sidebar('social-links'); ?>
				      	</div>
		      		</div>
		      	</div>
	      	</div>
		</div>
		<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		    <div class="modal-dialog" role="document">
		      <div class="modal-body">
		        <div class="serach_inner">
		          <?php get_search_form(); ?>
		        </div>
		      </div>
		      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		    </div>
		</div>
	</div>
</div>