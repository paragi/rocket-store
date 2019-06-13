# Rocket Store
Using the filesystem as a simple searchable database.
It's lightning fast compared to a fullblown database.
All packaged in a single file to include, without any dependencies.
However, if you need the superior search and storage power of a real SQL RDBM, this simple tool does not compare.

## Features:
* Extremely fast
* Very reliant
* Very little footprint.
* Very flexible.
* No dependencies
* Works without configuration or setup.
* Records (arrays) are stored in editable text files.
* Very configurable
* Also available for node in javascript

## Installation
All you need to do is to download the file 'rocket-store.php' and include it to your script.

#### To use composer:
Install the package. [Composer](http://getcomposer.org/)

Execute the command: `composer require paragi/rocket-store`

To modify the `composer.json` manually, add `"paragi/rocket-store" : "^0.5"` to your `required`

## Functions overview
### Post
Stores a record, in <filename\>, in <directory\> in the [storage area](#storage-area).
```php
post(string <directory\>,string <filename\>,array | scalar <data\> [, integer options])
```
* Directory name to contain the collection of file records.
* File name of the record
No path separators or wildcards are allowed in directory or file names
* Options:
  * RS_ADD_AUTO_INC:  Add an auto incremented sequence to the beginning of the file name

Returns an array containing the result of the operation:
* error : string Empty or containing an error message i the operation failed.
* key:   string containing the actual file name used
* count : number of files affected (1 on succes)

If the file already exists, the record will be replaced.

Subdirectories and full path is not supported. Path separators and other illigal charakters are silently striped off.

### Get
Find an retrieve a record, in <filename\>, in <directory\> in the [storage area](#storage-area).
```php
get([string <directory\> [,string <filename with wildcards\> [integer <option flags]]]])
```
* Directory name to search. If no directory name is given, get will return at list of collections (Directories)
* File name to search for. Can be mixed with wildcards '\*' and '?'. If no file name is given, it's the equvivalent of '*'
* Options:
  * RS_ORDER       : Results returned are ordered alphabetically acending.
  * RS_ORDER_DESC  : Results returned are ordered alphabetically decending.

Return an array of
* error : error message if any or NULL
* count : number of files read
* key   : array of records

NB: wildcards are very expensive on large datasets, with most filesystems.
(on a regular PC with +10 mill records in the collection, it might take up to a second to retreive one record, where as one might retreive 20.000 records with an exact key match, in the same time)

### Delete
Search for one or more files, whos name match the querry. and delete them.

```php
delete([string <directory> [,string <filename with wildcards>]])
```
* Directory name to search. If no directory name is given, **all data are deleted!**
* File name to search for. Can be mixed with wildcards '\*' and '?'. If no file name is given, **all files in a directory are deleted!**

Return an array of
* error : error message if any or NULL
* count : number of files or directories affected


Can also be used to delete a whole collection with its sequences and the entire database.

### Configuring
Configuration options is set with an array passed to the constructor.
The array can have these options:

#### Set data storage directory and file format to JSON
```php
$options = [
   "data_storage_area" => "/home/simon/webapp"
  ,"data_format"       => RS_FORMAT_JSON
];
$rs = new paragi/rocket-store($options);
```

|index name|values|
|---|---|
|data_storage_area | The directory where the database resides. The default is to use the temporary directory provided by the operating system. If that doesn't work, the DOCUMENT_ROOT directory is used. |
|data_format       | Specify which format the records are stored in, on the file system. Values are: RS_FORMAT_PHP - default. Use the very fast php serialization mechanism. and RS_FORMAT_JSON - Use JSON data format.
|



## Usage
#### Storing records:
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

#### Inserting an auto inceremented key
File names must always be unique. If you have more than one instance of a file name, you can add an auto incremented sequence to the name:

```php
$rs->post("cars", "BMW-740li",[ "owner" => "Greg Onslow"], RS_ADD_AUTO_INC);
$rs->post("cars", "BMW-740li", ["owner" => "Sam Wise"], RS_ADD_AUTO_INC);
$rs->post("cars", "BMW-740li", ["owner" => "Bill Bo"], RS_ADD_AUTO_INC);

print_r(
    $rs->get("cars", "*")
);
```

The above will output this:

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

#### Inserting with Globally Unique IDentifier key
Another option is to add a GUID to the key.
The GUID is a combination of a timestamp and a random sequence, formatet in accordance to  RFC 4122 (Valid but slightly less random)

If ID's are generated more than 1 millisecond apart, they are 100% unique.
If two ID's are generated at shorter intervals, the likelyhod of collission is up to 1 of 10^18.
```php
$rs->post("cars", "BMW-740li",[ "owner" => "Greg Onslow"], RS_ADD_AUTO_INC);
$rs->post("cars", "BMW-740li", ["owner" => "Sam Wise"], RS_ADD_AUTO_INC);
$rs->post("cars", "BMW-740li", ["owner" => "Bill Bo"], RS_ADD_AUTO_INC);

print_r(
    $rs->get("cars", "*")
);
```

The above will output this:

    [error] =>
    [result] => Array(
            [16b511bc-1cf0-4000-867a-e3a22c073280-BMW-740li] => Array (
                    [owner] => Greg Onslow
                )
            [16b50c59-1598-4000-83c7-679b458c2730-BMW-740li] => Array (
                    [owner] => Sam Wise
                )
            [16b50c59-263c-4000-872b-f31ba07b97f0-BMW-740li] => Array (
                    [owner] => Bill Bo
                )
        )
    [count] => 3


#### Mass insterts
```php
$dataset = [
     "Gregs-BMW-740li" => ["owner" => "Greg Onslow"]
    ,"Lisas-Mercedes-Benz GT R" => ["owner" => "Lisa Simpson"]
    ,"Bills-BMW-740li" => ["owner" => "Bill Bo"]
];

foreach($dataset as $key => $record)
    $rs->post("cars", $key, $record);
```

#### Get records with matching keys
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
#### Delete matching records from a collection
```php
$rs->delete("cars", "*BMW*");
```
The above example might output this:
```php
    [error] =>
    [count] => 2
```

#### Delete a whole collection
```php
$rs->delete("cars");
```

#### Delete the entire database
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
## Benchmarks

The test is performed with 1 million records in in a single collection.

|System | Mass insert | exact key search | wildcard search | no hit | delete |
|---|---|---|---|---|---|
|System: i7 3rd gen on SSD |69000/sec.|87000/sec.|14.6/sec.|123000/sec.|325/sec.|
|Raspbarry Pi Zero|561/sec.|96/sec.|0.27/sec.|147/sec.|0.29/sec.|


---
## Remarks
This class was made to optimize resources in an embedded project, with limited resources. But it quickly became apparent, that a generalized interface to the file systems awesome cashing capabilities was very useful in other areas as well.
It's all to easy to stick with your stack. Even when you don't need its complexity. That approach can be taxing on both the global environment and very local economy, as bloated software use much more power.
The goal of this project is to make it very simple to use the file system for storage purposes, with a few very versatile methods.

---   
## Contributions
* Contributions of any kind are highly appreciated.
* Don't hesitate to submit an issue on github. But please provide a reproducible example.
* Code should look good and compact, and be covered by a test case or example.
* Please don't change the formatting style laid out, without a good reason. I know its not the most common standard, but its rather efficient one.
