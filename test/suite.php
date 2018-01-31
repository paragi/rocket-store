<?php
/*============================================================================*\
  Rocket store - test suite
  
  (c) Simon Riget 2017
  License MIT
\*============================================================================*/      
require "../src/rocket-store.php";
$rs = new Paragi\RocketStore();

//  Test validation function
$test = ["count" => 0, "failed" => 0];
function test($name, $result, $count = null, $must_fail = false){
    global $test;
    if( (!empty($count) and $result['count'] != $count) 
        or ($must_fail xor !empty($result['error'])) ) {
        $test['failed']++;
        echo "Test: $name - failed:\n";
        print_r($result);
    }else
        echo "Test: $name - OK\n";
    $test['count']++;
}

// Test data
$record = [
     "id" => "22756"
    ,"name"  => "Adam Smith"
    ,"title" => "developer"
    ,"email" => "adam@smith.com"
    ,"phone" => "+95 555 12345"
    ,"zip"   => "DK4321"
    ,"country" => "Distan"
    ,"address" => "Elm tree road 555"  
];
$collection = "test";

// start testing
echo str_repeat("=",80) . "\n";

$dir = realpath("." . DIRECTORY_SEPARATOR);
$save = $rs->data_storage_area;
$rs->__construct(["data_storage_area" => $dir]);
test("Set data storage area"
    ,["error" => $rs->data_storage_area == $dir . DIRECTORY_SEPARATOR . "rocket_store" . DIRECTORY_SEPARATOR ? "" : "Data storage ares directory: '$dir' != $rs->data_storage_area" ]
);
$rs->data_storage_area = $save;

test("Post"
    ,$rs->post($collection,"{$record['id']}-{$record['name']}",$record)
    ,1
);

test("Create sequence"
    ,["error" => "", "count" => $rs->sequence("{$collection}_seq") ]
    ,1
);

test("Post with empty key -> auto incremented key"
    ,$rs->post($collection,"",$record)
    ,1
);

test("Post with auto incremented key only"
   ,$rs->post($collection,"",$record,RS_ADD_AUTO_INC)
);

test("Post with auto increment added to key"
    ,$rs->post($collection,"{$record['name']}",$record,RS_ADD_AUTO_INC)
    ,1
);

test("Get with exact key"
    ,$rs->get($collection,"{$record['id']}-{$record['name']}")
    ,1
);

test("Get with wildcard in key"
    ,$rs->get($collection,"22*-{$record['name']}")
    ,1
);

test("Get exact key no hit"
    ,$rs->get($collection,"{$record['id']}-{$record['name']}X")
    ,0
);

test("Get wildcard in key with no hit"
    ,$rs->get($collection,"*-{$record['name']}X")
    ,0
);

$record['id']++;
$rs->post("$collection?<|>*\":&~\x0a","{$record['id']}-{$record['name']}",$record);
test("Post invalid collection"
    ,$rs->get($collection,"{$record['id']}-{$record['name']}")
    ,1
);

$record['id']++;
$rs->post($collection,"{$record['id']}-?<|>*\":&~\x0a{$record['name']}",$record);
test("Post invalid key"
    ,$rs->get($collection,"{$record['id']}-{$record['name']}")
    ,1
);

test("Get a list"
    ,$rs->get($collection,"*")
    ,6
);

test("Get a list entire collection"
    ,$rs->get($collection)
    ,6
);

test("Get a list of collections and sequences"
    ,$rs->get()
    ,2
);


test("Get list of matching collections"
    ,$rs->get(null, "*_seq")
    ,1
);


test("Delete record with exact key"
    ,$rs->delete($collection,"{$record['id']}-{$record['name']}")
    ,1
);


// Test order_by flags
// test time limits
// test Json and XML


// Make some delete fodder
$rs->post($collection."1","",$record);
$rs->post($collection."2","",$record);
$rs->post($collection."3","",$record);

test("Delete collection"
    ,$rs->delete($collection ."1")
    ,1
);

test("Safe delete ../*"
    ,$rs->delete(
          $collection ."2"
        . DIRECTORY_SEPARATOR
        . ".."
        . DIRECTORY_SEPARATOR
        . "*"
    )
    ,1
);

test("Safe delete ~/*"
    ,$rs->delete(
          $collection ."3"
        . DIRECTORY_SEPARATOR
        . ".."
        . DIRECTORY_SEPARATOR
        . "~/*"
    )
    ,1
);

test("Delete sequence along with collection"
    ,["error" => "", "count" => $rs->sequence("{$collection}1_seq") ]
    ,1
);

test("Safe delete all"
    ,$rs->delete()
    ,7
);

// Summarize test results
echo str_repeat("=",80) . "\n";
echo "{$test['count']} Tests performed: ";
echo $test['failed'] ? "{$test['failed']} failed\n" : "With success\n" ;

