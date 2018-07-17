<?php
$getFiles=get_included_files();
if(	array_shift($getFiles)===__FILE__	){
	exit("直接アクセス禁止");
}

//呼び出し先で
//define("hoge")を設定しておく
//defined("hoge") or exit("アクセスが許可されていません");
//
//自動ログインを行うlib
//auth
//仕様
//auth(__construct)
//	ログインテーブル,
//	トークンテーブル,
//	ログテーブルの存在確認を行う
//	各テーブルがない場合は作成する
//
//auth->manualLogin(ID,myPass)
//	新規ログイン者のuserName,userPasswordを受け取り
//	ログインテーブルに新規にデータを登録する
//	トークンテーブルに新規データを記録する
//	ログテーブルに新規データを追加する
//
//auth->autoLogin()
//	トークンをloginテーブル,tokenテーブルと照会し成否を返す
//	トークンは再発行し、tokenTableも書き直す
//
//auth->logout(破棄したいuserName)
//	トークンがあれば破棄
//	セッション破棄
//	tokenTableの該当データをdelete
//auth->adminCheck()
//
//
//
//追加予定
//auth->ID()
//現在ログイン中のIDを返す。
//
//log記録機能
//全メソッドに関してlogを記録して残す。
//一部ログテーブルに記録する機能があるが完成させる。
//
//auth->signup
//新規idの登録を行う
//
//auth->
//
//

//プロパティ
//	auth->PDO	PDO
//	auth->error	エラーメッセージprivate error()で内容が返る
//	auth->errorpar	エラーの詳細、だいたいquery$string、bindの内容を配列形式にしたものでで必ずあるとは限らない
class auth{
//	private $createTableNames=array("userTable","tokenTable","loginLogTable");
	private $createTableNames=array("tokenTable","logTable");

	//db_configの$userTableと同じもの
	private $userTableColum=array(
		"ID"=>"varchar(10)",
		"myName"=>"varchar(255)",
		"myNameHurigana"=>"varchar(255)",
		"myPassword"=>"varchar(255)",
		"myImage"=>"MEDIUMBLOB",
		"myBirthday"=>"date",
		"myBirthday_publication"=>"bool",
		"myAddress"=>"varchar(255)",
		"myAddress_publication"=>"bool",
		"myAllergy"=>"varchar(255)",
		"myPhoneNumber"=>"varchar(255)",
		"myPhoneNumberPublication"=>"bool",
		"myMobilePhoneNumber"=>"varchar(255)",
		"myMobilePhoneNumberPublication"=>"bool",
		"myMailAddress"=>"varchar(255)",
		"myMailAddress_publication"=>"bool",
		"myFamily"=>"text",
		"myFamilyPublication"=>"bool",
		"myComment"=>"text",
		"myAdmin"=>"bool",
		"myDelete"=>"bool"
	);
	private $tokenTableColum=array(
				"ID"=>"varchar(10)",
				"userName"=>"varchar(30)",
				"token"=>"varchar(300)",
				"expirationDate"=>"datetime"
			);
	//loginLogTableDB構成
	//eventについて
	//あった事象について文字列で記録
	//'login','logout','createAccount','loginError','createAccountError'
	private $logTableColum=array(
				"ID"=>"varchar(10)",
				"time"=>"datetime",
				"event"=>"varchar(20)"
	);
	public	$PDO;
	private $error=NULL;
	private $errorNo=0;

	//カラムを受け取り
	//ログインテーブル,トークンテーブル,ログインログテーブルを作成する
	//	二次元配列で二個目の値はデータ長、形式はすべてvarcharで処理される
	function __construct(){
		require(dirname(__FILE__)."/dbConfig.php");
		//接続
		try{
			$this->PDO=new PDO("mysql:host=".$host.";dbname=".$db,$user,$password);

			foreach($this->createTableNames as $createTableName){
				if(	!($this->createTable($createTableName))	){
					return;
				}
			}

			return;
			}
	
		//接続失敗				
		catch (PDOException $e) {
			    $this->error= "PDO connect error! " . $e->getMessage();
			    return false;
		}


	}

