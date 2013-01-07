<?php
/**
Plugin Name: Social Linkz
Plugin Tag: social, facebook, twitter, google, buttons
Description: <p>Add social links such as Twitter or Facebook in each post. </p><p>You can choose the buttons to be displayed such as : </p><ul><li>Twitter</li><li>FaceBook</li><li>LinkedIn</li><li>Viadeo</li><li>Google+</li><li>StumbleUpon</li><li>Pinterest</li><li>Print</li></ul><p>If you want to add the buttons in a very specific location, your may edit your theme and insert <code>$this->print_buttons($post);</code> (be sure that <code>$post</code> refer to the current post). </p><p>It is also possible to add a widget to display buttons. </p><p>This plugin is under GPL licence. </p>
Version: 1.5.0
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
	var $path = false;
	

	protected function _init() {
		global $wpdb ; 
		global $do_not_show_inSocialLinkz ; 
		// Configuration
		$this->pluginName = 'Social Linkz' ; 
		$this->tableSQL = "id mediumint(9) NOT NULL AUTO_INCREMENT, id_post mediumint(9) NOT NULL, counters MEDIUMTEXT DEFAULT '', date_maj DATETIME, UNIQUE KEY id (id)" ; 
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array('sociallinkz','uninstall_removedata'));
		
		//Parametres supplementaires
		add_shortcode( 'sociallinkz', array( $this, 'display_button_shortcode' ) );

		add_action( 'wp_ajax_nopriv_forceUpdateSocialLinkz', array( $this, 'forceUpdateSocialLinkz'));
		add_action( 'wp_ajax_forceUpdateSocialLinkz', array( $this, 'forceUpdateSocialLinkz'));
		
		add_action( 'wp_ajax_emailSocialLinkz', array( $this, 'emailSocialLinkz'));
		
		wp_register_sidebar_widget('social_linkz', 'Social Linkz', array( $this, '_sidebar_widget'), array('description' => __('Display the social buttons as a widget.', $this->pluginID)));
		
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
	
	/**====================================================================================================================================================
	* To display the widget.
	*
	* @return void
	*/
	
	public function _sidebar_widget() {
		$id = url_to_postid($_SERVER['REQUEST_URI']) ; 
		if ($id==0) {
			$post_art = new stdClass() ;
			$post_art->ID = 0 ; 
			echo $this->print_buttons($post_art) ; 
		} else {
			$post_art = get_post($id) ; 
			echo $this->print_buttons($post_art) ; 
		} 
	}
	
	/**====================================================================================================================================================
	* Function called when the plugin is activated
	* For instance, you can do stuff regarding the update of the format of the database if needed
	* If you do not need this function, you may delete it.
	*
	* @return void
	*/
	
	public function _update() {
		SL_Debug::log(get_class(), "Update the plugin." , 4) ; 
		// Delete the former counter ...
		$names = $this->get_name_params() ; 
		foreach($names as $n) {
			if (strpos($n, "counter_")===0) {
				$this->del_param($n) ; 
			}
		}
	}	
	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
	
	public function uninstall_removedata () {
		global $wpdb ;
		// DELETE OPTIONS
		delete_option('sociallinkz'.'_options') ;
		if (is_multisite()) {
			delete_site_option('sociallinkz'.'_options') ;
		}
		
		// DELETE SQL
		if (function_exists('is_multisite') && is_multisite()){
			$old_blog = $wpdb->blogid;
			$old_prefix = $wpdb->prefix ; 
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->blogs));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query("DROP TABLE ".str_replace($old_prefix, $wpdb->prefix, $wpdb->prefix . "pluginSL_" . 'sociallinkz')) ; 
			}
			switch_to_blog($old_blog);
		} else {
			$wpdb->query("DROP TABLE ".$wpdb->prefix . "pluginSL_" . 'sociallinkz' ) ; 
		}
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
			case 'viadeo_hosted' 					: return false 	; break ; 
			case 'viadeo_hosted_count' 		: return false 	; break ; 
						
			case 'googleplus_standard' 					: return false 	; break ; 
			case 'googleplus_standard_count' 					: return false 	; break ; 
			case 'googleplus' 					: return true 	; break ; 
			case 'googleplus_count' 			: return true 	; break ; 

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

			case 'refresh_time'						: return 10; break ; 

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

			case 'exclude' : return "*" 		; break ; 

		}
		return null ;
	}

	/** ====================================================================================================================================================
	* Init javascript for the public side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('my_plugin_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _public_js_load() {	
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
		
		ob_start() ; 
		
		
	}
	
	/** ====================================================================================================================================================
	* Init css for the public side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _public_css_load() {	
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
				$params->add_comment(sprintf(__('Insight provides metrics around your content. See %s for futher details. To identify your Facebook ID, please visit the previous link and then click on Statistic of my website.',$this->pluginID), "<a href='http://www.facebook.com/insights'>Facebook Insights</a>")) ; 
				$params->add_comment(__('You may use an user id, an app id or a page id.',$this->pluginID)) ; 
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
				$params->add_param('viadeo_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_viadeo_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title),"","",array('viadeo_hosted_count')) ; 
				$params->add_param('viadeo_hosted_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 

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
				$params->add_param('display_in_excerpt', __('These buttons should be displayed in excerpt:',$this->pluginID)) ; 

				$params->add_title(__('Where do you want to display the buttons in post?',$this->pluginID)) ; 
				$params->add_param('display_top_in_post', "".__('At the Top:',$this->pluginID)) ; 
				$params->add_param('display_bottom_in_post', "".__('At the Bottom:',$this->pluginID)) ; 

				$params->add_title(__('Where do you want to display the buttons in page?',$this->pluginID)) ; 
				$params->add_param('display_top_in_page', "".sprintf(__('At the Top:',$this->pluginID), $title)) ; 
				$params->add_param('display_bottom_in_page', "".sprintf(__('At the Bottom:',$this->pluginID), $title)) ; 
				
				$params->add_title(__('Advanced options',$this->pluginID)) ; 
				$params->add_param('refresh_time', __('Number of minutes between two refreshes:',$this->pluginID)) ; 
				$params->add_param('html', __('HTML:',$this->pluginID)) ; 
				$default = str_replace("*", "", str_replace(" ", "&nbsp;", str_replace("\n", "<br>", str_replace(">", "&gt;", str_replace("<", "&lt;", $this->get_default_option('html'))))))."<br/>" ; 
				$params->add_comment(sprintf(__('Default HTML is : %s with %s the displayed buttons',$this->pluginID), "<br/>"."<code>".$default."</code>", "<code>%buttons%</code>")) ; 
				$params->add_param('css', __('CSS:',$this->pluginID)) ; 
				$default = str_replace("*", "", str_replace(" ", "&nbsp;", str_replace("\n", "<br>", str_replace(">", "&gt;", str_replace("<", "&lt;", $this->get_default_option('css'))))))."<br/>" ; 
				$params->add_comment(sprintf(__('Default CSS is : %s',$this->pluginID), "<br/>"."<code>".$default."</code>")) ; 
				$params->add_param('exclude', __('Page to be excluded:',$this->pluginID)) ; 
				$params->add_comment(sprintf(__("Please enter one entry per line. If the page %s is to be excluded, you may enter %s.",  $this->pluginID), "<code>http://yourdomain.tld/contact/</code>","<code>contact</code>")) ; 

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
	* Called when the content is displayed
	*
	* @param string $content the content which will be displayed
	* @param string $type the type of the article (e.g. post, page, custom_type1, etc.)
	* @param boolean $excerpt if the display is performed during the loop
	* @return string the new content
	*/
	
	function _modify_content($content, $type, $excerpt) {	
		global $post ; 
		
		// We check whether there is an exclusion
		$exclu = $this->get_param('exclude') ;
		$exclu = explode("\n", $exclu) ;
		foreach ($exclu as $e) {
			$e = trim(str_replace("\r", "", $e)) ; 
			if ($e!="") {
				$e = "@".$e."@i"; 
				if (preg_match($e, get_permalink($post->ID))) {
					return $content ; 
				}
			}
		}
		
		// If it is the loop and an the_except is called, we leave
		if ($excerpt) {
			// Excerpt
			if ($this->get_param('display_in_excerpt')) {
				return $content.$this->print_buttons($post) ; 
			}
		} else {
			// Page
			if ($type=="page") {
				$return =  $content ; 
				if ($this->get_param('display_bottom_in_page')) {
					$return =  $return.$this->print_buttons($post) ;  
				}
				if ($this->get_param('display_top_in_page')) {
					$return =  $this->print_buttons($post).$return ; 
				}
				return $return; 				
			}
			// Post
			if ($type=="post") {
				$return =  $content ; 
				if ($this->get_param('display_bottom_in_post')) {
					$return =  $return.$this->print_buttons($post) ;  
				}
				if ($this->get_param('display_top_in_post')) {
					$return =  $this->print_buttons($post).$return ; 
				}
				return $return; 				
			}
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
		
		$rand = rand(0,1000000000) ; 
		?>
		<script>
			function forceUpdateSocialLinkz_<?php echo $rand ; ?>() {	
				var arguments = {
					action: 'forceUpdateSocialLinkz', 
					id:<?php echo $post->ID ;  ?>
				} 
				//POST the data and append the results to the results div
				var ajaxurl2 = "<?php echo admin_url()."admin-ajax.php"?>" ; 
				jQuery.post(ajaxurl2, arguments, function(response) {
					// nothing
				});
			}
			
			// We launch the callback
			if (window.attachEvent) {window.attachEvent('onload', forceUpdateSocialLinkz_<?php echo $rand ; ?>);}
			else if (window.addEventListener) {window.addEventListener('load', forceUpdateSocialLinkz_<?php echo $rand ; ?>, false);}
			else {document.addEventListener('load', forceUpdateSocialLinkz_<?php echo $rand ; ?>, false);} 
		</script>

		<?php
		
		if ($post->ID==0) {
			$url = home_url("/") ; 
			$long_url = home_url("/") ; 
			$titre = get_bloginfo('name') ." - ".get_bloginfo('description') ; 		
		} else {
			$url = wp_get_shortlink($post->ID) ; 
			$long_url = get_permalink($post->ID) ; 
			$titre = $post->post_title ; 
		}
		
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
					$this->display_bubble($this->get_counter("facebook", $post->ID)) ; 
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
					$this->display_bubble($this->get_counter("twitter", $post->ID)) ; 
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
				<a rel="nofollow" target="_blank" href="https://plus.google.com/share?url=<?php echo $long_url ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "Googe+") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_googleplus.png" alt="Google+" height="24" width="24"/></a>
				<?php
				if ($this->get_param('googleplus_standard_count')) {
					$this->display_bubble($this->get_counter("google+", $post->ID)) ; 
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
			
			if ($this->get_param('linkedin')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($long_url) ; ?>&title=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>&source=<?php echo urlencode(get_bloginfo('name')) ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "LinkedIn") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_linkedin.png" alt="LinkedIn" height="24" width="24"/></a>
				<?php
				if ($this->get_param('linkedin_count')) {
					$this->display_bubble($this->get_counter("linkedin", $post->ID)) ; 
				}
			}
			
			if ($this->get_param('linkedin_hosted')) {
				$coun = "" ; 
				if ($this->get_param('linkedin_hosted_count')) {
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
			
			if ($this->get_param('viadeo_hosted')) {
				$coun = "" ; 
				if ($this->get_param('linkedin_hosted_count')) {
					$coun = 'data-count="right"' ; 
				}
				$viadeoUrl = 'data-url="'.$url.'"' ; 
				?>
				
				<script type="text/javascript">window.viadeoWidgetsJsUrl = document.location.protocol+"//widgets.viadeo.com";(function(){var e = document.createElement('script'); e.type='text/javascript'; e.async = true;e.src = viadeoWidgetsJsUrl+'/js/viadeowidgets.js';var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(e, s);})();</script><div class="viadeo-share" <?php echo $viadeoUrl ?> data-display="btnlight" <?php echo $coun ;?> ></div>
				<?php
			}
			
			if ($this->get_param('stumbleupon')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.stumbleupon.com/submit?url=<?php echo urlencode($long_url) ; ?>&title=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>" title="<?php echo sprintf(__("Share -%s- on %s", $this->pluginID), htmlentities($titre, ENT_QUOTES, 'UTF-8'), "StumbleUpon") ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_stumbleupon.png" alt="StumbleUpon" height="24" width="24"/></a>
				<?php
				if ($this->get_param('stumbleupon_count')) {
					$this->display_bubble($this->get_counter("stumbleupon", $post->ID)) ; 
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
						<p class='textEmailSocialLinkz'><?php echo __("What is your name?", $this->pluginID) ;?></p>
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
		return trim(str_replace("\r", "", str_replace("\n", "", str_replace('%buttons%', $content, $this->get_param('html'))))) ; 
	}
	
	
	/** ====================================================================================================================================================
	* Get counter
	* 
	* @return void
	*/

	function get_counter($social, $id) {
		global $wpdb ; 
		if (isset($this->cache[$id]->{$social})) {
			return $this->cache[$id]->{$social} ; 
		} else {
			$select = "SELECT counters FROM ".$this->table_name." WHERE id_post='".$id."'" ;
			$result = $wpdb->get_var($select) ; 
			if (($result==null)||($result==false)||($result=="")) {
				return 0 ; 
			} else {
				$result = @json_decode($result) ;
				if ($result==NULL) {
					return 0 ; 
				} else {
					// Cache the result to avoid plurality of Mysql Request
					$this->cache[$id] = $result ; 
					// Return the result
					if (isset($result->{$social})) {
						return $result->{$social} ; 
					} else {
						return 0 ; 
					}
				}
			}
		}
	}
	
	/** ====================================================================================================================================================
	* Set counter
	* 
	* @return void
	*/

	function set_counter($socials, $id) {
		global $wpdb ; 
		$new_counters = array() ; 
		
		if ($id==0) {
			$url = home_url("/") ; 
		} else {
			$url = get_permalink($id) ; 
		}
		
		foreach ($socials as $s) {
			$old_counter = $this->get_counter($s, $id) ; 
			
			$nb = $old_counter ; 
			
			// TWITTER
			if ($s=="twitter") {
				$result = wp_remote_get('http://urls.api.twitter.com/1/urls/count.json?url=' .  $url ); 
				if ( is_wp_error($result) ) {
					//trigger_error("SOCIAL LINKZ PLUGIN : Twitter API could not be retrieved to count hits") ; 
				} else {
					$res = @json_decode($result['body'], true);
					if (isset($res['count'])) {
						if (intval($res['count'])>$old_counter)
							$nb =  intval($res['count']);
					} else {
						trigger_error("SOCIAL LINKZ PLUGIN : Twitter API responded but no count can be retrieved for $url") ; 
					}
				}	
			}
			
			// FACEBOOK
			if ($s=="facebook") {
				$result = wp_remote_get("https://graph.facebook.com/fql?q=SELECT%20url,%20normalized_url,%20share_count,%20like_count,%20comment_count,%20total_count,%20commentsbox_count,%20comments_fbid,%20click_count%20FROM%20link_stat%20WHERE%20url='".urlencode($url)."'"); 
				if ( is_wp_error($result) ) {
					//trigger_error("SOCIAL LINKZ PLUGIN : Facebook API could not be retrieved to count hits") ; 
				} else {
					$res = @json_decode($result['body'], true);
					if (isset($res['data'][0]['total_count'])) {
						if (intval($res['data'][0]['total_count'])>$old_counter)
							$nb =  intval($res['data'][0]['total_count']);
					} else {
						ob_start() ; 
							print_r($res) ; 
						$more = ob_get_clean() ; 
						trigger_error("SOCIAL LINKZ PLUGIN : Facebook API responded but no count can be retrieved for $url. <br>$more") ; 
					}
				}
			}		
			
			// LINKEDIN
			if ($s=="linkedin") {
				$result = wp_remote_get('http://www.linkedin.com/countserv/count/share?url=' .  $url ); 
				if ( is_wp_error($result) ) {
					//trigger_error("SOCIAL LINKZ PLUGIN : Linkedin API could not be retrieved to count hits") ; 
				} else {
					if (!(preg_match('/IN.Tags.Share.handleCount\({"count":(\d+),/i',$result['body'],$tmp))) {
						trigger_error("SOCIAL LINKZ PLUGIN : Linkedin API responded but no count can be retrieved for $url") ; 
					} else {
						if (intval($tmp[1])>$old_counter)
							$nb = intval($tmp[1]) ;
					}
				}
			}			
			
			// GOOGLE +
			if ($s=="google+") {
				$post_data = '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' .  $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]' ; 
				$result = wp_remote_post("https://clients6.google.com/rpc?key=AIzaSyCKSbrvQasunBoV16zDH9R33D88CeLr9gQ", array( 'headers' => array('content-type' => 'application/json'), 'body' => $post_data ) );
				if ( is_wp_error($result) ) {
					//trigger_error("SOCIAL LINKZ PLUGIN : Google+ API could not be retrieved to count hits") ; 
				} else {
					$res = @json_decode($result['body'], true);
					if (isset($res[0]['result']['metadata']['globalCounts']['count'])) {
						if (intval($res[0]['result']['metadata']['globalCounts']['count']) >$old_counter)
							$nb = intval($res[0]['result']['metadata']['globalCounts']['count']) ;
					} else {
						trigger_error("SOCIAL LINKZ PLUGIN : Google+ API responded but no count can be retrieved for $url") ; 
					}
				}
			}
			
			// STUMBLEUPON
			if ($s=="stumbleupon") {
				$result = wp_remote_get('http://www.stumbleupon.com/services/1.01/badge.getinfo?url='.$url ); 
				if ( is_wp_error($result) ) {
					//trigger_error("SOCIAL LINKZ PLUGIN : StumbleUpon API could not be retrieved to count hits") ; 
				} else {
					$res = @json_decode($result['body'], true);
					if (isset($res['result']['views'])) {
						if (intval($res['result']['views'])>intval($old_counter))
							$nb = intval($res['result']['views']) ;	
					} else {
						if ((isset($res['result']['in_index'])) && ($res['result']['in_index']==false)){
							//  nothing
						} else {
							trigger_error("SOCIAL LINKZ PLUGIN : StumbleUpon API responded but no count can be retrieved for $url") ;
						}
					}
				}
			}
			
			$new_counters[$s] = $nb ; 	
		}
		// FINALIZATION
		$select = "SELECT COUNT(*) FROM ".$this->table_name." WHERE id_post='".$id."'" ;
		$result = $wpdb->get_var($select) ; 
		if ($result==0) {
			$query = "INSERT INTO ".$this->table_name." (id_post, counters, date_maj) VALUES ('".$id."', '".@json_encode($new_counters)."', '".date_i18n("Y-m-d H:i:s")."')" ; 
		} else {
			$query = "UPDATE ".$this->table_name." SET counters='".@json_encode($new_counters)."', date_maj='".date_i18n("Y-m-d H:i:s")."' WHERE id_post='".$id."'" ; 
		}
		$wpdb->query($query) ; 
	}
	
	/** ====================================================================================================================================================
	* Callback for updating the counters
	* 
	* @return void
	*/
	
	function forceUpdateSocialLinkz() {
		$id = $_POST['id'] ; 
		global $wpdb ; 
		if (!is_numeric($id)) {
			echo "no_numeric" ; 
			die() ; 
		}
		
		$select = "SELECT date_maj FROM ".$this->table_name." WHERE id_post='".$id."'" ;
		$date = $wpdb->get_var($select) ; 
		$now = strtotime(date_i18n("Y-m-d H:i:s")) ; 
		$shouldbeupdate = false ; 
		
		if (($date==null)||($date==false)||($date=="")) {
			$shouldbeupdate = true ; 
		} else {
			$date = strtotime($date) ; 
			if ($now-$date>$this->get_param('refresh_time')*60) {
				$shouldbeupdate = true ; 
			}
		}
		
		if ($shouldbeupdate) {
			$this->set_counter(array("twitter", "facebook", "google+", "stumbleupon", "linkedin"), $id) ; 
			echo "refreshed" ; 
		} else {
			echo "nothing" ; 
		}
		die() ; 
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