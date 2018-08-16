# minimal-lamp-api
A document store api

Use this CRUD endpoint on a LAMP server (Linux/Apache/PHP/MySQL) if you are writing a javascript application and just need to store, retrieve, and update documents.

## Getting Started
This project assumes you already have a LAMP server and have php installed and a mysql (or other PDO supported) database. If you are using a NodeJS/Webpack workflow, see [minimal-lamp-api-react](https://github.com/mosphei/minimal-lamp-api-react/) for an example of how to do this.

1. Clone (`git clone https://github.com/mosphei/minimal-lamp-api.git`) 
or [download](https://github.com/mosphei/minimal-lamp-api/archive/master.zip) this project 
2. Rename config.php.sample to config.php
3. Enter database information into config.php
```javascript
    $db ='name_of_database';
    $user = 'username';
    $host = 'dbserver.example.com';
    $pass = 'correcthorse';
```
## Usage
Here is a quick introduction to using the api.

### Save a document
Documents must have an _id property. They can be retrieved using this property, and when multiple documents are returned they will be sorted by _id. Here is an example using fetch.

Save a document.
```javascript
var doc={
    _id:'example doc',
    stringProperty:'ABC',
    numericProperty:123,
    bool:true
};
var table='misc_docs';
fetch('api.php',{
    method:'POST',
    body:JSON.stringify({doc:doc,table:table})
})
.then(res => res.json())
.then(item => {
    console.log('got',item);
    console.log('saved document revision number='+item._rev);
});
```
Retrieve a document
```javascript
var _id='example doc';
fetch('api.php?_id='+encodeURIComponent(_id)+'&table=misc_docs')
.then(res => res.json())
.then(item => {
    console.log('got document',item);
});
```

Retrieve all `misc_docs` documents
```javascript
fetch('api.php?table=misc_docs')
.then(res => res.json())
.then(res => {
    console.log('number of documents retrieved:'+res.docs.length);
    console.log('array of documents:',res.docs);
});
```

Search for documents with wildcard (%)
```javascript
fetch('api.php?table=misc_docs&search=example%')
.then(res => res.json())
.then(res => {
    console.log('number of documents retrieved:'+res.docs.length);
    console.log('array of documents:',res.docs);
});
```

## TODO
This project is under active development and still has a lot left to implement

1. start/end for multiple documents
2. undo operations/document history
3. add indexed fields
4. compact/drop tables
