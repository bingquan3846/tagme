<?php
namespace TYPO3\Tagme\Service;

    /***************************************************************
     *  Copyright notice
     *
     *  (c) 2014 Bingquan Bao <bingquan.bao@gmail.com>, BBQ
     *
     *  All rights reserved
     *
     *  This script is part of the TYPO3 project. The TYPO3 project is
     *  free software; you can redistribute it and/or modify
     *  it under the terms of the GNU General Public License as published by
     *  the Free Software Foundation; either version 3 of the License, or
     *  (at your option) any later version.
     *
     *  The GNU General Public License can be found at
     *  http://www.gnu.org/copyleft/gpl.html.
     *
     *  This script is distributed in the hope that it will be useful,
     *  but WITHOUT ANY WARRANTY; without even the implied warranty of
     *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *  GNU General Public License for more details.
     *
     *  This copyright notice MUST APPEAR in all copies of the script!
     ***************************************************************/

/**
 *
 *
 * @package tagme
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
Class DataHandler implements  \TYPO3\CMS\Core\SingletonInterface {
    /**
     * @var
     */
    private $url;
    /**
     * @var
     */
    private $tag;

    public $cache_folder;

    /**
     * @param $url
     * @param array $parameters
     */
    public function __construct($url,$tag,$parameters = ""){

        if(is_array($parameters)){
            $keyArray = array();
            $valArray = array();
            foreach($parameters as $key => $val){
                $keyArray[] = $key;
                $valArray[] = $val;
            }
            $this->url = str_replace($keyArray, $valArray, $url);
        }else{
            $this->url = $url;
        }

        $this->tag = $tag;
        $this->cache_folder = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tagme').'Resources/Public/Cache';
    }

    /**
     * @return mixed
     */
    public function readXml(){
        $url = $this->url;

        for($i=0; $i < 10 ; $i++){
            $day = $i * 5;
            $this->url = $url;
            $time = date("YmdH",strtotime("-$day days")) ;
            $this->url = $this->url . '&before='.strtotime("-$day days");
            //print_r($this->url);
            if(!file_exists($this->cache_folder . '/' . $this->tag . '/tag' . $time . '.xml')){
                $this->saveCache($time);
            }
        }

        $objects = array();
        $itemArray = array();
        //print_r($xmlArray);die;
        // read the xml file in folder
        $mydir = @opendir(($this->cache_folder . '/' . $this->tag));
        if(!$mydir) return false;

        while(false !== ($file = readdir($mydir))) {
            if($file != "." && $file != ".."){
                $xml = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($this->cache_folder . '/' . $this->tag . '/' . $file );
                $xmlObject = simplexml_load_string($xml);
                $itemArray[] = $xmlObject;
            }
        }
       // print_r($itemArray);die;
        //$itemArray = array_merge($itemArray[0],$itemArray[1]);
        $i = 0;
        if(count($itemArray)){
            foreach($itemArray as $item){
                foreach($item->item as $val){

                    foreach($val as $key => $value){

                        $objects[$i][$key] = html_entity_decode((string)$value);

                        if( ($key == 'photo') && (trim($objects[$i][$key])== '') ){

                            $objects[$i][$key] = 'typo3conf/ext/tagme/Resources/Public/default.png';
                        }
                    }
                    $i++;
                }
            }

        }
        //print_r($objects);die;

        return $objects;
    }

    /**
     * save pictures and create xml
     * @return
     */
    public function saveCache($time){
        $out = $this->readData($this->url);

        if(!is_dir($this->cache_folder . '/' . $this->tag)){
            mkdir($this->cache_folder . '/' . $this->tag);
            //mkdir($this->cache_folder . '/' . $this->tag . '/img');
        }

        $posts = json_decode($out)->response;
        if(count($posts)){
            $xml = "<?xml version='1.0'?>\n<nss>\n";

            foreach($posts as $post){
                $xml .= "\t<item>
                              <channel>nss</channel>
                              <id>".$post->id."</id>
                        <created>".$post->date."</created>
                        <blog_name>".$post->blog_name."</blog_name>
                        <short_url>".$post->short_url."</short_url>
                        <caption><![CDATA[".$post->caption."]]></caption>
                        <note_count>".$post->note_count."</note_count>";
                        if(count($post->photos)){
                            foreach($post->photos  as $photo){
                                $photoUrl = $photo->original_size->url;
                                $xml .= "<photo>".$photoUrl."</photo>";
                                break;
                            }
                        }else{
                            $xml .= "<photo></photo>";
                        }
                $xml .= "</item>";
            }
            $xml .= "</nss>";

        }
//        print_r($this->readData($photoUrl));die;
        $cache_file = $this->cache_folder."/" . $this->tag . "/tag".$time.".xml" ;
        $fh = fopen($cache_file, 'w');
        fwrite($fh, $xml);
        fclose($fh);
    }


    /**
     * read json data from url
     * @return json
     */
    public function readData($url){
        $parsedUrl = parse_url($url);
        $data = null;

        //CURL
        if  (in_array  ('curl', get_loaded_extensions())){

            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec ($ch);
            curl_close ($ch);
            //print_r($data);
            if($data) return $data;
        }

        //fsockopen
        $fp = @fsockopen ('ssl://'.$parsedUrl['host'] , 443, $errno, $errstr, 30);
        if ($fp){
            fputs($fp, "GET /".$parsedUrl['path']."?".$parsedUrl['query']."/ HTTP/1.1\r\n");
            fputs($fp, "Host: ".$parsedUrl['host']."\r\n");
            fputs($fp, "Referer: ".$parsedUrl['host']."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            while (!feof($fp)){
                $data = fgets($fp);
            }
            fclose($fp);
            if($data) return $data;
        }

        //file_get_contents
        $data = @file_get_contents($url);
        if($data) return $data;

        //Anymore alternatives?

        //Error
        return 'error';

    }

    /**
     * delete cache
     * @param bool $dir
     * @return bool
     */
    public function clearCache($dir = false){
            $mydir = @opendir($dir);
            if(!$mydir) return false;

            while(false !== ($file = readdir($mydir))) {
                if($file != "." && $file != "..") {
                    chmod($dir.$file, 0777);
                    if(is_dir($dir.$file)) {
                        chdir('.');
                        $this->clearCache($dir.$file.'/');
                        rmdir($dir.$file) or die("couldn't delete $dir$file<br />");
                    }
                    else
                        unlink($dir.$file) or die("couldn't delete $dir$file<br />");
                }
            }
            closedir($mydir);
            return true;
    }
}