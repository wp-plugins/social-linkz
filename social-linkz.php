<?php
/*
Plugin Name: Social Linkz
Description: <p>Add social links such as Twitter or Facebook at the bottom of every post. </p><p>You can choose the buttons to be displayed. </p><p>This plugin is under GPL licence. </p>
Version: 1.0.3
Author: SedLex
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
		
		//Paramètres supplementaires
		add_filter('the_content', array($this,'print_social_linkz'));
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
			case 'twitter' 		: return true 	; break ; 
			case 'googleplus' 		: return true 	; break ; 
			case 'googleplus_count' : return true 	; break ; 
			case 'name_twitter'	: return "" 	; break ; 
			case 'facebook' 	: return true 	; break ; 
			case 'print'	 	: return true 	; break ; 
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
			<?php echo $this->signature ; ?>
			<p>This plugin help you sharing on the social network by adding facebook or twitter buttons.</p>
			<!--debut de personnalisation-->
		<?php
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
	?>		
			<script>jQuery(function($){ $('#tabs').tabs(); }) ; </script>		
			<div id="tabs">
				<ul class="hide-if-no-js">
					<li><a href="#tab-parameters"><? echo __('Parameters',$this->pluginID) ?></a></li>					
				</ul>
				<?php
				//==========================================================================================
				//
				// Premier Onglet 
				//		(bien verifier que id du 1er div correspond a celui indique dans la mise en 
				//			place des onglets)
				//
				//==========================================================================================
				?>
				<div id="tab-parameters" class="blc-section">
				
					<h3 class="hide-if-js"><? echo __('Parameters',$this->pluginID) ?></h3>
					<p><?php echo __('Here is the parameters of the plugin. Please modify them at your convenience.',$this->pluginID) ; ?> </p>
				
					<?php
					$params = new parametersSedLex($this, 'tab-parameters') ; 
					$params->add_title(__('What do you want to print at the end of your post?',$this->pluginID)) ; 
					$params->add_param('twitter', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_twitter.png'/> ".__('The Twitter button:',$this->pluginID)) ; 
					$params->add_param('name_twitter', __('Your twitter name:',$this->pluginName)) ; 
					$params->add_param('facebook', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_facebook.png'/> ".__('The FaceBook button:',$this->pluginID)) ; 
					$params->add_param('googleplus', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_googleplus.png'/> ".__('The Google+ button:',$this->pluginID)) ; 
					$params->add_param('googleplus_count', __('Show the count for the Google+ button:',$this->pluginID)) ; 
					$params->add_param('print', "<img src='".WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/img/lnk_print.png'/> ".__('The print button:',$this->pluginID)) ; 
					
					$params->flush() ; 
					
					?>
				</div>
			</div>
			<!--fin de personnalisation-->
			<?php echo $this->signature ; ?>
		</div>
		<?php
	}
	
	function print_social_linkz ($content) {
		global $post ; 
		$url = wp_get_shortlink() ; 
		$titre = $post->post_title ; 
		ob_start() ; 
		?>
		<div class="social_linkz">
			<?php
			if ($this->get_param('facebook')) {
			?>
			<a rel="nofollow" target="_blank" href="http://www.facebook.com/sharer.php?u=<?php echo urlencode($url) ; ?>&amp;t=<?php echo urlencode($titre) ; ?>" title="<?php echo sprintf(__("Share -%s- on Facebook", $this->pluginID), htmlentities($titre, ENT_QUOTES)) ; ?>">
				<img class="lnk_social_linkz" src="<?php echo WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__)) ; ?>/img/lnk_facebook.png" alt="Facebook" height="24" width="24"/> 
			</a>
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
}

$sociallinkz = sociallinkz::getInstance();

?>