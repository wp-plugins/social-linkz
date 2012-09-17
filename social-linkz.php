<?php
/**
Plugin Name: Social Linkz
Plugin Tag: social, facebook, twitter, google, buttons
Description: <p>Add social links such as Twitter or Facebook in each post. </p><p>You can choose the buttons to be displayed such as : </p><ul><li>Twitter</li><li>FaceBook</li><li>LinkedIn</li><li>Viadeo</li><li>GoogleBuzz</li><li>Google+</li><li>StumbleUpon</li><li>Pinterest</li><li>Print</li></ul><p>This plugin is under GPL licence. </p>
Version: 1.4.2



Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/extend/plugins/social-linkz/
License: GPL3
*/

require_once('core.php') ; 

class sociallinkz extends pluginSedLex {
	/** ====================================================================================================================================================
	* Initialisation du plugin
	* 
	* @return void
	*/
	static $instance = false;
	static $path = false;
	

	protected function _init() {
		global $wpdb ; 
		global $do_not_show_inSocialLinkz ; 
		// Configuration
		$this->pluginName = 'Social Linkz' ; 
		$this->tableSQL = "" ; 
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array($this,'uninstall_removedata'));
		
		//Parametres supplementaires
		$this->excerpt_called = false ; 
		add_filter('the_content', array($this,'print_social_linkz'), 1000);
		add_action('wp_print_styles', array( $this, 'addcss'), 1);
		add_action('wp_print_scripts', array($this,'header_init'));
		add_filter('get_the_excerpt', array( $this, 'the_excerpt'),1000000);
		add_filter('get_the_excerpt', array( $this, 'the_excerpt_ante'),2);
		
		add_shortcode( 'sociallinkz', array( $this, 'display_button_shortcode' ) );

		add_action( 'wp_ajax_nopriv_emailSocialLinkz', array( $this, 'emailSocialLinkz'));
		add_action( 'wp_ajax_emailSocialLinkz', array( $this, 'emailSocialLinkz'));
		
