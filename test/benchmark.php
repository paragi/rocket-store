<?php
/*============================================================================*\
  Rocket store - test suite

  (c) Simon Riget 2017
  License MIT


  Some results:

  PHP on i7 3rd gen on SSD
  ┌───────────────────────────────────┬─────────────┐
  │ Mass insert                       │ 31601 /sec  │
  ├───────────────────────────────────┼─────────────┤
  │ Exact random key search           │ 25623 /sec  │
  ├───────────────────────────────────┼─────────────┤
  │ Exact ramdom key search no hit    │ 235735 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom key search 2 hits │ 2.25 /sec   │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom key search no hit │             │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom delete 2 hits     │ 2.22 /sec   │
  ├───────────────────────────────────┼─────────────┤
  │ Exact random delete               │ 29852 /sec  │
  └───────────────────────────────────┴─────────────┘


  Nodeon i7 3rd gen on SSD
  ┌───────────────────────────────────┬─────────────┐
  │ Mass insert                       │ 69434 /sec  │
  ├───────────────────────────────────┼─────────────┤
  │ Exact random key search           │ 86775 /sec  │
  ├───────────────────────────────────┼─────────────┤
  │ Exact ramdom key search no hit    │ 123304 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom key search 2 hits │ 14.6 /sec   │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom key search no hit │ 15.5 /sec   │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom delete 2 hits     │ 15.5 /sec   │
  ├───────────────────────────────────┼─────────────┤
  │ Exact random delete               │ 325.7 /sec  │
  └───────────────────────────────────┴─────────────┘

  PHP on Raspbarry PI Zero
  ┌───────────────────────────────────┬─────────────┐
  │ Mass insert                       │    532 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Exact random key search           │    197 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Exact ramdom key search no hit    │   1571 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom key search 2 hits │   0.11 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom key search no hit │             │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom delete 2 hits     │   0.11 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Exact random delete               │   181 /sec  │
  └───────────────────────────────────┴─────────────┘

  Node on Raspbarry Pi Zero
  ┌───────────────────────────────────┬─────────────┐
  │ Mass insert                       │    561 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Exact random key search           │     96 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Exact ramdom key search no hit    │    147 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom key search 2 hits │   0.27 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom key search no hit │   0.27 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Wildcard ramdom delete 2 hits     │   0.29 /sec │
  ├───────────────────────────────────┼─────────────┤
  │ Exact random delete               │   10.3 /sec │
  └───────────────────────────────────┴─────────────┘

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
echo "┌───────────────────────────────────┬─────────────┐\n";


if($create){
$rows = 0;
  echo "| Mass insert:                      |";

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
  printf(" % 6d /sec. |\n",5 * $c / (microtime(true) - $ts));
  echo "├───────────────────────────────────┼─────────────┤\n";
}


if(true){
  echo "| Exact key search 1 hit:           |";

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
  printf(" % 6d /sec. |\n",5 * $c / (microtime(true) - $ts));
  echo "├───────────────────────────────────┼─────────────┤\n";
}


if(true){
  echo "| Exact key search not found:       |";

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
  printf(" % 6d /sec. |\n",5 * $c / (microtime(true) - $ts));
  echo "├───────────────────────────────────┼─────────────┤\n";

}


if(true){
  echo "| Wildcard key search 2 hits:       |";

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
  printf(" % 6d /sec. |\n",5 * $c / (microtime(true) - $ts));
  echo "├───────────────────────────────────┼─────────────┤\n";
}

if($delete){
  echo "| Wildcard key delete 2 hits:       |";

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
  printf(" % 6d /sec. |\n",5 * $c / (microtime(true) - $ts));
  echo "├───────────────────────────────────┼─────────────┤\n";
}

if($delete){
  echo "| Excat key delete 1 hits:          |";

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

  echo "├───────────────────────────────────┼─────────────┤\n";

  $c--;
  printf(" % 6d /sec. |\n",5 * $c / (microtime(true) - $ts));
  echo "├───────────────────────────────────┼─────────────┤\n";
}

if($delete){
  echo "| Delete collection:                |";

  $id = 1;
  $ts = microtime(true);
  $result = $rs->delete($collection);
  if(!empty($result['error']) || $result['count'] == 0){
    echo "Failed: ";
    print_r($result);
    exit;
  }
  echo $result['count'] / (microtime(true) - $ts) ."/sec.\n";
  printf(" % 6d /sec. |\n",5 * $c / (microtime(true) - $ts));
}

echo "└───────────────────────────────────┴─────────────┘\n";
