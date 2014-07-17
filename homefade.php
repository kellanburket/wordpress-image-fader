<?php
/*
*    Plugin Name: Furniture Fade
*    Description: Front page area
*    Version: 1.0
*    Author: Tagline Movement
*    Author URI: taglinegroup.com
*    License: GPLv2
*/
define("HOMEPAGE_ID", get_option('page_on_front'));


//shortcode label is [homefade]$atts[/homefade]
add_shortcode('homefade', 'product_fader_shortcode');
add_action('admin_menu', 'product_fader_settings_menu', $priority=1);

add_action('admin_post_process_product_fader_options', 'process_product_fader_options' );

add_action('wp_enqueue_scripts', 'enqueue_product_fader_js');
add_action('wp_ajax_product_fader_ajax_action', 'product_fader_ajax_callback');
add_action('wp_ajax_nopriv_product_fader_ajax_action', 'product_fader_ajax_callback');

add_action('admin_enqueue_scripts', 'enqueue_product_fader_admin_css');
add_action('admin_footer', 'product_fader_admin_ajax_js');
add_action('wp_ajax_product_fader_admin_ajax_callback', 'product_fader_admin_ajax_callback');


function init_product_fader() {
	add_action('admin_post_save_product_fader_options', 'process_product_fader_options' );
}

function product_fader_shortcode($atts){ 
	//extract(shortcode_atts(array('src'=>'', 'head'=>'', 'description'=>'', 'link'=>'/?kln_collection=eco-flex'), $atts));
	//if (stristr($src, 'http'))
		//$src = $src;
	//else if (!strpos($src, '/') === 0 && !stristr($src, 'http'))
		//$src = site_url().'/'.$src;
	
	$images = get_fader_images();
	$output = '<div class="alfa-wrap" id="homefade-wrap"><div class="homeclass" id="homefade-pic-wrap">';

	$toggle = true;
	$i = 0;
	foreach ($images as $image) {	
		$output .= '<img class="homefade-pic" id="homefade-pic-'.$i++.'" src="'.$image->guid.'"';
		$output .= (($toggle) ? '>' : 'style="display:none">');
		$toggle = false;
	}

	$toggle = true;
	$i = 0;
	$output .= '</div><div class="homeclass" id="homefade-text-wrap"><div class="homefade-hd-wrap">';
	foreach ($images as $image) {
		$output .= '<h2 class="homefade-head" id="homefade-head-'.$i++.'"';
		$output .= (($toggle) ? '>' : 'style="display:none">');
		$output .= $image->post_title.'</h2>';
		$toggle = false;
	}
	
	$toggle = true;
	$i = 0;
	foreach ($images as $image) {
		$output .= '<p class="homefade-description" id="homefade-description-'.$i++.'"';
		$output .= (($toggle) ? '>' : 'style="display:none">');
		$output .= $image->post_content.'</p>';
		$toggle = false;
	}
	
	$toggle = true;
	$i = 0;
	foreach ($images as $image) {
		$output .= '<p class="learnmore" id="learnmore-'.$i++.'"';
		$output .= ($toggle) ? '>' : 'style="display:none">';
		$output .= '<a href="'.get_learnmore_url($image->ID).'">LEARN MORE</a></p>';
		$toggle = false;
	}
	
	$output .= '</div></div><div class="homefade-pointer-wrap"><ul class="homefade-pointer-ul">';
	
	$i = 0;
	foreach ($images as $image) {
		if ($i == 0) {
			$output .= '<li class="homefade-pointer-li active-pointer" id="pointer-'.$i++.'"></li>';
		} else {
			$output .= '<li class="homefade-pointer-li" id="pointer-'.$i++.'"></li>';
		}
	}
	$output .= '</ul></div></div>';
	return $output;
};

function get_learnmore_url($id) {
	return get_learnmore_taxonomy_url('kln_collection', get_post_meta(intval($id), 'link-id', true));
}

function get_learnmore_custompage_url($id) {
	get_permalink(intval($id));
}

function get_learnmore_taxonomy_url($tax, $id) {
	return get_term_link(intval($id), $tax);
}

