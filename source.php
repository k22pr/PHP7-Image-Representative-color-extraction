<?php
	Class ImageColor{
		function __construct(){
			$this->time = time();//UNIX time값을 저장해 둡니다.;
			if(!is_dir("./tmp")) mkdir("./tmp"); //TMP폴더를 만들어냅니다.
			
			//유저 설정 부분
			
			$this->cut = array(0,0);
			$this->remain = array(0,0);
			$this->color = array();
			$this->sum = 0;
			$this->avg = 0;
			$this->r = array();
			$this->g = array();
			$this->b = array();
			$this->arr = array("r","g","b");
			$this->out = array();
		}
		
		////////////////////////////////////////////////////////
		//$img = 업로드된 이미지나 이미지의 경로
		//$quick = 빠른방법으로 뽑아낼 것인지 deafult = quick
		public function Color(string $img,$type=NULL){
			if($type > 1) $this->cut_size = $type; //이미지를 자를 갯수의 제곱근 혹은 sqrt(갯수); 이수치가 증가하면 연산속도가 감소합니다.
			else $this->cut_size = 5;
			if(gettype($img) == "array") $img = (object) $img; //array타입의 img정보를 object형식으로 변경합니다.
			else if(gettype($img) == "string"){
				$tmp = $img;
				$img = new stdClass();
				$img->tmp_name = $tmp;
				$tmp = explode("/",$img->tmp_name);
				$img->name = $tmp[count($tmp)-1];
			}else{
				print("Image type error.");
				return false;
			}
			
			//이미지의 사이즈와 MIME타입을 구해냅니다.
			$this->tmp = $img->tmp_name;
			$this->base_size = getimagesize($this->tmp);
			//지원하지 않는 이미지의 형식
			if(empty($this->base_size["mime"])) return false;
			$tmp = explode("/",$this->base_size["mime"]);
			$this->mime_type = $tmp[1];
			if(empty($this->cut_size) || $this->cut_size <= 1){
				$color = $this->QuickSetting();
			}else{
				$this->CutSetting();
				$this->MakeSmallImage();
				for($i=0;$i<3;$i++){
					// r, g, b 총 3번의 loop문
					foreach($this->arr as $key){
						$this->sum = $this->GetSum($key);
						$this->avg = round($this->sum/count($this->$key),1); //평균을 구해주는 부분
						//컬러의 합에서 avg만큼을 뺀 절대값을 정렬 시킴
						//평균값에서 가장 먼 값을 알아내기 위함
						if($i == 2) $this->out[$key] = round($this->avg);
						else $this->unsetColor($key); //가장 먼값들을 지워주는 부분
					}
				}
			}
			$this->out = $this->ColorOption();
			return array($this->out["r"],$this->out["g"],$this->out["b"]);
		}
		private function SetImageType($x,$y){
			$name = "./tmp/".$x.$y.".".$this->mime_type; //생성할 이미지의 이름 뒤에 MIME타입을 붙혀 줍니다. NAME -> NAME.png
			$canvas=imagecreatetruecolor(1,1);
			$x = $x*$this->cut[0];
			$y = $y*$this->cut[1];
			$this->create= $this->convertPNG($this->tmp);
			imagealphablending($this->create, false);
			imagecopyresampled($canvas,$this->create,0,0,$x,$y,$this->base_size[0],$this->base_size[1],$this->base_size[0],$this->base_size[1]);
			imagesavealpha($this->create, true);
			imagepng($canvas,$name,9);
			
			return $name;
		}
		
		//이미지의 컷팅 사이즈를 지정합니다.
		//this->cut : 부분적으로 잘라낼 이미지의 크기 this->remain : 이미지가 나눠떨어지지 않을경우에 나머지 부분
		private function CutSetting(){
			$i = 0;
			do{
				$this->cut[$i] = round($this->base_size[$i]/$this->cut_size);
				$this->remain[$i] = $this->base_size[$i]%$this->cut[$i];
			}while(++$i == 1);
		}
		
		//작은 이미지를 생성하는 함수
		private function MakeSmallImage(){
			$i = $j = $count = 0;
			do{
				do{
					$name = $this->SetImageType($j,$i);
					$this->GetColorValue($name,$count++,$j,$i);
				}while(++$j < $this->cut_size);
				$j = 0;
			}while(++$i < $this->cut_size);
		}
		
		private function QuickSetting(){
			$this->cut[0] = $this->base_size[0];
			$this->cut[1] = $this->base_size[1];
			$name = $this->SetImageType(0,0);
			$this->GetColorValue($name,0,0,0);
		}
		
		//해당 이미지의 컬러를 얻어오븐 부분
		private function GetColorValue($name,$count,$j,$i){
			$img = $this->convertPNG($name);
			$rgb = imagecolorat($img,0,0);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			$this->r[$count]  = $r;
			$this->g[$count] = $g;
			$this->b[$count] = $b;
			unlink($name);
		}
		
		private function unsetColor($key){
			$i = 0;
			$max = count($this->$key)-1;
			do{
				sort($this->$key);
				$low = abs($this->$key[$i]-$this->avg);
				$high = abs($this->$key[$max-$i]-$this->avg);
				if($low >= $high) unset($this->$key[$i]);
				else if($low < $high) unset($this->$key[$max-$i]);
			}while(++$i < floor($max/2));
		}
		
		private function GetSum($key){
			$sum = 0;
			foreach($this->$key as $val){
				$sum += $val;
			}
			return $sum;
		}
		
		//대표색을 좀더 뚜렷하게 설정해 주는 부분;
		private function ColorOption(){
			$total = $this->out["r"]+$this->out["g"]+$this->out["b"];
			foreach($this->out as $key => $val){
				#if($this->out[$key] > $total/3) $this->out[$key] += round(($total-$this->out[$key])/$weight);
				if($this->out[$key] < $total/6) $this->out[$key] -= round(($total-$this->out[$key])/($total/6));
				$this->out[$key] += round($this->out[$key]/10);
				if($this->out[$key] > 255) $this->out[$key] = 255;
				else if($this->out[$key] < 0) $this->out[$key] = 0;
			}
			return $this->out;
			
		}
		
		public function convertPNG($name){
			$tmp = getimagesize($name);
			switch($tmp["mime"]){
				case "image/png";
					$img = (imagecreatefrompng($name));
				break;
				case "image/gif";
					$img =(imagecreatefromgif($name));
				break;
				case "image/jpeg";
					$img = (imagecreatefromjpeg($name));
				break;
				default:
					print("type error");
				break;
			}
			return ($img);
		}
		
		public function hex($arr){
			$arr[0] = str_pad(dechex($arr[0]),2,'0',STR_PAD_LEFT);
			$arr[1] = str_pad(dechex($arr[1]),2,'0',STR_PAD_LEFT);
			$arr[2] = str_pad(dechex($arr[2]),2,'0',STR_PAD_LEFT);
			return $arr[0].$arr[1].$arr[2];
		}
	}

?>