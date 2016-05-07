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
	$forklist = array("item");
	$forknum = 0;
	foreach ($actions as $key => $value) {
		if (! in_array($key, $ignore)) {
			if (in_array($key, $forklist)) {
				$forknum = $forknum + 1;
				//创建子进程处理
				echo "fork $value create\n";
				$npid = pcntl_fork();
				if ($npid == 0) {
					$value();
					//执行完后退出
					echo "fork $value exit\n";
					exit(0);
				}
			} else {
				$value();
			}
		}
	}

	//等待子进程执行完毕，避免僵尸进程
	$n = 0;
	while ($n < $forknum) {
		$status = -1;
		$npid = pcntl_wait($status, WNOHANG);
		if ($npid > 0) {
			++$n;
		}
	}
}


//指令集合
$Actions = array(
	"genall" => "gen_all", //所有导表
	"item" => "gen_item", //指令数据
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
