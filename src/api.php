<?php
require './config.php'; 
define("FUTURE_TIME",'9999-12-31');
$milliseconds = round(microtime(true) * 1000);
$d=date("Y-m-y H:i:s",microtime(true)) . '.' . $milliseconds;
define("CURRENT_TIME",$d);
$data;
$data['CURRENT_TIME']=$d;
//main
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$raw_post_data=file_get_contents("php://input");
	$input=json_decode($raw_post_data);
	$table=preg_replace('/[^a-zA-Z0-9]/','',$input->table);
	if ($input->doc) {
		//save a document
		$doc=$input->doc;
		$data= array('table' => $table, 'messages' =>'','doc'=>$doc);
		save_doc($doc,$table);
	} elseif ($input->docs) {
		//save multiple docs
		$data=array('table'=>$table,'messages'=>"multiple docs\n",'ids'=>array());
		foreach($input->docs as $doc) {
			save_doc($doc,$table);
			$data['ids'][]=array(
				'_id'=>$data['_id'],
				'_rev'=>$data['_rev']
			);
		}
	}
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$table=preg_replace('/[^a-zA-Z0-9]/','',$_GET['table']);
	if ($_GET['_id']) {
		$id=$_GET['_id'];
		$data['messages']='GET "' . $_GET['_id'] . '" from '. $_GET['table'];
		$data['doc']=get_doc($id,$table);
	} else {
		$query='%';
		if ($_GET['search']) {
			$query=$_GET['search'] . '%';
		}
		$data['docs']=search_docs($query,$table);
	}
} else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
	$id=$_GET['_id'];
	$table=$_GET['table'];
	$rev=$_GET['_rev'];
	$body=file_get_contents('php://input');	
	if ($body) {
		$input=json_decode($body);
		$doc=$input->doc;
		$id=$doc->_id;
		$rev=$doc->_rev;
		$table=preg_replace('/[^a-zA-Z0-9]/','',$input->table);
	}
	$data['messages']="delete doc '$id'($rev) from $table";
	delete_doc($id,$rev,$table);
}

header('Content-Type: application/json');
echo json_encode($data);

function save_doc($doc,$table) {
	global $pdo, $data;
	$old_doc = get_doc($doc->_id,$table);
	$data['old_doc'] = $old_doc;
	//echo json_encode($old_doc);
	if (!$old_doc && !$doc->_rev) {
		//insert
		
		$sql="insert into `$table` (_id,_rev,doc,valid_from,valid_to) values(?,?,?,?,?)";
		$data['messages'] .= "new doc\nsql \"$sql\"";
		$docstring=json_encode($doc);
		
		$rev='1-' . sha1($docstring);
		try {
			$pdo->prepare($sql)->execute([
				$doc->_id,
				$rev,
				json_encode($doc),
				CURRENT_TIME,
				FUTURE_TIME
			]);
		} catch (PDOException $e) {
			//create the table
			if ($e->getCode() == '42S02') {
				$data->messages="create table $table\n";
				$createsql="create table $table(_id varchar(255) not null,_rev varchar(255) not null,doc longtext,valid_from datetime not null,valid_to datetime not null, primary key (_id,valid_to))";
				$pdo->exec($createsql);
				//try the insert again
				$pdo->prepare($sql)->execute([
					$doc->_id,
					$rev,
					json_encode($doc),
					CURRENT_TIME,
					FUTURE_TIME
				]);
			} else {
				throw $e;
			}
		}
		$data['_rev']=$rev;
		$data['_id']=$doc->_id;
		http_response_code(201);
		
	} else if ($old_doc->_rev == $doc->_rev) {
		//update
		//calculate new rev
		$rev_number=$doc->_rev + 1;
		unset($doc->_rev);
		$docstring .= json_encode($doc);
		$docstring=json_encode($doc);
		$rev = $rev_number . '-' . sha1($docstring);
		//delete old doc
		$sql="update $table set valid_to=? where _id=? and _rev=? and valid_to=?";
		$pdo->prepare($sql)->execute([
			CURRENT_TIME,
			$doc->_id,
			$old_doc->_rev,
			FUTURE_TIME
		]);
		$sql="insert into $table (_id,_rev,doc,valid_from,valid_to) values(?,?,?,?,?)";
		$data['messages'] .= "update doc\nsql \"$sql\"";
		$pdo->prepare($sql)->execute([
			$doc->_id,
			$rev,
			$docstring,
			CURRENT_TIME,
			FUTURE_TIME
		]);
		$data['_rev']=$rev;
		$data['_id']=$doc->_id;
	} else {
		http_response_code(409);
		$data['http_response_code']=http_response_code();
		$data['error'] = '_rev does not match';
	}
}
function get_doc($id,$table) {
	global $pdo, $data;
	$retval;
	try {
		
		$sql="select * from $table where _id = ? and valid_from <= ? and valid_to > ? order by _id";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([
			$id,
			CURRENT_TIME,
			CURRENT_TIME
		]);
		while ($row = $stmt->fetch()) {
			$retval = json_decode($row['doc']);
			$retval->_id=$row['_id'];
			$retval->_rev=$row['_rev'];
		}
		$count = $stmt->rowCount();
		$data['messages'].="\nfound $count docs ('$id')\n";
		if ($count==0) {
			http_response_code(404);
		}
		
	} catch (PDOException $e) {
		if ($e->getCode() == '42S02') {
			
		}
	}
	return $retval;
}
function delete_doc($id,$rev,$table) {
	global $pdo, $data;
	$old_doc = get_doc($id,$table);
	if ($old_doc->_rev == $rev) {
		$sql="update $table set valid_to=? where _id=? and _rev=? and valid_from <= ? and valid_to > ?";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute([
			CURRENT_TIME,
			$id,
			$rev,
			CURRENT_TIME,
			CURRENT_TIME
		]);
		$deleted = $stmt->rowCount();
		$data['messages'].="\ndeleted doc '$id' ($deleted)\n";
	} else {
		http_response_code(409);
		$data['http_response_code']=http_response_code();
		$data['error'] = '_rev does not match';
	}
}
function search_docs($query,$table) {
	global $pdo, $data;
	$retval=array();
	$sql="select * from $table where _id like ? and valid_from <= ? and valid_to > ? order by _id";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		$query,
		CURRENT_TIME,
		CURRENT_TIME
	]);
	while ($row=$stmt->fetch()) {
		$doc=json_decode($row['doc']);
		$doc->_id=$row['_id'];
		$doc->_rev=$row['_rev'];
		$retval[]=$doc;
	}
	$count = $stmt->rowCount();
	if ($count == 0) {
		//do a full text search 
		$sql="select * from $table where doc like ? and valid_from <= ? and valid_to > ? order by _id";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([
			$query,
			CURRENT_TIME,
			CURRENT_TIME
		]);
		while ($row=$stmt->fetch()) {
			$doc=json_decode($row['doc']);
			$doc->_id=$row['_id'];
			$doc->_rev=$row['_rev'];
			$retval[]=$doc;
		}
	}
	return $retval;
}