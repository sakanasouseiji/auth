<?php
$getFiles=get_included_files();
if(	array_shift($getFiles)===__FILE__	){
	exit("直接アクセス禁止");
}
$host="localhost";
$user="root";
$password="2kaisdGCR7";
$db="auth";
$tableNameList=array("Table","userTable");
$userTable=array(
		"ID"=>"varchar(10)",
		"myName"=>"varchar(255)",
		"myNameHurigana"=>"varchar(255)",
		"myPassword"=>"varchar(255)",
		"myImage"=>"MEDIUMBLOB",
		"myBirthday"=>"date",
		"myBirthdayPublication"=>"bool",
		"myAddress"=>"varchar(255)",
		"myAddressPublication"=>"bool",
		"myAllergy"=>"varchar(255)",
		"myPhoneNumber"=>"varchar(255)",
		"myPhoneNumberPublication"=>"bool",
		"myMobilePhoneNumber"=>"varchar(255)",
		"myMobilePhoneNumberPublication"=>"bool",
		"myMailAddress"=>"varchar(255)",
		"myMailAddressPublication"=>"bool",
		"myFamily"=>"text",
		"myFamilyPublication"=>"bool",
		"myComment"=>"text",
		"myAdmin"=>"bool",
		"myDelete"=>"bool"
);
$attendanceAndLeavingTable=array(
		"ID"=>"int",
		"inTime"=>"datetime",
		"outTime"=>"datetime",
		"comment"=>"text"
);
//テスト用データ
$testUserData=array(
		"ID"=>"0000000000",
		"myName"=>"admin",
		"myNameHurigana"=>"あどみん",
		"myPassword"=>"$2y$10\$UuPEblP80sbw9egMwrI7G.P/CeTH/gRydPLRC7RWUy.O0uvU972Hu ",
		"myImage"=>"hoge",
		"myBirthday"=>"1972-07-29",
		"myBirthdayPublication"=>false,
		"myAddress"=>"埼玉県八潮市緑町２−８−２グリーンパーク第３八潮１２０２号室",
		"myAddressPublication"=>false,
		"myAllergy"=>"花粉症、不整脈",
		"myPhoneNumber"=>"048-997-2299",
		"myPhoneNumberPublication"=>false,
		"myMobilePhoneNumber"=>"080-7012-0512",
		"myMobilePhoneNumberPublication"=>false,
		"myMailAddress"=>"qgyhc679@gmail.com",
		"myMailAddressPublication"=>false,
		"myFamily"=>"同居なし",
		"myFamilyPublication"=>false,
		"myComment"=>"テスト用データ",
		"myAdmin"=>true,
		"myDelete"=>false
);

?>