function product_fader_settings_menu() {
	$submenu='Product Fader';
	$capabilities='manage_options';
	$function_callback='product_fader_complex_menu';
	add_menu_page('Add Images to Product Fader', $submenu, $capabilities, 'product_fader_main_menu', $function_callback);
}

function process_product_fader_options() {
	if ( !current_user_can( 'manage_options' ))
       wp_die( 'Not allowed' );
	
	//echo "Link: ".$_POST['link'].'<br>';
    check_admin_referer( 'process_product_fader_options',  'kln' );

	$uploads_path = WP_CONTENT_DIR.'/uploads';
	$homefade_folder = '/homefader/';
	$pathname = $uploads_path.$homefade_folder;

 	if (!is_dir($pathname)) {
		mkdir($pathname);
	}

	$allowedExts = array("gif", "jpeg", "jpg", "png");
	$temp = explode(".", $_FILES["file"]["name"]);
	$extension = end($temp);
	
	if ($_FILES['file']['error'] == 4 && $_POST['attachment_id']) {
		$message = update_product_fader_image_metadata(null, $_POST['title'], $_POST['description'], $_POST['link'], $_POST['attachment_id']);
	} elseif ($_FILES["file"]["error"] > 0) {
  		$message = "Error: " . $_FILES["file"]["error"] . "<br>";
  	} elseif((($_FILES["file"]["type"] == "image/gif")
		|| ($_FILES["file"]["type"] == "image/jpeg")
		|| ($_FILES["file"]["type"] == "image/jpg")
		|| ($_FILES["file"]["type"] == "image/pjpeg")
		|| ($_FILES["file"]["type"] == "image/x-png")
		|| ($_FILES["file"]["type"] == "image/png"))
		&& in_array($extension, $allowedExts)) {
		
		$filepath = $pathname.$_FILES["file"]["name"];
		$file_has_been_moved = move_uploaded_file($_FILES["file"]["tmp_name"], $filepath);
      	
		if ($file_has_been_moved) {
			
			if ($_POST['attachment_id']) {
				$metadata_has_been_set = update_product_fader_image_metadata($filepath, $_POST['title'], $_POST['description'], $_POST['link'], $_POST['attachment_id']);
			} else {
				$metadata_has_been_set = set_product_fader_image_metadata($filepath, $_POST['title'],  $_POST['description'], $_POST['link']);
			}
			
			if($metadata_has_been_set) {
				$message = "Upload: " . $_FILES["file"]["name"] . "<br>"
					."Type: " . $_FILES["file"]["type"] . "<br>"
					."Size: " . ($_FILES["file"]["size"] / 1024). " kB<br>"
					."Stored in: ".$_FILES["file"]["tmp_name"]
					."Stored in: ".$filepath;
			} else {
				$message = "There was an error processing your image MetaData!";
			}
			
		} else {
			$message = "Error Moving File into New Directory!";
		}
	} else {
  		$message = "Invalid File Type! :".$_FILES["file"]["name"] . "<br>"
					."Type: " . $_FILES["file"]["type"] . "<br>"
					."Size: " . ($_FILES["file"]["size"] / 1024). " kB<br>"
					."Stored in: ".$_FILES["file"]["tmp_name"];
  	}
	wp_redirect(add_query_arg(array('page'=>'product_fader_main_menu', 'message'=>$message), admin_url('options-general.php')));
}

function set_product_fader_image_metadata($filepath, $title, $link_id, $description) {
	
	$upload_dir = wp_upload_dir();
	$image_basename = preg_replace(array('#.*\/#'), array(''), $filepath);
	$guid = $upload_dir['baseurl'].'/homefader/'.$image_basename;
	
	$wp_filetype = wp_check_filetype($filepath, null);
	$attachment = array(
		'guid' => $guid, 
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => $title,
		'post_name' => 'fader-image-'.preg_replace('#\-#', '', $title),		
		'post_content' => $description,
		'post_status' => 'inherit'
	);
	
	$attach_id = wp_insert_attachment($attachment, $filepath, HOMEPAGE_ID);
	wp_update_attachment_metadata($id, array('link-id'=>$link_id));	
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filepath);
	
	$updated_attachment_metadata = wp_update_attachment_metadata( $attach_id, $attach_data );
	if ($updated_attachment_metadata) {
		return "Successfully Uploded Metadata";
	} else {
		return 0;
	}
}

