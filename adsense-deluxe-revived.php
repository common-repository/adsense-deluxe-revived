<?php
/*
 * Plugin Name:   Adsense Deluxe Revived
 * Version:       3.0
 * Plugin URI:    http://wordpress.org/extend/plugins/adsense-deluxe-revived/
 * Description:   Place Google <a href="https://www.google.com/adsense/" title="adsense">AdSense</a> ads in your WordPress Posts. Requires WordPress 1.5 or higer. Adjust your settings <a href="options-general.php?page=adsense-deluxe-revived/adsense-deluxe-revived.php">here</a>.
 * Author:        MaxBlogPress
 * Author URI:    http://www.maxblogpress.com
 *
 * License:       GNU General Public License
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * Copyright (C) 2007 www.maxblogpress.com
 *
 * This is the improved version of "Adsense-Deluxe" plugin by Acme Technologies 
 *
 */

$mbpad_path     = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
$mbpad_path     = str_replace('\\','/',$mbpad_path);
$mbpad_dir      = substr($mban_path,0,strrpos($mbpad_path,'/'));
$mbpad_siteurl  = get_bloginfo('wpurl');
$mbpad_siteurl  = (strpos($mbpad_siteurl,'http://') === false) ? get_bloginfo('siteurl') : $mbpad_siteurl;
$mbpad_fullpath = $mbpad_siteurl.'/wp-content/plugins/'.$mbpad_dir.'';
$mbpad_fullpath  = $mbpad_fullpath.'adsense-deluxe-revived/';

define('MBP_AD_LIBPATH', $mbpad_fullpath);
define('MBP_AD_NAME', 'Adsense Deluxe Revived');
define('MBP_AD_VERSION', '3.0');
add_action('activate_'.$mbpad_path, 'adsense_active' );
//--
//-- You can select in the Adsense-Deluxe options page to give something back to this
//-- plugin's author (me) by having 5% of the ads shown on your WP blog use my adsense
//-- client ID. This is DISABLED by default, and I assure you I do nothing in the code
//-- to subversively turn it on! The way it works is if you enable the option (and it's
//-- just as easily disabled...), approximately 5% of the time an adsense ad block is
//-- displayed, it will use my AdSense client-id, and if someone happens to click one of 
//-- those ads, I benefit from it and you've helped encourage me to continue supporting
//-- this plugin. If you're going to enable this option, you can make me feel even happier
//-- posting a comment on the blog page for this plugin to let mee know, and I can 
//-- personally thank you...
//--  http://www.acmetech.com/blog/2005/07/26/adsense-deluxe-wordpress-plugin/
//--

$__ACMETECH_CLIENT_ID__ = "pub-6179066220764588";
$__ACMETECH_AD_PARTNER__ = "1881826992";

//--
//-- 		CONSTANTS
//--
define('ADSDEL_OPTIONS_ID', 'acmetech_adsensedeluxe');

//--
//-- OUTPUTS debugging info in html comments on blog pages.
//--
$__AdSDelx_Debug__ = false;

//--
//-- If set to false, live adsense ads displayed in Post editing preview
//--
$__AdSDelx_USE_PREV_PLACEHOLDER = true;


/* 
adsense-deluxe
This function replaces <!--adsense--> or <!--adsense[#name]-->tags with actual Google Adsense code
*/ 

if (function_exists('is_plugin_page') && is_plugin_page()) :

	AdsenseDeluxeOptionsPanel(); // check here to see if the broken 1.5 options page feature is fixed

