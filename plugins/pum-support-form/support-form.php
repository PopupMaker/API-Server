<?php
/*
Plugin Name: Support Form
Plugin URI: 
Description: 
Version: 
Author: 
Author URI: 
License: 
License URI: 
*/

// Used for the GForms Doc Search Field.
define( 'HELPSCOUT_DOCS_SUBDOMAIN', 'popupmaker' );

/**
 * Include IframeResizer
 */
function support_form_scripts() {
    wp_register_script( 'iframe-resizer-content',  plugin_dir_url( __FILE__ ) . 'iframeResizer.contentWindow.min.js' );

    if ( is_page( 'dashboard-support' ) ) {
        wp_enqueue_script( 'iframe-resizer-content' );
    }   
}
add_action( 'wp_enqueue_scripts', 'support_form_scripts' );

function _240_test_scripts() {
    if ( is_page( 'dashboard-support' ) ) {
?>
<script type="text/javascript">
(function ($, document) {
    if(window.location.href.indexOf("fl_builder") === -1) {
        $('#submit-a-support-ticket').hide(0);
    }
    
    $('.fl-node-589598f6c3452 a[role="button"]').click(function () {
        $('#submit-a-support-ticket').show(0, function () {
		setTimeout(function () {

		if ('parentIFrame' in window) {
			parentIFrame.scrollTo(0, $('#submit-a-support-ticket').offset().top);
		} else {
			$('html, body').animate({ scrollTop: $('#submit-a-support-ticket').offset().top }, 'fast');
		}

		}, 50);

	});


    });

    $('body').on( 'gf_hs_search_results_found', function () {
	setTimeout(function () {
		if ('parentIFrame' in window) {
			parentIFrame.scrollTo(0, $('.docs-search-wrap .results-found.message-results').offset().top);
		} else {
			$('html, body').animate({ scrollTop: $('.docs-search-wrap .results-found.message-results').offset().top }, 'fast');
		}
	}, 50);
    });
}(jQuery, document));
</script>
<?php
    }
}
add_action( 'wp_footer', '_240_test_scripts', 1000 );


add_filter( 'gform_pre_render_7', 'site_gform_populate_download_select' );
add_filter( 'gform_pre_validation_7', 'site_gform_populate_download_select' );
add_filter( 'gform_pre_submission_filter_7', 'site_gform_populate_download_select' );
add_filter( 'gform_admin_pre_render_7', 'site_gform_populate_download_select' );
function site_gform_populate_download_select( $form ) {

	foreach ( $form['fields'] as &$field ) {

		if ( $field->type != 'select' || strpos( $field->cssClass, 'populate-downloads' ) === false ) {
			continue;
		}

		// you can add additional parameters here to alter the posts that are retrieved
		// more info: [http://codex.wordpress.org/Template_Tags/get_posts](http://codex.wordpress.org/Template_Tags/get_posts)
		$posts = get_posts( 'numberposts=-1&post_type=download&post_status=publish' );

		$choices = array();

		foreach ( $posts as $post ) {
			$download = new EDD_Download( $post->ID );
			if ( ! $download->is_bundled_download() ) {
				$choices[] = array( 'text' => $post->post_title, 'value' => $post->post_title );
			}
		}

		// update 'Select a Post' to whatever you'd like the instructive option to be
		// $field->placeholder = 'Select a Post';
		$field->choices = $choices;

	}

	return $form;
}


function site_get_license_by_user_and_download( $user_id = null, $download_id = null ) {

	if ( ! $user_id || ! $download_id ) {
		return false;
	}

	if ( ! is_array( $download_id ) ) {
		$download_id = array( $download_id );
	}

	$meta_query = array(
		array(
			'key'   => '_edd_sl_user_id',
			'value' => $user_id,
		),
		array(
			'key'     => '_edd_sl_download_id',
			'value'   => $download_id,
			'compare' => 'IN',
		),
	);

	$args = array(
		'posts_per_page' => 1,
		'meta_query'     => $meta_query,
		'post_type'      => 'edd_license',
		'post_status'    => 'any',
	);

	$licenses = get_posts( $args );

	if ( $licenses ) {
		return $licenses[0];
	}

	return false;
}


function site_user_has_active_priority_support( $email = null ) {

	if ( ! $email ) {
		return false;
	}

	$user = get_user_by( 'email', $email );

	if ( ! $user || $user->ID <= 0 || ! edd_has_purchases( $user->ID ) || ! edd_has_user_purchased( $user->ID, array(
			38022,
			38021,
		) )
	) {
		return false;
	}

	$license = site_get_license_by_user_and_download( $user->ID, array( 38022, 38021 ) );

	if ( ! $license || ! in_array( edd_software_licensing()->get_license_status( $license->ID ), array(
			'active',
			'inactive',
		) )
	) {
		return false;
	}

	return true;
}


add_action( 'gform_pre_submission_7', 'site_priority_support_pre_submission_check' );
function site_priority_support_pre_submission_check( $form ) {
	if ( rgpost( 'input_4' ) == 'Technical Support' ) {

		if ( site_user_has_active_priority_support( rgpost( 'input_2' ) ) ) {
			$_POST['input_15'] = ',priority';
		}

	}
}
