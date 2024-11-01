<?php
/**
 * @package YD_Prevent-Comment-Impersonation
 * @author Yann Dubois
 * @version 0.1.0
 */

/*
 Plugin Name: YD Prevent Comment Impersonation
 Plugin URI: http://www.yann.com/en/wp-plugins/yd-prevent-comment-impersonation
 Description: Prevents unregistered comment users from using registered member's logins. | Funded by <a href="http://www.nogent-citoyen.com">Nogent Citoyen</a>
 Version: 0.1.0
 Author: Yann Dubois
 Author URI: http://www.yann.com/
 License: GPL2
 */

/**
 * @copyright 2010  Yann Dubois  ( email : yann _at_ abc.fr )
 *
 *  Original development of this plugin was kindly funded by http://www.nogent-citoyen.com
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 Revision 0.1.0:
 - Original alpha release 0
 */

/** Misc. Texts **/

global $pci_texts; 
$pci_texts = array (

	'option_page_title' => 'YD Prevent Comment Impersonation',

	'prevention_message1' => 
		'You cannot post a comment using the identity or email of a registered user of this site.',

	'prevention_message2' =>
		'If you are the usual user of this identity, please %s click here to connect %s.',

	'prevention_message3' =>
		'Otherwise, please %s go back %s and choose another identity to post your comment.',
	
	'prevention_message4' =>
		'To prevent other visitors from using your personal identifier, you can %s register here to create a personal account %s.',

	'prevention_message5' =>
		'To avoid having to re-type your password on each visit of the site, consider checking the %s "remember me" %s checkbox located right under the %s registration form %s.',

	'prevention_message6' =>
		'(tick that checkbox only if you connect from home or from your personal computer, don\'t check it if you are connecting from a public space such as a an Internet cafe.)',

	'prevention_message7' =>
		'Thanks.'
	
	/**
	 	'prevention_message' => '
		Vous ne pouvez pas poster de commentaire en utilisant l\'identifiant ou l\'adresse e-mail 
		d\'un utilisateur enregistré.<br/><br/>
		Si vous êtes l\'utilisateur habituel de cet identifiant, veuillez
		<a href="/wp-login.php?redirect_to=%s">vous connecter en cliquant ici</a>.<br/><br/>
		Dans le cas contraire, vous pouvez <a href="javascript:history.back()">revenir en arrière</a>
		et choisir un autre identifiant.<br/><br/>
		Pour garantir que votre identifiant personnel ne soit pas utilisé par quelqu\'un d\'autre,
		vous pouvez
		<a href="http://www.nogent-citoyen.com/forum/register.php">créer un compte personnel
		en vous inscrivant ici</a>.<br/><br/>
		Pour ne pas avoir à entrer votre mot de passe à chaque visite du site, pensez à cocher la case 
		<strong>"se souvenir de moi"</strong>, située juste en-dessous du 
		<a href="/wp-login.php?redirect_to=%s">formulaire de connexion</a>.
		<em>(ne cochez cette case que si vous vous connectez depuis chez vous ou depuis votre propre ordinateur,
		ne la cochez pas si vous vous connectez depuis un lieu public comme par exemple un cyber-café)</em>
		<br/><br/>
		Merci.
	'
	**/
);

/** Class includes **/

include_once( 'inc/yd-widget-framework.inc.php' );	// standard framework VERSION 20110328-01 or better
//include_once( 'inc/pci.inc.php' );					// custom classes

class pciPlugin extends YD_Plugin {
	
	const DEBUG = false;
	
	/** constructor **/
	function pciPlugin ( $opts ) {
		parent::YD_Plugin( $opts );
		add_filter( 'preprocess_comment', array(  &$this, 'preprocess_comment' ) );
	}
	
