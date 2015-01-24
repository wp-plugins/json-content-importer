<?php
/*
CLASS JsonContentImporter
Description: Class-Lib for WP-plugin "JSON Content Importer"
Version: 1.0.2
Author: Bernhard Kux
Author URI: http://www.kux.de/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


class JsonContentImporter {

    /* shortcode-params */		
    private $numberofdisplayeditems = -1; # -1: show all
		private $feedUrl;
    private $basenode = ""; 

    /* plugin settings */
    private $isCacheEnable = FALSE;
 
    /* internal */
		private $cacheFile = "";
		private $jsondata;
		private $feedData  = "";
 		private $cacheFolder;
    private $datastructure = "";
    private $triggerUnique = NULL;


		public function __construct(){  
			 add_shortcode('jsoncontentimporter' , array(&$this , 'shortcodeExecute')); # hook shortcode
		}
    
    
    /* shortcodeExecute: read shortcode-params and check cache */
		public function shortcodeExecute($atts , $content = ""){
			
      extract(shortcode_atts(array(
        'url' => '',
        'numberofdisplayeditems' => '',
        'basenode' => '',
      ), $atts));
      
      $this->feedUrl = $url;

      /* caching or not? */
			if (get_option('jci_enable_cache')==1) {
        # 1 = checkbox "enable cache" activ
        $this->cacheEnable = TRUE;
        # check cacheFolder
        $this->cacheFolder = WP_CONTENT_DIR.'/cache/jsoncontentimporter/'; 
        if (!is_dir($this->cacheFolder)) {
          # $this->cacheFolder is no dir: not existing
          # try to create $this->cacheFolder
          $mkdirError = @mkdir($this->cacheFolder); 
          if (!$mkdirError) {
            # mkdir failed, usually due to missing write-permissions
            echo "<hr><b>caching not working, plugin aborted:</b><br>";
            echo "plugin / wordpress / webserver can't create<br><i>".$this->cacheFolder."</i><br>";
            echo "therefore: set directory-permissions to 0777 (or other depending on the way you create directories with your webserver)<hr>"; 
            # abort: no caching possible
            exit;
          }
        }
        # $this->cacheFolder writeable?
        if (!is_writeable($this->cacheFolder)) {
          echo "please check cacheFolder:<br>".$this->cacheFolder."<br>is not writable. Please change permissions.";
          exit;
        }
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
      $this->basenode = $basenode;
      $this->datastructure = $content;

			return $this->retrieveDataAndBuildAllHtmlItems();
			
		}
    
    /* retrieveDataAndBuildAllHtmlItems: get json-data, build html*/
		public function retrieveDataAndBuildAllHtmlItems(){
			
			$this->retrieveJsonData();
      $jsonTree = $this->jsondata;
      $noofitems = 0;
      $output = "";
      
      $baseN = $this->basenode;
      if ($baseN!="") {
        $baseNArr = explode(".", $baseN);
        foreach($baseNArr as $key => $val) {
          $jsonTree = $jsonTree->$val;
        }
      } 
      
      foreach($jsonTree as $key => $val) {
          if (is_numeric($this->numberofdisplayeditems) && $this->numberofdisplayeditems>=0 && ($noofitems>$this->numberofdisplayeditems-1) ) {
            break;
          }
					$locHTML = $this->getItemHtml($val);
          if ($locHTML!="") {
            $noofitems++;
					  $output .= $locHTML;
          }
      }
      if ($noofitems>0) {
        $loctop = "<div id=\"hiddenDisplayNoOfFoundDivs\" no=\"$noofitems\"></div>"; ###### ?????????????
      }
			
			return $loctop.$output;

		}
    
    /* retrieveJsonData: get json-data and build json-array */
		private function retrieveJsonData(){
      # check cache: is there a not expired file? 
			if ($this->cacheEnable) {
        # use cache
        if ($this->isCacheFileExpired()) {
          # get json-data from cache
          $this->retrieveFeedFromCache();
        } else {
          $this->retrieveFeedFromWeb();
        }
      } else {
        # no use of cache OR cachefile expired: retrieve json-url
        $this->retrieveFeedFromWeb();
      }

  		if(empty($this->feedData)) {
        echo "error: get of json-data failed - plugin aborted: check url of json-feed";
        exit;
      }
      
			# build json-array
			$this->decodeFeedData();
		}
    
    
    /* isCacheFileExpired: check if cache enabled, if so: */
		private function isCacheFileExpired(){
			# get age of cachefile, if there is one...
      if (file_exists($this->cacheFile)) {
        $ageOfCachefile = filemtime($this->cacheFile);  # time of last change of cached file
      } else {
        # there is no cache file yet
        return FALSE;
      }
      
      # get cache parameter
      $cacheTime = get_option('jci_cache_time');  # max age of cachefile: if younger use cache, if not retrieve from web
			$format = get_option('jci_cache_time_format');
      $cacheExpireTime = strtotime(date('Y-m-d H:i:s'  , strtotime(" -".$cacheTime." " . $format )));

      # if $ageOfCachefile is < $cacheExpireTime use the cachefile:  isCacheFileExpired = FALSE
      if ($ageOfCachefile < $cacheExpireTime) {
        return FALSE;
      } else {
        return TRUE;
      }
		}
    
    
		/* retrieveFeedFromWeb: get raw json-data */
		private function retrieveFeedFromWeb(){
			$response = wp_remote_get($this->feedUrl);
			if(isset($response['body']) && !empty($response['body'])){
				$this->feedData = $response['body'];
				$this->storeFeedInCache();
			}
		}
    
    /* retrieveFeedFromCache: get cached filedata  */
		private function retrieveFeedFromCache(){
			if(file_exists($this->cacheFile)) {
        $this->feedData = file_get_contents($this->cacheFile);
      } else {
        # get from cache failed, try from web
        $this->retrieveFeedFromWeb();
      }
		}
    
    /* storeFeedInCache: store retrieved data in cache */
		private function storeFeedInCache(){
		  if (!$this->cacheEnable) {
        # no use of cache if cache is not enabled or not working
        return NULL;
      }
			#if(!file_exists($this->cacheFile)) 
      $handle = fopen($this->cacheFile, 'w');
			if(isset($handle) && !empty($handle)){
				$cacheWritesuccess = fwrite($handle, $this->feedData); # false if failed
				fclose($handle);
        if (!$cacheWritesuccess) {
          echo "cache-error:<br>".$this->cacheFile."<br>can't be stored - plugin aborted";
          exit;
        } else {
          return $cacheWritesuccess; # no of written bytes
        }
			} else {
        echo "cache-error:<br>".$this->cacheFile."<br>is either empty or unwriteable - plugin aborted";
        exit;
      }
		}

    /* decodeFeedData: convert raw-json-data into array */
		public function decodeFeedData(){
			if(!empty($this->feedData))
				$this->jsondata = json_decode($this->feedData);
		}

		private function getItemHtml($jsonItemDataArr){
			if (!($jsonItemDataArr instanceof stdClass)) {
        return "";
      }

      # html with placeholders
      $returnHTML = $this->datastructure;
      $returnHTML = preg_replace("/\n/", "", $returnHTML); # remove linefeeds from HTML-Pattern
      $subloopCounter = 0;
      $thereIsASubloopInThePattern = FALSE;
      if (preg_match("/{subloop:/", $returnHTML)) {
        $thereIsASubloopInThePattern = TRUE;
      }
      
      foreach($jsonItemDataArr as $param => $valOfParam) {
        $valtype = gettype($valOfParam);
        if ($valtype!="object") {
          # datafields right at the basenode
  	   	  $returnHTML = str_replace('{'.$param.'}' , $valOfParam , $returnHTML);
  	 	    $returnHTML = str_replace('{'.$param.':urlencode}' , urlencode(html_entity_decode($valOfParam)) , $returnHTML);
          # {street:ifNotEmptyAdd:","}
          if (trim($valOfParam)=="") {
      		  $returnHTML = preg_replace('/{'.$param.':ifNotEmptyAdd:([a-zA-Z0-9,;\-\:]*)}/i' , '' , $returnHTML);
          } else {
      		  $returnHTML = preg_replace('/{'.$param.':ifNotEmptyAdd:([a-zA-Z0-9,;\-\:]*)}/i' , $valOfParam.'\1' , $returnHTML);
          }
          
          
          ## define a datafield as unique: display only the FIRST data, igore all following
          $uniqueParam = '{'.$param.':unique}';
          if (preg_match("/$uniqueParam/", $returnHTML)) {
    	 	    $returnHTML = str_replace('{'.$param.':unique}' , $valOfParam , $returnHTML);
            $this->triggerUnique{$valOfParam}++;
          }
          if ($this->triggerUnique{$valOfParam}>1) {
            return "";
          }
        } else {
          # there is a object at the basenode: e.g. some more data for a datafield 
          preg_match('/{subloop:([a-zA-Z0-9]*):([0-9]*)}/', $returnHTML, $subloopNodeArr);
          $subloopNode = $subloopNodeArr[1]; # name of subloop-datanode
          $subLoopNumber = $subloopNodeArr[2];
          preg_match('/{subloop:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop}/', $returnHTML, $subloopStructureArr);
          $subloopStructure = $subloopStructureArr[1];
          $subloopHTML = "";
          $subloopCounter = 0;
          foreach($valOfParam as $keySubloop => $valSubloop) {
            $valtype1 = gettype($valSubloop);
            $subloopHTMLitem = $subloopStructure;
            $subLoopContentFound = FALSE;
            foreach($valSubloop as $keySubloop1 => $valSubloop1) {
              $valtype2 = gettype($valSubloop1);
              if ($valtype4!="object") {
          	 	  $subloopHTMLitem = str_replace('{'.$keySubloop1.'}' , $valSubloop1 , $subloopHTMLitem);
          		  $subloopHTMLitem = str_replace('{'.$keySubloop1.':urlencode}' , urlencode(html_entity_decode($valSubloop1)) , $subloopHTMLitem);
                if (trim($valSubloop1)=="") {
            		  $subloopHTMLitem = preg_replace('/{'.$keySubloop1.':ifNotEmptyAdd:([a-zA-Z0-9,;\-\:]*)}/i' , '' , $subloopHTMLitem);  # not working yet: öäüßÖÄÜ
                } else {
            		  $subloopHTMLitem = preg_replace('/{'.$keySubloop1.':ifNotEmptyAdd:([a-zA-Z0-9,;\-\:]*)}/i' , $valSubloop1.'\1' , $subloopHTMLitem);  # not working yet: öäüßÖÄÜ
                }
                $subLoopContentFound = TRUE;
              }
            }
            if ($subLoopContentFound) {
              $subloopCounter++;
              if ($subloopCounter <= $subLoopNumber) {
                $subloopHTML .= $subloopHTMLitem;
              }
            }
          }
        }
      }

      if ($thereIsASubloopInThePattern && ($subloopCounter==0)) { 
		    return "";  
      }
      $sli = '{subloop:'.$subloopNode.':'.$subLoopNumber.'}'.$subloopStructure.'{/subloop}';
  		$returnHTML = str_replace($sli , $subloopHTML , $returnHTML);
      return $returnHTML;

		}
		
		
	}

?>