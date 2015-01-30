<?php
/*
CLASS JsonContentParser
Description: Basic template engine Class: building code with JSON-data and template markups 
Version: 1.0.1
Author: Bernhard Kux
Author URI: http://www.kux.de/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


class JsonContentParser {

    /* shortcode-params */		
		private $jsondata = "";
    private $datastructure = "";
    private $basenode = ""; 
    private $numberofdisplayeditems = -1; # -1: show all
    private $oneOfTheseWordsMustBeIn = "";
    
    /* internal */
    private $showDebugMessages = FALSE; # set TRUE in constructor for debugging
    private $triggerUnique = NULL;
    private $subLoopParamArr = NULL;
    private $regExpPatternDetect = "([a-zA-Z0-9,;\_\-\:\,\<\>\/ ]*)";


		public function __construct($jsonData, $datastructure, $basenode, $numberofdisplayeditems, $oneOfTheseWordsMustBeIn){  
      # $this->showDebugMessages = TRUE; # sometimes helpful     
      $this->numberofdisplayeditems = $numberofdisplayeditems;            
      $this->oneOfTheseWordsMustBeIn = $oneOfTheseWordsMustBeIn;
      $this->jsondata = $jsonData;
      $this->datastructure = $datastructure;
      #$this->datastructure = preg_replace("/\n/", "", $this->datastructure); # remove linefeeds from template
      $this->basenode = $basenode;
		}
    
    /* retrieveDataAndBuildAllHtmlItems: get json-data, build html*/
		public function retrieveDataAndBuildAllHtmlItems(){
      $jsonTree = $this->jsondata;
      #var_Dump($jsonTree);
      $noofitems = 0;
      $output = "";
      
      $baseN = $this->basenode;
      $this->debugEcho("basenode: ".$baseN."<br>");
      
      if ($baseN!="") {
        $baseNArr = explode(".", $baseN);
        foreach($baseNArr as $key => $val) {
          $jsonTree = $jsonTree->$val;
        }
      } 
      
      $this->debugEcho("selected 1st: ".gettype($jsonTree)."<hr>");
      
      if (!is_object($jsonTree) && !is_array($jsonTree)) {
        $this->debugEcho("no object - never ever error ;-) congrats for creating!");
        echo "<hr>unsupported JSON-structure, please open ticket at <a href=\"https://wordpress.org/plugins/json-content-importer/\" target=\"_blank\">wordpress.org</a><<hr>";
        exit;
      }
      
      $returnHTMLinside = $this->datastructure;
      $foundItems = 1;
      $this->result = "";      
      foreach($jsonTree as $key => $val) {
          # base-loop through JSON
          $this->debugEcho("<hr><b>base-loop</b> $foundItems (of ".$this->numberofdisplayeditems."): ".gettype($val)."<br>");
          if (
            ($this->numberofdisplayeditems>0) && 
            ($foundItems > $this->numberofdisplayeditems)
          ) {                             
            break;
          }    
          $foundItems++;
          $this->addToResult = TRUE;
           
          if (is_object($val)) {
            $returnHTMLinside = $this->datastructure;
            $this->debugEcho("1st is object,, template to fill:----<br>$returnHTMLinside<br>----<br>");
            foreach($val as $param => $valOfParam) {
              if (is_object($valOfParam)) {
                $this->debugEcho("is_object: ".gettype($param)."  ## ".gettype($valOfParam)."<br>");
                
                $subloopHTML1 = "";
                $subloopcounter = 0;
                foreach($valOfParam as $param1 => $valOfParam1) {
                   preg_match('/{subloop:'.$this->regExpPatternDetect.':([0-9]*)}/', $this->datastructure, $subloopNodeArr);
                   $this->subLoopParamArr{"$subloopNode"} = $subloopNodeArr[1];
                   $this->subLoopParamArr{"subLoopNumber"} = $subloopNodeArr[2];
                   preg_match('/{subloop:'.$this->subLoopParamArr{"$subloopNode"}.':'.$this->subLoopParamArr{"subLoopNumber"}.'}(.*){\/subloop}/', $this->datastructure, $subloopStructureArr);
                   $this->subLoopParamArr{"subloopStructure"} = $subloopStructureArr[1];

                   $subloopHTML = "";
                   $subloopHTMLTmp = $this->subLoopParamArr{"subloopStructure"};
                   if (is_object($valOfParam1) || is_array($valOfParam1)) {
                     foreach($valOfParam1 as $param2 => $valOfParam2) {
               	   	    $subloopHTMLTmp = $this->replacePattern($subloopHTMLTmp, $param2, $valOfParam2);
                     }
                   } else if (is_numeric($valOfParam1) || is_string($valOfParam1)) {
                    # not implemented yet
                   }
                   if ($subloopHTMLTmp!="") {
                     $subloopcounter++;
                   }
                   if ($subloopcounter-1 <= $this->subLoopParamArr{"subLoopNumber"}) {
                     $subloopHTML1 .= $subloopHTMLTmp;
                   }
                }      
                          
                $sli = '{subloop:'.$this->subLoopParamArr{"$subloopNode"}.':'.$this->subLoopParamArr{"subLoopNumber"}.'}'.$this->subLoopParamArr{"subloopStructure"}.'{/subloop}';
            		$returnHTMLinside = str_replace($sli , $subloopHTML1 , $returnHTMLinside);
               } else if (is_array($valOfParam)) {
                $this->debugEcho("is_array: ".$param."  /  $valOfParam<br>");

                preg_match('/{subloop-array:([a-zA-Z0-9\_\-]*):([0-9]*)}/', $this->datastructure, $subloopNodeArr);
                $subloopNode = $subloopNodeArr[1]; # name of subloop-datanode
                $subLoopNumber = $subloopNodeArr[2];
                preg_match('/{subloop-array:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop-array}/', $this->datastructure, $subloopStructureArr);
                $subloopStructure = $subloopStructureArr[1];
                $subloopHTML = $subloopStructure;
                $this->debugEcho("subloop-array: node:$subloopNode  number:$subLoopNumber html:$subloopHTML<br>");

                $i = 1;
                foreach($valOfParam as $param1 => $valOfParam1) {
                    $this->debugEcho("is_array subloop: string or numeric ($param1 / $valOfParam1), template to fill:----<br>$subloopHTML<br>----<br>");
                    $subloopHTML = $this->replacePattern($subloopHTML, $i, $valOfParam1);
                    $this->debugEcho("$i subloopHTML: $subloopHTML<hr>");
                    $i++;
                }
                
                $subloopHTML = $this->clearUnusedArrayDatafields($subloopHTML); ## clear empty {n}-datafiels

                $sli = '{subloop-array:'.$subloopNode.':'.$subLoopNumber.'}'.$subloopStructure.'{/subloop-array}';
            		$returnHTMLinside = str_replace($sli , $subloopHTML , $returnHTMLinside);
              } else if (is_string($valOfParam) || is_numeric($valOfParam)) {
                $this->debugEcho("string or numeric ($param / $valOfParam), template to fill:----<br>$returnHTMLinside<br>----<br>");
        	   	  $returnHTMLinside = $this->replacePattern($returnHTMLinside, $param, $valOfParam);
              }
            }
					  $locHTML = $returnHTMLinside;
            if ($locHTML!="") {
              $noofitems++;
				      $output .= $this->checkIfAddToResult($locHTML);
            }
            if ($noofitems>0) {
              $loctop = "<div id=\"hiddenDisplayNoOfFoundDivs\" no=\"$noofitems\"></div>"; # the number of found items is given in a empty div, fetch and display via jQuery 
            }
          } else if (is_array($val)) {
            $this->debugEcho("is_array, search for subloop-array:<br>");
                $i = 1;
                $this->debugEcho("datastructure: ".$this->datastructure."<br>");
                preg_match('/{subloop-array:'.$this->regExpPatternDetect.':([0-9]*)}/', $this->datastructure, $subloopNodeArr);
                $subloopNode = $subloopNodeArr[1]; # name of subloop-datanode
                $subLoopNumber = $subloopNodeArr[2];
                $this->debugEcho("subloop-array: Node $subloopNode<br>");
                $this->debugEcho("subLoop-array: Number $subLoopNumber<br>");
                preg_match('/{subloop-array:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop-array}/', $this->datastructure, $subloopStructureArr);
                $subloopStructure = $subloopStructureArr[1];
                $this->debugEcho("subloop-array: Structure: $subloopStructure<br>");

                $subloopHTML = "";
                $subloopHTML1 = "";
                foreach($val as $param1 => $valOfParam1) {
                  $this->debugEcho("loop obj,  template to fill:----<br>$subloopStructure<br>----<br>"); 
                  if (is_object($valOfParam1)) {
                    $subloopHTMLTmp = $subloopStructure;
                    foreach($valOfParam1 as $param2 => $valOfParam2) {
              	   	  $subloopHTMLTmp = $this->replacePattern($subloopHTMLTmp, $param2, $valOfParam2);
                    }
                    $subloopHTML1 .= $subloopHTMLTmp;
                  } else {
                    $this->debugEcho("not an object: no subloop processing implemented for this JSON-structure<br>");
                  }
                }
                $sli = '{subloop-array:'.$subloopNode.':'.$subLoopNumber.'}'.$subloopStructure.'{/subloop-array}';
            		$output = $this->checkIfAddToResult(str_replace($sli , $subloopHTML1 , $returnHTMLinside));
          } else if (is_string($val) || is_numeric($val)) {   
            $this->debugEcho("string or numeric ($key / $val), template to fill:----<br>$returnHTMLinside<br>----<br>");
     	   	  $returnHTMLinside = $this->replacePattern($returnHTMLinside, $key, $val);
            $this->debugEcho("result of replacing markup: $returnHTMLinside<hr>");
				    $output = $this->checkIfAddToResult($returnHTMLinside); # {} in $returnHTMLinside are replaced by values step by step
          }
       }   
 			return $loctop.$output;

		}
    
    /* checkIfAddToResult: the code created by the template and the JSON-data is checked on 
    - remaining markups
    - needed keywords
    - ignore flag $this->addToResult might set somewhere before to FALSE
    */
    private function checkIfAddToResult($resultCode) {
      # some {}-markups like {subloop} or {subloop-array} can't be removed, because the JSON-data is incomplete (e.g. no events at location...)
      # here we finally check the created code and remove all reminaing {subloop} or {subloop-array}-markups      
      $resultCode = preg_replace("/{subloop:(.*)subloop}/" , "" , $resultCode);
      $resultCode = preg_replace("/{subloop-array:(.*)array-subloop}/" , "" , $resultCode);
       
      if ($this->oneOfTheseWordsMustBeIn!="") {
        $oneOfTheseWordsMustBeInArr = explode(",", trim($this->oneOfTheseWordsMustBeIn));
        $isIn = FALSE;
        foreach($oneOfTheseWordsMustBeInArr as $keyword) {
          if (trim($keyword)=="") { continue; }
          $kw = htmlentities(trim($keyword), ENT_COMPAT, 'UTF-8', FALSE); 
          if (preg_match("/".$kw."/i", strip_tags($resultCode))) {
            $isIn = TRUE;
          }
        } 
        if (!$isIn) {   return "";    } # none of the keywords was found: ignore this       
      }
      if ($this->addToResult) {
        return $resultCode; # ok, add this code 
      }
      return "";
    }

    /* debugEcho: display debugMessages or not */
    private function debugEcho($txt) {
      if ($this->showDebugMessages) {
        echo $txt;
      }
    }

    /* clearUnusedArrayDatafields: remove unfilled markups: we loop the JSON-data, not the markups. If there is no JSON, the markup might stay markup... */
    private function clearUnusedArrayDatafields($datastructure) {
      $regExpPatt = "([0-9]*)";
      $datastructure = preg_replace("/{".$regExpPatt."}/i", "", $datastructure);
      $datastructure = preg_replace("/{".$regExpPatt.":urlencode}/i", "", $datastructure);
      $datastructure = preg_replace("/{".$regExpPatt.":ifNotEmptyAdd:".$this->regExpPatternDetect."}/i", "", $datastructure);
      $datastructure = preg_replace("/{".$regExpPatt.":ifNotEmptyAddLeft:".$this->regExpPatternDetect."}/i", "", $datastructure);
      $datastructure = preg_replace("/{".$regExpPatt.":ifNotEmptyAddRight:".$this->regExpPatternDetect."}/i", "", $datastructure);
      return $datastructure;
    }

    /* replacePattern: replace markup with data and do the specials like urlencode etc.*/
    private function replacePattern($datastructure, $pattern, $value) {
      $datastructure = str_replace("{".$pattern."}" , $value , $datastructure);
      $datastructure = str_replace("{".$pattern.":urlencode}" , urlencode(html_entity_decode($value)) , $datastructure);
      if (trim($value)=="") {
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAdd:".$this->regExpPatternDetect."}/i" , '' , $datastructure);
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAddRight:".$this->regExpPatternDetect."}/i" , '' , $datastructure);
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAddLeft:".$this->regExpPatternDetect."}/i" , '' , $datastructure);
      } else {
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAdd:".$this->regExpPatternDetect."}/i" , $value.'\1' , $datastructure);
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAddRight:".$this->regExpPatternDetect."}/i" , $value.'\1' , $datastructure);
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAddLeft:".$this->regExpPatternDetect."}/i" , '\1'.$value , $datastructure);
      }

      # a markup can be defined as unique: display only the FIRST data, ignore all following...
      $uniqueParam = '{'.$pattern.':unique}';
      if (preg_match("/$uniqueParam/", $datastructure)) {
    	   # there is a markup defined as unique 
         $datastructure = str_replace("{".$pattern.":unique}", $value, $datastructure);
         $this->triggerUnique{$value}++;
         if ($this->triggerUnique{$value}>1) {
            $this->addToResult = FALSE; # set flag to "ignore this code" created by this loop
         }
      }
      return $datastructure; # return template filled with data
    }
	}
?>