<?

class UserModel extends CModel {
	private $user_name;
	public function attributeNames(){
		return	array('user_name');
	
	
	}
	public function attributeLabels(){
		return	array(
				'user_name'   => 'User Name',
				
		);
	
	
	}
	
	//Inner Variables
	var $connection;
	
	
	//Inner functions
	function getDayFromDate($date){
		return date("l", strtotime($date));
	
	}
	function __construct(){
		$this->connection=Yii::app()->db;
	}
	function checkEntry($date,$project_fk){
		$sql = "SELECT `entry_pk` FROM `time_sheet` WHERE `date` = '".$date."' AND `project_fk` = ".$project_fk."";
		$command=$this->connection->createCommand($sql);
		$command->setFetchMode(PDO::FETCH_OBJ);
		$rows=$command->queryRow();
		if($rows){
			return false;
		}
		else
		{
			return true;
		}
	}
	//public functions
	public function getUser($mac_id){
		$sql = "SELECT `user_pk`, `first_name`,`last_name` FROM `users` WHERE `mac_id` = '".$mac_id."'";
		$command=$this->connection->createCommand($sql);
		$command->setFetchMode(PDO::FETCH_OBJ);
		$rows=$command->queryRow();
		return $rows;
		
	}

	public function	getPDF($user_pk,$project_pk,$date){
		$sql = "SELECT p.name, t.`entry_pk`,T.`body`,T.`hours`,T.`date` FROM `time_sheet` AS T JOIN projects AS P ON T.project_fk = P.project_pk  WHERE `user_fk` = ". $user_pk ."  AND `project_fk` = ". $project_pk ." AND `date` BETWEEN '".$date."-01' AND '".$date."-31'";
		$command=$this->connection->createCommand($sql);
		$command->setFetchMode(PDO::FETCH_OBJ);
		$rows=$command->queryAll();
		$ret = $this->createPDF($rows);
		return $ret;
	}
	public function createPDF($data){
		if(count($data) > 0){
			$objPHPExcel= XPHPExcel::createPHPExcel();
			$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
			->setLastModifiedBy("Maarten Balliauw")
			->setTitle("Office 2007 XLSX Test Document")
			->setSubject("Office 2007 XLSX Test Document")
			->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Test result file");
			
			//function to change background color URL : http://stackoverflow.com/questions/6773272/set-background-cell-color-in-phpexcel
			function changeCellColor($cells,$color){
				global $objPHPExcel;
				$objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()
				->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,
						'startcolor' => array('rgb' => $color)
				));
			}
			
			// Add some data
			$sheet = $objPHPExcel->setActiveSheetIndex(0);
			//setting height for first two rows
			$sheet->getRowDimension('1')->setRowHeight(30);
			$sheet->getRowDimension('2')->setRowHeight(35);
			//setting width for the columns
			$sheet->getColumnDimension('A')->setWidth(10);
			$sheet->getColumnDimension('B')->setWidth(30);
			$sheet->getColumnDimension('C')->setWidth(20);
			$sheet->getColumnDimension('D')->setWidth(80);
			$sheet->getColumnDimension('E')->setWidth(40);
			
			//adding Image
			$objDrawingPType = new PHPExcel_Worksheet_Drawing();
			$objDrawingPType->setWorksheet($objPHPExcel->setActiveSheetIndex(0));
			$objDrawingPType->setName("Pareto By Type");
			$objDrawingPType->setPath(Yii::app()->basePath.DIRECTORY_SEPARATOR."../images/img.jpg");
			$objDrawingPType->setCoordinates('A1');
			$objDrawingPType->setOffsetX(1);
			$objDrawingPType->setOffsetY(5);
			
			//style array for first two rows http://phpexcel.codeplex.com/discussions/206914
			$styleArray = array(
					'font' => array(
							'name' => 'Rockwell',
							'size' => '12',
							'bold'  => true,
							'color'	=> array('rgb' => 'ffffff')
					),
					'fill' => array(
							'type' => PHPExcel_Style_Fill::FILL_SOLID,
							'startcolor' => array(
									'rgb' => 'f5812a',
							),
					),
			);
			$headerstyleArray = array(
					'font' => array(
							'name' => 'Rockwell',
							'size' => '10',
							'color'	=> array('rgb' => '000000')
					),
					'fill' => array(
							'type' => PHPExcel_Style_Fill::FILL_SOLID,
							'startcolor' => array(
									'rgb' => 'a6a6a6',
							),
					),
			);
			$overTimeStyleArray = array(
					'font' => array(
							'name' => 'Rockwell',
							'size' => '10',
							'color'	=> array('rgb' => '129600'),
							'bold' => true
					)
			);
			
			//Apply style for main heading
			$sheet->getStyle('A1:E2')->applyFromArray($styleArray);
			//Freezing header pane
			$sheet->freezePane('E3');
			//adding value
			//$sheet->setCellValue('D1', 'TimeSheet  '.date('F d, Y' , $data[0]->date));
			$sheet->setCellValue('D2', $data[0]->name.'    Location : Bangalore');
			$sheet->setCellValue('E1',date('F d, Y'));
			