	function preprocess_comment( $com_data ) {
		global $wpdb;
		global $pci_texts;
		
		//wp_die( 'les commentaires sont momentanément désactivés pour maintenance technique.' );
		
		//This code inspired by: http://www.dagondesign.com/articles/prevent-author-impersonation-in-wordpress-comments/
		//many thanks to the folks at DagonDesign
		
		// get list of user (display) names for blog
		//TODO: this is not scalable. Make it scalable.
		$valid_users = (array)$wpdb->get_results("
		  SELECT display_name, user_email FROM " . $wpdb->prefix . "users");
	
		// get ID of logged in user (if there is one)
		global $userdata;
		get_currentuserinfo();
		$logged_in_name = $userdata->ID;
		$logged_in_email = $userdata->user_email;
	
		// see if the comment author matches an existing author
		$found_match = FALSE;
		foreach ($valid_users as $va) {
		  if (trim($va->display_name) != '') {
		    if (strtolower($va->display_name) == strtolower($com_data['comment_author'])) {
		      $found_match = TRUE;
		      break;
		    }
		  }
		  if (trim($va->user_email) != '') {
		    if (strtolower($va->user_email) == strtolower($com_data['comment_author_email'])) {
		      $found_match = TRUE;
		      break;
		    }
		  }  
		}
	
		// if commenter is not logged in, but match was found, block the comment
		if (trim($logged_in_name) == '') {
		  if ( self::DEBUG ) {
		  	$optmsg = "<br/><br/><hr/>";
		  	//$optmsg .= "\nDEBUG:\n" . serialize( $com_data ) . "<br/>";
		  	$optmsg .= "referrer: " . $com_data['comment_as_submitted']['referrer'] . '<br/>';
		  }
		  if ($found_match == TRUE) {
		  	$referrer = $com_data['comment_as_submitted']['referrer'] . '#comments';
		  	$connect_link = '<a href="' . wp_login_url( $referrer ) . '">';
		  	if( function_exists( 'bb_get_uri' ) ) {
		  		$register_link = '<a href="' 
		  			. bb_get_uri( 'register.php', null, BB_URI_CONTEXT_A_HREF + BB_URI_CONTEXT_BB_USER_FORMS )
		  			. '">';
		  	} else {
		  		$register_link = '<a href="'
		  			//. wp_login_url( $referrer )
		  			. '/wp-login.php?action=register'
		  			. '">';
		  	}
		  	$back_link = '<a href="javascript:history.back();">';
		  	$sa = '</a>';
		  	$son = "<strong>";
		  	$sof = "</strong>";
		  	$eon = '<em>';
		  	$eof = '</em>';
		  	$message =
		  		sprintf( __( $pci_texts['prevention_message1'], 'ydpci' ) ) . '<br/><br/>' .
		  		sprintf( __( $pci_texts['prevention_message2'], 'ydpci' ), $connect_link, $sa ) . '<br/><br/>' .
		  		sprintf( __( $pci_texts['prevention_message3'], 'ydpci' ), $back_link, $sa ) . '<br/><br/>' .
		  		sprintf( __( $pci_texts['prevention_message4'], 'ydpci' ), $register_link, $sa ) . '<br/><br/>' .
		  		sprintf( __( $pci_texts['prevention_message5'], 'ydpci' ), $son, $sof, $connect_link, $sa ) . '<br/>' .
		  		sprintf( '%s' . __( $pci_texts['prevention_message6'], 'ydpci' ) . '%s', $eon, $eof ) . '<br/><br/>' .
		  		sprintf( __( $pci_texts['prevention_message7'], 'ydpci' ) );
		    wp_die( $message . $optmsg ); 
		    	//You cannot post using the name or email of a registered author.
		  }
		} 
		return $com_data;
	}
}

/**
 * 
 * Just fill up necessary settings in the configuration array
 * to create a new custom plugin instance...
 * 
 */
global $pci_o;
$pci_o = new pciPlugin( 
	array(
		'name' 				=> 'YD Prevent Comment Impersonation',
		'version'			=> '0.1.0',
		'has_option_page'	=> false,
		'option_page_title' => $pci_texts['option_page_title'],
		'op_donate_block'	=> false,
		'op_credit_block'	=> true,
		'op_support_block'	=> true,
		'has_toplevel_menu'	=> false,
		'has_shortcode'		=> false,
		'shortcode'			=> '',
		'has_widget'		=> false,
		'widget_class'		=> '',
		'has_cron'			=> false,
		'crontab'			=> array(
			//'daily'			=> array( 'YD_MiscWidget', 'daily_update' ),
			//'hourly'		=> array( 'YD_MiscWidget', 'hourly_update' )
		),
		'has_stylesheet'	=> false,
		'stylesheet_file'	=> 'css/yd.css',
		'has_translation'	=> true,
		'translation_domain'=> 'ydpci', // must be copied in the widget class!!!
		'translations'		=> array(
			array( 'English', 'Yann Dubois', 'http://www.yann.com/' ),
			array( 'French', 'Yann Dubois', 'http://www.yann.com/' )
		),		
		'initial_funding'	=> array( 'Nogent Citoyen', 'http://www.nogent-citoyen.com' ),
		'additional_funding'=> array(),
		'form_blocks'		=> array(
			'Main options' => array( 
				'autotrack'	=> 'bool',
				'autoattr'	=> 'text',
				//'autopage'	=> 'bool'
			)
		),
		'option_field_labels'=>array(
				'autotrack'	=> 'Auto track visitors on all profile pages',
				'autoattr'	=> 'Default tracking attributes',
				//'autopage'	=> 'Add visitors tracking page to member menu'
		),
		'option_defaults'	=> array(
				'autotrack'	=> true,
				'autoattr'	=> '',
				//'autopage'	=> true
		),
		'form_add_actions'	=> array(
				//'Manually run hourly process'	=> array( 'YD_MiscWidget', 'hourly_update' ),
				//'Check latest'				=> array( 'YD_MiscWidget', 'check_update' )
		),
		'has_cache'			=> false,
		'option_page_text'	=> 'Welcome to the YD Prevent Comment Impersonation settings page.',
		'backlinkware_text' => '',
		'plugin_file'		=> __FILE__,
		'has_activation_notice'	=> false,
		'activation_notice' => '',
		'form_method'		=> 'post'
 	)
);
?>