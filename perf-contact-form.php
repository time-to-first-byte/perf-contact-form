<?php
/*
 *  Plugin Name:       Perfthemes Contact Form
 *  Plugin URI:        https://github.com/perfthemes/contact-form
 *  Description:       Add a simple contact form on Perfthemes themes
 *  Version:           1.0.0
 *  Author:            Perfthemes
 *  Author URI:        https://perfthemes.com/
 *  Text Domain:       perf
 *  Domain Path:       /languages
 */

 /**
 * Load ACF Meta box
 */
require 'acf-meta-box.php';

/*
* Register scripts
*/
add_action( 'wp_enqueue_scripts', 'perf_contact_scripts' );
function perf_contact_scripts() {
	global $post;

	if( is_object( $post ) ){
        $content = $post->post_content;
    }
	

	if( !is_search() && !is_404() && get_field("perf_contact_recaptcha","option") && has_shortcode( $content, 'perf_contact_form' ) ){
		wp_enqueue_script( 'perf-contact-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), '', true );
	}
}

/*
* Form HTML
*/
function perf_contact_html_form_code() {
	?>
	<form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>#contact_form" method="post">

		<div class="clearfix mxn2">
			<div class="md-col md-col-6 px2 mb2">
				<label for="cf-name"><?php _e("Name","perf_contact"); ?></label> <span class="main-color">*</span>
				<input type="text" id="cf-name" name="cf-name" pattern="[a-zA-Z0-9 ]+" value="<?php echo ( isset( $_POST["cf-name"] ) ? esc_attr( $_POST["cf-name"] ) : '' ) ?>" size="40" required>
			</div>

			<div class="md-col md-col-6 px2 mb2">
				<label for="cf-email"><?php _e("Email","perf_contact"); ?></label> <span class="main-color">*</span>
				<input type="email" id="cf-email" name="cf-email" value="<?php echo ( isset( $_POST["cf-email"] ) ? esc_attr( $_POST["cf-email"] ) : '' ) ?>" size="40" required>
			</div>
		</div>


		<div class="mb2">
			<label for="cf-subject"><?php _e("Subject","perf_contact"); ?></label> <span class="main-color">*</span>
			<input type="text" id="cf-subject"  name="cf-subject" value="<?php echo ( isset( $_POST["cf-subject"] ) ? esc_attr( $_POST["cf-subject"] ) : '' ) ?>" size="40" required>
		</div>

		<div class="mb2">
			<label for="cf-message"><?php _e("Message","perf_contact"); ?></label> <span class="main-color">*</span>
			<textarea rows="10" cols="35" id="cf-message" name="cf-message" required><?php echo ( isset( $_POST["cf-message"] ) ? esc_attr( $_POST["cf-message"] ) : '' ) ?></textarea>
		</div>

		<?php if( get_field("perf_contact_recaptcha","option") ): ?>
			<div class=" clearfix">
				<div class="g-recaptcha mb2" data-sitekey="<?php echo get_field("perf_contact_public_key","option"); ?>"></div>
	 		<div>
	 	<?php endif; ?>

		<input type="submit" name="cf-submitted" class="perf_btn mb2 pointer" value="<?php _e("Send","perf_contact"); ?>">

	</form>
	<?php
 }

/*
* Form validation and notification
*/
function perf_contact_deliver_mail() {

	// if the submit button is clicked, send the email
	if ( isset( $_POST['cf-submitted'] ) ) {

		// sanitize form values
		$name    = sanitize_text_field( $_POST["cf-name"] );
		$email   = sanitize_email( $_POST["cf-email"] );
		$subject = sanitize_text_field( $_POST["cf-subject"] );
		$message = esc_textarea( $_POST["cf-message"] );

		// get the blog administrator's email address
		$to = get_option( 'admin_email' );

		$headers = "From: $name <$email>" . "\r\n";

		if( get_field("perf_contact_recaptcha","option") ){

            $q = http_build_query(array(
                'secret'    => get_field("perf_contact_private_key_copy","option"),
                'response'  => $_POST['g-recaptcha-response'],
                'remoteip'  => $_SERVER['REMOTE_ADDR'],
            ));

			$temp_siteverify = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?' . $q);
 		    $siteverify = $temp_siteverify['body'];
			$recaptcha = json_decode($siteverify);
			$recaptcha = $recaptcha->success;
		}else{
			$recaptcha = true;
		}

		if ( $recaptcha && wp_mail( $to, $subject, $message, $headers ) ) {
			//wp_mail( $to, $subject, $message, $headers );

			echo '<div>';
			if( get_field("perf_contact_success_message","option") ){
				echo '<p id="contact_form" class="green-color">' . get_field("perf_contact_success_message","option") . '</p>';
			}else{
				echo '<p id="contact_form" class="green-color">' . __("Your message was successfully sent. Thank you.","perf_contact") . '</p>';
			}
			echo '</div>';
		} else {
			echo '<p class="red-color" id="contact_form">';
			if( get_field("perf_contact_error_message","option") ){
				echo get_field("perf_contact_error_message","option");
			}else{
				echo __("There was a problem with your submission. All fields are required","perf_contact");
			}
			echo '</p>';
		}
	}
}

/*
 * Create Shortcode
 */
add_shortcode( 'perf_contact_form', 'perf_contact_cf_shortcode' );
function perf_contact_cf_shortcode() {
	if( function_exists( 'get_field' ) ){
	    ob_start();
	    perf_contact_deliver_mail();
	    perf_contact_html_form_code();

	 	return ob_get_clean();
	}
}

if( function_exists('acf_add_options_page') ) {

    acf_add_options_sub_page(array(
        'page_title' 	=> 'Contact Form',
        'menu_title'	=> 'Contact Form',
        'parent_slug'	=> 'perfthemes-settings',
    ));
	
}