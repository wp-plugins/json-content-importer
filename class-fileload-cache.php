<?php
# version 20150601-1

class CheckCacheFolder {
  /* internal */
  private $cacheFolder = "";

  public function __construct($cacheFolder){
    $this->cacheFolder = $cacheFolder;
    $this->checkCacheFolder();
	}

  /* decodeFeedData: convert raw-json-data into array */
	private function checkCacheFolder() {
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
	}
}



class JSONdecode {
  /* internal */
  private $jsondata = "";
  private $feedData = "";

  public function __construct($feedData){
    $this->feedData = $feedData;
    $this->jsondata = "";
    $this->decodeFeedData();
	}

  /* decodeFeedData: convert raw-json-data into array */
	public function decodeFeedData() {
	 if(!empty($this->feedData))
	   $this->jsondata =  json_decode($this->feedData);
     if (is_null($this->jsondata)) {
      # utf8_encode JSON-datastring, then try json_decode again
  		$this->jsondata =  json_decode(utf8_encode($this->feedData));
      if (is_null($this->jsondata)) {
        echo "JSON-Decoding failed. Check structure and encoding if JSON-data.";
        return FALSE;
      }
    }
	}

  /* get */
	public function getJsondata() {
    return $this->jsondata;
  }
}

class FileLoadWithCache {
  /* internal */
  private $feedData = "";
  private $urlgettimeout = 5; # 5 seconds default timeout for get of JSON-URL
  private $cacheEnable = "";
  private $cacheFile = "";
  private $feedUrl = "";
  private $cacheExpireTime = 0;
  private $cacheWritesuccess = FALSE;

  public function __construct($feedUrl, $urlgettimeout, $cacheEnable, $cacheFile, $cacheExpireTime){
    $this->cacheEnable = $cacheEnable;
    if (is_numeric($urlgettimeout) && $urlgettimeout>=0) {
      $this->urlgettimeout = $urlgettimeout;
    }
    $this->cacheFile = $cacheFile;
    $this->feedUrl = $feedUrl;
    $this->cacheExpireTime = $cacheExpireTime;
	}

  /* get */
	public function getFeeddata() {
    return $this->feedData;
  }

    /* retrieveJsonData: get json-data and build json-array */
		public function retrieveJsonData(){
      # check cache: is there a not expired file?
			if ($this->cacheEnable) {
        # use cache
        if ($this->isCacheFileExpired()) {
          # get json-data from cache
          #$this->retrieveFeedFromCache();
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
        return "";
      }
		}

      /* isCacheFileExpired: check if cache enabled, if so: */
		public function isCacheFileExpired(){
			# get age of cachefile, if there is one...
      if (file_exists($this->cacheFile)) {
        $ageOfCachefile = filemtime($this->cacheFile);  # time of last change of cached file
      } else {
        # there is no cache file yet
        return FALSE;
      }

      # if $ageOfCachefile is < $cacheExpireTime use the cachefile:  isCacheFileExpired = FALSE
      if ($ageOfCachefile < $this->cacheExpireTime) {
        return FALSE;
      } else {
        return TRUE;
      }
		}

    /* storeFeedInCache: store retrieved data in cache */
		private function storeFeedInCache(){
		  if (!$this->cacheEnable) {
        # no use of cache if cache is not enabled or not working
        return NULL;
      }
      $handle = fopen($this->cacheFile, 'w');
			if(isset($handle) && !empty($handle)){
				$this->cacheWritesuccess = fwrite($handle, $this->feedData); # false if failed
				fclose($handle);
        if (!$this->cacheWritesuccess) {
          echo "cache-error:<br>".$this->cacheFile."<br>can't be stored - plugin aborted";
          return "";
        } else {
          return $this->cacheWritesuccess; # no of written bytes
        }
			} else {
        echo "cache-error:<br>".$this->cacheFile."<br>is either empty or unwriteable - plugin aborted";
        return "";
      }
		}

		public function retrieveFeedFromWeb(){
      # wordpress unicodes http://openstates.org/api/v1/bills/?state=dc&q=taxi&apikey=4680b1234b1b4c04a77cdff59c91cfe7;
      # to  http://openstates.org/api/v1/bills/?state=dc&#038;q=taxi&#038;apikey=4680b1234b1b4c04a77cdff59c91cfe7
      # and the param-values are corrupted
      # un_unicode ampersand:
      $this->feedUrl = preg_replace("/&#038;/", "&", $this->feedUrl);
      $this->feedUrl = str_replace('&amp;', '&', $this->feedUrl);
      #$useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0"; # in case of simulating a browser
      $args = array(
        'timeout'     => $this->urlgettimeout,
        #'httpversion' => '1.0',
        # 'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
        #'blocking'    => true,
        #'headers'     => array(),
        #'cookies'     => array(),
        #'body'        => null,
        #'compress'    => false,
        #'decompress'  => true,
        #'sslverify'   => true,
        #'stream'      => false,
        #'filename'    => null
      );
      $response = wp_remote_get($this->feedUrl, $args);
      if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong fetching URL with JSON-data: $error_message";
      } else if(isset($response['body']) && !empty($response['body'])){
				$this->feedData = $response['body'];
        $this->storeFeedInCache();
			}
		}

    /* retrieveFeedFromCache: get cached filedata  */
		public function retrieveFeedFromCache(){
			if(file_exists($this->cacheFile)) {
        $this->feedData = file_get_contents($this->cacheFile);
      } else {
        # get from cache failed, try from web
        $this->retrieveFeedFromWeb();
      }
		}
}
?>