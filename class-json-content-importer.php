<?php
/*
CLASS JsonContentImporter
Description: Class for WP-plugin "JSON Content Importer"
Version: 1.2.3
Author: Bernhard Kux
Author URI: http://www.kux.de/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


class JsonContentImporter {

    /* shortcode-params */		
    private $numberofdisplayeditems = -1; # -1: show all
    private $feedUrl = ""; # url of JSON-Feed
    private $urlgettimeout = 5; # 5 sec default timeout for http-url
    private $basenode = ""; # where in the JSON-Feed is the data? 
    private $oneofthesewordsmustbein = ""; # optional: one of these ","-separated words have to be in the created html-code
    private $oneofthesewordsmustbeindepth = 1; # optional: one of these ","-separated words have to be in the created html-code
    private $oneofthesewordsmustnotbeIn = ""; # optional: one of these ","-separated words must NOT in the created html-code
    private $oneofthesewordsmustnotbeindepth = 1; # optional: one of these ","-separated words must NOT to in the created html-code

    /* plugin settings */
    private $isCacheEnable = FALSE;
 
    /* internal */
		private $cacheFile = "";
		private $jsondata;
		private $feedData  = "";
 		private $cacheFolder;
    private $datastructure = "";
    private $triggerUnique = NULL;
    private $cacheExpireTime = 0;


		public function __construct(){  
			 add_shortcode('jsoncontentimporter' , array(&$this , 'shortcodeExecute')); # hook shortcode
		}
    
    
    /* shortcodeExecute: read shortcode-params and check cache */
		public function shortcodeExecute($atts , $content = ""){
			
      extract(shortcode_atts(array(
        'url' => '',
        'urlgettimeout' => '',
        'numberofdisplayeditems' => '',
        'oneofthesewordsmustbein' => '',
        'oneofthesewordsmustbeindepth' => '',
        'oneofthesewordsmustnotbein' => '',
        'oneofthesewordsmustnotbeindepth' => '',
        'basenode' => '',
      ), $atts));
      
      $this->feedUrl = $url;
      $this->oneofthesewordsmustbein = $oneofthesewordsmustbein;
      $this->oneofthesewordsmustbeindepth = $oneofthesewordsmustbeindepth;
      $this->oneofthesewordsmustnotbein = $oneofthesewordsmustnotbein;
      $this->oneofthesewordsmustnotbeindepth = $oneofthesewordsmustnotbeindepth;
      /* caching or not? */
      if (
          (!class_exists('FileLoadWithCache'))
          || (!class_exists('JSONdecode'))
      ) {
        require_once plugin_dir_path( __FILE__ ) . '/class-fileload-cache.php';
      }
			if (get_option('jci_enable_cache')==1) {
        # 1 = checkbox "enable cache" activ
        $this->cacheEnable = TRUE;
        # check cacheFolder
        $this->cacheFolder = WP_CONTENT_DIR.'/cache/jsoncontentimporter/';
        $checkCacheFolderObj = new CheckCacheFolder($this->cacheFolder);

        # cachefolder ok: set cachefile
  			$this->cacheFile = $this->cacheFolder . urlencode($this->feedUrl);  # cache json-feed
      } else {
        # if not=1: no caching
        $this->cacheEnable = FALSE;
      }

      /* set other parameter */      
      if ($numberofdisplayeditems>=0) {
        $this->numberofdisplayeditems = $numberofdisplayeditems;
      }
      if (is_numeric($urlgettimeout) && ($urlgettimeout>=0)) {
        $this->urlgettimeout = $urlgettimeout;
      }

      /* cache */
      $this->cacheEnable = FALSE;
      if (get_option('jci_enable_cache')==1) {
        $this->cacheEnable = TRUE;
      }
      $cacheTime = get_option('jci_cache_time');  # max age of cachefile: if younger use cache, if not retrieve from web
			$format = get_option('jci_cache_time_format');
      $cacheExpireTime = strtotime(date('Y-m-d H:i:s'  , strtotime(" -".$cacheTime." " . $format )));
      $this->cacheExpireTime = $cacheExpireTime;


      $fileLoadWithCacheObj = new FileLoadWithCache($this->feedUrl, $this->urlgettimeout, $this->cacheEnable, $this->cacheFile, $this->cacheExpireTime);
      $fileLoadWithCacheObj->retrieveJsonData();
      $this->feedData = $fileLoadWithCacheObj->getFeeddata();
			# build json-array
      $jsonDecodeObj = new JSONdecode($this->feedData);
      $this->jsondata = $jsonDecodeObj->getJsondata();


      $this->basenode = $basenode;
      $this->datastructure = preg_replace("/\n/", "", $content);
      
      require_once plugin_dir_path( __FILE__ ) . '/class-json-parser.php';
      $JsonContentParser = new JsonContentParser123($this->jsondata, $this->datastructure, $this->basenode, $this->numberofdisplayeditems,
            $this->oneofthesewordsmustbein, $this->oneofthesewordsmustbeindepth,
            $this->oneofthesewordsmustnotbein, $this->oneofthesewordsmustnotbeindepth);
			return apply_filters("json_content_importer_result_root", $JsonContentParser->retrieveDataAndBuildAllHtmlItems());
		}
}
?>