			//setting header
			$sheet->setCellValue('A3', 'Sl No');
			$sheet->setCellValue('B3', 'Date');
			$sheet->setCellValue('C3', 'Hours');
			$sheet->setCellValue('D3', 'Task Description');
			$sheet->setCellValue('E3', 'Comments');
			//Applying mini header style
			$sheet->getStyle('A3:E3')->applyFromArray($headerstyleArray);
			//$starting_cell_char = 'A';
			$starting_cell_num	= '5';
			foreach ($data as $key => $row){
				$sheet->setCellValue('A'.$starting_cell_num, $key+1);
				$sheet->setCellValue('B'.$starting_cell_num, substr($row->date, 0, -8));
				$sheet->setCellValue('C'.$starting_cell_num, $row->hours);
				$sheet->setCellValue('D'.$starting_cell_num, $row->body);
				$day = $this->getDayFromDate($row->date);
				if( ($day === 'Sunday') || ($day === 'Saturday')){ // Checking if it is sunday or saturday for highligting
					$sheet->setCellValue('E'.$starting_cell_num, 'Worked on Off Day');
					$sheet->getStyle('A'.$starting_cell_num.':E'.$starting_cell_num )->applyFromArray($overTimeStyleArray);
				}
				$starting_cell_num++;
				
			}
			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="01simple.xls"');
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');
			
			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0
			
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save(Yii::app()->basePath.DIRECTORY_SEPARATOR."../assets/Reports/report_".strtotime(gmdate('D, d M Y H:i:s')).".xls");
			//Yii::app()->end();
			$path =  Yii::app()->basePath.DIRECTORY_SEPARATOR."../assets/Reports/report_".strtotime(gmdate('D, d M Y H:i:s')).".xls";
			//var_dump($path);
			return $path;
		}
		else
		{
			return 0;
		}
	}
	public function addNewUser($first_name,$last_name,$mac_id){
		$sql = "INSERT INTO `users` (`first_name`,`last_name`,`mac_id`) VALUES ('".$first_name."','".$last_name."','".$mac_id."')";
		//$sql = "INSERT INTO `users` (`first_name`,`last_name`,`mac_id`) VALUES ('testingfirst','testinglast','testingmac')";
		$command=$this->connection->createCommand($sql);
		return $command->execute();
	}
	
	public function addEditTimeSheetEntry($entry_id,$date,$project,$hours,$body,$user_fk,$isEdit){
		if(!$entry_id){ //New Entry
				$val = $this->checkEntry($date,$project) ; // Checking if entry is already there
				$edit = $isEdit === 'true'?true:false;
				if($val || $edit){
				$sql = "INSERT INTO `time_sheet` (`user_fk`,`project_fk`,`body`,`date`,`day`,`hours`) VALUES ('".$user_fk."',".
						"'".$project."','".$body."','".$date."','".$this->getDayFromDate($date)."','".$hours."')";
				$command = $this->connection->createCommand($sql);
				$ret_val = $command->execute();
				return $ret_val;
			}
			else{
				return -1;
			}
		}
		else{
			$sql = "UPDATE `time_sheet` SET `body` = '".$body."',`hours` = ".$hours." WHERE `entry_pk` = ".$entry_id;
			$command = $this->connection->createCommand($sql);
			$ret_val = $command->execute();
			return $ret_val;
		}	
	}
	
	public function getTimeSheetEntry($date,$project,$user_pk){
		if ($project!=""){
			$sql = "SELECT `entry_pk`, `body`, `hours` FROM `time_sheet` WHERE `date`='".$date."' AND `project_fk` = ".$project." AND `user_fk` =".$user_pk."";
		}
		else{
			$sql = "SELECT `entry_pk`, `body`, `hours` FROM `time_sheet` WHERE `date`='".$date."' AND `user_fk` =".$user_pk."";
		}
		$command=$this->connection->createCommand($sql);
		$command->setFetchMode(PDO::FETCH_OBJ);
		$rows=$command->queryRow();
		return $rows;
	}
	//LP
	public function deleteTimeSheetEntry($entry_id){
		
	}
	public function getProjectListPerUser($user_fk){
		$sql = "SELECT `project_pk`, `name` FROM `projects` JOIN `project_user_mapping` ON projects.project_pk=project_user_mapping.project_fk WHERE project_user_mapping.user_fk=".$user_fk."";
		$command=$this->connection->createCommand($sql);
		$command->setFetchMode(PDO::FETCH_OBJ);
		$rows=$command->queryAll();
		return $rows;
	}
	
	public function addEditProject($project_id,$user_pk,$project_name){
		if(!project_id){
			$sql = "INSERT INTO `project` (`name`,`user_fk`) VALUES ('".$name."','".$user_pk."')";
			$command = $this->connection->createCommand($sql);
			var_dump($command->execute());
		}
	}
	public function getAllProjects(){
		$sql = "SELECT `project_pk`, `name` FROM projects";
		$command = $this->connection->createCommand($sql);
		$command->setFetchMode(PDO::FETCH_OBJ);
		$rows=$command->queryAll();
		return $rows;
		
	}
	
	public function updateMyProjects($user_pk,$added,$deleted){
		
		if(sizeof($deleted) !==0){
			foreach ($deleted as $deleted_project){
				$sqldelete = "DELETE FROM project_user_mapping WHERE user_fk =".$user_pk." AND project_fk =".$deleted_project;
				$command = $this->connection->createCommand($sqldelete);
				 $command->execute();
				//var_dump($sqldelete);
			}
		}
		if(sizeof($added) !==0){
			foreach ($added as $added_project){
				$sqldelete = "INSERT INTO project_user_mapping (user_fk,project_fk) VALUES (".$user_pk.",".$added_project.")";
				$command = $this->connection->createCommand($sqldelete);
				 $command->execute();
			}
		}
		return 1;
	}
	
	//LP
	public function deleteProject($project_id,$user_id){
		$sql = "DELETE FROM `project_user_mapping` WHERE project_fk=".$project_id." AND user_fk =".$user_id;
		$command = $this->connection->createCommand($sql);
		$command->execute();
	}
	
	public function getTimeSheetEntryPerMonth($month){
		
	}
}