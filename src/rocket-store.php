<?php
/*============================================================================*\
  Rocket Store (Rocket store)

  A very simple and yes powerfull flat file storage.
  
  (c) Paragi 2017, Simon Riget. 

  License MIT  
\*============================================================================*/
namespace Paragi;

// Get options
define('RS_ORDER'         ,0x01);    
define('RS_ORDER_DESC'    ,0x02);
define('RS_ORDERBY_TIME'  ,0x04);
define('RS_DELETE'        ,0x10);

// Post options
define('RS_ADD_AUTO_INC'  ,0x40);

// Data storage format options
define('RS_FORMAT_PHP'    ,0x01);
define('RS_FORMAT_JSON'   ,0x02);
define('RS_FORMAT_XML'    ,0x04); // Not implemened 

class RocketStore {
    public $data_storage_area = null;
    public $data_format = RS_FORMAT_PHP;
    
    /*============================================================================*\
      Configure
    \*============================================================================*/      
    public function __construct($option = []) {
        if(!empty($option['data_storage_area']))
           $dir = realpath($option['data_storage_area']);
        elseif(is_dir(sys_get_temp_dir()))
           $dir = sys_get_temp_dir();
        elseif(is_dir($_SERVER['DOCUMENT_ROOT']))
            $dir = $_SERVER['DOCUMENT_ROOT'];
        else
           $dir = "." . DIRECTORY_SEPARATOR;
        if(!is_dir($dir) || !is_writable($dir)) 
           throw new Error("RocketStore data storage area '$dir' is not at writable directory");
        $this->data_storage_area = 
           $dir . DIRECTORY_SEPARATOR . "rocket_store" . DIRECTORY_SEPARATOR;  

        $this_data_format = 
              intval(@$option['data_format'])
              & ( RS_FORMAT_PHP | RS_FORMAT_JSON | RS_FORMAT_XML )
            ? : RS_FORMAT_PHP;
    }
        
    /*============================================================================*\
      Post a data record (Insert or overwrite)  
    \*============================================================================*/      
    public function post($collection, $key, $record ,$flags = 0){

        $collection = $this->path_safe($collection);
        if(empty($collection))
            return ["error" => "No valid collection name given", "count" => 0];
        
        $dir = $this->data_storage_area . $collection;
        if(!is_writable($dir))
            if(!mkdir($dir, 0777, true))
              return ["error" => "Unable to create directory '$collection' in '$this->data_storage_area'", "count" => 0];

        $key = $this->path_safe($key);

        // Insert a sequence
        if(empty($key) || ($flags & RS_ADD_AUTO_INC)) {
            $seq = $this->sequence($collection . '_seq');
            if($seq < 0) 
               return ["error" => "Unable to access sequence '{$colleciton}_seq'", "count" => 0];
            $key = empty($key) ? "$seq" : "{$seq}-" . $key;
        }

        // Write to file
        if($this->data_format & RS_FORMAT_JSON)
            $chars_written = @file_put_contents(
                 $dir . DIRECTORY_SEPARATOR . $key
                ,json_encode($record, JSON_HEX_QUOT | JSON_PRETTY_PRINT)
           );
        else
            $chars_written = @file_put_contents(
                 $dir . DIRECTORY_SEPARATOR . $key
                ,serialize($record)
            );
            
        if($chars_written === false) 
            return ["error" => "Unable to write to filesystem: "
                . "{$this->data_storage_area}{$collection}" . DIRECTORY_SEPARATOR . $key
                , "count" => 0
            ];  
        
        return ["error" => "", "key" => $key, "count" => 1];  
    }

    /*============================================================================*\
      Get one or more records or list all collections (or delete it)
    \*============================================================================*/      
    public function get($collection = '', $key = '', $min_time = null , $max_time = null, $flags = 0){

        $collection = $this->path_safe($collection);
        $key = $this->path_safe($key,true);

        $path = $this->data_storage_area . $collection . DIRECTORY_SEPARATOR . $key;
        $path .= !($flags & RS_DELETE) && empty($key) ? "*" : "";
        $count = 0;
        $result = [];
        $hit = glob($path, $flags & (RS_ORDER | RS_ORDER_DESC) ? null : GLOB_NOSORT);
        foreach($hit as $full_path){
            // delete 
            if($flags & RS_DELETE){
               $count += $this->recursive_file_delete($full_path);

            // Read record
            }else{
                $i = @substr($full_path,strrpos($full_path,DIRECTORY_SEPARATOR) + 1);
                if($this->data_format & RS_FORMAT_JSON)
                    $result[$i] = @json_encode(@file_get_contents($full_path));
                else
                    $result[$i] = @unserialize(@file_get_contents($full_path));
                $count++;    
            }
        }

        return [
             "error" => ""
            ,"result" => $flags & RS_ORDER_DESC ? array_reverse($result) : $result
            ,"count" => $count
        ];  
    }

    /*============================================================================*\
      Delete one or more records or collections
    \*============================================================================*/      
    public function delete($collection = null, $key = null){
        return $this->get($collection,$key,null,null,RS_DELETE);
    }

    /*============================================================================*\
      increment (or create) a sequence
      
      Return count or negative value when failing
    \*============================================================================*/      
    public function sequence($name){
        $name = $this->path_safe($name);
        if(empty($name)) 
           return -1;
        $file_name = $this->data_storage_area . $name;
        
        $file = fopen($file_name, "cb+");
        if(!$file || !flock($file, LOCK_EX)) 
           return -2;
          
        $sequence = intval(fread($file,100)) + 1; // if empty it returns False => 0 + 1
        rewind($file);
        fwrite($file,$sequence);
        flock($file, LOCK_UN);

        return $sequence;
    } 
    
    /*============================================================================*\
      Private functions
    \*============================================================================*/      
    private function recursive_file_delete($path) {
        if(!is_dir($path)) return unlink($path) ? 1 : 0;
       
        @unlink(substr($path,0,-1) . "_seq");
        $count = 0;
        foreach(glob("$path/*", GLOB_NOSORT) as $file) {
            if(is_dir($file)) 
               $count += $this->recursive_file_delete($file);
            else  
               $count += unlink($file) ? 1 : 0;
            }    
        rmdir($path);
      return $count;
    } 
    
    private function path_safe($name, $allow_wildcards = false){
        // No unnessesary limits
        $regex = '/[\x00-\x1F\|<>\'"\~\&\\' . DIRECTORY_SEPARATOR;
        if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') $regex .= '\\\:';
        if(!$allow_wildcards) $regex .= '\?\*'; 
        $regex .= ']|([\.]{2,})/';
         
        return preg_replace($regex, '', $name);
    }
}
