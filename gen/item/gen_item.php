<?php

class gen {
	public $xlsObj; //xls对象
	public $in_file = "xls/item/item.xls"; //xls文件
	public $out_file = "setting/item/item_data.php"; //输出文件
	public $var_name = 1; //变量名所在行
	public $var_type = 2; //变量类型所在行
	public $var_null = 3; //是否为空行
	public $data_start_row = 4; //数据行

	//构造函数
	public function __construct($xlsObj) {
		$this->xlsObj = $xlsObj;
	}

	//解析数据
	public function getdata($sheetname=NULL) {
		$data = $this->xlsObj->read_data($this->in_file, $sheetname);
		$retdata = array();
		if($sheetname === NULL) {
			foreach ($data as $key => $value) {
				if(count($value)>4) {
					$tmp = $this->xlsObj->gen_data($this->in_file, $key, $value, $this->var_name, $this->var_type, $this->var_null, $this->data_start_row);
					$retdata[$key] = $tmp;
				}
			}
			return $retdata;
		}
		else {
			return $this->xlsObj->gen_data($this->in_file, $sheetname, $data, $this->var_name, $this->var_type, $this->var_null, $this->data_start_row);
		}
	}

	//执行
	public function DoGen() {
		$data = $this->getdata("item");
		$tmp = array();
		foreach ($data as $key => $value) {
			$tmp[$value["ItemId"]] = $value;
		}
		$this->xlsObj->save($this->out_file, "ItemData", $tmp, "道具数据");
	}
}

function DoGen($xlsObj) {
	$gen = new gen($xlsObj);
	$data = $gen->DoGen();
}