<?php

//道具数据
function gen_item() {
	require_once "gen/item/gen_item.php";
	$gen = new class_gen_item();
	$gen->DoGen();
}

//gen all
function gen_all() {
	$actions = $GLOBALS['Actions'];
	$ignore = array("genall");
	foreach ($actions as $key => $value) {
		if (! in_array($key, $ignore)) {
			$value();
		}
	}
}


//指令集合
$Actions = array(
	"genall" => "gen_all",//所有导表
	"item" => "gen_item",//指令数据
);


function gen($argv) {
	if (count($argv)<=1) {
		echo "\nplease input action. eg: php genxls.php genall\n\n";
		return;
	}
	$actions = $GLOBALS['Actions'];
	for ($i=1; $i<count($argv); $i++) {
		$act = $actions[($argv[$i])];
		$act();
	}
	echo "\ngen success!!!\n\n";
}
gen($argv);