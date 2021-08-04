<?php 
/*
*
*	***** TBL Firebase auth *****
*
*	This file initializes all TFA Core components
*	
*/
// If this file is called directly, abort. //
if ( ! defined( 'WPINC' ) ) {die;} // end if
// Define Our Constants
define('TFA_CORE_INC',dirname( __FILE__ ).'/assets/inc/');
define('TFA_CORE_IMG',plugins_url( 'assets/img/', __FILE__ ));
define('TFA_CORE_CSS',plugins_url( 'assets/css/', __FILE__ ));
define('TFA_CORE_JS',plugins_url( 'assets/js/', __FILE__ ));
/*
*
*  Register CSS
*
*/
function tfa_register_core_css(){
wp_enqueue_style('tfa-core', TFA_CORE_CSS . 'tfa-core.css',null,time(),'all');
};
add_action( 'wp_enqueue_scripts', 'tfa_register_core_css' );    
/*
*
*  Register JS/Jquery Ready
*
*/
function tfa_register_core_js(){
// Register Core Plugin JS	
//wp_enqueue_script('tfa-core', TFA_CORE_JS . 'tfa-core.js','jquery',time(),true);
};
add_action( 'wp_enqueue_scripts', 'tfa_register_core_js' );    
/*
*
*  Includes
*
*/ 
// Load the Functions
if ( file_exists( TFA_CORE_INC . 'tfa-core-functions.php' ) ) {
	require_once TFA_CORE_INC . 'tfa-core-functions.php';
}     
// Load the ajax Request
if ( file_exists( TFA_CORE_INC . 'tfa-ajax-request.php' ) ) {
	require_once TFA_CORE_INC . 'tfa-ajax-request.php';
} 
// Load the Shortcodes
if ( file_exists( TFA_CORE_INC . 'tfa-shortcodes.php' ) ) {
	require_once TFA_CORE_INC . 'tfa-shortcodes.php';
}

if ( file_exists( TFA_CORE_INC . 'app-auth-fb.php' ) ) {
	require_once TFA_CORE_INC . 'app-auth-fb.php';
}


add_filter( 'wp_nav_menu_items', 'wti_loginout_menu_link', 10, 2 );

function wti_loginout_menu_link( $items, $args ) {
   if ($args->theme_location == 'primary') {
      if (is_user_logged_in()) {
         $items .= '<li class="right"><a href="'. wp_logout_url(home_url('/registration-login')) .'">'. __("Log Out") .'</a></li>';
      }
   }
   return $items;
}