	//constructから呼び出される
	//テーブルの存在確認	→　なければ作る
	//テーブルのカラム確認	→	なければエラー
	//テーブルの作製
	private function createTable($tableName=null){
		require_once(dirname(__FILE__)."/dbConfig.php");
		if(	!(isset($tableName))	){
			$this->error="no param error";
			return false;
		}
		$tableColum=$tableName."Colum";
		//
		if(	!(is_array($this->$tableColum))	){
			$this->error="tableColumName error";
			return false;	
		}

		$length=count($this->$tableColum);	

		//テーブル確認
		$s="SHOW TABLES LIKE '".$tableName."'";
		$stmt=$this->PDO->query($s);
		if(!($stmt)){
			$this->error="SHOW TABLES LIKE '".$tableName."' error";
			return false;
		}
		$result=count($stmt->fetchAll());
		if(	!($result)	){
			//テーブル作製
			$innerString=$this->gattaiArray($this->$tableColum);
			$createQuery="CREATE TABLE ".$tableName."(".implode(" ",$innerString).")";
			if(	!($this->PDO->query($createQuery))	){
				$this->error="CREATE TABLE ".$tableName." error::".$createQuery;
				return false;	
			}
		}


		//カラム確認
		$numColum=count($this->$tableColum);
		$query="show columns from ".$tableName." where field in (".
			implode(" ",array_keys($this->$tableColum)).";";
		$stmt=$this->PDO->query($query);
		if(	!($stmt)	){
			$this->error="table DESC error::".$query;
			return false;
		}
		$result=$stmt->fetchAll();

		//テーブルカラム確認
		if(	count($result)>=$numColum	){
				$this->error="colum under error::".$query;
				return false;
		}

		//
		return true;
	}

	//マニュアルログイン
	function manualLogin($ID=null,$myPass=null){
		require_once(dirname(__FILE__)."/dbConfig.php");
		//textCheckにかける
		//半角英数5文字以上と大雑把にできている。formCheckでの事前チェック必須(というか気休め)
		if(	!($this->textCheck($ID))	||	!($this->textCheck($myPass))	){
			return false;
		}

		//
		$string="SELECT * FROM userTable where ID = ? ";
		$stmt=$this->PDO->prepare($string);
		if(	!($stmt)	){
			$this->error="loginName search prepare error";
			return false;
		}
		$stmt->bindvalue(1,$ID);
		$flag=$stmt->execute();
		if(	$flag===false	){
			$this->error="manualLogin execute error";
			return false;
		}
		$resultArray=$stmt->fetchall();
		if(	count($resultArray)>=2	){
			$this->error="many ID error";
			return false;
		}
		if(	count($resultArray)<=0	){
			$this->error="no ID error";
			return false;
		}
		//パスワードヴェリファイ
		if(	!(password_verify($myPass,$resultArray[0]["myPassword"]))	){
			$this->error="password_verify error!:".$myPass.":".$resultArray[0]["myPassword"];
			return false;
		}
	
		//ネームゲット
		$myName=$this->getName($ID);
		if(	!($myName)	){
			return false;
		}
		//トークン発行
		if(	!($this->issueToken($ID,$myName))	){
			return false;
		}
	
		return true;
	}



