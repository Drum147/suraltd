<?php
/**
 * Related posts based on categories and tags.
 * 
 */

$industrial_lite_related_posts_taxonomy = get_theme_mod( 'industrial_lite_related_posts_taxonomy', 'category' );

$industrial_lite_post_args = array(
    'posts_per_page'    => absint( get_theme_mod( 'industrial_lite_related_posts_count', '3' ) ),
    'orderby'           => 'rand',
    'post__not_in'      => array( get_the_ID() ),
);

$industrial_lite_tax_terms = wp_get_post_terms( get_the_ID(), 'category' );
$industrial_lite_terms_ids = array();
foreach( $industrial_lite_tax_terms as $tax_term ) {
	$industrial_lite_terms_ids[] = $tax_term->term_id;
}

$industrial_lite_post_args['category__in'] = $industrial_lite_terms_ids; 

if(get_theme_mod('industrial_lite_related_post',true)==1){

$industrial_lite_related_posts = new WP_Query( $industrial_lite_post_args );

if ( $industrial_lite_related_posts->have_posts() ) : ?>
    <div class="related-post">
        <h3><?php echo esc_html(get_theme_mod('industrial_lite_related_post_title','Related Post'));?></h3>
        <div class="row">
            <?php while ( $industrial_lite_related_posts->have_posts() ) : $industrial_lite_related_posts->the_post(); ?>
                <?php get_template_part('template-parts/grid-layout'); ?>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif;
wp_reset_postdata();

}