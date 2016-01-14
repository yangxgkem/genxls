<?php

require_once 'gen/3rd/phpexcel/PHPExcel.php';

class class_gen {

	public $var_name = 1; //变量名所在行
	public $var_type = 2; //变量类型所在行
	public $var_null = 3; //是否为空行
	public $data_start_row = 4; //数据行

	//读取xls数据
	public function read_data($filename, $sheetname=NULL) {
		//文件不存在直接返回
		if ( ! file_exists($filename)) return;
		$readertype = 'Excel5';
		if(strstr($filename, ".xlsx")) {
			$readertype = 'Excel2007';
		}
		$reader = PHPExcel_IOFactory::createReader($readertype);
		//$reader->setReadDataOnly(true);//设置为只读模式
		$excel = $reader->load($filename);
		$allsheet = $excel->getSheetNames();

		$data = array();
		foreach ($allsheet as $key => $name) {
			if ( ! $sheetname OR $name==$sheetname) {
				$sheet = $excel->getSheetByName($name);
				$sheetdata = $sheet->toArray();

				//兼容合并单元格 注意此接口, 如果你设置了 $reader->setReadDataOnly(true), 那么此接口将返回空值
				//数据样式： Array( ["D5:D6"] => "D5:D6", )
				$mergeCells = $sheet->getMergeCells();
				foreach ($mergeCells as $key => $value) {
					$var = explode(":", $value);

					list($fc, $fr) = PHPExcel_Cell::coordinateFromString($var[0]);//把单元格名分为 列, 行 (eg: D,5)
					$fc = PHPExcel_Cell::columnIndexFromString($fc) - 1;//把字母列转换为数值 (eg: D转换后为4)

					list($lc, $lr) = PHPExcel_Cell::coordinateFromString($var[1]);
					$lc = PHPExcel_Cell::columnIndexFromString($lc) - 1;

					$tmpvalue = $sheetdata[($fr-1)][$fc];//最左上角值
					$r = $fr - 1;
					while($r++ < $lr) {
						$c = $fc - 1;
						while($c++ < $lc) {
							$sheetdata[$r-1][$c] = $tmpvalue;//将合并的单元格全部设置为最左上角值
						}
					}
				}

				if ($sheetname) return $sheetdata;
				$data[$name] = $sheetdata;
			}
		}

		return $data;
	}

	//数据写入xls
	public function write_data($filename, $data, $sheetname) {
		$readertype = 'Excel5';
		if(strstr($filename, ".xlsx")) {
			$readertype = 'Excel2007';
		}
		if(file_exists($filename)) {
			$reader = PHPExcel_IOFactory::createReader($readertype);
			$excel = $reader->load($filename);
			$sheet = $excel->getSheetByName($sheetname); //工作表存在,则直接把数据写入该工作表
			if ( ! $sheet) {
				$sheet = $excel->createSheet(); //工作表不存在创建一个
			}
		}
		else {
			$excel = new PHPExcel();
			$sheet = $excel->getActiveSheet();
		}

		//行数据 单元格使用数据[1,65535]
		foreach($data as $row => $rowdata) {
            //列数据 单元格使用字母A-Z,第27列为AA, 28列AB, 以此类推
            foreach ($rowdata as $col => $value) {
            	//组装单元格:列+行
            	$columnName = PHPExcel_Cell::stringFromColumnIndex($col); //将列数字转换为字母 27==AA1
            	$cell = $columnName . ($row+1);
            	$sheet->setCellValue($cell, $value);
            }
        }
        //写入数据方法2
        //$sheet->fromArray($data);

        $sheet->setTitle($sheetname);
		$writer = PHPExcel_IOFactory::createWriter($excel, $readertype);
		$filename = iconv("utf-8", "gb2312", $filename);
		$writer->save($filename);
	}

	//校验数据完整性
	//filename xls文件路径
	//sheetname 工作表名称
	//$data 由$this->read_data获得的某个sheet数据
	//$var_name 变量名行号
	//$var_type 类型行号
	//$var_null 非空行号
	//$data_start_row 数据开始行号
	public function gen_data($filename, $sheetname, $data, $var_name, $var_type, $var_null, $data_start_row) {
		$name_dat = $data[$var_name];
		$type_dat = $data[$var_type];
		$null_dat = $data[$var_null];

		//校验头信息完整性
		foreach ($name_dat as $key => $value) {
			if($value===NULL OR $type_dat[$key]===NULL) {
				echo printf("\n头信息ERROR: 文件:%s, 工作表:%s, 行号:(%s or %s), 列号:%s\n", $filename, $sheetname, $var_name+1, $var_type+1, $key+1);
				exit();
			}
		}

		$newdata = array();
		for ($i=$data_start_row;$i<count($data);$i++) {
			$dat = $data[$i];
			$tmp = array();
			foreach ($name_dat as $key => $value) {
				//校验数据是否非空
				if($null_dat[$key]!==NULL AND $dat[$key]===NULL) {
					echo printf("\n数据非空ERROR: 文件:%s, 工作表:%s, 行号:%s, 列号:%s\n", $filename, $sheetname, $i+1, $key+1);
					exit();
				}
				//校验数据类型
				if($dat[$key]!==NULL) {
					switch ($type_dat[$key]) {
						case 'number':
							if(! is_numeric($dat[$key])) {
								echo printf("\n数据类型ERROR: 文件:%s, 工作表:%s, number, 行号:%s, 列号:%s\n", $filename, $sheetname, $i+1, $key+1);
								exit();
							}
							break;

						case 'string':
							if(! is_string($dat[$key])) {
								echo printf("\n数据类型ERROR: 文件:%s, 工作表:%s, string, 行号:%s, 列号:%s\n", $filename, $sheetname, $i+1, $key+1);
								exit();
							}
							break;
					}
				}
				//整理数据
				if($dat[$key]!==NULL) {
					$tmp[$value] = $dat[$key];
				}
			}
			array_push($newdata, $tmp);
		}
		return $newdata;
	}

	//解析数据
	public function getdata($in_file, $sheetname=NULL) {
		$data = $this->read_data($in_file, $sheetname);
		$retdata = array();
		if($sheetname === NULL) {
			foreach ($data as $key => $value) {
				if(count($value)>4) {
					$tmp = $this->gen_data($in_file, $key, $value, $this->var_name, $this->var_type, $this->var_null, $this->data_start_row);
					$retdata[$key] = $tmp;
				}
			}
			return $retdata;
		}
		else {
			return $this->gen_data($in_file, $sheetname, $data, $this->var_name, $this->var_type, $this->var_null, $this->data_start_row);
		}
	}

	//存储文件 php数组
	public function save($file, $name, $data, $desc) {
		$lastdir = strripos($file, "/");
		if($lastdir>0) {
			$path = substr($file, 0, $lastdir);
			file_exists($path) OR mkdir($path, 0755, TRUE);
		}

		$fp = fopen($file, "w");
		$datastr = var_export($data, TRUE);
		require 'gen/base/template.php';
		$str = sprintf($func_template, $desc, $name, $datastr);
		fwrite($fp, $str);
		fclose($fp);
	}

	//存储文件 json
	public function save_json($file, $name, $data) {
		$lastdir = strripos($file, "/");
		if($lastdir>0) {
			$path = substr($file, 0, $lastdir);
			file_exists($path) OR mkdir($path, 0755, TRUE);
		}

		$fp = fopen($file, "w");
		$str = json_encode($data, JSON_UNESCAPED_UNICODE);
		fwrite($fp, $str);
		fclose($fp);
	}
}