	//
	//textCheckメソッド
	//manualLoginからの呼び出し
	private function textCheck($string){
		//id&pass存在チェック
		if(	!(isset($string))	){
			$this->error="Id or pass nothing:".$string;
			return false;
		}
		
		//htmlspecialcharsチェック
		if(	$string!=htmlspecialchars($string)	){
			$this->error="manualLogin htmlspecialchars error";
			return false;
		}

		//passwordチェック

		//正規表現チェック(半角英数記号)
		if(	!(preg_match("/^[_a-zA-Z0-9]*$/",$string)	)	){
			$this->error="hankaku eisuu check error";
			return false;	
		}
		//文字数チェック(5文字以上)
		if(	!(mb_strlen($string)>=5)	){
			$this->error="mojisuu match error!:".mb_strlen($string);
			return false;
		}

		//正常終了
		return true;
	}
	//トークン確認メソッド
	//トークンを確認しtokenTableと照らしあわせる
	//tokenTableに存在があればuserNameで返し、なければfalse と$this->error="no token"
	private function checkToken(){
		//token確認
		if(	!(isset($_COOKIE["loginToken"]))	){
			$this->error="no loginToken cookie error";
			return false;
		}
		$myToken=$_COOKIE["loginToken"];
	
		//tokenTable捜索
		$string="SELECT * FROM tokenTable WHERE token= :myToken";
		$stmt=$this->PDO->prepare($string);
		if(	!($stmt)	){
			$this->error="checkToken SELECT prepare error";
			return false;
		}
		$stmt->bindvalue("myToken",$myToken);
		$flag=$stmt->execute();
		if(	$flag===false	){
			$this->error="checkToken SELECT execute error";
			return false;
		}
		$result=$stmt->fetchAll();
		if(	$result===false	){
			$this->error="checkToken SELECT fetchAll error";
			$this->errorpar=array($string,$myToken,$result);
			return false;
		}
		if(	count($result)>1	){
			$this->error="checkToken overlap ID";
			return false;
		}
		
		if(	!(isset($result[0]["ID"]))	){
			$this->error="no db token";
			$this->errorpar=array($string,$myToken,$result);
			return false;
		}
		return $result[0]["ID"];
	}

	//トークン削除メソッド
	private function deleteToken($ID){

		//userTable捜索
		$string="SELECT * FROM tokenTable WHERE userName= :ID";
		$stmt=$this->PDO->prepare($string);
		if(	!($stmt)	){
			$this->error="deleteToken SELECT prepare error";
			return false;
		}
		$stmt->bindvalue("ID",$ID);
		$flag=$stmt->execute();
		if(	$flag===false	){
			$this->error="deleteToken SELECT execute error";
			$this->errorpar=array($string,$ID);
			return false;
		}
		$result=$stmt->fetchAll();
		if(	!(isset($result))	){
			$this->error="deleteToken SELECT fetchAll error";
			$this->errorpar=array($string,$ID,$result);
			return false;
		}
		//すでにトークンがある場合、dbから削除する
		if(	count($result)>=1	){
			$string="DELETE FROM tokenTable WHERE userName= :ID";
			$stmt=$this->PDO->prepare($string);
			if(	!($stmt)	){
				$this->error="deleteToken DELETE prepare error";
				return false;
			}
			$stmt->bindvalue("ID",$ID);
			$flag=$stmt->execute();
			if(	$flag===false	){
				$this->error="deleteToken DELETE execute error".$string.":".$ID."<br>".count($result);
				$this->errorpar=$result;
				return false;
			}
		}
		return true;
	}

	//トークン発行メソッド
	private function issueToken($ID,$myName){
		require(dirname(__FILE__)."/config.php");
		//作業内容
		//tokenTable+$_cookie[loginToken]とuserNameの重複を確認	→	サーバー側の確認

		//与えられたmyNameから
		//tokenTableにuserNameの重複を確認、あればトークンと期限の更新
		//テーブルに新規データを追加し、トークンを発行する(すでにある場合は更新)
		//db更新後setcookieにてトークンを発行する
		//乱数作製

		//dbからトークンを探し、一旦削除する
		if(	!($this->deleteToken($ID))	){
			return false;
		}

		$token=$this->make_seed();
	

		//tokenTableに新規token追加
		$string="INSERT INTO tokenTable(ID,userName,token,expirationDate) VALUES( :ID , :userName , :token , now()  )";
		$stmt=$this->PDO->prepare($string);
		if(	!($stmt)	){
			$this->error="tokenTable INSERT prepare error";
			return false;
		}
		$stmt->bindvalue("ID",$ID);
		$stmt->bindvalue("userName",$myName);
		$stmt->bindvalue("token",$token);
		if(	$stmt->execute()===false	){
			$this->error="tokenTable INSERT execute error";
			return false;
		}

		//tokenクッキー発行
		if(	!(setcookie("loginToken",$token,time()+1209600,$setCookiePath))	){
			$this->error="loginToken issue error";
			return false;
		}
		
		return true;
	
	}