else :

	function adsense_active(){
		update_option('mbp_remve_pwdby_adsense', 1 );
	}

	function adsense_deluxe_insert_ads($data) {
		global	$__AdSDelx_USE_PREV_PLACEHOLDER,
				$__ACMETECH_CLIENT_ID__,
				$__ACMETECH_AD_PARTNER__,
				$doing_rss, 	/* will be true if getting RSS feed */
				$_adsdel_adcount; /* tracks number of posts we've processed on home page */
	
		$MAX_ADS_PER_PAGE = 3; // MAX # of AdSense ads to allow on a given page
		$EDITING_PAGE = false;
		$PLACEHOLDER = '<span style="background-color:#99CC00;border:1px solid #0000CC;padding:3px 8px 3px 8px;font-weight:bold;color:#111;">&lt;!--@@--&gt;</span>';
		$PLACEHOLDER_DISABLED = '<span style="background-color:#99CC00;border:1px solid #0000CC;padding:3px 8px 3px 8px;font-weight:normal;font-style:italic;color:#C00;">&lt;!--@@--&gt;</span>';
	/*
	 * For format of $options, see _AdsDel_CreateDefaultOptions()
	 *
	 */
	
		$options = get_option(ADSDEL_OPTIONS_ID);
		//-- see if global switch is off
		if( ! $options['all_enabled'] ){
			return  "\n<!-- ALL ADSENSE ADS DISABLED -->\n" . $data;
		}
		// NO ADSENSE IN FEEDS!
		if($doing_rss){
			//return  "\n<!-- RSS FEED IN PROGRESS -->\n" . $data;
			return $data;
		}
		if( strstr($_SERVER['PHP_SELF'], 'post.php') ){
			// user is editing a page or post, show placeholders, not real ads
			$EDITING_PAGE = ($__AdSDelx_USE_PREV_PLACEHOLDER ? true : false);
		}
		
		// set up some variables we need
		$patts = array();
		$subs = array();
		$default = $options['default'];
		$rewardAut = $options['reward_author'];
		$qualifer = '';
		$msg = "<!--AdSense-Deluxe Plug-in Debug -->\n";
		$msg .= "\n<!-- Posts Enabled=".$options['enabled_for']['posts']." -->"; //DEBUGGING
		$msg .= "\n<!-- Home Enabled=".$options['enabled_for']['home']." -->"; //DEBUGGING
		$msg .= "\n<!-- Archives Enabled=".$options['enabled_for']['archives']." -->"; //DEBUGGING
		$msg .= "\n<!-- Pages Enabled=".$options['enabled_for']['page']." -->"; //DEBUGGING
		if( isset($_adsdel_adcount) )
			$msg .= "\n<!-- _adsdel_adcount = $_adsdel_adcount -->"; //DEBUGGING
	
		//-- fill in stuff to search for ($patts) and substition blocks ($subs)
		foreach( $options['ads'] as $key => $vals ){
			if( $key == $default ){
				$msg .= "\n<!-- DEFAULT Ad=[$key] -->\n"; //DEBUGGING
				$patts[] = "<!--adsense-->";
				$subs[] = ($vals['enabled'] ? stripslashes($vals['adsense']).ad_pwd_by() : "<!-- Default Block: $key DISABLED-->\n");
				if($EDITING_PAGE) $subs[ sizeof($subs)-1] = str_replace('@@', 'adsense', ($vals['enabled'] ? $PLACEHOLDER : $PLACEHOLDER_DISABLED));
			}
			$msg .= "\n<!-- FOUND Ad [" . $key ."] -->"; //DEBUGGING
			$patts[] = "<!--adsense#" . $key . "-->";
			$subs[] = ($vals['enabled'] ? stripslashes($vals['adsense']).ad_pwd_by() : "<!-- $key DISABLED-->");
			if($EDITING_PAGE) $subs[ sizeof($subs)-1] = str_replace('@@', 'adsense#'.$key, ($vals['enabled'] ? $PLACEHOLDER : $PLACEHOLDER_DISABLED));
		}

		/*if( rand(0, 100) >= 95 && ! $EDITING_PAGE && $rewardAut ){
			if( is_single() || is_page() ){
				$msg .= "\n<!-- REWARDING PLUGIN AUTHOR -->"; //DEBUGGING
				$subbed = preg_replace ( '/pub-[0-9]+/', $__ACMETECH_CLIENT_ID__, $subs );
				$subs = preg_replace ( '/google_ad_channel *= *\"[^"]*\"/', 'google_ad_channel = "1478884331"', $subbed );
				$subbed = preg_replace ( '/ctxt_ad_partner *= *\"[^"]*\"/', 'ctxt_ad_partner = "' . $__ACMETECH_AD_PARTNER__ . '"', $subs );
				$subs = preg_replace ( '/ctxt_ad_section *= *\"[^"]*\"/', 'ctxt_ad_section = "20007"', $subbed );

			}
		}*/
		
		// check that post contains adsense token so we can count # of times
		// we've shown ads in this page load
		$matchCount = 0;
		$matchCount = preg_match_all ( "/<!--adsense(#)?[^- ]*-->/", $data, $matches , PREG_PATTERN_ORDER );
		$show_ads = false;
		$msg .= "\n<!-- AD PLACEHOLDERS FOUND (in post) = [$matchCount] -->"; //DEBUGGING
		if( $matchCount > 0 ){
			//--
			//-- Have to take into account the fact that perhaps we've already shown
			//-- 2 ads for a page (not necessarily a single post page), but the current $data 
			//-- contains 2 or more placeholder comments. 
			//-- Since replacements in $data are done en_masse, we might go 
			//-- over our limit for this post, but but we'll prefer that over
			//-- not showing at least $MAX_ADS_PER_PAGE ad blocks.
			//-- 
			$show_ads = true;
			if( ! isset($_adsdel_adcount) ){
				$_adsdel_adcount = $matchCount;
			}else{
				if( $_adsdel_adcount > $MAX_ADS_PER_PAGE )
					$show_ads = false;
				$_adsdel_adcount+=$matchCount;
			}
		}
		
		if( $show_ads )
		{
			// NOTE: might have to use ksort() on patts,subs if wrong blocks are being subbed in.
			if( is_single() )
			{
				if( $options['enabled_for']['posts'] )
					return str_replace($patts, $subs, $data); //. $msg;
				return $data;
			}
			elseif ( is_home() )
			{  
				$msg .= "\n<!-- Handling home page -->"; //DEBUGGING
				$msg .= "\n<!-- _adsdel_adcount = $_adsdel_adcount -->"; //DEBUGGING
				if( $options['enabled_for']['home'] ) return str_replace($patts, $subs, $data);
				return  $data;
			}
			elseif( is_page() )
			{
				$msg .= "\n<!-- Handling PAGE Ad-Sense -->"; //DEBUGGING
				if( $options['enabled_for']['page'] ) return str_replace($patts, $subs, $data);
				return $data;
			}
			elseif( is_archive() )
			{
				$msg .= "\n<!-- Handling ARCHIVES Ad-Sense -->"; //DEBUGGING
				if( $options['enabled_for']['archives'] ) return str_replace($patts, $subs, $data);// .$msg;
				return $data;			
			}
			elseif( is_search() )
			{
				$msg .= "\n<!-- Handling SEARCH Page Ad-Sense -->"; //DEBUGGING
				if( $options['enabled_for']['archives'] )
					return str_replace($patts, $subs, $data);
				return $data; // . $msg;
			}
			else
			{
				$msg .= "\n<!-- Handling **DEFAULT** Page Ad-Sense -->"; //DEBUGGING
				return str_replace($patts, $subs, $data); // . $msg;
				//return str_replace( $tag, '', $data );
			}
		}else{// if( $show_ads )
			return $data ; //. $msg;
		}

	} // function adsense_deluxe_insert_ads(...)
	
	function ad_pwd_by(){
	    $remove_pwd = get_option('mbp_remve_pwdby_adsense');
		if( $remove_pwd != 1 ){ 
			return	$pwd_by ='<br><span style="font-size:9px;font-weight:normal;font-color:#0000FF;letter-spacing:-1px" target="_blank">Powered by </span><a href="http://wordpress.org/extend/plugins/adsense-deluxe-revived/" style="font-size:9px;font-weight:normal;font-color:#0000FF;letter-spacing:-1px" target="_blank">Adsense Deluxe Revived</a><br>';
		}
	}

	/*
	 * Can be used outside the loop. Prints the adsense code for a named Ad block.
	 * Leave the parameter empty to output the default block.
	 * example: for a block named "blue_banner", call adsense_deluxe_ads("blue_banner");
	 * or within your templates, use <?php adsense_deluxe_ads("ad_block_name"); ?>
	 */
	function adsense_deluxe_ads($adname='') {
		global	$__AdSDelx_USE_PREV_PLACEHOLDER,
				$_adsdel_adcount; /* tracks number of posts we've processed on home page */
	
		$MAX_ADS_PER_PAGE = 3; // MAX # of AdSense ads to allow on a given page
		$EDITING_PAGE = false;
		/*
		 * For format of $options, see _AdsDel_CreateDefaultOptions()
		 *
		 */
	
		$options = get_option(ADSDEL_OPTIONS_ID);
		//-- see if global switch is off
		if( ! $options['all_enabled'] ){
			echo  "\n<!-- ALL ADSENSE ADS DISABLED -->\n";
			return;
		}

		// set up some variables we need
		$patts = array();
		$subs = array();
		$default = $options['default'];

		if( $adname == '' )
			$adname = $default;

		$show_ads = true;
		$msg = "<!--AdSense-Deluxe Plug-in Debug [adsense_deluxe_ads()]-->\n";
	
		//-- locate ad block
		foreach( $options['ads'] as $key => $vals ){
			if( $key == $adname ){
				$msg .= "<!-- Matched adblock named " . $key . "-->\n";
				if( ! isset($_adsdel_adcount) ){
					$_adsdel_adcount = 0;
				}else{
					if( $_adsdel_adcount > $MAX_ADS_PER_PAGE )
						$show_ads = false;
				}
				$_adsdel_adcount+=1;
	
				$msg .= "<!-- _adsdel_adcount = $_adsdel_adcount -->\n"; //DEBUGGING

				//echo $msg;
				if( $show_ads )
					echo ($vals['enabled'] ? stripslashes($vals['adsense']) : "<!-- $key DISABLED-->");
				return;
			}
		}
		$msg .= "<!-- AdSense-Deluxe: ad not found for " . $adname . ".-->\n";
		echo $msg;

	} // function adsense_deluxe_ads(...)


	function add_adsense_deluxe_handle_head()
	{
		global $__ADSENSE_DELUXE_VERSION__;
		echo "\n".'<!-- Powered by AdSense-Deluxe WordPress Plugin v' . $__ADSENSE_DELUXE_VERSION__ . ' - http://www.acmetech.com/blog/adsense-deluxe/ -->' . "\n";

	}
	
	
	function _AdsDel_GetVersion(){
		global $__ADSENSE_DELUXE_VERSION__;
		return $__ADSENSE_DELUXE_VERSION__;
	}
	function _AdsDel_FormatVersion(){
		return "<span style='color:red;'>v" . _AdsDel_GetVersion() . "</span>";
	}

