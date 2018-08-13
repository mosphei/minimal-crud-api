# minimal-crud-api
A document store api

Use this CRUD endpoint on the server end if you are writing a javascript application and just need to store, retrieve, and update documents.

## Getting Started
This project assumes you already have a LAMP server and have php installed and a mysql (or other PDO supported) database.

1. Rename config.php.sample to config.php
2. Enter database information into config.php
```
    $db ='name_of_database';
    $user = 'username';
    $host = 'dbserver.example.com';
    $pass = 'correcthorse';
```

## TODO
This project is under active development and still has a lot left to implement

1. get multiple documents
2. undo operations/document history
3. add indexed fields
4. compact/drop tables