	//autoLogin
	function autoLogin(){
		require(dirname(__FILE__)."/config.php");
		$ID=$this->checkToken();
		if(	$ID==false	){
			return false;
		}
		//過去のtoken削除
		if(	!(setcookie("loginToken","",time()-1000,$setCookiePath))	){
			$this->error="token delete error";
			return false;
		}

	
		//ネームゲット
		$myName=$this->getName($ID);
		if(	!($myName)	){
			return false;
		}
		//token再発行
		if(	!($this->issueToken($ID,$myName))	){
			return false;
		}
	return true;
	}
	
	//logout
	function logout(){
		require(dirname(__FILE__)."/config.php");
		$ID=$this->checkToken();
		if(	$ID==false	){
			return false;
		}
		//過去のtoken削除
		if(	!(setcookie("loginToken","",time()-1000,$setCookiePath))	){
			$this->error="token delete error";
			return false;
		}

		//dbからトークンを探し、削除する
		if(	($this->deleteToken($ID))==false	){
			return false;
		}

		//セッション破棄
		session_destroy();
		return true;
	}
	

	//error()
	public function error(){
		if(	$this->error	){
			return $this->error;
		}
	}
	//randam生成器
	function make_seed(){
		return bin2hex(openssl_random_pseudo_bytes(128));
	}
//	$this->register
//	格納するデーター
//	
//	private $userTableColum=array(
//				array("userName","varchar(30)"),		必須
//				array("userId","varchar(255)"),
//				array("userPassword","varchar(255)"),		必須
//				array("Remarks","text"),
//				array("RegisterDate","datetime"),		now()
//				array("admin","bool"),				必須
//				array("successOrFailure","bool"),		デフォでfalse
//				array("die","bool")				デフォでfsalse
//	);

	//データを$this->userTableColumの形式で受け取り,格納する
	function registration($register){
		//配列か？
		if(	!(is_array($register))	){
			$this->error="registration not array error";
			return false;
		}
		//カラム存在チェック
		if(	!(search_array("userName",$register)	&&	search_array("userPassword",$register))	){
			$this->error="registration not colum error";
			return false;
		}
		
		$org=array("userId"=>password_hash($register["userName"],PASSWORD_DEFAULT),"RegisterDate"=>now(),"admin"=>false,"successOrFailure"=>false,"Die"=>false);

		$string="insert into userTable(userName,userId,userPassword,remarks,RegisterDate,admin,successOrFailure,die)".
		//" values( :userName, :userId, :userPassword, :Remarks, :RegisterDate, :admin, :successOrFailure, :die )";
		" values( :userName, :userId, :userPassword, :Remarks, now(), :admin, :successOrFailure, :die )";
		$stmt=$this->PDO->prepare($string);
		if(	!($stmt)	){
			$this->error="registration register prepare error";
			return false;
		}

		$register=array_merge($register,$org);
		$flag=$stmt->execute($register);
		if(	$flag===false	){
			$this->error="registration register execute error";
			return false;
		}
		return true;
	
	}
	//配列とキーを全部数値添字の配列に組み替える
	//ついでにカンマもつける
	private function gattaiArray($arrayName){
		require(dirname(__FILE__)."/dbConfig.php");
		$keyArray=array_keys($arrayName);
		$valueArray=array_values($arrayName);
		$result=array();
		foreach($keyArray as $index => $key){
			$result[]=$key;
			$result[]=$valueArray[$index].",";
		}
		$last=array_pop($result);
		$result[]=substr($last, 0, -1);
		return $result;
	}

