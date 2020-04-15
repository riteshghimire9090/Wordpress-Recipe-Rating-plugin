<?php
/**
 * Plugin Name:      Rating For Recipe
 * Plugin URI:        #
 * Description:       Handle the basic with recipe rating.
 * Version:           1.0.0
 * Requires at least: 4.9
 * Requires PHP:      7.2
 * Author:            Ritesh

 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       recipe-rating
 * Domain Path:       /languages
 */



 if(!function_exists('add_action'))
 {
 return "Use Wordpress to activate plugin";
 exit;
 }
 //setup

 

 
 //Hooks


 register_activation_hook(__FILE__,"RFR_activate_plugin");
 add_action("init","RFR_init");
 add_action('save_post_recipe','RFR_save_post_admin',10,3);
 add_filter('the_content','RFR_the_content');
 add_action('wp_enqueue_scripts', 'RFR_load_scripts');
 add_action('wp_ajax_r_rate_recipe','RFR_rate_recipe');
 add_action("admin_init",'RFR_main_init');

 
 //Shortcodes


 //function
 function RFR_activate_plugin()
 {
     if (version_compare(get_bloginfo('version'),'4.9',"<")) { wp_die("Update Wordpress to 5.0 and above"); } global
     	$wpdb; $table_name=$wpdb->prefix . 'recipe_ratings';
     	$wpdb_collate = $wpdb->collate;
     	$createSQL ="CREATE TABLE {$table_name} ( `ID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT ,
     	`recipe_id` BIGINT(20) UNSIGNED NOT NULL ,
     	`ratings` FLOAT(3,2) UNSIGNED NOT NULL ,
     	`user_ip` VARCHAR(50) NOT NULL
     	, PRIMARY KEY (`ID`)) ENGINE = InnoDB COLLATE {$wpdb_collate};";
     	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

     	dbDelta( $createSQL );
     
 }

 function RFR_init()
 {
    $labels = array(
    'name' => _x( 'Recipes', 'post type general name', 'recipe' ),
    'singular_name' => _x( 'Recipe', 'post type singular name', 'recipe' ),
    'menu_name' => _x( 'Recipes', 'admin menu', 'recipe' ),
    'name_admin_bar' => _x( 'Recipe', 'add new on admin bar', 'recipe' ),
    'add_new' => _x( 'Add New Recipe', 'Recipe', 'recipe' ),
    'add_new_item' => __( 'Add New Recipe', 'recipe' ),
    'new_item' => __( 'New Recipe', 'recipe' ),
    'edit_item' => __( 'Edit Recipe', 'recipe' ),
    'view_item' => __( 'View Recipe', 'recipe' ),
    'all_items' => __( 'All Recipes', 'recipe' ),
    'search_items' => __( 'Search Recipes', 'recipe' ),
    'parent_item_colon' => __( 'Parent Recipes:', 'recipe' ),
    'not_found' => __( 'No Recipes found.', 'recipe' ),
    'not_found_in_trash' => __( 'No Recipes found in Trash.', 'recipe' )
    );

    $args = array(
    'labels' => $labels,
    'description' => __( 'This is plugin for Recipe.', 'recipe' ),
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'recipe' ),
    'capability_type' => 'post',
    'has_archive' => true,
    'hierarchical' => false,
    'menu_position' => 10,
    'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'comments' ),
    'taxonomies' =>['category','post_tag'],
    'show_in_rest' =>true
    );
    register_post_type( 'recipe', $args );
 }
 function RFR_save_post_admin($post_id,$post,$update)
 {
	 $recipe_data 	=	get_post_meta( $post_id, 'recipe_data', true );
	 $recipe_data	=	empty($recipe_data)	?	[]	:	$recipe_data;
	 $recipe_data['rating']	=	isset( $recipe_data['rating'])?absint( $recipe_data['rating']):0;
	 $recipe_data['rating_count']	=	isset( $recipe_data['rating_count'])?absint( $recipe_data['rating_count']):0;
	update_post_meta( $post_id, 'recipe_data', $recipe_data );
 }

 function RFR_the_content($the_content)
 {
	 if (!is_singular($post_types = "recipe")) {
	 return ''. $the_content;
	 }
	 global $post,$wpdb;
	 $table = $wpdb->prefix."recipe_ratings";
	 $ip = $_SERVER['REMOTE_ADDR'];
	 $rating_count= $wpdb->get_var(
	 "SELECT COUNT(*) FROM `".$table."` WHERE recipe_id='". $post->ID ."' AND user_ip='".$ip."'"
	 );
	 //return $recipe_data['rating'];
	 $recipe_data = get_post_meta( $post->ID, "recipe_data", true );
	 $recipe_HTML = file_get_contents("recipe-template.php",true);
	 $recipe_HTML = str_replace("RATE_I18N","Rating",$recipe_HTML);
	 $recipe_HTML = str_replace("RECIPE_ID",$post->ID,$recipe_HTML);
	 $recipe_HTML = str_replace("RECIPE_RATING",$recipe_data['rating'],$recipe_HTML);

	 if($rating_count>0){
	 $recipe_HTML = str_replace("READONLY_PLACEHOLDER","data-rateit-READONLY='true'",$recipe_HTML);

	 }
	 else{
	 $recipe_HTML = str_replace("READONLY_PLACEHOLDER","",$recipe_HTML);
	 }


	 return $recipe_HTML."<br>". $the_content;
 }
 function RFR_load_scripts()
  {
 

	wp_enqueue_script( 'rate-it-min', plugins_url( 'assets\js\rateit-js/scripts/jquery.rateit.min.js', __FILE__ ) );
	wp_enqueue_script( 'rate-it', plugins_url( 'assets\js\rateit-js/scripts/jquery.rateit.js', __FILE__ ), array() );
	 
	
	wp_register_style( 'rate-it-css',    plugins_url( 'assets\js\rateit-js/scripts/rateit.css',    __FILE__ ) );
	wp_enqueue_style('rate-it-css');
	
	wp_enqueue_script( 'r_main', plugins_url( 'assets\js\main.js', __FILE__ ),99 );
	wp_localize_script('r_main','recipe_obj',[
			'ajax_url'=>admin_url('admin-ajax.php')
		]);
		

   
 
}