function update_product_fader_image_metadata($filepath, $title, $description, $link_id, $id) {
	if ($filepath) {
		$upload_dir = wp_upload_dir();
		$image_basename = preg_replace(array('#.*\/#'), array(''), $filepath);
		$guid = $upload_dir['baseurl'].'/homefader/'.$image_basename;	

		$wp_filetype = wp_check_filetype($filepath, null);
		$attachment = array(
			'guid' => $guid, 
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => $title,
			'post_name' => 'fader-image-'.preg_replace('#\-#', '', $title),		
			'post_content' => $description,
			'post_status' => 'inherit'
		);

		wp_delete_attachment($id);
		$attach_id = wp_insert_attachment($attachment, $filepath, HOMEPAGE_ID);
		wp_update_attachment_metadata($attach_id, array('link-id'=>$link_id));	
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
		$updated_attachment_metadata = wp_update_attachment_metadata($attach_id, $attach_data );
	} else {

		$attachment_metadata = array(
			'ID'=>intval($id), 
			'post_content'=>$description, 
			'post_title'=>$title, 
			'post_name' =>'fader-image-'.preg_replace('#\-#', '', $title)
		);	
		//echo 'link_id: '.$link_id;
		//echo 'attachment metadata: ';
		//print_r($attachment_metadata);
		//echo '<br>';
		//$meta = get_post_meta(intval($id));
		//echo 'post meta data: ';
		//print_r($meta);
		//echo '<br>';
		
		$updated_attachment_metadata = update_post_meta(intval($id), 'link-id', $link_id);
		//$updated_attachment_metadata =  wp_update_attachment_metadata(intval($id), array('link-id'=>$link_id));		
		$updated_attachment_metadata = wp_update_post($attachment_metadata);
	}
	
	if ($updated_attachment_metadata) {
		return "Successfully Uploded Metadata";
	} else {
		return 0;
	}
}


function product_fader_admin_ajax_callback() {
	$id = $_POST['attachment_id'];
	if ($id) {
		$deleted = wp_delete_post($id);
	}
	if ($deleted) echo '<h5>Successfully Deleted Post: '.$id.'</h5>'.get_fader_thumbnails();
	else echo '<h5>There was a problem deleting your file.</h5>'.get_fader_thumbnails();
	exit;
}


function product_fader_admin_ajax_js() { ?>
	<script>
	jQuery('document').ready(function($) {
		var xMarkClickEvent = function(event) {
			event.preventDefault;
			event.stopPropagation;		
			id = jQuery(this).parent().find('[name="attachment_id"]').val();
			console.log(id);
			var data = {action: 'product_fader_admin_ajax_callback', attachment_id: id};
			if (confirm('Are you sure you want to delete this item?')) {
				jQuery.post(ajaxurl, data, function(response) {
					jQuery('.fader-thumbnail-wrap').replaceWith(response);					
					jQuery('.x-mark').on("click", xMarkClickEvent);
				});
			}
		};

		jQuery('.x-mark').on("click", xMarkClickEvent);		
	});
	</script>
<?php }

function enqueue_product_fader_js() {
	if (is_front_page()) {
		wp_enqueue_script('kc-homefader', plugins_url().'/kln-collections/js/kc-homefader.js',  array('jquery', 'jquery-ui-core'));
		wp_localize_script('kc-homefader', 'data', array('admin_url'=>admin_url('admin-ajax.php'), 'total_faders'=>count(get_fader_images())));
	}
}

function enqueue_product_fader_admin_css() {
	wp_enqueue_style('homefader-css', plugins_url().'/kln-collections/css/kc-fader.css');
}