	//IDを受け取りuserTableのmyNameを返す
	private function getName($ID){
	
		//userTable捜索
		$string="SELECT * FROM userTable WHERE ID= :ID";
		$stmt=$this->PDO->prepare($string);
		if(	!($stmt)	){
			$this->error="getName SELECT prepare error";
			return false;
		}
		$stmt->bindvalue("ID",$ID);
		$flag=$stmt->execute();
		if(	$flag===false	){
			$this->error="getName SELECT execute error";
			return false;
		}
		$result=$stmt->fetchAll();
		if(	$result===false	){
			$this->error="getName SELECT fetchAll error";
			return false;
		}
		if(	count($result)>1	){
			$this->error="getName overlap ID";
			return false;
		}
		
		if(	!(isset($result[0]["myName"]))	){
			$this->error="getName no db token";
			return false;
		}
		return $result[0]["myName"];

	}

	//IDを受け取りuserTableのadminを返す
	private function getAdmin($ID){
	
		//userTable捜索
		$string="SELECT * FROM userTable WHERE ID= :ID";
		$stmt=$this->PDO->prepare($string);
		if(	!($stmt)	){
			$this->error="getAdmin SELECT prepare error";
			return false;
		}
		$stmt->bindvalue("ID",$ID);
		$flag=$stmt->execute();
		if(	$flag===false	){
			$this->error="getAdmin SELECT execute error";
			return false;
		}
		$result=$stmt->fetchAll();
		if(	$result===false	){
			$this->error="getAdmin SELECT fetchAll error";
			return false;
		}
		if(	count($result)>1	){
			$this->error="getAdmin overlap ID";
			return false;
		}
		
		if(	!(isset($result[0]["myAdmin"]))	){
			$this->error="getAdmin no db token";
			return false;
		}
		return $result[0]["myAdmin"];

	}

	//アドミンチェック,現在(auto)ログインしているユーザーがadminかどうかを調べてboolで返す
	public function adminCheck(){
		//ID取得、
		$ID=$this->checkToken();
		if(		$ID===false	){
			$this->error="adminCheck error:<br>".$this->error;
			return false;
		}		
		$admin=$this->getAdmin($ID);
		if(	$admin===false	){
			return false;
		}
		return true;
	}
	//ログ記録
	//記録に使用されるのはloginLogTable(固定)
	//->logの下に
	//		->write
	//		->read等を配置
	//eventについて
	//あった事象について文字列で記録
	//'login','logout','createAccount','loginError','createAccountError'
	//等々
	//書き込みに失敗したらfalseを返す。
	private function log(){
		//書き込み
		private function write(int $event=0,int $id=0){
			private $eventArray=array(
				"false",
				"login",
				"logout",
				"createAccount",
				"loginError",
				"createAccountError"
			);
			$eventName=$eventArray($event);

			$string="INSERT INTO loginLogTable (ID,time,event) VALUES(:ID,now(),:event )";
			$stmt=$this->PDO->prepare($string);
			if(	!($stmt)	){
				$this->error="logWrite prepare error";
				return false;
			}
			$stmt->bindvalue("ID",$id);
			$stmt->bindvalue("event",$eventName);
			$flag=$stmt=excute();
			if(	$flag===false	){
				$this->error="logWrite excute  error";
			return false;
			}
		}
		private function read(int $id=0){
			
		}
	}

}//authの終点

	//otherLogin
	//
//	private otherLogin(){
//		require_once(dirname(__FILE__)."/../opauth/lib/Opauth/Opauth.php");
//		require_once(dirname(__FILE__)."/OPAuth_config.php");
//	}


?>
