<?php
/*
Plugin Name: Social Linkz
Description: <p>Add social links such as Twitter or Facebook at the bottom of every post. </p><p>You can choose the buttons to be Geted. </p><p>This plugin is under GPL licence. </p>
Version: 1.1.0
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
		// Configuration
		$this->pluginName = 'Social Linkz' ; 
		$this->tableSQL = "" ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'uninstall'));
		
		//Parametres supplementaires
		add_filter('the_content', array($this,'print_social_linkz'), 1000);
		add_action('wp_print_scripts', array( $this, 'google_api'));
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
	* Define the default option value of the plugin
	* 
	* @return variant of the option
	*/
	function get_default_option($option) {
		switch ($option) {
			case 'twitter' 						: return true 	; break ; 
			case 'twitter_count' 						: return false 	; break ; 
			case 'twitter_hosted' 				: return false 	; break ; 
			case 'twitter_hosted_count' 		: return false 	; break ; 
			case 'name_twitter'					: return "" 	; break ; 
			
			case 'linkedin' 					: return false 	; break ; 
			case 'linkedin_count' 					: return false 	; break ; 
			case 'linkedin_hosted' 				: return false 	; break ; 
			case 'linkedin_hosted_count' 		: return false 	; break ; 

			case 'viadeo' 					: return false 	; break ; 
			//case 'viadeo_count' 					: return false 	; break ; 
						
			case 'googleplus_standard' 					: return false 	; break ; 
			case 'googleplus_standard_count' 					: return false 	; break ; 
			case 'googleplus' 					: return true 	; break ; 
			case 'googleplus_count' 			: return true 	; break ; 

			case 'googlebuzz' 					: return false 	; break ; 
			case 'googlebuzz_count' 			: return false	; break ; 
			case 'googlebuzz_hosted'			: return false	; break ; 
			case 'googlebuzz_hosted_count'			: return false	; break ; 
			
			case 'facebook' 					: return true 	; break ; 
			case 'facebook_count' 					: return false 	; break ; 
			case 'facebook_hosted' 				: return false 	; break ; 
			case 'facebook_hosted_share'			: return false 	; break ; 
			
			case 'stumbleupon' 					: return false 	; break ; 
			case 'stumbleupon_count' 					: return false 	; break ; 
			case 'stumbleupon_hosted'				: return false 	; break ; 
			case 'print'	 					: return true 	; break ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* Add the Google API for the scripts
	* 
	* @return variant of the option
	*/
	function google_api() {
		wp_enqueue_script('google_plus', 'https://apis.google.com/js/plusone.js');
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
				$params->add_param('twitter', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_twitter.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title))  ; 
				$params->add_param('twitter_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title))  ; 
				$params->add_param('twitter_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_twitter_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title))  ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('twitter_hosted_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 
				$params->add_param('name_twitter', sprintf(__('Your %s pseudo:',$this->pluginID), $title)) ; 
				
				$title = "FaceBook&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('facebook', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_facebook.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('facebook_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title)) ; 
				$params->add_param('facebook_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_facebook_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), "Like ".$title)) ; 
				$params->add_param('facebook_hosted_share', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_facebook_hosted_share.png'/> ".sprintf(__('The official %s button:',$this->pluginID), "Share ".$title)) ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 

				$title = "LinkedIn&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('linkedin', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_linkedin.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('linkedin_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title))  ; 
				$params->add_param('linkedin_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_linkedin_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('linkedin_hosted_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 

				$title = "Viadeo&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('viadeo', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_viadeo.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				//$params->add_param('viadeo_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title))  ; 

				$title = "GoogleBuzz&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('googlebuzz', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googlebuzz.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('googlebuzz_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title))  ; 
				$params->add_param('googlebuzz_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googlebuzz_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('googlebuzz_hosted_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 
				
				$title = "Google+&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('googleplus_standard', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googleplus.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('googleplus_standard_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title)) ; 
				$params->add_param('googleplus', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googleplus_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(__('The SSL websites may not work properly with this official button... Moreover the rendering is not perfect !',$this->pluginID)) ; 
				$params->add_param('googleplus_count', sprintf(__('Show the counter of this official %s button:',$this->pluginID), $title) ) ; 
				
				$title = "StumbleUpon&#8482;" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('stumbleupon', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_stumbleupon.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				$params->add_comment(sprintf(__('To share the post on %s !',$this->pluginID), $title)) ; 
				$params->add_param('stumbleupon_count', sprintf(__('Show the counter of this %s button:',$this->pluginID), $title)) ; 
				$params->add_param('stumbleupon_hosted', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_stumbleupon_hosted.png'/> ".sprintf(__('The official %s button:',$this->pluginID), $title)) ; 
				
				$title = "Print" ; 
				$params->add_title(sprintf(__('Display %s button?',$this->pluginID), $title)) ; 
				$params->add_param('print', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_print.png'/> ".sprintf(__('The %s button:',$this->pluginID), $title)) ; 
				
				$params->flush() ; 
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() ) ; 	
			
			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new translationSL($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() ) ; 	

			ob_start() ; 
				echo __('This form is an easy way to contact the author and to discuss issues / incompatibilities / etc.',  $this->pluginID) ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() ) ; 	
			
			ob_start() ; 
				echo "<p>".__('Here is the plugins developped by the author',  $this->pluginID) ."</p>" ; 
				$trans = new otherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other possible plugins',  $this->pluginID), ob_get_clean() ) ; 	
			

			
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
		$url = wp_get_shortlink() ; 
		$long_url = get_permalink() ; 
		$titre = $post->post_title ; 
		ob_start() ; 
		?>
		<div class="social_linkz">
			<?php
			
			if ($this->get_param('facebook')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.facebook.com/sharer.php?u=<?php echo urlencode($long_url) ; ?>&amp;t=<?php echo urlencode($titre) ; ?>" title="<?php echo sprintf(__("Share -%s- on Facebook", $this->pluginID), htmlentities($titre, ENT_QUOTES)) ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_facebook.png" alt="Facebook" height="24" width="24"/> 
				</a>
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
				<a rel="nofollow" target="_blank" href="http://twitter.com/?status=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>%20-%20<?php echo urlencode($url) ; ?>.<?php echo str_replace('+','%20',urlencode($via)) ; ?>" title="<?php echo sprintf(__("Share -%s- on Twitter", $this->pluginID), htmlentities($titre, ENT_QUOTES)) ;?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_twitter.png" alt="Twitter" height="24" width="24"/> 
				</a>
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
				<a href="http://twitter.com/share" class="twitter-share-button" data-count="<?php echo $coun ; ?>" <?php echo $via ; ?> ><?php echo __('Tweet', $this->pluginID) ; ?></a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
				<?php
			}

			if ($this->get_param('googleplus_standard')) {
				?>
				<a rel="nofollow" target="_blank" href="https://m.google.com/app/plus/x/?v=compose&content=<?php the_title(); ?>%20-%20<?php echo $long_url ; ?>" onclick="window.open('https://m.google.com/app/plus/x/?v=compose&content=<?php the_title(); ?>%20-%20<?php echo $long_url ; ?>','gplusshare','width=450,height=300,left='+(screen.availWidth/2-225)+',top='+(screen.availHeight/2-150));return false;">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_googleplus.png" alt="Google+" height="24" width="24"/> 
				</a>
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
				<a rel="nofollow" target="_blank" href="http://www.google.com/buzz/post?message=<?php the_title(); ?>&url=<?php echo $long_url ; ?>" >
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_googlebuzz.png" alt="Google Buzz" height="24" width="24"/> 
				</a>
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
				<a rel="nofollow" target="_blank" href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($long_url) ; ?>&title=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>&source=<?php echo urlencode(get_bloginfo('name')) ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_linkedin.png" alt="LinkedIn" height="24" width="24"/> 
				</a>
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
				<a rel="nofollow" target="_blank" href="http://www.viadeo.com/shareit/share/?url=<?php echo urlencode($long_url) ; ?>&title=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>&overview=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_viadeo.png" alt="Viadeo" height="24" width="24"/> 
				</a>
				<?php
			}
			
			if ($this->get_param('stumbleupon')) {
				?>
				<a rel="nofollow" target="_blank" href="http://www.stumbleupon.com/submit?url=<?php echo urlencode($long_url) ; ?>&title=<?php echo str_replace('+','%20',urlencode("[Blog] ".$titre)) ; ?>">
					<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ;  ?>/img/lnk_stumbleupon.png" alt="StumbleUpon" height="24" width="24"/> 
				</a>
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
			
			if ($this->get_param('print')) {
				?>
				<a rel="nofollow" target="_blank" href="#" title="<?php echo __("Print", $this->pluginID) ;?>">
					<img onclick="window.print();return false;" class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_print.png" alt="Print" height="24" width="24"/> 
				</a>
				<?php
			}
			?>
		</div>
		<?php
		$content .= ob_get_contents();
		ob_end_clean();
		return $content ; 
	}
	


	
	/** ====================================================================================================================================================
	* Get  twitter counter
	* 
	* @return void
	*/

	function get_twitter_counter($url) {
		$counter = $this->get_param('counter_twitter'.url_to_postid($url)) ; 
		// update every 30min
		if ((is_array($counter))&&($counter['date']+(30*60)>time())) {
			$nb = $counter['count']; 
		} else {
			$result = wp_remote_get('http://urls.api.twitter.com/1/urls/count.json?url=' .  $url ); 
			if ( is_wp_error($result) ) {
				$nb = "?" ;
			} else {
				$res = @json_decode($result['body'], true);
				if (isset($res['count'])) {
					$nb =  $res['count'];
				} else {
					$nb =  "??" ; 
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
		if ((is_array($counter))&&($counter['date']+(30*60)>time())) {
			$nb = $counter['count'] ; 
		} else {
			$result = wp_remote_get('http://graph.facebook.com/?ids=' .  $url ); 
			if ( is_wp_error($result) ) {
				$nb = "?" ;
			} else {
				
				$res = @json_decode($result['body'], true);
				if (isset($res[$url]['shares'])) {
					$nb =  $res[$url]['shares'];
				} else {
					if (isset($res[$url]['id'])){
						$nb = "0" ; 
					} else {
						$nb =  "??" ; 
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
		if ((is_array($counter))&&($counter['date']+(30*60)>time())) {
			$nb = $counter['count'] ; 
		} else {
			$result = wp_remote_get('http://www.linkedin.com/cws/share-count?url=' .  $url ); 
			if ( is_wp_error($result) ) {
				$nb = "?" ;
			} else {
				//IN.Tags.Share.handleCount({"count":0,"url":"http://www.sedlex.fr/cas-pratiques/les-donnees-a-conserver-pour-les-hebergeurs/"}
				if (!(preg_match('/IN.Tags.Share.handleCount\({"count":(\d+),/i',$result['body'],$tmp))) {
					$nb = "??" ; 
				} else {
					$nb = $tmp[1] ; 
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
		if ((is_array($counter))&&($counter['date']+(30*60)>time())) {
			$nb = $counter['count'] ; 
		} else {
			$nb=0 ; 
			$post_data = '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' .  $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]' ; 
			$result = wp_remote_post("https://clients6.google.com/rpc?key=AIzaSyCKSbrvQasunBoV16zDH9R33D88CeLr9gQ", array( 'headers' => array('content-type' => 'application/json'), 'body' => $post_data ) );
			if ( is_wp_error($result) ) {
				$nb = "?" ;
			} else {
				$res = @json_decode($result['body'], true);
				if (isset($res[0]['result']['metadata']['globalCounts']['count'])) {
					$nb =  $res[0]['result']['metadata']['globalCounts']['count'];
				} else {
					$nb =  "??" ; 
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
		if ((is_array($counter))&&($counter['date']+(30*60)>time())) {
			$nb = $counter['count'] ; 
		} else {
			$nb=0 ; 
			$result = wp_remote_get("https://www.googleapis.com/buzz/v1/activities/count?alt=json&url=".$url );
			if ( is_wp_error($result) ) {
				$nb = "?" ;
			} else {
				$res = @json_decode($result['body'], true);
				//print_r($res) ; 
				if (isset($res['data']['counts']['count'])) {
					$nb = $res['data']['counts']['count'] ; 
				} else {
					$nb =  "??" ; 
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
		if ((is_array($counter))&&($counter['date']+(30*60)>time())) {
			$nb = $counter['count'] ; 
		} else {
			$result = wp_remote_get('http://www.stumbleupon.com/services/1.01/badge.getinfo?url='.$url ); 
			if ( is_wp_error($result) ) {
				$nb = "?" ;
			} else {
				$res = @json_decode($result['body'], true);
				if (isset($res['result']['views'])) {
					$nb =  $res['result']['views'];
				} else {
					if ((isset($res['result']['in_index'])) && ($res['result']['in_index']==false)){
						$nb =  "0" ; 
					} else {
						$nb =  "??" ; 
						print_r($res) ; 
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
}

$sociallinkz = sociallinkz::getInstance();

?>