function RFR_rate_recipe()
{
	global $wpdb;
	$output = ['status'=>1];
	$table = $wpdb->prefix."recipe_ratings";
	$postID = absint( $_POST['rid'] );
	$rating = round($_POST['rating'],1);
	$ip = $_SERVER['REMOTE_ADDR'];
	$rating_count= $wpdb->get_var(
	"SELECT COUNT(*) FROM `".$table."` WHERE recipe_id='". $postID ."' AND user_ip='".$ip."'"
	);
	$rating_Avg= $wpdb->get_var(
	"SELECT AVG(`ratings`) FROM `".$table."` WHERE recipe_id='". $postID ."'"
	);

	if($rating_count>0){
	wp_send_json( $output['status']=1 );
	}
	//Insert into database
	$wpdb->insert(
	$table,
	[
	'recipe_id'=>$postID,
	'ratings'=>$rating,
	'user_ip'=>$ip
	],
	[
	'%d','%f','%s'
	]
	);
	//Update the Database
	$recipe_data = get_post_meta( $postID,'recipe_data', true );
	$recipe_data['rating_count']++;
	$recipe_data['rating'] = round($rating_Avg,1);

	update_post_meta( $postID, "recipe_data", $recipe_data );

	$output['status'] = 2;
	wp_send_json( $rating_Avg );
}

function RFR_main_init()
{
	
	add_filter('manage_recipe_posts_columns','RFR_add_new_recipe_function');
	add_action('manage_recipe_posts_custom_column','RFR_manage_recipe_custom_columns',10,2);
	function RFR_add_new_recipe_function($column)
	{
	$new_columns = [];
	$new_columns['cb'] = '<input type="checkbox/>';
		$new_columns['title']	=	__('Recipe Title','recipe');
		$new_columns['author']	=	__('Recipe Author','recipe');
		$new_columns['categories']	=	__('Recipe Categories','recipe');
		$new_columns['count']	=	__('Recipe Count','recipe');
		$new_columns['rating']	=	__('Recipe Rating','recipe');
		$new_columns['date']	=	__('Recipe Date','recipe');

		return $new_columns;
	}
	function RFR_manage_recipe_custom_columns($columns , $post_id){
		switch ($columns) {
			case 'count':
				$recipe_data	=	get_post_meta($post_id, 'recipe_data', true);
			echo 	isset($recipe_data['rating_count'])	? $recipe_data['rating_count']:0;
					
				
		
				break;
				case 'rating':
					$recipe_data	=	get_post_meta($post_id, 'recipe_data', true);
				if (isset($recipe_data['rating'])) {
					echo $recipe_data['rating'];
				} else {
					echo 0;
				}
		
					break;
			
			default:
			
				break;
		}

	}
}
