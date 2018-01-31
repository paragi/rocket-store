# Rocket Store
A simple but powerful database, utilising flat file storage.

Sometimes you just need to store and retrieve data in a simple and relaiable manner, without the need for a separate database process. Its amazing how often a storage tasks can be accomplished, using the filesystem and 3 very simple, but versatile function: POST, GET and DELETE.
Its the fastest way to store data outside RAM. Its lightning fast compared to a fullblown database. All packaged in a single file to include, without any dependencies.

However, if you need the superior search and storage power of a real SQL RDBM, this simple tool does not compare in functionallity.

## Features:
* Extremely fast
* Very reliant
* Very little footprint.
* Very flexible.
* No dependencies 
* Works without configuration or setup.
* Records (arrays) are stored in editable text files.

Contra indications
* No record locking
* No linked relations
* No NULL values
* No duplicate keys
* No full table scan search
* No complex search facilities

## Introduction
You can POST, GET or DELETE one or more records in a collection.

If you are used to SQL you can relate to this:
storage area = database
collection = table
record = row
However the terms are very lose and flexible, compared to SQL. 

### Record
A record is an arbitrary chunk of data in the form of a PHP array or a scalar variable. There are no fixed structure or definition. They don't have to be alike at all.
(It is a loose equivalent of a SQL row)

