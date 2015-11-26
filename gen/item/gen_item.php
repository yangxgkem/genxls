<?php

require_once "gen/base/xls.php";

class class_gen_item extends class_gen {
	public $in_file = "xls/item/item.xls"; //xls文件
	public $out_file = "setting/item/item_data.php"; //输出文件

	//执行
	public function DoGen() {
		$data = $this->getdata($this->in_file, "item");
		$tmp = array();
		foreach ($data as $key => $value) {
			$tmp[$value["ItemId"]] = $value;
		}
		$this->save($this->out_file, "ItemData", $tmp, "道具数据");
	}
}