<?php

### adding TinyMCE button




$adsense_root=get_option('siteurl')."/wp-content/plugins/adsense/";


function adsense_addbuttons() {
		global $wp_db_version, $adsense_root;

		### WordPress 2.5+
		if ( $wp_db_version >= 6846 && 'true' == get_user_option('rich_editing') ) 
		{
				add_filter( 'mce_external_plugins', 'adsense_plugin');
				add_filter( 'mce_buttons', 'adsense_button');
			}
		### WordPress 2.1+
		elseif ( 3664 <= $wp_db_version && 'true' == get_user_option('rich_editing') ) {
				add_filter("mce_plugins", "adsense_plugin");
				add_filter('mce_buttons', 'adsense_button');
				add_action('tinymce_before_init','adsense_button_script');
		}
}



### used to insert button in wordpress 2.1x & 2.5x editor
function adsense_button($buttons) {
		array_push($buttons, "separator", "adsense");
		return $buttons;
}



### adding to TinyMCE
function adsense_plugin($plugins) {
	global $adsense_root,$wp_db_version;

	if ( $wp_db_version >= 6846 )
		 $plugins['adsense'] = $adsense_root.'editor_plugin.js';
	else
		array_push($plugins, "-adsense");

	return $plugins;
}



### Load the TinyMCE plugin : editor_plugin.js (wp2.1)
function adsense_button_script() {

		global $adsense_root,$wp_db_version;
		$pluginURL = $adsense_root ;

		$fns = getAllformNames();

		echo 'var placeholder="'.__('placeholder for:','adsense').'";';
		echo 'var formnames=new Array('.$fns.');';
		echo 'tinyMCE.loadPlugin("adsense", "'.$pluginURL.'");'."\n";
		echo 'var purl="'.$pluginURL.'";'."\n";
		return;
}



### retrieve all form names
function getAllformNames() {


		$options = get_option(ADSDEL_OPTIONS_ID);
		
				
		$fns = '';
		$forms =count($options['ads']);
		
		if($forms > 0)
		{
			foreach ($options['ads'] as $key=>$val) 
			{
				$fns .= '"'.$key.'",';
			}
		}
		
		
		return substr($fns,0,-1);
}



### Load the Script for the Button(wp2.1)
function insert_adsense_script() {
		global $adsense_root, $wp_db_version, $adsenseSettings;

		$options = '';
      
		$optionsads = get_option(ADSDEL_OPTIONS_ID);
		
		if(!empty($optionsads))
		{		
			foreach ($optionsads['ads'] as $key=>$val) 
			{
			$options .= '<option value=\"'.sanitize_title_with_dashes($key).'\">'.$key.'</option>';
			}
		}else
		{
		$options .= '<option value=\"<!--adsense-->\">Default</option>';
		}

		$fns = getAllformNames();
		?>
<style>
#adsenseins{
	font-size:11px;
<?php if ( $wp_db_version < 6846 ) echo "width:100%;"; ?>
	margin:2px 4px 5px 4px;
	text-align:center;
	padding:2px 0;
	border-top:2px solid #247FAB;
	border-bottom:2px solid #247FAB;
}

#adsenseins label{
	font-variant:small-caps;
	font-size:14px;
	padding-right:10px;
	line-height:25px;
}
#cfselect {
	font-size:12px;
	width:210px;
}
#cancel,
#insert{
	font-size:11px;
	margin-left:10px;
	width:120px!important;
}
</style>
<script type="text/javascript">
var globalPURL = "<?php echo $adsense_root ?>";

var placeholder = "<?php _e('placeholder for:','adsense') ?>";
var formnames = new Array(<?php echo $fns; ?>);
var purl = globalPURL+'';

function closeInsert(){
    var el = document.getElementById("quicktags");
    el.removeChild(document.getElementById("adsenseins"));
}
function insertSomething(){
    buttonsnap_settext('<!--adsense#"'+document.getElementById("cfselect").value+'"-->');
    closeInsert();
}
function adsense_buttonscript() {
        if ( document.getElementById("adsenseins") ) {
            return closeInsert();
        }

        function edInsertContent(myField, myValue) {
            //IE support
            if (document.selection) {
                myField.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
                myField.focus();
            }
            //MOZILLA/NETSCAPE support
            else if (myField.selectionStart || myField.selectionStart == '0') {
                var startPos = myField.selectionStart;
                var endPos = myField.selectionEnd;
                myField.value = myField.value.substring(0, startPos)
                              + myValue
                              + myField.value.substring(endPos, myField.value.length);
                myField.focus();
                myField.selectionStart = startPos + myValue.length;
                myField.selectionEnd = startPos + myValue.length;
            } else {
                myField.value += myValue;
                myField.focus();
            }
        }

    var rp = document.createElement("div");
    var el = document.getElementById("quicktags");

    rp.setAttribute("id","adsenseins");

    rp.innerHTML =  "<form onSubmit=\"insertSomething();\" action=\"#\"><label for=\"nodename\"><?php _e('Your Adsense:','adsense')?></label>"+
            "<select id=\"cfselect\" name=\"nodename\"/><?php echo $options ?></select>"+
            "<input type=\"button\" id=\"insert\" name=\"insert\" value=\"<?php _e('Insert','adsense') ?>\" onclick=\"javascript:insertSomething()\" />"+
            "<input type=\"button\" id=\"cancel\" name=\"cancel\" value=\"<?php _e('Cancel','adsense') ?>\" onclick=\"javascript:closeInsert()\" />"+
            "</form>";

    el.appendChild(rp);

}
</script>
<?php
		return;
}



### only insert buttons!

	add_action('init', 'adsense_addbuttons');

    ### TinyMCE error fix
	
		add_action('edit_page_form', 'insert_adsense_script');
		add_action('edit_form_advanced', 'insert_adsense_script');	
		add_action('admin_head', 'insert_adsense_script');


?>