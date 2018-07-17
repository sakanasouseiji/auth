<?php
define("authKey","sakanasouseiji");
require_once("./lib/auth.php");
$auth=new auth();


if($auth->autoLogin()){
	print "login成功";
	exit();
}
if(	$_SERVER["REQUEST_METHOD"]=="POST"	){
	if(	isset($_POST["myID"])	){
		$id=$_POST["myID"];
		$password=$_POST["myPassword"];
		if($auth->manualLogin($id,$password)){
			print "ログイン完了";
			exit();
		}else{
			print "ログイン失敗";
		}
	}
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<body>
<form action="" method="POST" id="myForm">
	<label>id入力<label><input name="myID" type="text">
	<label>password入力<label><input name="myPassword" type="text">
	<label>完了</label><input name="kanryo" type="submit"> 
</form>
</body>
</html>
