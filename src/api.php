<?php
require './config.php'; 
define("FUTURE_TIME",'9999-12-31');
$milliseconds = round(microtime(true) * 1000);
$d=date("Y-m-y H:i:s",microtime(true)) . '.' . $milliseconds;
define("CURRENT_TIME",$d);
$data;
$data['timeish']=$d;
//main
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$input=json_decode($HTTP_RAW_POST_DATA);
	$doc=$input->doc;
	$table=preg_replace('/[^a-zA-Z0-9]/','',$input->table);
	$data= array('table' => $table, 'messages' =>'','doc'=>$doc);
	save_doc($doc,$table);
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$data['messages']='GET "' . $_GET['_id'] . '" from '. $_GET['table'];
	$id=$_GET['_id'];
	$table=preg_replace('/[^a-zA-Z0-9]/','',$_GET['table']);
	$data['doc']=get_doc($id,$table);
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
		$sql="insert into $table (_id,_rev,doc,valid_from,valid_to) values(?,?,?,?,?)";
		$data['messages'] .= "new doc\nsql \"$sql\"";
		$docstring=json_encode($doc);
		
		$rev='1-' . sha1($docstring);
		
		$pdo->prepare($sql)->execute([
			$doc->_id,
			$rev,
			json_encode($doc),
			CURRENT_TIME,
			FUTURE_TIME
		]);
		$data['_rev']=$rev;
		$data['_id']=$doc->_id;
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
	try {
		$retval;
		$sql="select * from $table where _id = ? and valid_from <= ? and valid_to > ?";
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
		return $retval;
	} catch (PDOException $e) {
		if ($e->getCode() == '42S02') {
			$data->messages="create table $table\n";
			$sql="create table $table(_id varchar(255) not null,_rev varchar(255) not null,doc longtext,valid_from datetime not null,valid_to datetime not null, primary key (_id,valid_to))";
			$pdo->exec($sql);
		}
	}
}
function delete_doc($id,$rev,$table) {
	global $pdo, $data;
	$old_doc = get_doc($id,$table);
	if ($old_doc->_rev == $rev) {
		$sql="update $table set valid_to=? where _id=? and _rev=? and valid_to=?";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute([
			CURRENT_TIME,
			$id,
			$rev,
			FUTURE_TIME
		]);
		$deleted = $stmt->rowCount();
		$data['messages'].="\ndeleted doc '$id' ($deleted)\n";
	} else {
		http_response_code(409);
		$data['http_response_code']=http_response_code();
		$data['error'] = '_rev does not match';
	}
}