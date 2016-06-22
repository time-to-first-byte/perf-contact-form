
<?php
/*
 *  Plugin Name: Perfthemes Contact Form
 *  Plugin URI:        https://github.com/perfthemes/contact-form
 *  Description:       Simple plugin to add contact form on Perfthemes themes
 *  Version:           1.0.0
 *  Author:            Perfthemes
 *  Author URI:        https://perfthemes.com/
 *  Text Domain:       perf_contact
 *  Domain Path:       /languages
 */

 function perf_html_form_code() {
 	?>
 	<form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>#contact_form" method="post">

 		<div class="clearfix mxn2">
 			<div class="md-col md-col-6 px2 mb2">
 				<label for="cf-name"><?php _e("Name","perf_contact"); ?></label> <span class="main-color">*</span>
 				<input type="text" name="cf-name" pattern="[a-zA-Z0-9 ]+" value="<?php echo ( isset( $_POST["cf-name"] ) ? esc_attr( $_POST["cf-name"] ) : '' ) ?>" size="40" required>
 			</div>

 			<div class="md-col md-col-6 px2 mb2">
 				<label for="cf-email"><?php _e("Email","perf_contact"); ?></label> <span class="main-color">*</span>
 				<input type="email" name="cf-email" value="<?php echo ( isset( $_POST["cf-email"] ) ? esc_attr( $_POST["cf-email"] ) : '' ) ?>" size="40" required>
 			</div>
 		</div>


 		<div class="mb2">
 			<label for="cf-subject"><?php _e("Subject","perf_contact"); ?></label> <span class="main-color">*</span>
 			<input type="text" name="cf-subject" pattern="[a-zA-Z ]+" value="<?php echo ( isset( $_POST["cf-subject"] ) ? esc_attr( $_POST["cf-subject"] ) : '' ) ?>" size="40" required>
 		</div>

 		<div class="mb2">
 			<label for="cf-message"><?php _e("Message","perf_contact"); ?></label> <span class="main-color">*</span>
 			<textarea rows="10" cols="35" name="cf-message" required><?php echo ( isset( $_POST["cf-message"] ) ? esc_attr( $_POST["cf-message"] ) : '' ) ?></textarea>
 		</div>

 		<?php if( get_field("perf_contact_recaptcha","option") ): ?>
 			<div class="mb2">
 				<div class="g-recaptcha" data-sitekey="<?php echo get_field("perf_contact_public_key","option"); ?>"></div>
 	 		<div>
 	 	<?php endif; ?>

 		<input type="submit" name="cf-submitted" class="perf_btn mb2 mt2" value="<?php _e("Send","perf_contact"); ?>">

 	</form>
 	<?php
 }

 function perf_deliver_mail() {

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

 		$q = http_build_query(array(
 	        'secret'    => '6LcS-xsTAAAAAO_29q1EINmcCAdbFtnY9XzKcKuc',
 	        'response'  => $_POST['g-recaptcha-response'],
 	        'remoteip'  => $_SERVER['REMOTE_ADDR'],
 		));

 		if( get_field("perf_contact_recaptcha","option") ){

 			$temp_siteverify = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?' . $q);
     		$siteverify = $temp_siteverify['body'];
 			$recaptcha = json_decode($siteverify);
 			$recaptcha = $recaptcha->success;
 		}else{
 			$recaptcha = true;
 		}

 		if ( $recaptcha && wp_mail( $to, $subject, $message, $headers ) ) {
 			wp_mail( 'eric.v@bulledev.com', 'WP Mail Test', 'Mail is working' );
 			wp_mail( $to, $subject, $message, $headers );

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

 function perf_cf_shortcode() {
    if( function_exists( 'get_field' ) ){
        ob_start();
        perf_deliver_mail();
        perf_html_form_code();

     	return ob_get_clean();
    }
 }

 add_shortcode( 'perf_contact_form', 'perf_cf_shortcode' );