function _AdsDel_DisplayAvailUpdate($pi_vers=0.0)
{	
	$pi_vers+=0.0;
	
	$options = get_option(ADSDEL_OPTIONS_ID);
	// NEXT LINE ONLY FOR TESTING CODE, just ignore... 
	//unset($options['next_update_check']); unset($options['latest_version']); update_option(ADSDEL_OPTIONS_ID, $options); return '';
	if( isset($options) ){
		$check = $options['next_update_check'];
		if( time() > (integer)$check ){
			$next_week = time() + (7 * 24 * 60 * 60);
			$options['next_update_check'] = $next_week;
			$new_vers = _AdsDel_VersionCheck();
			if( $new_vers != '' ){
				$options['latest_version'] = floatval($new_vers);
			}else{
				$options['latest_version'] = floatval($pi_vers);
			}
			update_option(ADSDEL_OPTIONS_ID, $options);
		}
	}

	if( isset($options) && isset($options['latest_version']) ){
		$new_vers = $options['latest_version'];
		if( floatval($options['latest_version']) > $pi_vers ){
			return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style='font-weight:bold;color:#ff0;'  href='http://www.acmetech.com/blog/adsense-deluxe/' target='external' title='New AdSense-Deluxe version available'>DOWNLOAD LATEST UPDATE (v$new_vers)</a>";
		}
	}else{
		return '';
	}
}
function _AdsDel_VersionCheck()
{
	$string = '';
	$url = "http://software.acmetech.com/wordpress/plugins/adsense-deluxe-version.txt";
	$url = parse_url ($url);
	if ($handle = @fsockopen ($url['host'], 80,$errno, $errstr,10)) {
		fwrite ($handle, "GET $url[path]?$url[query] HTTP/1.0\r\nHost: $url[host]\r\nConnection: Close\r\n\r\n");
		while (!feof($handle)) {
			$string .= @fread($handle, 30);
		}
				$string = explode ("
", $string);
				$string = array_pop ($string);
		$string = trim($string);
	}
	fclose($handle);
	return 0+$string; // convert to float
}

	/*
	**
	** Create default set of options and add to database
	**/
	function _AdsDel_CreateDefaultOptions()
	{
		$ADSDEL_OPTIONS_ID = 'acmetech_adsensedeluxe';

		$options = array();
		$options['version'] = (string)_AdsDel_GetVersion(); //this is a string but casting it anyway
		$options['next_update_check'] = time(); // when to check for update to plugin next.
		$options['all_enabled'] = true; // controls whether all ads on/off; can also disable at ad-level
		//-- control whether ads are enabled for specific areas: 
		//-- individual posts, Pages, home page or any archive page
		$options['enabled_for'] = array('home' => true,'posts' => true,'page'=>true,'archives' =>true);
		$options['default'] = NULL;		// always have to check against NULL for default.
		$options['reward_author'] = false; // DO NOT reward author with 5% of adsense impressions
		$options['ads'] = array();
		add_option(ADSDEL_OPTIONS_ID, $options, 'Options for AdSense-Deluxe from www.acmetech.com');
		return $options;
	}
	function _AdsDel_CheckOptions($o)
	{
		if( ! isset($o['all_enabled']) )
			$o['all_enabled'] = true;
		if( ! isset($o['ads']) )
			$o['ads'] = array();
		if( ! isset($o['default']) )
			$o['default'] = NULL;
		if( ! isset($o['reward_author']) )
			$o['reward_author'] = false; // DEFAULT IS TO not REWARD PLUGIN AUTHOR...
		
		foreach( $options['ads'] as $key => $vals ){
			if( ! isset($vals['enabled']) )
				$o['ads'][$key]['enabled'] = true;
			if( ! isset($vals['desc']) )
				$o['ads'][$key]['desc'] = '(No Description)';
		}
	}
	
	/*
	**
	** Output Top of Options page.
	**/
	function _AdsDel_Header()
	{
		global $__ADSENSE_DELUXE_VERSION__;
		$get_url = $_SERVER[PHP_SELF] . '?page=adsense-deluxe-revived/' . basename(__FILE__);
		$def_url = $get_url . "&amp;fn=debug";
		echo "\n<h2>".MBP_AD_NAME." ".MBP_AD_VERSION." (<a href='#template'>Add New</a>)</h2>";
?>
<strong style="padding:0 0 10px 0"><img src="<?php echo MBP_AD_LIBPATH;?>images/how.gif" border="0" align="absmiddle" /> <a href="http://wordpress.org/extend/plugins/adsense-deluxe-revived/other_notes/" target="_blank">How to use it</a>&nbsp;&nbsp;&nbsp;
		<img src="<?php echo MBP_AD_LIBPATH;?>images/comment.gif" border="0" align="absmiddle" /> <a href="http://www.maxblogpress.com/forum/forumdisplay.php?f=25" target="_blank">Community</a></strong>
<br/><br/>
<?php				
	}// _AdsDel_Header()
	
	/*
	**
	** Output bottom of Options page including instructions.
	**/
	function _AdsDel_Footer()
	{
		$ads_deluxe_blog_url = get_settings('home');
		?>
<script>
		function __mbanShowHide(curr, img, path) { 
			var curr = document.getElementById(curr);
			if ( img != '' ) {
				var img  = document.getElementById(img);
			}
			var showRow = 'block'
			if ( navigator.appName.indexOf('Microsoft') == -1 && curr.tagName == 'TR' ) {
				var showRow = 'table-row';
			}
			if ( curr.style == '' || curr.style.display == 'none' ) {
				curr.style.display = showRow;
				if ( img != '' ) img.src = path + 'images/minus.gif';
			} else if ( curr.style != '' || curr.style.display == 'block' || curr.style.display == 'table-row' ) {
				curr.style.display = 'none';
				if ( img != '' ) img.src = path + 'images/plus.gif';
			}
		}
</script>
		<br />
		<fieldset class="options">
		<legend id="instructions"><span style="font-weight:bold;color:#00C;"><img src="<?php echo MBP_AD_LIBPATH; ?>images/plus.gif" id="replace_image" border="0" /><a style="cursor:hand;cursor:pointer" onClick="__mbanShowHide('hide_ads','replace_image','<?php echo MBP_AD_LIBPATH ?>');">AdSense Deluxe Instructions</a></span></legend> 
		<div id="hide_ads" style="display:none">
		<p>
   		This plugin allows you to insert html comments in your posts (or WordPress templates) and have them replaced
   		by the actual Google AdSense or Yahoo Publisher Network code. You can define a single default code block to use, or as many variations as you like. <b>Adsense Deluxe Revived</b> makes it easy to test different AdSense styles in all your posts without having to edit the WordPress code or templates, or change all the posts manually.
   		</p><p>
   		The designated default AdSense code is included in a post by inserting this: <code style="color:blue;">&lt;!--adsense--&gt;</code> wherever you want the ads to appear. To insert an alternate AdSense block which you've defined by a keyword (for example, &quot;wide_banner&quot;, you would use: <code style="color:blue;">&lt;!--adsense#wide_banner--&gt;</code>.
   		</p>
		<p>When viewing the list of ads you've defined, the default ad block will have a shaded background. <span style="color:red;font-weight:bold;">Tip:</span> When viewing the list of ad units you've defined you can click on the linked Description text to preview the ad style.</p>
		<p>If you want to use the ads defined in Adsense Deluxe Revived within your WordPress templates, place the following code where you want the ads to appear:<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<code style="color:#0033CC;">&lt;?php adsense_deluxe_ads('Ad_Name'); ?&gt;</code>.<br />Calling that PHP function without a parameter will return the default ad unit.
		</p>
		<p>
   		Please restrict your keywords to the letters a-zA-Z, 0-9 and underscore (_). Matching is case-sensitive, so you might save yourself headaches by sticking to lowercase keywords. Also avoid extraneous spaces inside the html comments; regular expressions (which could account for extra whitespace) are not used so that replacements when the page is serving are as fast as possible.
   		</p>
			<blockquote><dl>
				<dt><b>Name</b></dt>
				<dd>This is the name by which you reference an AdSense
		 block of code when creating posts. For example, if you <b><i>name</i></b> a block &quot;wide_banner&quot;, you would insert into your post<br />&quot;<code style="color:blue;">&lt;!--adsense#wide_banner--&gt;</code>&quot;.
		 <br /><br />Whichever block is designated as the <i>default</i> AdSense block will be substituted wherever the default comment string is found (&quot;<code style="color:blue;">&lt;!--adsense--&gt;</code>&quot;), and also for any comment strings which reference it by its unique name (e.g., &quot;<code style="color:blue;">&lt;!--adsense#test--&gt;</code>&quot;). You'll want to set the <i>default</i>  AdSense block to the AdSense code you will use in the most places within your posts.
		 		</dd>
		 		<dt><b>AdSense Code</b></dt>
		 		<dd>This is the block of AdSense code to substitute for the given keyword.</dd>
		 		<dt><b>Description</b></dt>
		 		<dd>This is for your own use to help remember what each block of AdSense code looks like. You might use something like &quot;banner 468x60, white background&quot;</dd>
		 	</dl>
		 	</blockquote>
			<p> Please make sure you read <a href="https://www.google.com/adsense/policies" target="external">Google's TOS</a> before using this plugin!
			</p>
			</div>
			<p><hr><span style="font-size:.9em;color:#888;">
			
<div align="center" >
  <p align="center"><strong><?php echo MBP_AD_NAME.' '.MBP_AD_VERSION; ?> by <a href="http://www.maxblogpress.com" target="_blank">MaxBlogPress</a></strong></p>
  <p align="center">This plugin is the result of <a href="http://www.maxblogpress.com/blog/219/maxblogpress-revived/" target="_blank">MaxBlogPress Revived</a> project.</p>
</div>

			</span>
			</p>
		</fieldset>
<?php
	}//_AdsDel_Footer()

	/*
	**
	** Output AdSense Preview tool (http://www.acmetech.com/tools/adsense-preview)
	**/
	function _AdsDel_AdSense_sandbox()
	{
		$ads_deluxe_blog_url = get_settings('home');
		$msg_ad_txt = "This form allows you to preview the ads which would appear on a web page. Just enter any URL in the text box and the ads will display in a new window. Since they are shown in test mode, none of the impressions are recorded and clicking them does not cost nor benefit anyone.";
	?>
	<script type="text/javascript" src="<?php echo MBP_AD_LIBPATH;?>tooltip.js"></script>
	<link href="<?php echo MBP_AD_LIBPATH;?>tooltip.css" rel="stylesheet" type="text/css">
		<br />
		<fieldset class="options">
		<legend id="adsense_sandbox"><span style="font-weight:bold;color:#00C;">AdSense SandBox</span> (Preview Tool)</legend> 
<blockquote>
<form target="external" name="adsense_sandbox" action="http://www.acmetech.com/tools/adsense-preview/#adsense" method="get"><input type="hidden" name="client" value="deluxe"/>
View AdSense for:<br /><input type="text" size="30" name="u[0]" value="<?php echo $ads_deluxe_blog_url ?>"/>&nbsp;<input name="submit" type="submit" value="Preview AdSense"/>&nbsp;<a href="#" onMouseover="tooltip('<?php echo $msg_ad_txt;?>',280)" onMouseout="hidetooltip()" style="border-bottom:none;"><img src="<?php echo MBP_AD_LIBPATH;?>images/help.gif" border="0" align="absmiddle" /></a>
</form>
</blockquote>
</fieldset>
<?php
}

function remove_pwdby(){
if($_POST['submit'] == "Submit" ){
update_option('mbp_remve_pwdby_adsense', $_POST['chk_pwdby']);
}
$chk_option = get_option('mbp_remve_pwdby_adsense');
if( $chk_option == 1 ){ $checked = 'checked'; }
 ?>
<br>
<legend id="adsense_sandbox"><span style="font-weight:bold;color:#00C;">Remove Option</span></legend>
<blockquote>
<form name="hide_pwdby" action="" method="post">
<input name="chk_pwdby" type="checkbox" value="1" <?php echo $checked; ?> />&nbsp;<b>Remove "Powered by Adsense Deluxe Revived"</b>
<input name="submit" type="submit" value="Submit"/>
</form>
</blockquote>

<?php

}

	/*
	**
	** Output Reward Plugin Author settings
	**/
	function _AdsDel_RewardAuthor($vals=NULL)
	{
		$action_url = $_SERVER[PHP_SELF] . '?page=adsense-deluxe-revived/' . basename(__FILE__);
		$rewards_checked = '';
		if( isset($vals) ){
			if( isset($vals['reward_author']) && $vals['reward_author'] )
				$rewards_checked = 'checked="checked"';
		}

}

	/*
	**
	** Output New Adsense block form
	**/
	function _AdsDel_NewAdForm($vals=NULL)
	{
		if( ! isset($vals) ){
			$vals = array(	'name' => '',
							'code' => '',
							'comment' => '',
							'enabled' => '1',
							'make_default' => ''
							);
		}
		$name = $vals['name'];
		$enabled = ($vals['enabled'] == '1');
		$code = htmlentities(stripslashes($vals['code']) , ENT_COMPAT);
		$comment = htmlentities(stripslashes($vals['comment']), ENT_COMPAT);
		$submit_text = "Add AdSense Block &raquo;";
		if( isset($vals['edit_kw']) ){
			$submit_text = "Edit AdSense Block &raquo;";
		}
		
		// this url will scroll the page to the new ad form.
		//$action_url = $_SERVER[PHP_SELF] . '?page=adsense/' . basename(__FILE__) . "&amp;#new_ad";
		// this url reloads to unscrolled page.
		$action_url = $_SERVER[PHP_SELF] . '?page=adsense-deluxe-revived/' . basename(__FILE__);
		
		//--
		//-- check for aleady defined _default item and if not, pre-fill the keyword
		//-- with that name
		//--
		echo <<<END
	<br />
	<br />
	<form name="template" action="$action_url" name="adsenseform" method="post">
	<fieldset class="options">
	<legend id="new_ad"><span style="font-weight:bold;color:#00C;">New AdSense Block</span></legend> 
	<a name="template">&nbsp;</a>
	<input type="hidden" name="fn" value="new" />
	<input type="hidden" name="edit_kw" value="$name" />
	<input type="hidden" name="enabled" value="$enabled" />
	<table border="0" cellpadding="3" width="100%">
		<tr>
		<th>Name</th>
		<th>AdSense Code</th>
		<th>Description (optional)</th>
		</tr>
		<tr>
		<td valign="top" align="center"><input type="text" size="16" name="name" value="$name" />
		<br /><input type="checkbox" name="make_default" id="make_default" value="1" 
END;
	if ($vals['make_default'] == '1')
		echo 'checked="checked" ';

	echo <<<END
/><label for="make_default">&nbsp;&nbsp;Make Default</label></td><td valign="top" align="center"><textarea name="code" rows="6" cols="35">$code</textarea></td>
		<td valign="top" align="center"><textarea name="comment" rows="6" cols="18">$comment</textarea></td>
		</tr>

		<tr>
		<td colspan="3" align="right">
				<p class="submit"><input type="reset" name="reset" value="Discard Changes" />&nbsp;&nbsp;<input type="submit" name="submit" value="$submit_text" />
				</p>
			</td>
		</tr>
		</table>
		</fieldset>
	</form>
END;
	
	}//_AdsDel_NewAdForm()
	
	/*
	**
	** Display existing ads.
	**/
	function _AdsDel_ListAds($options=NULL)
	{
		function makeUrl($u, $anchor_text, $tt, $fragment='adsense_list')
		{
			return "<a href=\"$u#$fragment\" title=\"$tt\">$anchor_text</a>";
		}
		
		$action_url = $_SERVER[PHP_SELF] . '?page=adsense-deluxe-revived/' . basename(__FILE__);
		$get_url = $_SERVER[PHP_SELF] . '?page=adsense-deluxe-revived/' . basename(__FILE__);
		$def_url = $get_url . "&amp;fn=default";
		$edit_url = $get_url . "&amp;fn=edit";
		$delete_url = $get_url . "&amp;fn=del";
		$enable_url = $get_url . "&amp;fn=enable";
		
		
		echo <<<END
	<form action="$action_url" name="adsform" method="post">
	<fieldset class="options">
	<legend id="adsense_list"><span style="font-weight:bold;color:#00C;">AdSense Blocks</span></legend> 
	<input type="hidden" name="fn" value="update" />
	<div align="center">
	<table border="0" width="95%" cellpadding="3" cellspacing="3" >
END;
		if( !isset($options) ) :
			echo '<tr><td>Internal Error: missing $options</td></tr>';
		else :
			$altclass = 'alternate';
			echo "<tr><th>Name</th><th>Description</th><th>Actions</th><th>On</th></tr>";
			foreach( $options['ads'] as $key => $vals ){
				// setup locals for on/off checkboxes
				$onOffChecked = '';
				if( $vals['enabled'] ){
					$onOffChecked = 'checked="checked"';
				}
			
				if( $options['default'] == $key )
					echo "<tr style=\"background-color:#CCFF99;\">";
				else
					echo "<tr class=\"$altclass\">";

				echo "<td align=\"center\">&lt;!--adsense";
				if( $options['default'] != $key )
					echo '#' . $key;
				echo "--&gt;</td>";
				echo '<td style="font-size:.9em;">' . '<a title="Click to Preview This Ad Style in a new window" onClick=\''. AdsDel_makePreviewUrl($vals['adsense'], get_settings('home'), $key).'\'>'.$vals['desc'] . '</a></td>';
				echo '<td style="font-size:.9em;" align="center">';
				echo makeUrl($delete_url . '&amp;kw=' . $key, 'delete', 'Delete AdSense') .' | ';
				echo makeUrl($def_url . '&amp;kw=' . $key, 'default', 'Make this the default')."\n | ";
				echo makeUrl($edit_url. '&amp;kw=' . $key, 'edit', 'Edit this configuration', 'template');
				echo '</td>' ."\n";
				// on/off checkbox
				echo '<td align="center"><input type="checkbox" name="'.$key.'" value="1" ' .  $onOffChecked . '/></td></tr>' ."\n";
				$altclass = ($altclass == '' ? 'alternate' : '');
			}
		endif;

		$all_on_checked = '';
		$posts_on_checked = '';
		$home_on_checked = '';
		$archives_on_checked = '';
		$page_on_checked = '';
		if( $options['all_enabled'] )		$all_on_checked = 'checked="checked"';
		if( $options['enabled_for']['home'] )		$home_on_checked = 'checked="checked"';
		if( $options['enabled_for']['archives'] )	$archives_on_checked = 'checked="checked"';
		if( $options['enabled_for']['page'] )		$page_on_checked = 'checked="checked"';
		if( $options['enabled_for']['posts'] )		$posts_on_checked = 'checked="checked"';
		
		echo <<<END
		<tr><td>&nbsp;</td><td colspan="3" align="center"><i style="color:gray;">The options below this line control where Ads will be shown.</i></td></tr>
		<tr>
			<td colspan="3" align="right">Enable Ads on Individual Posts</td>
			<td align="center"><input type="checkbox" name="posts_on" value="1" $posts_on_checked /></td>
		</tr>
		<tr>
			<td colspan="3" align="right">Enable Ads on Home page</td>
			<td align="center"><input type="checkbox" name="home_on" value="1" $home_on_checked /></td>
		</tr>
		<tr>
			<td colspan="3" align="right">Enable Ads on &quot;pages&quot;</td>
			<td align="center"><input type="checkbox" name="page_on" value="1" $page_on_checked /></td>
		</tr>
		<tr>
			<td colspan="3" align="right">Enable Ads on any Archive page</td>
			<td align="center"><input type="checkbox" name="archives_on" value="1" $archives_on_checked /></td>
		</tr>
		<tr>
			<td colspan="3" align="right"><b>Globally enable/disable all ads</b></td>
			<td align="center"><input type="checkbox" name="all_on" value="1" $all_on_checked/></td>
		</tr>
		<tr><td colspan="4" align="right"><input type="submit" name="submit" value="Update Enabled Options &raquo;" /></td></tr>
		</table>
		</div>
		</fieldset>
		</form>
END;
	}// _AdsDel_ListAds

	function _AdsDel_find_posts_with_ads()
	{
/*
		// this locates all tokens in data
		// output looks like:
		// Array
		// (
		//     [0] => Array
		//         (
		//             [0] => <!--adsense-->
		//             [1] => <!--adsense#test-->
		//         )
		// )
		$matches;
		preg_match_all( '/<!--adsense(?:#[^-]+)?-->/ismeU', $data, $matches , PREG_PATTERN_ORDER  );
		if( $matches ){
		}	
*/
	}
	
	/*
	**
	** This is the main Options handling function.
	**/
	function AdsenseDeluxeOptionsPanel()
	{
		global $_POST, $_GET;
		
		// check keyword name for only allowed characters
		function valid_kw_chars($text)
		{
			if( preg_match("/[^a-zA-Z0-9_]/",$text) ){
				return false;
			}
			return true;
		}
		
		// delete specified keyword $kw from options and save the options if $saveOptions = true
		function _AdsDel_DeleteAdsenseBlock( &$options, $kw, $save_options=TRUE )
		{
			$newVals = array();
			$lastKey = NULL;
			foreach( $options['ads'] as $key => $vals ){
				if( $key == $kw ){
					echo "\n\n<!-- Matched Keyword $kw -->\n\n";
					if( $options['default'] == $key )
						$options['default'] = NULL;
				}else{
					$newVals[$key] = $vals;
					$lastKey = $key;
				}
			}
			
			// deleted item may have been default AdSense code, so adjust to something else
			if( $options['default'] == NULL ){
					$options['default'] = $lastKey; //lastKey may be NULL, it's OK.
			}
			
			$options['ads'] = $newVals;
			if( $save_options )
				update_option(ADSDEL_OPTIONS_ID, $options);
		}


		// place to pass msgs back to user about state of form submission
		$submit_msgs = array();

		$action_url = $_SERVER[PHP_SELF] . '?page=adsense-deluxe-revived/' . basename(__FILE__) . "&amp;#new_ad";

		// Create option in options database if not there already:
		$options = get_option(ADSDEL_OPTIONS_ID);
		if( !$options){
			$options = _AdsDel_CreateDefaultOptions();
			$submit_msgs[] = "&raquo; Created default options.";
		}


		//--
		//-- Handle post (new adsense block definitions)
		//--
		if ( isset($_POST['fn']) ) {
			
			if (get_magic_quotes_gpc()) {
				$_GET	= array_map('stripslashes', $_GET);
				$_POST	= array_map('stripslashes', $_POST);
				$_COOKIE= array_map('stripslashes', $_COOKIE);
			}
			if( $_POST['fn'] == 'new' ){
				//_AdsDel_HandlePostNew(&$options,&$submit_msgs,&$newform_values);
				if( isset($_POST['name']) && $_POST['name'] != '' 
					&& isset($_POST['code']) && $_POST['code'] != '' ){
					$kw = $_POST['name'];
					$theCode = $_POST['code'];
					$desc = $_POST['comment'];
					$enabled = true;
					$isDefault = false;
					if( valid_kw_chars($kw) ){
					
						// if editing previous option, delete old first.
						// [ might be reasons not to do that at this point(?) ]
						if( isset($_POST['edit_kw']) && $_POST['edit_kw'] != $kw ){
							$submit_msgs[] = '&raquo; Deleting old keyword ' . $_POST['edit_kw'] . '.';
							_AdsDel_DeleteAdsenseBlock($options, $_POST['edit_kw'], FALSE);
						}

						if( (isset($_POST['make_default']) && $_POST['make_default'] == '1')
							|| ! isset($options['default']) || $options['default'] == '' ){
							$options['default'] = $kw;
						}
						if( isset($_POST['enabled']) && $_POST['enabled'] == '' )
							$enabled = false;
						
						$options['ads'][$kw] = array('adsense' => $theCode, 'desc' => $desc, 'enabled' => $enabled);
						update_option(ADSDEL_OPTIONS_ID, $options);
						$submit_msgs[] = '&raquo; New AdSense block added (' . $kw . ').';
					}else{
						$submit_msgs[] = '&raquo; Invalid characters in Keyword; submission NOT saved';
						$newform_values = array();
						$newform_values['name'] = '';
						$newform_values['code'] = $theCode;
						$newform_values['comment'] = $desc;
						$newform_values['make_default'] = ($isDefault ? '1' : '');
					}//if( valid_kw_chars($kw) )
				}else{
					$submit_msgs[] = '&raquo; <font color="red">Missing Keyword or Code value</font>; Nothing added.';
				}

			//--
			//-- plugin author mileage rewards program....
			//--
			}elseif( $_POST['fn'] == 'rewards' ){
				$options['reward_author'] = (isset($_POST['reward_author']) && $_POST['reward_author'] == '1');
				$submit_msgs[] = '&raquo; Author Rewards turned  <b>' . ($options['reward_author'] ? 'ON' : 'OFF') . '</b>';

			//--
			//-- Handle change in on/off status
			//--
			}elseif( $_POST['fn'] == 'update' ){
				// handle all on/off first
				$options['all_enabled'] = (isset($_POST['all_on']) && $_POST['all_on'] == '1');
				$submit_msgs[] = '&raquo; AdSense ads globally <b><i>'
					.($options['all_enabled']?'enabled':'disabled')
					.'</i></b>. Individual ads may still be disabled though.';
				
				// update "areas" for turning ads on/off (Pages, Home, Archives)
				$areas = array('posts_on'=>'posts','page_on' => 'page', 'home_on' => 'home', 'archives_on'=>'archives');
				foreach($areas as $form_fld => $option_name )
					$options['enabled_for'][$option_name] = 
						(isset($_POST[$form_fld]) && $_POST[$form_fld] == '1');
/*					if((isset($_POST[$form_fld]) && $_POST[$form_fld] == '1') ){
						$options['enabled_for'][$option_name] = true;
					}else{
						$options['enabled_for'][$option_name] = false;
					}
*/
				// do indivdidual entries now
				foreach($options[ads] as $key => $val ){
					if( isset($_POST[$key]) ){
						$options['ads'][$key]['enabled'] = true;
						//$submit_msgs[] = "Setting <b>$key</b> to ". $_POST[$key];
					}else{
						$options['ads'][$key]['enabled'] = false;
					}
				}
				$submit_msgs[] = "&raquo; <b><i>Enabled</i></b> status for all ad blocks updated!";

			}else{
				$submit_msgs[] = '&raquo; <font color="red">Unrecognized POST action</font>.';
			}
			
			// make sure we save the (possibly) changed options
			update_option(ADSDEL_OPTIONS_ID, $options);

		//--
		//-- GET submissions (delete, make default, edit, on/off)
		//--
			
			
			}elseif ( isset($_GET['fn']) ) {
				$fn = $_GET['fn'];
				$kw = $_GET['kw'];

				if( $fn == 'debug' ){
					$submit_msgs[] = 'Number of ads: ' . sizeof($options['ads']) . "\n";
					$submit_msgs[] = 'Prefs Version: ' . $options['version'] . "\n";
					$submit_msgs[] = 'Latest Version: ' . $options['latest_version'] . "\n";
					$submit_msgs[] = 'Next Version Check: ' . date('Y-m-d', $options['next_update_check']) . "\n";
					$submit_msgs[] = 'Reward Author?: ' . (isset($options['reward_author']) && $options['reward_author'] == '1' ? 'YES' : 'NO') . "\n";
					$submit_msgs[] = 'All Enabled?: ' . $options['all_enabled'] . "\n";
					$submit_msgs[] = 'Ad Block set as default: ' . $options['default'] . "\n";
					foreach( $options['ads'] as $key => $vals ){
						$submit_msgs[] = 'BLOCK: ' . $key . ' -- Enabled: ' .$vals['enabled']. "\n";
						$submit_msgs[] = 'Comment: ' . $vals['desc'] . "\n";
						if( $key == $kw ){
							$submit_msgs[] = "DEFAULT = => $key\n";
						}
					}

				}elseif( $fn == 'default' ){
					
					// while we could just set $options[default] to the $kw, let's be safe
					// and make sure it exists.
					foreach( $options['ads'] as $key => $vals ){
						if( $key == $kw ){
							$options['default'] = $key;
							$submit_msgs[] = "&raquo; Default changed to $key.";
						}
					}
	
				}elseif($fn == 'edit' ){
					$newform_values = NULL;
					foreach( $options['ads'] as $key => $vals ){
						if( $key == $kw ){
							$newform_values = array();
							$newform_values['name'] = $newform_values['edit_kw'] = $key;
							$newform_values['code'] = $vals['adsense'];
							$newform_values['comment'] = $vals['desc'];
							$newform_values['make_default'] = ($options['default'] == $key ? '1' :'');						
							$newform_values['enabled'] = ($vals['enabled'] ? '1' :'');						
							break;
						}
					}
	
				}elseif($fn == 'enable' ){
					if( isset($_GET['flipit'] ) ){
						$flipit = $_GET['flipit'];
						foreach( $options['ads'] as $key => $vals ){
							if( $key == $kw ){
								if( $flipit == 'on' )
									$enable = true;
								else
									$enable = false;
								$options['ads'][$kw]['enabled'] = $enable;
								$submit_msgs[] = "&raquo; Ad block <i>$key</i> turned " .($enable ? 'on' : 'off');
							}
						}
					}else{
						$submit_msgs[] = "&raquo; <font color=red>Internal Error</font> missing switch\n";
					}
				}elseif($fn == 'del' ){
					$newVals = array();
					$lastKey = NULL;
					foreach( $options['ads'] as $key => $vals ){
						if( $key == $kw ){
							if( $options['default'] == $key )
								$options['default'] = NULL;
							$submit_msgs[] = "&raquo; Removed AdSense block for $kw.";
						}else{
							$newVals[$key] = $vals;
							$lastKey = $key;
						}
					}
					
					// deleted item may have been default AdSense code, so adjust to something else
					if( $options['default'] == NULL ){
							$options['default'] = $lastKey; //lastKey may be NULL, it's OK.
					}
					
					$options['ads'] = $newVals;
					
				}else{
					$submit_msgs[] = "&raquo; Unknown function:  $fn .";
				}

			// make sure we save the (possibly) changed options
			update_option(ADSDEL_OPTIONS_ID, $options);
		}

		// spit out status msgs first
		if ( count($submit_msgs) > 0 ) {
			echo '<div class="updated"><p>' 
					. implode('<br />', $submit_msgs )
					. '</p></div>';	
		}
		
		
		
		
	global $wpdb;
	global $wp_version;
	global $lynkff_txt_dir, $lynkff_txt_gd, $lynkff_submit_ok;
	$adr_activate = get_option('adr_activate');
	$reg_msg = '';
	$adr_msg = '';
	$form_1 = 'adr_reg_form_1';
	$form_2 = 'adr_reg_form_2';
		// Activate the plugin if email already on list
	if ( trim($_GET['mbp_onlist']) == 1 ) {
		$adr_activate = 2;
		update_option('adr_activate', $adr_activate);
		$reg_msg = 'Thank you for registering the plugin. It has been activated'; 
	} 
	// If registration form is successfully submitted
	if ( ((trim($_GET['submit']) != '' && trim($_GET['from']) != '') || trim($_GET['submit_again']) != '') && $adr_activate != 2 ) { 
		update_option('adr_name', $_GET['name']);
		update_option('adr_email', $_GET['from']);
		$adr_activate = 1;
		update_option('adr_activate', $adr_activate);
	}
	if ( intval($adr_activate) == 0 ) { // First step of plugin registration
		global $userdata;
		adrRegisterStep1($form_1,$userdata);
	} else if ( intval($adr_activate) == 1 ) { // Second step of plugin registration
		$name  = get_option('adr_name');
		$email = get_option('adr_email');
		adrRegisterStep2($form_2,$name,$email);
	} else if ( intval($adr_activate) == 2 ) { // Options page
		if ( trim($reg_msg) != '' ) {
			echo '<div id="message" class="updated fade"><p><strong>'.$reg_msg.'</strong></p></div>';
		}
		
		
		echo "<div class='wrap'>";
		
		_AdsDel_Header();
		
		_AdsDel_ListAds($options);
		//print_r($options);
		_AdsDel_NewAdForm($newform_values);

		_AdsDel_AdSense_sandbox();
		
		remove_pwdby();
		
		_AdsDel_RewardAuthor($options);
		
		_AdsDel_Footer();

		echo "\n</div>";
	}
}	
	
	
/**
 * Plugin registration form
 */
function adrRegistrationForm($form_name, $submit_btn_txt='Register', $name, $email, $hide=0, $submit_again='') {
	$wp_url = get_bloginfo('wpurl');
	$wp_url = (strpos($wp_url,'http://') === false) ? get_bloginfo('siteurl') : $wp_url;
	$plugin_pg    = 'options-general.php';
	$thankyou_url = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'];
	$onlist_url   = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'].'&amp;mbp_onlist=1';
	if ( $hide == 1 ) $align_tbl = 'left';
	else $align_tbl = 'center';
	?>
	
	<?php if ( $submit_again != 1 ) { ?>
	<script><!--
	function trim(str){
		var n = str;
		while ( n.length>0 && n.charAt(0)==' ' ) 
			n = n.substring(1,n.length);
		while( n.length>0 && n.charAt(n.length-1)==' ' )	
			n = n.substring(0,n.length-1);
		return n;
	}
	function adrValidateForm_0() {
		var name = document.<?php echo $form_name;?>.name;
		var email = document.<?php echo $form_name;?>.from;
		var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		var err = ''
		if ( trim(name.value) == '' )
			err += '- Name Required\n';
		if ( reg.test(email.value) == false )
			err += '- Valid Email Required\n';
		if ( err != '' ) {
			alert(err);
			return false;
		}
		return true;
	}
	//-->
	</script>
	<?php } ?>
	<table align="<?php echo $align_tbl;?>">
	<form name="<?php echo $form_name;?>" method="post" action="http://www.aweber.com/scripts/addlead.pl" <?php if($submit_again!=1){;?>onsubmit="return adrValidateForm_0()"<?php }?>>
	 <input type="hidden" name="unit" value="maxbp-activate">
	 <input type="hidden" name="redirect" value="<?php echo $thankyou_url;?>">
	 <input type="hidden" name="meta_redirect_onlist" value="<?php echo $onlist_url;?>">
	 <input type="hidden" name="meta_adtracking" value="mr-adsense-deluxe-revived">
	 <input type="hidden" name="meta_message" value="1">
	 <input type="hidden" name="meta_required" value="from,name">
	 <input type="hidden" name="meta_forward_vars" value="1">	
	 <?php if ( $submit_again == 1 ) { ?> 	
	 <input type="hidden" name="submit_again" value="1">
	 <?php } ?>		 
	 <?php if ( $hide == 1 ) { ?> 
	 <input type="hidden" name="name" value="<?php echo $name;?>">
	 <input type="hidden" name="from" value="<?php echo $email;?>">
	 <?php } else { ?>
	 <tr><td>Name: </td><td><input type="text" name="name" value="<?php echo $name;?>" size="25" maxlength="150" /></td></tr>
	 <tr><td>Email: </td><td><input type="text" name="from" value="<?php echo $email;?>" size="25" maxlength="150" /></td></tr>
	 <?php } ?>
	 <tr><td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $submit_btn_txt;?>" class="button" /></td></tr>
	 </form>
	</table>
	<?php
}

/**
 * Register Plugin - Step 2
 */
function adrRegisterStep2($form_name='frm2',$name,$email) {
	$msg = 'You have not clicked on the confirmation link yet. A confirmation email has been sent to you again. Please check your email and click on the confirmation link to activate the plugin.';
	if ( trim($_GET['submit_again']) != '' && $msg != '' ) {
		echo '<div id="message" class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
	}
	?>
	<style type="text/css">
	table, tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_AD_NAME.' '.MBP_AD_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff; text-align:left;">
	  <tr><td align="center"><h3>Almost Done....</h3></td></tr>
	  <tr><td><h3>Step 1:</h3></td></tr>
	  <tr><td>A confirmation email has been sent to your email "<?php echo $email;?>". You must click on the link inside the email to activate the plugin.</td></tr>
	  <tr><td><strong>The confirmation email will look like:</strong><br /><img src="http://www.maxblogpress.com/images/activate-plugin-email.jpg" vspace="4" border="0" /></td></tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr><td><h3>Step 2:</h3></td></tr>
	  <tr><td>Click on the button below to Verify and Activate the plugin.</td></tr>
	  <tr><td><?php adrRegistrationForm($form_name.'_0','Verify and Activate',$name,$email,$hide=1,$submit_again=1);?></td></tr>
	 </table>
	 </td></tr></table><br />
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding:8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding:8px; background-color:#ffffff; text-align:left;">
	   <tr><td><h3>Troubleshooting</h3></td></tr>
	   <tr><td><strong>The confirmation email is not there in my inbox!</strong></td></tr>
	   <tr><td>Dont panic! CHECK THE JUNK, spam or bulk folder of your email.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>It's not there in the junk folder either.</strong></td></tr>
	   <tr><td>Sometimes the confirmation email takes time to arrive. Please be patient. WAIT FOR 6 HOURS AT MOST. The confirmation email should be there by then.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>6 hours and yet no sign of a confirmation email!</strong></td></tr>
	   <tr><td>Please register again from below:</td></tr>
	   <tr><td><?php adrRegistrationForm($form_name,'Register Again',$name,$email,$hide=0,$submit_again=2);?></td></tr>
	   <tr><td><strong>Help! Still no confirmation email and I have already registered twice</strong></td></tr>
	   <tr><td>Okay, please register again from the form above using a DIFFERENT EMAIL ADDRESS this time.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr>
		 <td><strong>Why am I receiving an error similar to the one shown below?</strong><br />
			 <img src="http://www.maxblogpress.com/images/no-verification-error.jpg" border="0" vspace="8" /><br />
		   You get that kind of error when you click on &quot;Verify and Activate&quot; button or try to register again.<br />
		   <br />
		   This error means that you have already subscribed but have not yet clicked on the link inside confirmation email. In order to  avoid any spam complain we don't send repeated confirmation emails. If you have not recieved the confirmation email then you need to wait for 12 hours at least before requesting another confirmation email. </td>
	   </tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>But I've still got problems.</strong></td></tr>
	   <tr><td>Stay calm. <strong><a href="http://www.maxblogpress.com/contact-us/" target="_blank">Contact us</a></strong> about it and we will get to you ASAP.</td></tr>
	 </table>
	 </td></tr></table>
	 </center>		
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_AD_NAME.' '.MBP_AD_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}

/**
 * Register Plugin - Step 1
 */
function adrRegisterStep1($form_name='frm1',$userdata) {
	$name  = trim($userdata->first_name.' '.$userdata->last_name);
	$email = trim($userdata->user_email);
	?>
	<style type="text/css">
	tabled , tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_AD_NAME.' '.MBP_AD_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:2px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	  <tr><td align="center">
		<table width="548" align="center" cellpadding="3" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff;">
		  <tr><td align="center"><h3>Please register the plugin to activate it. (Registration is free)</h3></td></tr>
		  <tr><td align="left">In addition you'll receive complimentary subscription to MaxBlogPress Newsletter which will give you many tips and tricks to attract lots of visitors to your blog.</td></tr>
		  <tr><td align="center"><strong>Fill the form below to register the plugin:</strong></td></tr>
		  <tr><td align="center"><?php adrRegistrationForm($form_name,'Register',$name,$email);?></td></tr>
		  <tr><td align="center"><font size="1">[ Your contact information will be handled with the strictest confidence <br />and will never be sold or shared with third parties ]</font></td></tr>
		</table>
	  </td></tr></table>
	 </center>
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_AD_NAME.' '.MBP_AD_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}

	//--
	//-- Create mini javascript which will preview the current ad style
	//--
	function AdsDel_makePreviewUrl($adsense_code, $the_url, $winName="preview"){
		$p;
		if( AdsDel_GetASParams($adsense_code, $p) ){
			$as_url = 'http://pagead2.googlesyndication.com/pagead/ads?client=ca-test&adtest=on&url='
				. urlencode($the_url) 
				. '&format='. $p['ad_format']
				. '&color_border=' . $p['color_border'] 
				. '&color_bg=' . $p['color_bg'] 
				. '&color_text=' . $p['color_text']
				. '&color_link=' . $p['color_link']
				. '&color_url=' . $p['color_url']
				. '&alternate_color=' . $p['alternate_color']
				. '&type=' . $p['ad_type'];
			}
		$p['ad_width'] += 10;
		$p['ad_height']+= 10;
		return 'window.open("' . $as_url .'","'.$winName.'","width=' . $p['ad_width'] .',height=' . $p['ad_height'] .'top=120,left=100,resizable=yes"); return false;';
	
	//	return $as_url;
	}
	
	//--
	//-- Extract ad parameters from the raw AS javascript (in $asBlock)
	//-- returns items in params array (see $items below for list of key names)
	//-- Returns boolean false if something goes wrong, true otherwise.
	//--
	function AdsDel_GetASParams($asBloc, &$params)
	{
		$items = array(
			'ad_format'=>'', 'ad_type'=>'', 'ad_width'=>250,'ad_height'=>250,
			'color_border'=>'', 'color_bg'=>'', 'color_link'=>'', 'color_url'=>'', 'color_text'=>'', 'alternate_color'=>'FFFFFF'
		);
		$params = array();
		foreach( $items as $key => $val ){
			if( preg_match ( '/' . $key . ' *= *\"?([^";]+)\"?/', $asBloc, $m ) ){
				//echo "$key = $m[1] \n";
				//$items[$key] = $m[1];
				$params[$key] = $m[1];
			}else{
				$params[$key] = $items[$key]; // set to default
			}
		}
		
		//echo $as_url . "\n\n";
		return true; // always true for now...
	}

	// creates the AdSense options page button under Options menu in WP-admin
	function add_adsense_deluxe_menu()
	{
	
	 if (function_exists('add_options_page')) 
	 {
	  add_options_page('AdSense Deluxe Revived', 'AdSense Deluxe Revived', 8, __FILE__); //'AdsenseDeluxeOptionsPanel'); // wp 1.5.1 version
	  
	 }
	 
	}

	//--
	//-- creates QuickTags button for Adsense-Del. in editor
	//--
	function _AdsDel_InsertAdsenseButton()
	{
		$rich_editing = false;
		$tiger_style = 'float:left;padding:2px;margin-right:2px;margin-top:4px;';
		$button_style = '';
		if(	strpos($_SERVER['REQUEST_URI'], 'post.php')
			|| strstr($_SERVER['PHP_SELF'], 'page-new.php'))
		{
			if( function_exists('get_user_option') ) 
				$rich_editing = (get_user_option('rich_editing') == 'true');

			$check_plugins = get_settings('active_plugins');
			foreach ($check_plugins as $pi) {
				if( false !== strpos($pi,'wp-admin-tiger') )
					$button_style = $tiger_style;
			}
			
			if( function_exists('get_option') )
			{
				$opt = get_option(ADSDEL_OPTIONS_ID);
		
				$js = '';
				$js2 = '';
				foreach( $opt['ads'] as $key => $vals )
				{
					if( $key == $opt['default'] ) continue;
					$n = 'adsense#' . $key ;
					$js .= '<option value=\"-' . $n . '-\">&nbsp;&nbsp;&nbsp;' . $n . '</option>';
					$js2 .= ($js2 == '' ? "" : ',') . ' "' . $key . '"'; // no "adsense#" prepended
				}
			}
//color:#006633;
	?>
<script language="JavaScript" type="text/javascript"><!--
//var toolbar = document.getElementById("ed_toolbar");
if( <?php echo (($rich_editing) ?  "false" : "true");?> ){
if (document.getElementById('quicktags') != undefined){

	document.getElementById('quicktags').innerHTML += '<select style=\" background-color:#eee;color:#006633;width:120px;<?php echo $button_style;?>\" class=\"ed_button\" id=\"adsense_delx\" size=\"1\" onChange=\"return InsAdsDelux(this);\"><option style=\"font-weight:bold;\" selected disabled  value=\"\">Ad$ense-Delx</option><option value=\"-adsense-\">adsense</option><?php echo $js;?></select>'
};

}
function InsAdsDelux(ele) {
	try{
	if( ele != undefined && ele.value != '')
		edInsertContent(edCanvas, '<!-'+ ele.value +'->');
	}catch (excpt) { alert(excpt); }
	ele.selectedIndex = 0; // reset menu
	return false;
}
var __ADSENSE_DELUXE_ADS = new Array(<?php echo $js2;?>); //WP2.0 Rich Editor
//--></script>
	<?php
		}
	}


//=========================================================================
//--------------------------------custom button----------------------------

include('editor.btn.php');


//------------------------------------------------------------
//============================================================

	
	
	//add_filter('admin_footer', '_AdsDel_InsertAdsenseButton');


	if( function_exists('add_action') )
	{
	
		
		
		global $wp_version;
		
		if((float)$wp_version >= 2.8 )
		{
			add_action('admin_menu','add_adsense_deluxe_menu');
		}
		else
		{
		add_action('admin_head', 'add_adsense_deluxe_menu');
		}
		
		add_action('wp_head', 'add_adsense_deluxe_handle_head');
}
	if( function_exists('add_filter') )
		add_filter('the_content', 'adsense_deluxe_insert_ads'); 


endif; // if plugin_page()


/* ============= NOTES ================= *
v0.7	2006-01-09
	- (see readme with plugin download for all release info)
	- First release for WordPress 2.0 WYSIWYG editor (rich editing) support. May be bugs.

v0.4	2005-08-
	- Fixed ASD QuickTag when Tiger-Admin plugin is activated.
	- You can now click the descriptions in the ads list to preview the ad style.
	
v0.3	2005-08-01
	- Fixed problem of AdSense showing up in Full Text RSS feeds.
	- Fixed call-time pass-by-reference warnings from PHP.
	- No longer "rewarding author" on anything other than Post or Page pages.
	- Fixed problem with only two (2) ads being shown on a given page.
	- Added AdSense-Deluxe quicktag menu to post editor.
	- Stopped showing live adsense in post editing previews; now displays a placeholder
	- Added stripslashes() around calls to edit an ad and to display adsense code in posts.
		[axodys] reported his ads getting escaped on WP 1.5.3 (with magic_quotes_gpc Off).
	- Editing an ad which was disabled causes it to be enabled when saving (fixed).

	+ ToDo: run some timing to see check overhead plugin ads to page serving.
* =============== END NOTES ============ */
?>