### Collection  
Records are grouped into named collections. There can be as many records in a collection and collections in your data storage area as your filesystem can handle. >1.000.000
(It's like a SQL table)

### Keys
All records must have a unique key. A Key is a string of “legal characters” It is often a combination of values from the record, or an auto incremented sequence, or both.
Wildcards can be used in GET and DELETE operations.

### Storage area
A storage area is the directory where data are stored.
(It's almost the same as a SQL database name)
    
Technically records are compacted data, stored in files, who's name is the key, and placed in a collection, which is a subdirectory, of a data storage area, which is the data storage root directory. 
Modern file system are optimised and cashed in clever ways, thus eliminating the need to invent cashing and other optimization.

---
## Usage
A simple case of storing records:
```php
// Initialize    
require "../src/rocket-store.php";
$rs = new Paragi\RocketStore();

// POST a record
$rs->post("cars", "Mercedes-Benz GT R", ["owner" => "Lisa Simpson"]);
  
// GET a record
print_r(
    $rs->get("cars", "Mercedes*")
);
```
    
The above example will output this:

    [error] => 
    [result] => Array (
            [Mercedes-Benz GT R] => Array (
                    [owner] => Lisa Simpson
            )
        )
    [count] => 1


Keys must always be unique. If you have more than one instance of a key, you can add an auto incremented sequence to the key:

```php
$rs->post("cars", "BMW-740li",[ "owner" => "Greg Onslow"], RS_ADD_AUTO_INC);
$rs->post("cars", "BMW-740li", ["owner" => "Sam Wise"], RS_ADD_AUTO_INC);
$rs->post("cars", "BMW-740li", ["owner" => Bill Bo"], RS_ADD_AUTO_INC);

print_r(
    $rs->get("cars", "*")
);
```
    
The above example will output this:

    [error] => 
    [result] => Array(
            [1-BMW-740li] => Array (
                    [owner] => Greg Onslow
                )
            [2-BMW-740li] => Array (
                    [owner] => Sam Wise
                )
            [3-BMW-740li] => Array (
                    [owner] => Bill Bo
                )
        )
    [count] => 3

---    
## Post
The post operation inserts or if it already exists, updates a record.
```php
array post(string $collection [, string $key [, mixed $document [, int $flags = 0 ]]] )
```

#### Collection
Name of collection, that the record is insertet into. 
The name is any string of "legal charakters"
If the collection does not exists, it is created.
    
#### Key
The key is any string of "legal charakters", used to identify and find the record.
The key to each record must be unique NOT NULL. If a key already exists, it's assumed that the post operation is an updated version of the existing record.
If a null key is given, an auto incremented sequence is used (or created) as key.
    
#### Flags
    Valid flags: 
        * RS_ADD_AUTO_INC - Add an auto incremented sequence to the beginning of the key
                  
#### Return
Returns an array containing the result of the operation:
    
    * error: string Empty or containing an error message i the operation failed.
    * key:   string containing the actual key used
    * count: number of affected records
    
#### Example
##### Create or updates a record with the given key, appended with an auto incremented sequence
```php
$rs->post("cars", "BMW-740li", ["owner" => "Greg Onslow"], RS_ADD_AUTO_INC);
```

##### Mass insert 
```php
$dataset = [
     "Gregs-BMW-740li" => ["owner" => "Greg Onslow"]
    ,"Lisas-Mercedes-Benz GT R" => ["owner" => "Lisa Simpson"]
    ,"Bills-BMW-740li" => ["owner" => "Bill Bo"]
];

foreach($dataset as $key => $record)
    $rs->post("cars", $key, $record);
```

---
## Get
Search the given collection for one or more records, whos key match the querry. 
```php
array get( [string $collection [, string $querry [, int $flags = 0 [, int t_min [, int t_max ]]]]]] )
```
#### Collection
Name of collection, that is searched. If null, the get method return a list of collections and sequences matching the querry.
    
#### Querry
Is the full key to a record, or mixed in with wildcards '*' and '?'. If the querry in null, it's the equvivalent of '*'
    
NB: wildcards are very expensive on large datasets, with most filesystems. 
(on a regular PC with +10 mill records in the collection, it might take up to a second to retreive one record, where as one might retreive 20.000 with an exact key match, in the same time)
    
#### Flags
    Valid flags: 
        * RS_ORDER - Results returned are ordered alphabetically acending.
        * RS_ORDER_DESC - Results returned are ordered alphabetically decending.
                  
#### Return
Returns an array containing the result of the operation:
    
* error: string Empty or containing an error message i the operation failed.
* count: number of affected records
* result: an array of records, where the index is the full key to the record.
    
#### Example
##### Get records with matching keys
```php
print_r(
    $rs->get("cars", "*BMW*")
);
```
The above example might output this:

    [error] => 
    [result] => Array(
            [1-BMW-740li] => Array (
                    [owner] => Greg Onslow
                )
            [3-BMW-740li] => Array (
                    [owner] => Bill Bo
                )
        )
    [count] => 2

##### Get list ordered by alphabetically decending keys
```php
$rs->get("cars", "*BMW*", RS_ORDER_DESC);
```

##### Get list of collections and sequences
```php
$rs->get();
```
---
## Delete
Search for one or more records, whos key match the querry. and delete them.
Can also be used to delete a whole collection with its sequences and the entire database.

```php
array delete( [string $collection [, string $querry ]] )
```
#### Collection
Name of collection, that is searched. If null, the delete method will delete from the list of whole collections. I the query is also null, the entire database is deleted.
    
#### Query
Is the full key to a record, or mixed in with wildcards '*' and '?'. If the query in null, it's the equivalent of '*'

NB. Be cautious with the delete method; illegal characters are striped away. This could result in a null string, which could delete the entire database.
There are no warnings.

#### Return
Returns an array containing the result of the operation:
    
 * error: string Empty or containing an error message i the operation failed.
 * count: number of affected records, whole collection and sequences.
    
#### Example
##### Delete matching records from a collection
```php
$rs->delete("cars", "*BMW*");
```
The above example might output this:
```php
    [error] => 
    [count] => 2
```
    
##### Delete a whole collection
```php
$rs->delete("cars");
```

##### Delete the entire database
```php
$rs->delete();
```
---
## Legal characters
keys, queries and collections is a string of printable characters excluding: 
'|' '<' '>' '~' '&' '..' and the directory separator '/' or '\' and ':' on windows.
'*' and '?' are allowed in queries.
All other characters are striped from the string.
Limitations on other charakters and lenght is imposed by the filesystem

---
## Configuring
Configuration options are set with an array passed to the constructor.
The array can have these options:

|index name|values|
|---|---|
|data_storage_area | The directory where the database resides. The default is to use the temporary directory provided by the operating system. If that doesn't work, the DOCUMENT_ROOT directory is used. |
|data_format       | Specify which format the records are stored in, on the file system. Values are: RS_FORMAT_PHP - default. Use the very fast php serialization mechanism. and RS_FORMAT_JSON - Use JSON data format.
|

#### Example
##### Set data storage directory and file format to JSON
```php
$options = [
   "data_storage_area" => "/home/simon/webapp"
  ,"data_format"       => RS_FORMAT_JSON
];

$rs = new paragi/rocket-store($options);
```
---
## Installation
All you need to do is to download the file 'rocket-store.php' and include it to your script.

#### To use composer:
Install the package. [Composer](http://getcomposer.org/)

Execute the command: `composer require paragi/rocket-store`

To modify the `composer.json` manually, add `"paragi/rocket-store" : "^0.5"` to your `required`

---
## Benchmarks

The test is performed with 1 million records in in a single collection. 

|System | Mass insert | exact key search | wildcard search | no hit | delete |
|---|---|---|---|---|---|
|System: i7 3rd gen on SSD |35000/sec.|25000/sec.|2.2/sec.|200000/sec.|25000/sec.|

---
## Remarks
This class was made to optimize resources in an embedded project, with limited resources. But it quickly became apparent, that a generalized interface to the file systems awesome cashing capabilities was very useful in other areas as well. 
It's all to easy to stick with your stack. Even when you don't need its complexity. That approach can be taxing on both the global environment and very local economy, as bloated software use much more power. 
The goal of this project is to make it very simple to use the file system for storage purposes, with a few very versatile methods.

---   
## Contributions
contributions of all kind are highly appreciated. 
Don't hesitate to submit an issue on github. But please provide a reproducible example.

Code should look good and compact, and be covered by a test case or example.
Please don't change the formatting style laid out, without a good reason. I know its not the most common standard, but its rather efficient one.