		$do_not_show_inSocialLinkz = false ; 
	}
	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/** ====================================================================================================================================================
	* Add a button in the TinyMCE Editor
	*
	* To add a new button, copy the commented lines a plurality of times (and uncomment them)
	* 
	* @return array of buttons
	*/
	
	function add_tinymce_buttons() {
		$buttons = array() ; 
		$buttons[] = array(__('Add SocialLinkz buttons', $this->pluginID), '[sociallinkz]', '', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)).'img/sociallinkz_button.png') ; 
		return $buttons ; 
	}
	
	/** ====================================================================================================================================================
	* Define the default option value of the plugin
	* 
	* @return variant of the option
	*/
	function get_default_option($option) {
		switch ($option) {
			case 'display_in_excerpt' 			: return false ; break ; 
			
			case 'display_top_in_post' 			: return false ; break ; 
			case 'display_bottom_in_post' 			: return true ; break ; 

			case 'display_top_in_page' 			: return false ; break ; 
			case 'display_bottom_in_page' 			: return true ; break ; 

			case 'twitter' 						: return true 	; break ; 
			case 'twitter_count' 						: return false 	; break ; 
			case 'twitter_hosted' 				: return false 	; break ; 
			case 'twitter_hosted_count' 		: return false 	; break ; 
			case 'name_twitter'					: return "" 	; break ; 
			
			case 'pinterest_hosted' 				: return false 	; break ; 
			case 'pinterest_hosted_count' 		: return false 	; break ;
			case 'pinterest_hosted_defaultimage' 		: return "[file]/social-linkz/" 	; break ;
			
			case 'linkedin' 					: return false 	; break ; 
			case 'linkedin_count' 					: return false 	; break ; 
			case 'linkedin_hosted' 				: return false 	; break ; 
			case 'linkedin_hosted_count' 		: return false 	; break ; 

			case 'viadeo' 					: return false 	; break ; 
						
			case 'googleplus_standard' 					: return false 	; break ; 
			case 'googleplus_standard_count' 					: return false 	; break ; 
			case 'googleplus' 					: return true 	; break ; 
			case 'googleplus_count' 			: return true 	; break ; 

			case 'googlebuzz' 					: return false 	; break ; 
			case 'googlebuzz_count' 			: return false	; break ; 
			case 'googlebuzz_hosted'			: return false	; break ; 
			case 'googlebuzz_hosted_count'			: return false	; break ; 
			
			case 'facebook' 					: return true 	; break ; 
			case 'facebook_id' 					: return "" 	; break ; 
			case 'facebook_count' 					: return false 	; break ; 
			case 'facebook_hosted' 				: return false 	; break ; 
			case 'facebook_hosted_share'			: return false 	; break ; 
			
			case 'stumbleupon' 					: return false 	; break ; 
			case 'stumbleupon_count' 					: return false 	; break ; 
			case 'stumbleupon_hosted'				: return false 	; break ; 
			
			case 'print'	 					: return true 	; break ; 
			
			case 'mail'	 					: return false 	; break ; 
			case 'mail_max'	 					: return 5 	; break ; 
			case 'mail_address'					: return  get_option('admin_email') ; break ; 
			case 'mail_name'						: return get_bloginfo('name'); break ; 

			case 'html'	 					: return "*<div class='social_linkz'>
   %buttons%
</div>" 	; break ; 
			case 'css'	 					: return "*.social_linkz { 
	padding: 5px 0 10px 0 ; 
	border-bottom-width: 0px;
	border-bottom-style: none;
}

.social_linkz a { 
	text-decoration: none;		
	border-bottom-width: 0px;
	border-bottom-style: none;
}" 	; break ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* Add the Google API for the scripts, Facebook Insight tags
	* 
	* @return void
	*/
	function header_init() {
		//Google API for the scripts
		wp_enqueue_script('google_plus', 'https://apis.google.com/js/plusone.js');
		//Facebook Insight tags
		if ($this->get_param('facebook_id')!="") {
			echo '<meta property="fb:admins" content="'.$this->get_param('facebook_id').'" />' ; 
		}
		if ($this->get_param('mail')) {
			// jquery
			wp_enqueue_script('jquery');   
		
			ob_start() ; 
			?>
				function sendEmailSocialLinkz(md5, id) { 
					jQuery("#wait_mail"+md5).show();
					jQuery("#emailSocialLinkz"+md5).attr('disabled', 'disabled');
					
					listemail = jQuery("#emailSocialLinkz"+md5).val();
					nom = jQuery("#nameSocialLinkz"+md5).val();
					
					var arguments = {
						action: 'emailSocialLinkz', 
						id_article: id,
						name: nom, 
						list_emails: listemail
					} 
					var ajaxurl2 = "<?php echo admin_url()."admin-ajax.php"?>" ; 
					//POST the data and append the results to the results div
					jQuery.post(ajaxurl2, arguments, function(response) {
						jQuery("#innerdialog"+md5).html(response);
					});    
				}
		
			<?php 
			
			$java = ob_get_clean() ; 
			$this->add_inline_js($java) ; 
		}
	}
	
	/** ====================================================================================================================================================
	* Add CSS
	* 
	* @return void
	*/
	function addcss() {
		$this->add_inline_css($this->get_param('css')) ; 
	}
	
	/** ====================================================================================================================================================
	* The configuration page
	* 
	* @return void
	*/
	function configuration_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->pluginID;
	
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br></div>
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div style="padding:20px;">
			<?php echo $this->signature ; ?>
			<p><?php echo __('This plugin help you sharing on the social network by adding facebook or twitter buttons.', $this->pluginID) ; ?></p>
		<?php
		
			// On verifie que les droits sont corrects
			$this->check_folder_rights( array() ) ; 
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
			$tabs = new adminTabs() ; 
			
			ob_start() ; 
				$params = new parametersSedLex($this, 'tab-parameters') ; 
				$title = "Twitter&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('twitter', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_twitter.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title),"","",array('twitter_count')) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title))  ; 
				$params->add_param('twitter_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title))  ; 
				$params->add_param('twitter_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_twitter_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title),"","",array('twitter_hosted_count'))  ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('twitter_hosted_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 
				$params->add_param('name_twitter', sprintf(__('Your %s pseudo:',$this->pluginID), $title)) ; 
				
				$title = "FaceBook&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('facebook', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_facebook.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title),"","",array('facebook_count')) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('facebook_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title)) ; 
				$params->add_param('facebook_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_facebook_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), "Like ".$title)) ; 
				$params->add_param('facebook_hosted_share', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_facebook_hosted_share.png'/> ".sprintf(__('The official %s button:',$this->pluginID), "Share ".$title)) ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('facebook_id', __('Your FaceBook ID to enable Insight:',$this->pluginID)) ; 
				$params->add_comment(sprintf(__('Insight provides metrics around your content. See %shere%s for futher details. To identify your Facebook ID, please visit the previous link and then click on Statistic of my website',$this->pluginID), "<a href='http://www.facebook.com/insights'>", "</a>")) ; 
				
				$title = "LinkedIn&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('linkedin', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_linkedin.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title),"","",array('linkedin_count')) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('linkedin_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title))  ; 
				$params->add_param('linkedin_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_linkedin_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title),"","",array('linkedin_hosted_count')) ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('linkedin_hosted_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 

				$title = "Viadeo&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('viadeo', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_viadeo.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				
				$title = "GoogleBuzz&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('googlebuzz', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googlebuzz.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title),"","",array('googlebuzz_count')) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('googlebuzz_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title))  ; 
				$params->add_param('googlebuzz_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googlebuzz_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title),"","",array('googlebuzz_hosted_count')) ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('googlebuzz_hosted_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 
				
				$title = "Google+&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('googleplus_standard', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googleplus.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title),"","",array('googleplus_standard_count')) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('googleplus_standard_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title)) ; 
				$params->add_param('googleplus', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googleplus_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title),"","",array('googleplus_count')) ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('googleplus_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 
				
				$title = "StumbleUpon&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('stumbleupon', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_stumbleupon.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title),"","",array('stumbleupon_count')) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('stumbleupon_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title)) ; 
				$params->add_param('stumbleupon_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_stumbleupon_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title)) ; 
				
				$title = "Pinterest&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('pinterest_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_pinterest_hosted.jpg'/> ".sprintf(__('The %s button:',$this->pluginID), $title),"","",array('pinterest_hosted_count', 'pinterest_hosted_defaultimage')) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('pinterest_hosted_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title)) ; 
				$params->add_param('pinterest_hosted_defaultimage', __('Default image:',$this->pluginID)) ; 
				$params->add_comment(sprintf(__('%s requires that an image is pinned. By default, the plugin will take the first image in the post but if there is not any image, this image will be used.',$this->pluginID), $title)) ; 

				$title = "Print" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('print', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_print.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 

				$title = "Mail" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('mail', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_mail.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title),"","",array('mail_max', 'mail_address', 'mail_name')) ; 
				$params->add_param('mail_max', __('The maximum number of emails for each mailing:',$this->pluginID)) ; 
				$params->add_param('mail_name', __('The name used to send the email:',$this->pluginID)) ; 
				$params->add_param('mail_address', __('The mail address used to send the email:',$this->pluginID)) ; 
				$address = explode("/", home_url('/')) ; 
				$params->add_comment(sprintf(__('You may use the admin email %s, a noreply address such as %s or any other email',$this->pluginID), "<code>".get_option('admin_email')."</code>","<code>noreply@".str_replace("www.", "", $address[2])."</code>")) ; 
				
				$params->add_title(__('Display all these buttons in the excerpt?',$this->pluginID)) ; 
				$params->add_param('display_in_excerpt', "".__('These buttons should be displayed in excerpt:',$this->pluginID)) ; 
				
				$params->add_title(__('Where do you want to display the buttons in post?',$this->pluginID)) ; 
				$params->add_param('display_top_in_post', "".__('At the Top:',$this->pluginID)) ; 
				$params->add_param('display_bottom_in_post', "".__('At the Bottom:',$this->pluginID)) ; 

				$params->add_title(__('Where do you want to display the buttons in page?',$this->pluginID)) ; 
				$params->add_param('display_top_in_page', "".sprintf(__('At the Top:',$this->pluginID), $title)) ; 
				$params->add_param('display_bottom_in_page', "".sprintf(__('At the Bottom:',$this->pluginID), $title)) ; 

				$params->add_title(__('Advanced options',$this->pluginID)) ; 
				$params->add_param('html', __('HTML:',$this->pluginID)) ; 
				$default = str_replace("*", "", str_replace(" ", "&nbsp;", str_replace("\n", "<br>", str_replace(">", "&gt;", str_replace("<", "&lt;", $this->get_default_option('html'))))))."<br/>" ; 
				$params->add_comment(sprintf(__('Default HTML is : %s with %s the displayed buttons',$this->pluginID), "<br/>"."<code>".$default."</code>", "<code>%buttons%</code>")) ; 
				$params->add_param('css', __('CSS:',$this->pluginID)) ; 
				$default = str_replace("*", "", str_replace(" ", "&nbsp;", str_replace("\n", "<br>", str_replace(">", "&gt;", str_replace("<", "&lt;", $this->get_default_option('css'))))))."<br/>" ; 
				$params->add_comment(sprintf(__('Default CSS is : %s',$this->pluginID), "<br/>"."<code>".$default."</code>")) ; 

				$params->flush() ; 
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			
			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new translationSL($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				$trans = new otherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 					
			
			echo $this->signature ; ?>
		</div>
		<?php
	}
	
	/** ====================================================================================================================================================
	* Get the social button at the end of the posts
	* 
	* @return void
	*/

	function print_social_linkz ($content) {
		global $post ; 
		// If it is the loop and an the_except is called, we leave
		if (! is_single()) {
			// If page 
			if (is_page()) {
				$return =  $content ; 
				if ($this->get_param('display_bottom_in_page')) {
					$return =  $return.$this->print_buttons($post) ;  
				}
				if ($this->get_param('display_top_in_page')) {
					$return =  $this->print_buttons($post).$return ; 
				}
				return $return; 	
			// else
			} else {
				// if post
				if (($this->get_param('display_in_excerpt')) && (!$this->excerpt_called)) {
					return $content.$this->print_buttons($post) ; 
				}
				return $content ; 
			}
		} else {

			$return =  $content ; 
			if ($this->get_param('display_bottom_in_post')) {
				$return =  $return.$this->print_buttons($post) ;  
			}
			if ($this->get_param('display_top_in_post')) {
				$return =  $this->print_buttons($post).$return ; 
			}
			return $return ; 

		}
	}
	
	/** ====================================================================================================================================================
	* Shortcode to Print the buttons
	* 
	* @return void
	*/

	function display_button_shortcode( $_atts, $text ) {
		global $post ; 
		return $this->print_buttons($post) ; 
	}
	
	/** ====================================================================================================================================================
	* Print the buttons
	* 
	* @return void
	*/
	
	function print_buttons($post) {
		global $do_not_show_inSocialLinkz ; 
	
		$url = wp_get_shortlink($post->ID) ; 
		$long_url = get_permalink($post->ID) ; 
		$titre = $post->post_title ; 
		
		if ($do_not_show_inSocialLinkz) {
			return ; 
		}
		ob_start() ; 
		?>
			<?php
			
			if ($this->get_param('facebook')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.facebook.com/sharer.php?u=<?php echo urlencode($long_url) ; ?>&amp;t=<?php echo urlencode($titre) ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "Facebook") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_facebook.png" alt="Facebook" height="24" width="24"/></a>
				<?php
				if ($this->get_param('facebook_count')) {
					$this->display_bubble($this->get_facebook_counter($long_url)) ; 
				}
			}
			
			if ($this->get_param('facebook_hosted')) {
				?>
				<span id="fb-root"></span><script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script><fb:like href="<?php echo $long_url ; ?>" send="false" layout="button_count" width="35" show_faces="false" action="like" font=""></fb:like>
				<?php
			}
			
			if ($this->get_param('facebook_hosted_share')) {
				?>
				<a name="fb_share" type="button_count" share_url="<?php echo $long_url ?>" href="http://www.facebook.com/sharer.php">Share</a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>
				<?php
			}
			
			if ($this->get_param('twitter')) {
				$via = "" ; 
				if ($this->get_param('name_twitter')!="") {
					$via = " (via @".$this->get_param('name_twitter').")" ; 
				}
				
				?>
				<a rel="nofollow" target="_blank" href="http://twitter.com/?status=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>%20-%20<?php echo urlencode($url) ; ?>.<?php echo str_replace('+','%20',urlencode($via)) ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "Twitter") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_twitter.png" alt="Twitter" height="24" width="24"/></a>
				<?php
				if ($this->get_param('twitter_count')) {
					$this->display_bubble($this->get_twitter_counter($long_url)) ; 
				}
			}
			
			if ($this->get_param('twitter_hosted')) {
				$via = "" ; 
				if ($this->get_param('name_twitter')!="") {
					$via = 'data-via="'.$this->get_param('name_twitter').'"' ; 
				}
				$coun = "none" ; 
				if ($this->get_param('twitter_hosted_count')) {
					$coun = 'horizontal' ; 
				}
				?>
				<a href="http://twitter.com/share" class="twitter-share-button" data-text="<?php echo "[Blog] ".$titre ; ?> - <?php echo $url ; ?>" data-url="<?php echo urlencode($url) ; ?>" data-count="<?php echo $coun ; ?>" <?php echo $via ; ?> ><?php echo __('Tweet', $this->pluginID) ; ?></a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
				<?php
			}

			if ($this->get_param('googleplus_standard')) {
				?>
				<a rel="nofollow" target="_blank" href="https://plusone.google.com/_/+1/confirm?url=<?php echo $long_url ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "Googe+") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_googleplus.png" alt="Google+" height="24" width="24"/></a>
				<?php
				if ($this->get_param('googleplus_standard_count')) {
					$this->display_bubble($this->get_googleplus_counter($long_url)) ; 
				}
			}
			
			if ($this->get_param('googleplus')) {
				$count = "false" ; 
				if ($this->get_param('googleplus_count')) {
					$count = "true" ; 
				}
				?>
				<g:plusone size="standard" count="<?php echo $count; ?>"></g:plusone>
				<?php
			}
			
			if ($this->get_param('googlebuzz')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.google.com/buzz/post?message=<?php the_title(); ?>&url=<?php echo $long_url ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "GoogleBuzz") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_googlebuzz.png" alt="Google Buzz" height="24" width="24"/></a>
				<?php
				if ($this->get_param('googlebuzz_count')) {
					$this->display_bubble($this->get_googlebuzz_counter($long_url)) ; 
				}
			}
			
			if ($this->get_param('googlebuzz_hosted')) {
				$type = "small-button" ; 
				if ($this->get_param('googlebuzz_hosted_count')) {
					$type = "small-count" ; 
				}
				?>
				<a class="google-buzz-button" href="http://www.google.com/buzz/post" data-button-style="<? echo $type ?>"></a>
				<script type="text/javascript" src="http://www.google.com/buzz/api/button.js"></script>
				<?php
			}
			if ($this->get_param('linkedin')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($long_url) ; ?>&title=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>&source=<?php echo urlencode(get_bloginfo('name')) ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "LinkedIn") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_linkedin.png" alt="LinkedIn" height="24" width="24"/></a>
				<?php
				if ($this->get_param('linkedin_count')) {
					$this->display_bubble($this->get_linkedin_counter($long_url)) ; 
				}
			}
			
			if ($this->get_param('linkedin_hosted')) {
				$coun = "" ; 
				if ($this->get_param('linkedin_hosted_count')!="") {
					$coun = 'data-counter="right"' ; 
				}
				?>
				<script src="http://platform.linkedin.com/in.js" type="text/javascript"></script><script type="IN/Share" <?php echo $coun ; ?>></script>
				<?php
			}
			
			if ($this->get_param('viadeo')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.viadeo.com/shareit/share/?url=<?php echo urlencode($long_url) ; ?>&title=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>&overview=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "Viadeo") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_viadeo.png" alt="Viadeo" height="24" width="24"/></a>
				<?php
			}
			
			if ($this->get_param('stumbleupon')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.stumbleupon.com/submit?url=<?php echo urlencode($long_url) ; ?>&title=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "StumbleUpon") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_stumbleupon.png" alt="StumbleUpon" height="24" width="24"/></a>
				<?php
				if ($this->get_param('stumbleupon_count')) {
					$this->display_bubble($this->get_stumbleupon_counter($long_url)) ; 
				}
			}
			
			if ($this->get_param('stumbleupon_hosted')) {
				?>
				<script src="http://www.stumbleupon.com/hostedbadge.php?s=1&r=<?php echo urlencode($long_url) ?>"></script>
				<?php
			}
			
			
			if ($this->get_param('pinterest_hosted')) {
				// Get all image of the post
				$img = $this->get_first_image(get_the_ID()) ; 
				if ($img == "") {
					if ($this->get_param('pinterest_hosted_defaultimage')!=$this->get_default_option('pinterest_hosted_defaultimage')) {
						$upload = wp_upload_dir() ;
						$img = $upload['baseurl']."/".$this->get_param('pinterest_hosted_defaultimage') ; 
					} else {
						$img = WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/no_image.png" ; 
					}
				} 
				
				$coun = "none" ; 
				if ($this->get_param('pinterest_hosted_count')) {
					$coun = 'horizontal' ; 
				}
				?>
				<a href="http://pinterest.com/pin/create/button/?url=<?php echo urlencode($url) ; ?>&media=<?php echo urlencode($img) ; ?>&description=<?php echo str_replace('+','%20',urlencode($titre)) ; ?>" class="pin-it-button" count-layout="<?php echo $coun ; ?>"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a><script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>
				<?php
			}
			
			if ($this->get_param('print')) {
				?>
				<a rel="nofollow" target="_blank" href="#" title="<?php echo __("Print", $this->pluginID) ;?>">
					<img onclick="window.print();return false;" class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_print.png" alt="Print" height="24" width="24"/></a>
				<?php
			}
			
			if ($this->get_param('mail')) {
				?>
				<a rel="nofollow" target="_blank" href="#" title="<?php echo __("Mail", $this->pluginID) ;?>">
					<img onclick="openEmailSocialLinkz('<?php echo md5($long_url) ?>');return false;" class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_mail.png" alt="Mail" height="24" width="24"/></a>
				<div id="mask<?php echo md5($long_url) ?>" class="social_mask"></div>
				<div id="dialog<?php echo md5($long_url) ?>" class="social_window">
					<div id="innerdialog<?php echo md5($long_url) ?>">
						<h3><?php echo __("Send this article by email", $this->pluginID) ;?></h3>
						<p class='textEmailSocialLinkz'><?php echo __("What is you name?", $this->pluginID) ;?></p>
						<p><input name="nameSocialLinkz<?php echo md5($long_url) ?>" id="nameSocialLinkz<?php echo md5($long_url) ?>" /></p>
						<p class='textEmailSocialLinkz'><?php echo sprintf(__("Please indicate below the emails to which you want to send this article: %s", $this->pluginID), "<b>".$titre."</b>") ;?></p>
						<p><textarea name="emailSocialLinkz<?php echo md5($long_url) ?>" id="emailSocialLinkz<?php echo md5($long_url) ?>" rows="5"></textarea></p>
						<p class='closeEmailSocialLinkz'><?php echo sprintf(__("Enter one email per line. No more than %s emails.", $this->pluginID), $this->get_param('mail_max')) ;?></p>
						<p class='sendEmailSocialLinkz'><a href="#" title="<?php echo __("Close", $this->pluginID) ;?>" onclick="sendEmailSocialLinkz('<?php echo md5($long_url) ?>', <? echo $post->ID ?>);return false;"><span class='sendEmailSocialLinkz'><?php echo __("Send", $this->pluginID) ;?></span></a></p>
					</div>
					<p class='closeEmailSocialLinkz'><a href="#" title="<?php echo __("Close", $this->pluginID) ;?>" onclick="closeEmailSocialLinkz('<?php echo md5($long_url) ?>');return false;"><span class='closeEmailSocialLinkz'><?php echo __("Close", $this->pluginID) ;?></span></a></p>
				</div>
				<?php
			}
			?>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		return str_replace('%buttons%', $content, $this->get_param('html')) ; 
	}
	
	
	/** ====================================================================================================================================================
	* Get  twitter counter
	* 
	* @return void
	*/

	function get_twitter_counter($url) {
		$counter = $this->get_param('counter_twitter'.url_to_postid($url)) ; 
		// update every 30min
		if ((is_array($counter))&&($counter['date']+(30*60)>time())&&(is_numeric($counter['count']))) {
			$nb = $counter['count']; 
		} else {
			$result = wp_remote_get('http://urls.api.twitter.com/1/urls/count.json?url=' .  $url ); 
			$old_counter = 0 ; 
			if (is_array($counter)) {
				$old_counter = explode(" ", $counter['count']) ; 
				$old_counter = $old_counter[0] ; 
			}
			if ( is_wp_error($result) ) {
				$nb = trim(intval($old_counter)." *") ;
			} else {
				$res = @json_decode($result['body'], true);
				if (isset($res['count'])) {
					if (intval($res['count'])>intval($old_counter))
						$nb =  $res['count'];
					else 
						$nb = intval($old_counter) ; 
				} else {
					$nb =  trim(intval($old_counter)." ?") ;
				}
			}	
			$this->set_param('counter_twitter'.url_to_postid($url), array('date'=>time(), 'count'=>$nb)) ; 
		}
		return $nb ; 
	}
	
	/** ====================================================================================================================================================
	* Get  facebook counter
	* 
	* @return void
	*/

	function get_facebook_counter($url) {
		$counter = $this->get_param('counter_facebook'.url_to_postid($url)) ; 
		// update every 30min
		if ((is_array($counter))&&($counter['date']+(30*60)>time())&&(is_numeric($counter['count']))) {
			$nb = $counter['count'] ; 
		} else {
			$result = wp_remote_get('http://graph.facebook.com/?ids=' .  $url ); 
			$old_counter = 0 ; 
			if (is_array($counter)) {
				$old_counter = explode(" ", $counter['count']) ; 
				$old_counter = $old_counter[0] ; 
			}
			if ( is_wp_error($result) ) {
				$nb = trim(intval($old_counter)." *") ;
			} else {
				$res = @json_decode($result['body'], true);
				if (isset($res[$url]['shares'])) {
					if (intval($res[$url]['shares'])>intval($old_counter))
						$nb =  $res[$url]['shares'];
					else 
						$nb = intval($old_counter) ; 
				} else {
					if (isset($res[$url]['id'])){
						$nb =  intval($old_counter) ; 
					} else {
						$nb =  trim(intval($old_counter)." ?") ;
					}
				}
			}
			$this->set_param('counter_facebook'.url_to_postid($url), array('date'=>time(), 'count'=>$nb)) ; 
		}
		return $nb ; 
	}
	
	/** ====================================================================================================================================================
	* Get  linkedin counter
	* 
	* @return void
	*/

	function get_linkedin_counter($url) {
		$counter = $this->get_param('counter_linkedin'.url_to_postid($url)) ; 
		// update every 30min
		if ((is_array($counter))&&($counter['date']+(30*60)>time())&&(is_numeric($counter['count']))) {
			$nb = $counter['count'] ; 
		} else {
			$result = wp_remote_get('http://www.linkedin.com/cws/share-count?url=' .  $url ); 
			$old_counter = 0 ; 
			if (is_array($counter)) {
				$old_counter = explode(" ", $counter['count']) ; 
				$old_counter = $old_counter[0] ; 
			}
			if ( is_wp_error($result) ) {
				$nb = trim(intval($old_counter)." *") ;
			} else {
				if (!(preg_match('/IN.Tags.Share.handleCount\({"count":(\d+),/i',$result['body'],$tmp))) {
					$nb =  trim(intval($old_counter)." ?") ;
				} else {
					if (intval($tmp[1]) >intval($old_counter))
						$nb =  $tmp[1] ;
					else 
						$nb = intval($old_counter) ; 
				}
			}
			$this->set_param('counter_linkedin'.url_to_postid($url), array('date'=>time(), 'count'=>$nb)) ; 
		}
		return $nb ; 
	}

	/** ====================================================================================================================================================
	* Get  Google+ counter
	* 
	* @return void
	*/

	function get_googleplus_counter($url) {
		$counter = $this->get_param('counter_googleplus'.url_to_postid($url)) ; 
		// update every 30min
		if ((is_array($counter))&&($counter['date']+(30*60)>time())&&(is_numeric($counter['count']))) {
			$nb = $counter['count'] ; 
		} else {
			$nb=0 ; 
			$post_data = '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' .  $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]' ; 
			$result = wp_remote_post("https://clients6.google.com/rpc?key=AIzaSyCKSbrvQasunBoV16zDH9R33D88CeLr9gQ", array( 'headers' => array('content-type' => 'application/json'), 'body' => $post_data ) );
			$old_counter = 0 ; 
			if (is_array($counter)) {
				$old_counter = explode(" ", $counter['count']) ; 
				$old_counter = $old_counter[0] ; 
			}
			if ( is_wp_error($result) ) {
				$nb =  trim(intval($old_counter)." *") ;
			} else {
				$res = @json_decode($result['body'], true);
				if (isset($res[0]['result']['metadata']['globalCounts']['count'])) {
					if (intval($res[0]['result']['metadata']['globalCounts']['count']) >intval($old_counter))
						$nb =  $res[0]['result']['metadata']['globalCounts']['count'] ;
					else 
						$nb = intval($old_counter) ; 
				} else {
					$nb =  trim(intval($old_counter)." ?") ;
				}
			}
			$this->set_param('counter_googleplus'.url_to_postid($url), array('date'=>time(), 'count'=>$nb)) ; 
		}
		return $nb ; 
	}
	
	/** ====================================================================================================================================================
	* Get  GoogleBuzz counter
	* 
	* @return void
	*/

	function get_googlebuzz_counter($url) {
		$counter = $this->get_param('counter_googlebuzz'.url_to_postid($url)) ; 
		// update every 30min
		if ((is_array($counter))&&($counter['date']+(30*60)>time())&&(is_numeric($counter['count']))) {
			$nb = $counter['count'] ; 
		} else {
			$nb=0 ; 
			$result = wp_remote_get("https://www.googleapis.com/buzz/v1/activities/count?alt=json&url=".$url );
			$old_counter = 0 ; 
			if (is_array($counter)) {
				$old_counter = explode(" ", $counter['count']) ; 
				$old_counter = $old_counter[0] ; 
			}
			if ( is_wp_error($result) ) {
				$nb =  trim(intval($old_counter)." *") ;
			} else {
				$res = @json_decode($result['body'], true);
				//print_r($res) ; 
				if (isset($res['data']['counts']['count'])) {
					if (intval($res['data']['counts']['count']) >intval($old_counter))
						$nb =  $res['data']['counts']['count'] ;
					else 
						$nb = intval($old_counter) ; 					
				} else {
					$nb =  trim(intval($old_counter)." ?") ;
				}
			}
			$this->set_param('counter_googlebuzz'.url_to_postid($url), array('date'=>time(), 'count'=>$nb)) ; 
		}
		return $nb ; 
	}

	
	/** ====================================================================================================================================================
	* Get  StympleUpon+ counter
	* 
	* @return void
	*/

	function get_stumbleupon_counter($url) {
		$counter = $this->get_param('counter_stumbleupon'.url_to_postid($url)) ; 
		// update every 30min
		if ((is_array($counter))&&($counter['date']+(30*60)>time())&&(is_numeric($counter['count']))) {
			$nb = $counter['count'] ; 
		} else {
			$result = wp_remote_get('http://www.stumbleupon.com/services/1.01/badge.getinfo?url='.$url ); 
			$old_counter = 0 ; 
			if (is_array($counter)) {
				$old_counter = explode(" ", $counter['count']) ; 
				$old_counter = $old_counter[0] ; 
			}
			if ( is_wp_error($result) ) {
				$nb =  trim($old_counter." *") ;
			} else {
				$res = @json_decode($result['body'], true);
				if (isset($res['result']['views'])) {
					if (intval($res['result']['views'])>intval($old_counter))
						$nb =  $res['result']['views'] ;
					else 
						$nb = intval($old_counter) ; 		
				} else {
					if ((isset($res['result']['in_index'])) && ($res['result']['in_index']==false)){
						$nb = intval($old_counter) ; 	
					} else {
						$nb =  trim(intval($old_counter)." ?") ;
					}
				}
			}
			$this->set_param('counter_stumbleupon'.url_to_postid($url), array('date'=>time(), 'count'=>$nb)) ; 
		}
		return $nb ; 
	}
	
	/** ====================================================================================================================================================
	* Display bubble 
	* 
	* @return void
	*/
	function display_bubble($nb) {
		?>
		<span class="social_bubble">
			<img class="arrow" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/arrow.png"/>
			<em><?php echo $nb  ; ?></em>
		</span>
		<?php
	}
	
	/** ====================================================================================================================================================
	* Add the buttons if needed
	* 
	* @return void
	*/
	function the_excerpt_ante($content) {
		$this->excerpt_called=true ; 
		return $content ; 
	}
	
	function the_excerpt($content) {
		global $post ; 
	
		if (($this->get_param('display_in_excerpt')) && ($this->excerpt_called)) {
			$this->excerpt_called = false ; 
			return $content.$this->print_buttons($post) ; 
		}
		return $content ; 
	}
	
	/** ====================================================================================================================================================
	* Get the URL of the first image of the post
	* 
	* @return string the URL of the image (empty, if there is none)
	*/
	function get_first_image ($postID) {					
		$args = array(
		'numberposts' => 1,
		'order'=> 'ASC',
		'post_mime_type' => 'image',
		'post_parent' => $postID,
		'post_status' => null,
		'post_type' => 'attachment'
		);
		
		$attachments = get_children( $args );
				
		if ($attachments) {
			foreach($attachments as $attachment) {
				return wp_get_attachment_url( $attachment->ID , 'full');
			}
		} else {
			return "" ; 
		}
	}
	
	/** ====================================================================================================================================================
	* Send an article by email
	* 
	* @return void
	*/

	function emailSocialLinkz() {
		global $post ; 
		global $do_not_show_inSocialLinkz; 
		if (!$this->get_param('mail')) {
			echo "ERROR: Sending has been disabled" ; 
			die() ; 
		}
		
		$id = preg_replace("/[^0-9]/", "", $_POST['id_article']) ;
		
		$name = trim(preg_replace("[:.,;()]", " ", strip_tags($_POST['name'])));
		$emails = explode("\n", $_POST['list_emails']) ; 
		echo "<h2>".__('Sending Report', $this->pluginID)."</h2>" ; 
		$nb = 0 ; 
		
		if ($name=="") {
			echo "<p>".sprintf(__('Sorry, but you have not provided a name. Please refresh the current page and then retry.', $this->pluginID), $email)."</p>" ; 
		}
		
		$content = "<p>".sprintf(__('%s has recommended this article to you: %s', $this->pluginID), "<b>$name</b>", '"<i>'.get_the_title($id).'</i>"')."</p>" ; 
		$content .= "<p>".__('Here is an extract of the article:', $this->pluginID)."</p>" ; 
		$post = get_post($id) ; 
		setup_postdata($post);
		$do_not_show_inSocialLinkz = true ; 
		$content .= "<p style='font-style:italic;border:1px #AAAAAA solid;margin:10px; left-margin:40px;padding:10px;background-color:#DDDDDD;'>".get_the_excerpt()."</p>" ; 
		$do_not_show_inSocialLinkz = false ; 
		
		$subject = html_entity_decode(sprintf(__('%s has recommended this article to you: %s', $this->pluginID), $name, '"'.get_the_title($id).'"')); 
		$subject = preg_replace_callback("/(&#[0-9]+;)/", array($this,'transformHTMLEntitiesWithDash'), $subject); 
		
		foreach ($emails as $email) {
			if ($nb>=$this->get_param('mail_max'))
				die() ; 
			$email = trim($email) ; 
			$email = filter_var($email, FILTER_VALIDATE_EMAIL) ; 
			if ($email !== FALSE) {
				$nb++ ; 
				
				$headers= 	"MIME-Version: 1.0\n" .
						"From: ".$this->get_param('mail_name')." <".$this->get_param('mail_address').">\n" .
						"Content-Type: text/html; charset=\"". get_option('blog_charset') . "\"\n";
					
				$result = wp_mail($email, $subject , $content, $headers);
				
				if ($result) {
					echo "<p>".sprintf(__('Email successfully sent to %s', $this->pluginID), $email)."</p>" ; 
				} else {
					echo "<p>".sprintf(__('Wordpress is unable to send an email to %s', $this->pluginID), $email)."</p>" ; 
					//echo "<p> DEBUG: <code>".$email.", ".$subject.", ".$content.", ".$headers."</code></p>" ; 
					die() ; 
				}
			} 
		}
		die() ; 
	}
	
	function transformHTMLEntitiesWithDash($m) { 
		return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); 
	}
}

$sociallinkz = sociallinkz::getInstance();

?>