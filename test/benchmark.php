<?php
/*============================================================================*\
  Rocket store - test suite
  
  (c) Simon Riget 2017
  License MIT
System: i7 3rd gen on SSD
Mass insert: 31601.258661459/sec.
Exact key search 1 hit: 25623.088110658/sec.
Exact key search not found: 235735.98246265/sec.
Wildcard key search 2 hits: 2.2448931913187/sec.
Wildcard key delete 2 hits: 2.2166423209533/sec.
Excat key delete 1 hits: 29852.697508897/sec.
Delete collection: 44355.386458937/sec.

\*============================================================================*/      
require "../src/rocket-store.php";
$rs = new Paragi\RocketStore();

$create = false;
$delete = false;
$collection = "person";

// Settings
//$collection = "temp/person";
$create = true;
$delete = true;

echo "collection dir: $rs->data_storage_area$collection\n";

$record[] = [
   "name" => "Adam Smith"
  ,"email" => "adam@smith.com"
  ,"id" => 1
];

$record[] = [
   "name" => "Alice Smith"
  ,"email" => "alice@smith.com"
  ,"relatet" => [1]
  ,"id" => 2
];

$record[] = [
   "name" => "Bob Smith"
  ,"email" => "bob@smith.com"
  ,"relatet" => [1,2]
  ,"id" => 3
];


$record[] = [
   "name" => "Dave Canoly"
  ,"email" => "dave@canoly.com"
  ,"relatet" => [4]
  ,"id" => 5
];

$record[] = [
   "name" => "Charlie Canoly"
  ,"email" => "charlie@canoly.com"
  ,"relatet" => []
  ,"id" => 4
];


//print_r($rs->get($collection,substr(rand(),-5) . "6-Adam Smith"));

echo "<pre>Bench mark test\n";
echo "System: i7 3rd gen on SSD\n";
$rows = 0;

if($create){
  echo "Mass insert: ";

  $id = 1;
  $ts = microtime(true);

  for($c = 0; $c<200000; $c++)
    foreach($record as $document){
      $document['id'] = $id++;
      $result = $rs->post($collection,"$document[id]-$document[name]",$document);
      if(!empty($result['error'])){
        echo "Failed: ";
        print_r($result);
        exit;
      }
    }

  $rows += --$c;
  echo 5 * $c / (microtime(true) - $ts) ."/sec.\n";
}


if(true){
  echo "Exact key search 1 hit: ";

  $id = 1;
  $ts = microtime(true);

  for($c = 0; $c<10000; $c++){
    $result = $rs->get($collection, intval(substr(rand(),-5))  . "6-Adam Smith");
    if(!empty($result['error']) || $result['count'] != 1){
      echo "Failed: ";
      print_r($result);
      exit;
    }
  }

  $c--;
  echo $c / (microtime(true) - $ts) ."/sec.\n";
}


if(true){
  echo "Exact key search not found: ";

  $id = 1;
  $ts = microtime(true);

  for($c = 0; $c<10000; $c++){
    $result = $rs->get($collection, intval(substr(rand(),-5))  . "6-Adrian Smith");
    if(!empty($result['error']) || $result['count'] != 0){
      echo "Failed: ";
      print_r($result);
      exit;
    }
  }

  $c--;
  echo $c / (microtime(true) - $ts) ."/sec.\n";
}


if(true){
  echo "Wildcard key search 2 hits: ";

  $id = 1;
  $ts = microtime(true);

  for($c = 0; $c<5; $c++){
    $result = $rs->get($collection, intval(substr(rand(),-5))  . "?-Adam Smith");
    if(!empty($result['error']) || $result['count'] == 0){
      echo "Failed: ";
      print_r($result);
      exit;
    }
  }

  $c--;
  echo $c / (microtime(true) - $ts) ."/sec.\n";
}

if($delete){
  echo "Wildcard key delete 2 hits: ";

  $id = 1;
  $ts = microtime(true);

  for($c = 0; $c<5; $c++){
    $result = $rs->delete($collection, intval(substr(rand(),-5))  . "?-Adam Smith");
    if(!empty($result['error']) || $result['count'] == 0){
      echo "Failed: ";
      print_r($result);
      exit;
    }
  }

  $c--;
  echo $c / (microtime(true) - $ts) ."/sec.\n";
}

if($delete){
  echo "Excat key delete 1 hits: ";

  $id = 1;
  $ts = microtime(true);

  for($c = 0; $c<5; $c++){
    $result = $rs->delete($collection, intval(substr(rand(),-5))  . "6-Adam Smith");
    if(!empty($result['error']) || $result['count'] == 0){
      echo "Failed: ";
      print_r($result);
      exit;
    }
  }

  $c--;
  echo $c / (microtime(true) - $ts) ."/sec.\n";
}

if($delete){
  echo "Delete collection: ";

  $id = 1;
  $ts = microtime(true);
  $result = $rs->delete($collection);
  if(!empty($result['error']) || $result['count'] == 0){
    echo "Failed: ";
    print_r($result);
    exit;
  }
  echo $result['count'] / (microtime(true) - $ts) ."/sec.\n";
}

