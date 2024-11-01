<?php
/*
Plugin Name: Show Other Images
Plugin URI: http://wordpress.org/plugins/show-other-images/
Description: Shows other images below the "Read more"-Link, that are contained in the rest of the Post.
Version: 1.2
Author: Faldrian
Author URI: http://jenseitsderfenster.de/
*/

add_filter('the_content', 'soimg_addThumbnails');

function soimg_addThumbnails($content) {
	global $post;
	$postID = $post->ID;
	
	// We don't need to process singular views, there is already everything shown.
	// TODO: Maybe someone could find a better way to determine, if the current post will be
	//       processed with "teaser + read more" by wordpress.
	if(is_singular()) {
		return $content;
	}
	
	// Get attachments for this Post
	$args = array(
			'order' => 'ASC',
			'post_mime_type' => 'image',
			'post_parent' => $postID,
			'post_status' => null,
			'post_type' => 'attachment',
	);
	
	$attachments = get_children($args);
	
	// If there are no attachments, exit here
	if (empty($attachments)) {
		return $content;
	}
	
	// Get the teasertext from the post (before the <!--more--> mark.)
	$teasertext = strstr($post->post_content, '<!--more-->', true );
	
	// If the <!--more--> could not be found, there is no need to continue; All images are already shown.
	if($teasertext === false) {
		return $content;
	}
	
	// Extract filenames
	preg_match_all("|<img.+src=\".+\/([^\/\"]+)\"|U", $teasertext, $matches);
	
	$teaserimages = $matches[1]; // only our inner group contains filenames
	
	// Display all images that have no matching images (thumbnails and so on)
	$return = '';
	foreach ($attachments as $attachment) {
		$image_attributes = wp_get_attachment_metadata( $attachment->ID);
		
		// Add the original Filename to our Array of File-Sizes, so it may also be compared.
		// Since this String is a path with directories included, we must only fetch the filename.
		$pathparts = explode('/', $image_attributes['file']);
		$originalfile = end($pathparts);
		$image_attributes['sizes']['original']['file'] = $originalfile;
		
		// Check sizes of this image against teaserimage-url
		foreach ($image_attributes['sizes'] as $imagesize) {
			foreach ($teaserimages as $teaserfile) {
				if($imagesize['file'] == $teaserfile) {
					continue 3; // next attachment
				}
			}
		}

		$return .= wp_get_attachment_image($attachment->ID, array(64,64));
	}
	
	if($return != '') { // Only append our section if there are any images left to show.
		return $content . "<div class='soimg_teaser_images'>
			<div>".__('More images in the post', 'soimg').":</div>
			<a href='".get_permalink($postID)."'><div>".$return."</div></a>
			</div>";
	} else {
		return $content;
	}
}


/**
 * Register with hook 'wp_enqueue_scripts', which can be used for front end CSS and JavaScript
 */
add_action( 'wp_enqueue_scripts', 'soimg_add_stylesheet' );

/**
 * Enqueue plugin style-file
*/
function soimg_add_stylesheet() {
	// Only embed style, if it's needed by the Plugin --> only on pages with no singluar content.
	if(!is_singular()) {
		// Respects SSL, Style.css is relative to the current file
		wp_register_style( 'soimg-style', plugins_url('css/style.css', __FILE__) );
		wp_enqueue_style( 'soimg-style' );
		
		// Also add the Language-Files only if needed
		load_plugin_textdomain('soimg', false, 'show-other-images/lang');
	}
}












