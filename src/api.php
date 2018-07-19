<?php
    require './config.php'; 
    
 ?>
 <p>
 admin user:<? echo $admin_user ?>
 </p>
 <p>
    post=<pre><? 
    $post=array(_id=>'post01',_rev=>'2-xxx',title=>'post #1');
    echo json_encode($post);
    ?></pre>

    insert sql ('<?
        $sql='insert into posts(id,doc) values(?,?)';
        echo $sql;
    ?>')

 </p>
 <p>
    <?php
        $sql="select * from posts";
    ?>
    query the db ('<? echo $sql ?>')
    <?
    $data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    var_export($data);
    /*
    array (
      0 => array('John'),
      1 => array('Mike'),
      2 => array('Mary'),
      3 => array('Kathy'),
    )*/
    ?>
<p>