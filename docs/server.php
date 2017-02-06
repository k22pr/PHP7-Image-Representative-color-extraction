<?php
	require_once("../source.php");
	if(isset($_FILES["upload"])){
		$test = new TestColor();
		echo $test->GetColor();
	}
	
	Class TestColor{
		function __construct(){
			$this->color = new ImageColor();
			$this->max = 1024*1024*2;
			$this->referer = $_SERVER['HTTP_REFERER'];
			$this->img = $_FILES["upload"];
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$this->type = explode("/",finfo_file($finfo, $this->img["tmp_name"]));
		}
	
		public function GetColor(){
			if($this->img["size"] > $this->max) $this->makeError(0);
			else{
				if(!is_dir("./tmp")) mkdir("tmp");
				$this->tmp = "./tmp/tmp.".end($this->type);
				if(!copy($this->img["tmp_name"],$this->tmp)) $this->makeError(1);
				else{
					$this->time[] = $this->nowtime();
					$this->ic = $this->color->Color($this->tmp);
					$this->time[] = $this->nowtime();
					//이미지를 저장하지 못했을경우
					if(empty($this->ic)) $this->makeError(2);
					$this->MakeDOM();
				}
			}
			
			unset($this->tmp);
		}
		
		private function makeError(int $get){
			if($get == 0){
				?>
					<div class="box red w12 center h3">
						이미지의 사이즈가 너무큽니다.<br>
						이미지의 사이즈는 <?=number_format($this->max/1024)?>KB를 넘길 수 없습니다.
					</div>
				<?
			}else if($get == 1){
				?>
					<div class="box red w12 center h3">
						서버에서 이미지를 저장하지 못했습니다.<br>
					</div>
				<?
			}else if($get == 2){
				?>
					<div class="box red w12 center h3">
						서버에서 이미지를 변환하지 못했습니다.<br>
						서버에서 지원하지 않는 이미지 형식일 가능성이 큽니다.
					</div>
				<?
			}
		}
		
		private function makeDOM(){
			$hex = $this->color->hex($this->ic);
			?>
				<div class="wac10 tw12 mw12">
					<div class="w12"  style="background:#<?=$hex?>">
						<div class="w12 p10 h4 center" style="position:absolute;top:0px;;left:0px;background:rgba(0,0,0,0.5);color:#fff;">
							<div class="w4">hex: <span style=""><?=$hex?></span></div>
							<div class="w4">RGB : <span style=""><?=$this->ic[0]?> <?=$this->ic[1]?> <?=$this->ic[2]?></span></div>
							<div class="w4"><?=round($this->time[1]-$this->time[0],2)?>초 소요</div>
						</div>
						<img src="<?=$this->tmp?>" class="w6 source-image">
					</div>
				</div>
			<?
		}
		
		private function nowtime(){
			$tmp =  explode(" ",microtime());
			return (float) $tmp[1]+$tmp[0];
		}
	}

?>