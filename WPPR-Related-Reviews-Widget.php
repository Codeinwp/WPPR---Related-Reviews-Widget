<?php
/*
Plugin Name: WPPR - Related Reviews
Description: Earn more visitors, displaying related reviews for each product.
Version: 1.2.1
Author: Themeisle
Author URI:  https://themeisle.com/
Plugin URI: https://themeisle.com/wppr-related-reviews/
Requires at least: 3.5
Tested up to: 4.5.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: cwppose
*/

define( 'WPPR_RELATED_REVIEWS_VERSION' ,'1.2.1');
define("WPPR_RELATED_REVIEWS_PATH", realpath(dirname(__FILE__)));


class wppr_related_reviews extends WP_Widget {
	var $widget_name; 	// Widget name
	var $widget_desc; 	// Widget description
	var $plugin_slug; 	// identifier of this plugin for WP
	var $text_domain; 	// text domain of this plugin
	var $number_posts;  // number of posts to show in the widget
	var $post_id;  		// get post id
	var $is_review;  	// if is review return true or false

	// Controller
	function __construct() {
		$this->widget_name   = 'Related reviews';
		$this->widget_desc   = 'Earn more visitors, displaying related reviews for each product.';
		$this->plugin_slug   = 'WPPR-Related-Reviews-Widget';
		$this->text_domain 	 = 'cwppose';
		$this->number_posts  = 5;
		
		$widget_ops = array('classname' => 'widget_cwp_latest_products_widget', 'description' => __($this->widget_desc, $this->text_domain));
		parent::__construct($this->plugin_slug, __($this->widget_name, $this->text_domain), $widget_ops);

		add_action('admin_notices', array($this, 'widget_admin_notice'));
	}

	// widget form creation
	function form($instance) {
		// Check values
		$title 		  = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number_posts = isset($instance['number_posts']) ? absint($instance['number_posts']) : $this->number_posts;
		$show_thumb   = isset($instance['show_thumb']) ? (bool) $instance['show_thumb'] : true;
		?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', $this->text_domain); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('number_posts'); ?>"><?php _e('Number of posts to show:', $this->text_domain); ?></label>
			<input id="<?php echo $this->get_field_id('number_posts'); ?>" name="<?php echo $this->get_field_name('number_posts'); ?>" type="text" value="<?php echo $number_posts; ?>" size="3" />
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked($show_thumb); ?> id="<?php echo $this->get_field_id('show_thumb'); ?>" name="<?php echo $this->get_field_name('show_thumb'); ?>" />
			<label for="<?php echo $this->get_field_id('show_thumb'); ?>"><?php _e('Display thumbnail?', $this->text_domain); ?></label>
		</p>
		<?php
	}

	// update widget
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		// Fields
		$instance['title'] 		  = strip_tags($new_instance['title']);
		$instance['number_posts'] = absint($new_instance['number_posts']);
		$instance['show_thumb']   = isset($new_instance['show_thumb']) ? (bool) $new_instance['show_thumb'] : false;
		return $instance;
	}

	// display widget
	function widget($args, $instance) {
	   	extract( $args );

	   	// these are the widget options
	   	$title		   	 = apply_filters('widget_title', $instance['title']);
		$number_posts  	 = (!empty($instance['number_posts'])) ? absint($instance['number_posts']) : $this->number_posts;
		$show_thumb    	 = isset($instance['show_thumb']) ? $instance['show_thumb'] : false;
		$this->post_id 	 = (is_single()) ? get_the_ID() : false;
		$this->is_review = ((get_post_meta($this->post_id, 'cwp_meta_box_check', true) == 'Yes') && $this->post_id) ? true : false;

		//empty if does not exist review
		if(!$this->is_review)
			return 0;

	   	echo $before_widget;

		   	// Check if title is set
		   	if ( $title ) {
		      	echo $before_title . $title . $after_title;
		   	}

		   	//Show reviews
		   	$this->get_reviews($number_posts, $show_thumb);

	   	echo $after_widget;
	}

	function get_reviews($number_posts, $show_thumb){
		global $post;

		$post_categories = wp_get_post_categories($this->post_id);
		$post_tags		 = wp_get_post_tags($this->post_id, array('fields' => 'ids'));

		$args = array(
		    'posts_per_page' => $number_posts,
			'post_status'	 => 'publish',
			'meta_key'		 => 'cwp_meta_box_check',
			'meta_value'	 => 'Yes',
			'orderby'	 	 => 'date',
			'order'	 	 	 => 'DESC',
			'post__not_in' 	 => array($this->post_id),
		    'tax_query' => array(
		        'relation' => 'OR',
		        array(
		            'taxonomy' => 'category',
		            'field' => 'id',
		            'terms' => $post_categories,
		            'include_children' => false 
		        ),
		        array(
		            'taxonomy' => 'post_tag',
		            'field' => 'id',
		            'terms' => $post_tags,
		        )
		    )
		);

		$reviews = new WP_Query( apply_filters('widget_posts_args', $args));

		if ($reviews->have_posts()): ?>
			<ul>
				<?php while ($reviews->have_posts()) : $reviews->the_post(); ?>
					<li class="cwp-popular-review cwp_top_posts_widget_<?php the_ID(); ?>">
						<?php 
							// show thumb
							if($show_thumb){
								$product_image = get_post_meta($post->ID, "cwp_rev_product_image", true);
								if ($product_image) {
									echo '<img src="'.$product_image.'" alt="'.get_the_title().'" class="cwp_rev_image"/>';
								}elseif (has_post_thumbnail()) {
									echo wp_get_attachment_image(get_post_thumbnail_id(), 'thumbnail', 0, array('alt' => get_the_title(), 'class' => 'cwp_rev_image')); 
								}
							}
							// show title
							?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</li>
				<?php endwhile; ?>
			</ul>
		<?php wp_reset_postdata();
		endif;
	}

	// notice when the plugin is activated
	function widget_admin_notice() {
		if(isset($_GET['activate']) && $_GET['activate'] == true){
			$url_widget = admin_url( 'widgets.php');
		    ?>
		    <div class="updated">
		        <p><?php printf(__( 'Great, now go under <a href="%s">Appearance &#8250 Widgets</a> and place your widget in your sidebar.', $this->text_domain), $url_widget); ?></p>
		    </div>
		    <?php
		}
	}

}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("wppr_related_reviews");'));

// Added by Ash/Upwork
function wppr_rrw_load_dependencies(){
    require_once WPPR_RELATED_REVIEWS_PATH . "/lib/dependencies/tgm-activation.php";
}
add_action('plugins_loaded', 'wppr_rrw_load_dependencies');
// Added by Ash/Upwork

