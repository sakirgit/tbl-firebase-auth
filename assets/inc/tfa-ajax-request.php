<?php 
/*
*
*	***** TBL Firebase auth *****
*
*	Ajax Request
*	
*/
// If this file is called directly, abort. //
if ( ! defined( 'WPINC' ) ) {die;} // end if
/*
Ajax Requests
*/
add_action( 'wp_ajax_tfa_custom_reg_ajax', 'tfa_custom_reg_ajax' );
add_action( 'wp_ajax_nopriv_tfa_custom_reg_ajax', 'tfa_custom_reg_ajax' );
function tfa_custom_reg_ajax(){
    ob_start();
    if(isset($_POST)){
       $printName = $_POST;
			$inputs = [];
			parse_str( $printName['inputFieldValues'], $inputs );
		//	print_r($inputs);
			
			/* ============= Save new user ============= */

			if (isset( $inputs["tfa_user_login"] ) && wp_verify_nonce($inputs['tfa_csrf'], 'vicode-csrf')) {
				$user_type	 	= $inputs["tfa_user_type"];
				$user_login		= $inputs["tfa_user_login"];	
				$user_email		= $inputs["tfa_user_email"];
				$user_pass		= $inputs["tfa_user_pass"];
				$pass_confirm 	= $inputs["tfa_user_pass_confirm"];
				$user_first 	    = $inputs["tfa_user_first"];
				$user_last	 	= $inputs["tfa_user_last"]; 
				
				// this is required for username checks
				require_once(ABSPATH . WPINC . '/registration.php');
				
				if(username_exists($user_login)) {
					 // Username already registered
					 tfa_errors()->add('username_unavailable', __('Username already taken'));
				}
				if(!validate_username($user_login)) {
					 // invalid username
					 tfa_errors()->add('username_invalid', __('Invalid username'));
				}
				if($user_login == '') {
					 // empty username
					 tfa_errors()->add('username_empty', __('Please enter a username'));
				}
				if(!is_email($user_email)) {
					 //invalid email
					 tfa_errors()->add('email_invalid', __('Invalid email'));
				}
				if(email_exists($user_email)) {
					 //Email address already registered
					 tfa_errors()->add('email_used', __('Email already registered'));
				}
				if($user_pass == '') {
					 // passwords do not match
					 tfa_errors()->add('password_empty', __('Please enter a password'));
				}
				if($user_pass != $pass_confirm) {
					 // passwords do not match
					 tfa_errors()->add('password_mismatch', __('Passwords do not match'));
				}
				
				$errors = tfa_errors()->get_error_messages();
				
				$return_msg = [];
				
				// if no errors then cretate user
				if(empty($errors)) {
					 
					 $new_user_id = wp_insert_user(array(
								'user_login'		=> $user_login,
								'user_pass'	 		=> $user_pass,
								'user_email'		=> $user_email,
								'first_name'		=> $user_first,
								'last_name'			=> $user_last,
								'user_registered'	=> date('Y-m-d H:i:s'),
								'role'				=> $user_type,
								'firebase_uid'			=> $printName['fbUid']
						  )
					 );
					 
					 if($new_user_id) {
							update_user_meta( $new_user_id, 'firebase_uid', $printName['fbUid'] );
							$return_msg['email'] = $user_email;
							$return_msg['username'] = $user_login;
							$return_msg['user_pass'] = $user_pass;
							$return_msg['status'] = 'success';
							$return_msg['message'] = 'WP Registration success';
							$return_msg['userdata'] = $new_user_id;
						// Your ajax Request & Response
							echo json_encode( $return_msg );
							exit;
							/*
						  // send an email to the admin
						  wp_new_user_notification($new_user_id);	
						  
						  // log the new user in
						  wp_setcookie($user_login, $user_pass, true);
						  wp_set_current_user($new_user_id, $user_login);	
						  do_action('wp_login', $user_login);
						  
						  // send the newly created user to the home page after logging them in
					
						  wp_redirect(home_url()); 
						  
						  exit;
						  */
					 }
				}else{
					$return_msg['email'] = $user_email;
					$return_msg['username'] = $user_login;
					$return_msg['status'] = 'error';
					$return_msg['message'] = 'Web Registration error';
					$return_msg['errors'] = $errors;
				// Your ajax Request & Response
					echo json_encode( $return_msg );
				}
			}
			/* =============/Save new user ============= */
						
    } else {
        // Your ajax Request & Response
        echo 'Error, Ajax is Working On Your New Plugin But Your field was empty! Try Typing in the field!';    
    } 

	wp_die();
}


function sess_start() {
    if (!session_id())
    session_start();
}



add_action( 'wp_ajax_tfa_custom_login_ajax', 'tfa_custom_login_ajax' );
add_action( 'wp_ajax_nopriv_tfa_custom_login_ajax', 'tfa_custom_login_ajax' );
function tfa_custom_login_ajax(){
    ob_start();
    if(isset($_POST)){
       $printName = $_POST;
			$inputs = [];
			parse_str( $printName['inputFieldValues'], $inputs );
		//	echo json_encode($inputs);
		//	exit;
			/* ============= Save new user ============= */
			
			if (isset( $inputs["tfa_user_email_log"] ) && wp_verify_nonce($inputs['tfa_csrf'], 'vicode-csrf')) {
				
				$user_email		= $inputs["tfa_user_email_log"];
				$user_pass		= $inputs["tfa_user_pass_log"];
								
				$return_msg = [];
			
				$ligin_creds = array(
					'user_login'    => $user_email,
					'user_password' => $user_pass,
					'remember'      => true
				);
				
				$loged_user = wp_signon( $ligin_creds );

				if ( is_wp_error( $loged_user ) ) {
					
					$error_string = $loged_user->get_error_message();					
					$return_msg['status'] = 'error';
					$return_msg['response'] = $error_string;
					$return_msg['message'] = 'Login error!';
				}else{
					
					$return_msg['response'] = $loged_user;
					$return_msg['status'] = 'success';
					$return_msg['message'] = 'Web Logged In success';
				}
				
				echo json_encode( $return_msg );
			}
			/* =============/Save new user ============= */
			
    } else {
        // Your ajax Request & Response
        echo json_encode( 'Error, Ajax is Working On Your New Plugin But Your field was empty! Try Typing in the field!' );    
    }

	wp_die();
}



