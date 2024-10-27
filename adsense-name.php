<?php
include('../../../wp-config.php');

$options = get_option(ADSDEL_OPTIONS_ID);
		
				
		$fns = '';
		$forms =count($options['ads']);
		foreach ($options['ads'] as $key=>$val) 
		{
			$fns .= '"'.$key.'",';
			
			$fnsop .='<option value="<!--adsense#'.$key.'-->">'.$key.'</option>';
			
		}
		$fns= substr($fns,0,-1);
		


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Add adsense</title>
<script type="text/javascript" type="text/javascript" src="tiny_mce_popup.js?v=3211"></script>
<script type="text/javascript" type="text/javascript">
var AdsenseDialog = {
	init : function(ed) {
		tinyMCEPopup.resizeToInnerSize();
	},

	insert : function insertEmotion(code) {
    	tinyMCEPopup.execCommand('mceInsertContent', false, code);
		tinyMCEPopup.close();
	}
};
tinyMCEPopup.onInit.add(AdsenseDialog.init, AdsenseDialog);


function insertAdsense()
{
var code=document.getElementById('adsel').value;
AdsenseDialog.insert(code);
}


function closepopup()
{
tinyMCEPopup.close();
}

</script>

<base target="_self" />
</head>
<body style="display: none">
	<div align="center">
		<table>
		 	
			<tr>
			<td><strong>Select Adsense:</strong> </td>
			<td><select id="adsel" style="width:100px;">
			<option value="<!--adsense-->">Default</option>
			<?php echo $fnsop; ?> 
			
			</select></td>
            </tr>
			<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
            </tr>
			
			<tr>
			  <td colspan="2">
			  <input type="button"  value="Cancel" onclick="javascript:closepopup();" id="cancel"/>	
			  &nbsp;	&nbsp;	&nbsp;	&nbsp;	&nbsp;	&nbsp;		    
			  <input type="button"  value="Insert" onclick="javascript:insertAdsense();" id="insert"/></td>
		  </tr>
		</table>
	</div>
</body>
</html>