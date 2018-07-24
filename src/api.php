<?php
    require './config.php'; 
	define("FUTURE_TIME",'9999-12-31');
	
    $input=json_decode($HTTP_RAW_POST_DATA);
	$doc=$input->doc;
	$table=preg_replace('/[^a-zA-Z0-9]/','',$input->table);
	//save the doc
	echo "doc _rev=". $doc->_rev." : ";
	$old_doc;
	try {
		$sql="select * from $table where _id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$doc->_id]);
		while ($row = $stmt->fetch()) {
			$old_doc= $row['doc'];
		}
	} catch (PDOException $e) {
		if ($e->getCode() == '42S02') {
			echo "create table $table\n";
			$sql="create table $table(_id varchar(255) not null,doc longtext,valid_from datetime not null,valid_to datetime not null, primary key (_id,valid_to))";
			$pdo->exec($sql);
		}
	}
	if ($old_doc->_rev == $doc->_rev) {
		if (!$doc->_rev) {
			//new document
			$sql="insert into $table(id,doc,valid_from,valid_to) values(?,?,now(),?)";
			echo "new doc\nsql \"$sql\"";
		} else {
			//update
			$sql="update $table set ";
			echo "update doc\nsql \"$sql\"";
		}
	} 
 ?>
 <p>
 input doc:<? echo json_encode($doc) ?> <br />
 input table <?echo $table ?>
 </p>