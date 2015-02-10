<?php
add_action('admin_menu', 'jci_create_menu');

function jci_create_menu() {

	//create new top-level menu
	add_menu_page('JSON Content Importer', 'JSON Content Importer', 'administrator', __FILE__, 'jci_settings_page',plugins_url('/images/icon-16x16.png', __FILE__));

	//call register settings function
	add_action( 'admin_init', 'register_mylocsettings' );
}


function register_mylocsettings() {
	//register our settings
	register_setting( 'jci-options', 'jci_json_url' );
	register_setting( 'jci-options', 'jci_enable_cache' );
	register_setting( 'jci-options', 'jci_cache_time' );
	register_setting( 'jci-options', 'jci_cache_time_format' );
	
}

function jci_settings_page() {
?>
<div class="wrap">
<h2>JSON Content Importer: Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'jci-options' ); ?>
    <?php do_settings_sections( 'jci-options' ); ?>
    <table class="form-table">
        <tr>
        	<td colspan="2">
        <strong>Cache:</strong>
        <br>
            Enable Cache: <input type="checkbox" name="jci_enable_cache" value="1" <?php echo (get_option('jci_enable_cache') == 1)?"checked=checked":""; ?> />
        	 &nbsp;&nbsp;&nbsp; reload json from web if cachefile is older than <input type="text" name="jci_cache_time" size="2" value="<?php echo get_option('jci_cache_time'); ?>" />
           <select name="jci_cache_time_format">
           			<option value="minutes" <?php echo (get_option('jci_cache_time_format') == 'minutes')?"selected=selected":""; ?>>Minutes</option>
                    <option value="days" <?php echo (get_option('jci_cache_time_format') == 'days')?"selected=selected":""; ?>>Days</option>
                    <option value="month" <?php echo (get_option('jci_cache_time_format') == 'month')?"selected=selected":""; ?>>Months</option>
                    <option value="year" <?php echo (get_option('jci_cache_time_format') == 'year')?"selected=selected":""; ?>>Years</option>
           </select> 
           </td>
        </tr>
        
        
        <tr>
        	<td colspan="2">
            <strong>Available Syntax for Wordpress-Pages and -Blogentries:</strong>
          <br>
          [jsoncontentimporter
         <br>&nbsp;&nbsp;url="http://...json" 
         <br>&nbsp;&nbsp;numberofdisplayeditems="number: how many items of level 1 should be displayed? display all: leave empty" 
         <br>&nbsp;&nbsp;basenode="starting point of datasets, tha base-node in the JSON-Feed where the data is?" 
         <br>&nbsp;&nbsp;]
         <br>Any HTML-Code plus "basenode"-datafields wrapped in "{}"
         <br>&nbsp;&nbsp;&nbsp;&nbsp;{subloop:"basenode_subloop":"number of subloop-datasets to be displayed"}
         <br>&nbsp;&nbsp;&nbsp;&nbsp;Any HTML-Code plus "basenode_subloop"-datafields wrapped in "{}"
         <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{subloop-array:"basenode_subloop_array":"number of subloop-array-datasets to be displayed"}
         <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Any HTML-Code plus "basenode_subloop_array"-datafields wrapped in "{}"
         <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{/subloop-array:"basenode_subloop_array"}
         <br>&nbsp;&nbsp;&nbsp;&nbsp;{/subloop:"basenode_subloop"}
         <br>[/jsoncontentimporter]

         <hr>
         If the subloop is not an object but an array, use {subloop-array} e.g.:
         <br>
         "{subloop-array:type:5}{1:ifNotEmptyAddRight:aa&lt;br&gt;bb}{2:ifNotEmptyAddLeft:AA}{3:ifNotEmptyAddRight:BB}{/subloop-array}
         <br>shows the first, second and third entry of that array, modified by ifNotEmptyAddLeft and ifNotEmptyAddRight.


         <hr>
          <strong>There are some special add-ons for datafields:</strong>
          <ul>
          <li>"{street:ifNotEmptyAddRight:,}": If datafield "street" is not empty, add "," right of datafield-value. allowed chars are: "a-zA-Z0-9,;_-:&lt;&gt;/ "</li>
          <li>"{street:ifNotEmptyAdd:,}": some as "ifNotEmptyAddRight"</li>
          <li>"{street:ifNotEmptyAddLeft:,}": If datafield "street" is not empty, add "," left of datafield-value. allowed chars are: "a-zA-Z0-9,;_-:&lt;&gt;/ "</li>
          <li>"{locationname:urlencode}": Insert the php-urlencoded value of the datafield "locationname". Needed when building URLs.</li>
          <li>"{locationname:unique}": only display the first instance of a datafield. Needed when JSON delivers data more than once.</li>
          </ul>
         <hr>
         <strong>How do I find the proper template for my JSON?</strong>
         <br><a href="https://wordpress.org/support/plugin/json-content-importer" target="_blank">If you're lost: open ticket here</a>
         <p>
          <strong>Example 1:</strong><br>
          <i>
         
         <?php
            $example = "[jsoncontentimporter ";
            $example .= "url=\"http://www.kux.de/extra/json/digimuc/location.php\" numberofdisplayeditems=\"30\" basenode=\"location\"]\n";
            $example .= "<ul><li>{locationid} <b>{locationname:unique}</b>\n";
            $example .= "{street:ifNotEmptyAdd:,} {zipcode} {cityname}\n";
            $example .= "<a href=\"https://duckduckgo.com/?q={locationname:urlencode}\">search duckduckgo</a>\n";
            $example .= "list of events at this location:\n";
            $example .= "{subloop:event:5}<a href=\"{eventlink}\">{eventname:ifNotEmptyAdd::} {eventstart}</a><br>{/subloop}<hr></li></ul>\n[/jsoncontentimporter]\n";
            $example = htmlentities($example);
            echo $example;
          ?> 
          </i>
          <p>
          <strong>Example 2:</strong><br>
          <br>
          <i>
          <?php
           $ex2 = "{subloop-array:flex:4}flex:{headline}<br>{text}{/subloop-array:flex}<br>\n{subloop-array:news:4}news: <b>{title}</b><br>\n {text}<br>{subloop:news.image:2}newsimage: {lastmodified} id:{id}{/subloop:news.image}{/subloop-array:news}<br>\n{subloop-array:aspects:20}aspects:{text} aspects-image:{subloop:aspects.image:2}aspectimage: {lastmodified} id:{id}{/subloop:aspects.image}<br>\n{/subloop-array:aspects}--{title}<br>{subloop:image:5}imageid: {id}<br>{/subloop:image}<br>\n{subloop:geodata:5}geodata: {lat}{/subloop:geodata}<br>\n   {subloop:object3d:5}object3D: {id}{/subloop:object3d}<br>\n   {subloop:author:5}author: {name}{/subloop:author}<hr>";
           echo htmlentities($ex2);
           ?>
          </i>>
          </td>
        </tr>

       <tr valign="top">
        <td colspan="2">
          <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=APWXWK3DF2E22" target="_blank">Do you like that plugin? Is it helpful? I'm looking forward for a Donation - easy via PayPal!</a>
        </td>
      </tr>
        
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php } ?>