function get_all_product_options($link) {
	$args = array(
			'posts_per_page'   => -1,
			'offset'           => 0,
			'category'         => '',
			'orderby'          => 'post_date',
			'order'            => 'DESC',
			'include'          => '',
			'exclude'          => '',
			'meta_key'         => '',
			'meta_value'       => '',
			'post_type'        => 'kln_products',
			'post_mime_type'   => '',
			'post_parent'      => '',
			'post_status'      => 'publish',
			'suppress_filters' => true );

	$all_posts = get_posts($args);
	
	foreach ($all_posts as $a_post) {
		$options .= '<option value="'.$a_post->ID.'"';
		$options .= ($a_post->ID == $link) ? ' selected>' : '>';
		$options .= $a_post->post_title.'</option>';	
	}
	
	return $options;
}

function get_all_taxonomy_options ($tax, $link) {
	$args = array(
		'parent'   => 0,
	); 

	$terms = get_terms(array($tax));
	$options = "";
	
	foreach ($terms as $term) {
		$options .= '<option value="'.$term->term_id.'"';
		$options .= ($term->term_id == $link) ? ' selected' : '';
		$options .= '>'.$term->name.'</option>';	
	}	
	return $options;
}

function get_all_options($link = "") {
	return get_all_taxonomy_options('kln_collection', $link);
}

function product_fader_complex_menu() { ?>
	<h2>Add New Fader Image</h2>
	<?php if(isset($_GET['message'])) {
		echo $_GET['message'].'<br><br>';
	} ?>



    <form class="add-new-fader-form" method="post" 
        enctype="multipart/form-data" 
        action="<?php echo admin_url('admin-post.php'); ?>">        
 		<?php wp_nonce_field('process_product_fader_options', 'kln'); ?>
    	<div class="title-and-link admin-fader-div">
            <label for="title">Title:</label>
            <input type="text" name="title">
			<br>
            <label for="link">Link:</label>
            <select name="link">
            <?php echo get_all_options(); ?>
            </select>
        </div>
        <div class="description admin-fader-div">        
            <label for="description">Description:</label>
            <br>
            <textarea type="textarea" name="description"></textarea>
		</div>
        <div class="image admin-fader-div">
            <label for="file">Image:</label>
            <input type="file" name="file" id="product-fader-file">
            <input type="hidden" name="action" value="process_product_fader_options">
		</div>    
        <input type="submit" name="submit" value="Submit">

    </form>

    <?php echo get_fader_thumbnails() ?>;
<?php }

function get_fader_thumbnails() {

    $return = '<div class="fader-thumbnail-wrap">';
    $children = get_fader_images();
	
	$i = 0;
	foreach($children as $child) {
		
		$link = get_post_meta($child->ID, 'link-id', true);
		
		$return .=  '<form class="fader-thumbnail" id="fader-thumbnail-'.$i.'"
            action="'.admin_url('admin-post.php').'"
            method="post" 
            enctype="multipart/form-data" 
            >';
		$return .= wp_nonce_field('process_product_fader_options', 'kln');
		$return .= '<input type="hidden" name="action" value="process_product_fader_options">
            <input type="hidden" name="attachment_id" value="'.$child->ID.'">
            <img src="'.plugins_url().'/kln-collections/img/x-mark.png" class="x-mark">
			<img src="'.$child->guid.'" class="fader-thumbnail-img">
            <label for="fader-title">Title: </label>
            <input type="text" value="'.$child->post_title.'" name="title">
            <label for="fader-content">Content: </label>
            <textarea type="textarea" value="'.$child->post_content.'" name="description">'.$child->post_content.'</textarea>
            <label for="link">Link:</label>
			<select name="link">';
		
		$return .= get_all_options($link);

		$return .= '</select>';	
		$return .= '<input type="file" name="file" id="product-fader-file">
            <input type="submit" name="submit" value="Submit">
        </form>';
	}
   	$return .= '</div>';
	
	return $return;
}

function get_fader_images() {
	$args = array(
		'post_parent'=>HOMEPAGE_ID,
		'post_type'=>'attachment',
		'post_mime_type'=>'image'
	);		
	//print_r(get_children($args));
	//exit;
	return get_children($args);
} ?>