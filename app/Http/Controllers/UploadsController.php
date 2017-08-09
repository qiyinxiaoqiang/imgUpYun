<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
//use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Image;
use Response; 
use App\Model\Upyun;
class UploadsController extends Controller {

	 
	public function store(Request $request)
	{  
		
		$validator = \Validator::make($request->all(), [
			'uc_image_token' => 'required',
			'uid' => 'required', // a/b/
			'type' => 'in:avatar,ogc,wx_bot,read_smart',//头像上传和ogc上传走的又拍云配置文件不一样
		]);
		if ($validator->fails()) { 
			$data = ['meta'=>['code'=>400,'message'=> '失败']];
			return $data; //返回错误信息
		}
		 
		//验证token
		$token = $request->input('uc_image_token'); 
		if ($token != env("UC_IMAGE_TOKEN",'NO')) { 
			return $data = ['meta'=>['code'=>400,'message'=> __METHOD__ .'失败']];
		}  
		$uid = $request->input('uid');
		$type = $request->input('type','avatar');
		switch ($type) {
			case 'avatar':
				$upyun_config = [
					'bucket' => 'xxx',
					'operator'=>'xxxx',
					'password' => 'xxx',
				];
				$upyun_dir = 'v5_avatar';
				break;
			case 'ogc':
				$upyun_config = [
					'bucket' => 'xxx',
					'operator'=>'xxxx',
					'password' => 'xxx',
				];
				$upyun_dir = 'v5_ogc'; 
				break;
			case 'wx_bot':
				$upyun_config = [
					'bucket' => 'xxx',
					'operator'=>'xxxx',
					'password' => 'xxx',
				];
				$upyun_dir = 'wx_bot'; 
				break;
			case 'read_smart':
				$upyun_config = [
					'bucket' => 'xxx',
					'operator'=>'xxxx',
					'password' => 'xxx',
				];
				$upyun_dir = 'read_smart'; 
				break;
			default:
				$upyun_config = [
					'bucket' => 'xxx',
					'operator'=>'xxxx',
					'password' => 'xxx',
				];
				$upyun_dir = 'v5_ogc'; 
				# code...
				break;
		}


		$imgurls = $request->input('imgurl','');
		if(empty($imgurls)){ 
			$img0 = Image::make($_FILES['image']['tmp_name']);
			if (!in_array($img0->mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'])) {
				$data = ['meta'=>['code'=>400],'message'=> '图片mime不符合规定更新成功'];
				return $data;
			} 
			$uuid = uniqid().time();
			$ext = substr(strrchr($_FILES['image']['name'], '.'), 1);
			$ext = in_array($ext, ['jpg', 'gif', 'png', 'bmp', 'jpeg']) ? strtolower($ext) : 'jpg';
			$imagename = $uuid . '.' . $ext; //生成的文件名 
			
			$upyun = new UpYun($upyun_config['bucket'], $upyun_config['operator'], $upyun_config['password']); 
			try { 
				$fh = fopen($_FILES['image']['tmp_name'], 'rb');
				$upyun->writeFile('/'.$upyun_dir.'/'.$imagename, $fh, True);
				fclose($fh);
				$data = ['meta'=>['code'=>200,'message'=> '成功'],'data'=>['pic'=>$upyun_dir.'/'.$imagename]];
				return $data;
			 }
			catch(Exception $info) { 
		        echo $info->getCode();
		        echo $info->getMessage();
		    } 
		}else{   
			if(!is_array($imgurls) || count($imgurls) <=0){
				return ['meta'=>['code'=>400,'message'=> '失败']];
			}
			foreach($imgurls as $key=> $value){  
				$uuid = uniqid().time();
				switch($upyun_dir){
					case 'wx_bot':
						$imagename = $uuid . '.jpg'; //生成的文件名 
						break;
					case 'read_smart':
						$pg = strrchr($value, '.');
//						$pg =  explode('!',strrchr($value, '.'));
						if(isset($pg)){
							preg_match('/[?|!]/',$pg,$match); 
							if(isset($match[0])){ 
								$pg = explode($match[0],$pg);
								if(in_array($pg[0], ['.jpg', '.gif', '.png', '.bmp', '.jpeg'])){ 
									$imagename = $uuid . $pg[0]; //生成的文件名 
								}else{
									$imagename = $uuid . '.jpg'; //生成的文件名 
								}
							}else{
								if(in_array($pg, ['.jpg', '.gif', '.png', '.bmp', '.jpeg'])){ 
									$imagename = $uuid . $pg; //生成的文件名 
								}else{
									$imagename = $uuid . '.jpg'; //生成的文件名 
								} 
							}
						}else{
							$imagename = $uuid . '.jpg'; //生成的文件名 
						}
						break;
					default: 
						$imagename = $uuid . '.jpg'; //生成的文件名 
						break;
				}
				
				
				if(!preg_match('/(http|https|ftp|file){1}(:\/\/)?([\da-z-\.]+)\.([a-z]{2,6})([\/\w \.-?&%-=]*)*\/?/',$value)){
					if(strpos($value, '//') === 0){
						$nvalue = substr($value,2);
						$result = $this->urlOpen($nvalue); 
						if(preg_match('/<body.*?>([\s\S]*)<\/body>/iU',$result) || empty($result) || $result == false){ 
							$result = $this->urlOpen($nvalue); 
							if(preg_match('/<body.*?>([\s\S]*)<\/body>/iU',$result) || empty($result) || $result == false){  
								$arr[$key]['url'] =  $value; 
								$arr[$key]['width'] =  1; 
								$arr[$key]['height'] =  1;
								$arr[$key]['is_grab_image'] =  'n';
								$arrs[$value] = $value;//新上传的图片与传来的图片url
							}else{
								$upyun = new UpYun($upyun_config['bucket'], $upyun_config['operator'], $upyun_config['password']); 
								try {  
									$source = $upyun->writeFile('/'.$upyun_dir.'/'.$imagename, $result, True); 
									if($upyun_dir == 'wx_bot'){
										$arr[$key] =  $upyun_dir.'/'.$imagename;  
									}else{
										$arr[$key]['url'] =  $upyun_dir.'/'.$imagename; 
										$arr[$key]['width'] =  $source['x-upyun-width']; 
										$arr[$key]['height'] =  $source['x-upyun-height']; 
										$arr[$key]['is_grab_image'] =  'y';
									}
									$arrs[$value] = $upyun_dir.'/'.$imagename;//新上传的图片与传来的图片url 
								 }
								catch(Exception $info) { 
							        echo $info->getCode();
							        echo $info->getMessage();
							    } 
							} 
						}else{   
							$upyun = new UpYun($upyun_config['bucket'], $upyun_config['operator'], $upyun_config['password']); 
							try {  
								$source = $upyun->writeFile('/'.$upyun_dir.'/'.$imagename, $result, True); 
								if($upyun_dir == 'wx_bot'){
									$arr[$key] =  $upyun_dir.'/'.$imagename;  
								}else{
									$arr[$key]['url'] =  $upyun_dir.'/'.$imagename; 
									$arr[$key]['width'] =  $source['x-upyun-width']; 
									$arr[$key]['height'] =  $source['x-upyun-height']; 
									$arr[$key]['is_grab_image'] =  'y';
								}
								$arrs[$value] = $upyun_dir.'/'.$imagename;//新上传的图片与传来的图片url 
							 }
							catch(Exception $info) { 
						        echo $info->getCode();
						        echo $info->getMessage();
						    } 
						}
					}else{ 
						$arr[$key]['url'] =  $value; 
						$arr[$key]['width'] =  1; 
						$arr[$key]['height'] =  1;
						$arr[$key]['is_grab_image'] =  'n';
						$arrs[$value] = $value;//新上传的图片与传来的图片url 
						\Log::info('路径替换失败'.$value); 
					}
				}else{ 
					$result = $this->urlOpen($value); 
					if(preg_match('/<body.*?>([\s\S]*)<\/body>/iU',$result) || empty($result) || $result == false){
						$result = $this->urlOpen($value); 
						if(preg_match('/<body.*?>([\s\S]*)<\/body>/iU',$result) || empty($result) || $result == false){ 
							$arr[$key]['url'] =  $value; 
							$arr[$key]['width'] =  1; 
							$arr[$key]['height'] =  1;
							$arr[$key]['is_grab_image'] =  'n';
							$arrs[$value] = $value;//新上传的图片与传来的图片url
						}else{
							$upyun = new UpYun($upyun_config['bucket'], $upyun_config['operator'], $upyun_config['password']); 
							try {  
								$source = $upyun->writeFile('/'.$upyun_dir.'/'.$imagename, $result, True);  
								if($upyun_dir == 'wx_bot'){
									$arr[$key] =  $upyun_dir.'/'.$imagename;  
								}else{
									$arr[$key]['url'] =  $upyun_dir.'/'.$imagename; 
									$arr[$key]['width'] =  $source['x-upyun-width']; 
									$arr[$key]['height'] =  $source['x-upyun-height']; 
									$arr[$key]['is_grab_image'] =  'y';
								}
								$arrs[$value] = $upyun_dir.'/'.$imagename;//新上传的图片与传来的图片url 
							 }
							catch(Exception $info) { 
						        echo $info->getCode();
						        echo $info->getMessage();
						    } 
						} 
					}else{  
						$upyun = new UpYun($upyun_config['bucket'], $upyun_config['operator'], $upyun_config['password']); 
						try {  
							$source =$upyun->writeFile('/'.$upyun_dir.'/'.$imagename, $result, True); 
							if($upyun_dir == 'wx_bot'){
								$arr[$key] =  $upyun_dir.'/'.$imagename;  
							}else{
								$arr[$key]['url'] =  $upyun_dir.'/'.$imagename; 
								$arr[$key]['width'] =  $source['x-upyun-width']; 
								$arr[$key]['height'] =  $source['x-upyun-height']; 
								$arr[$key]['is_grab_image'] =  'y';
							}
							$arrs[$value] = $upyun_dir.'/'.$imagename;//新上传的图片与传来的图片url 
						 }
						catch(Exception $info) { 
					        echo $info->getCode();
					        echo $info->getMessage();
					    } 
					}
				}
			} 
			$data = [
					'meta'=>[
						'code'=>200,
						'message'=> '成功'
					],
					'data'=>[
						'pic'=>$arr,
						'pics'=>$arrs
					]
			];
			return $data; 
		} 
	}
	
	private function urlOpen($imgurl){
		$httpheader = array(
		    'Host' => 'mmbiz.qpic.cn',
		    'Connection' => 'keep-alive',
		    'Pragma' => 'no-cache',
		    'Cache-Control' => 'no-cache',
		    'Accept' => 'textml,application/xhtml+xml,application/xml;q=0.9,image/webp,/;q=0.8',
		    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.89 Safari/537.36',
		    'Accept-Encoding' => 'gzip, deflate, sdch',
		    'Accept-Language' => 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4'
		);
		$options = array(
		    CURLOPT_HTTPHEADER => $httpheader,
		    CURLOPT_URL => $imgurl,
		    CURLOPT_TIMEOUT => 5,
		    CURLOPT_FOLLOWLOCATION => 1,
		    CURLOPT_RETURNTRANSFER => true
		);
		$ch = curl_init();
		curl_setopt_array( $ch , $options );
		$result = curl_exec( $ch );
		curl_close($ch);
		return $result;
	}
	
	
	/*public function store(Request $request)
	{
		$rules = array(
				'uc_image_token' => 'required',
				'uid' => 'required', // a/b/c
		);
	
		$validator = \Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			//$data = ['code' => 'A0400', 'msg' => $validator->messages()];
			$data = ['meta'=>['code'=>400,'message'=> '失败']];
			return $data; //返回错误信息
		}
	
		//验证token
		$token = $request->input('uc_image_token');
	
		if ($token != env("UC_IMAGE_TOKEN",'NO')) {
			//return ['code' => 'A0401', 'msg' => __METHOD__ . 'oauth failed'];
			return $data = ['meta'=>['code'=>400,'message'=> __METHOD__ .'失败']];
		}
		$uid = $request->input('uid');
		$img0 = Image::make($_FILES['image']['tmp_name']);
		if (!in_array($img0->mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'])) {
			$data = ['meta'=>['code'=>200],'message'=> '图片mime不符合规定更新成功'];
			echo json_encode($data);
		}
	
		$md5Id = strtolower(md5(intval($uid)));
		$adir = substr($md5Id, 0, 2);
		$bdir = substr($md5Id, 2, 2);
		$cdir = substr($md5Id, 4, 2);
	
		$paths = env('UC_AVATAR','/upload');
		$realpath = $_SERVER ['DOCUMENT_ROOT'].'/'.$paths;
		$imagepath =  $realpath.'/' . $adir . '/' . $bdir . '/' . $cdir;
		is_dir($imagepath) ? '' : mkdir($imagepath, 0777, true);
		$uuid = microtime();
		$ext = substr(strrchr($_FILES['image']['name'], '.'), 1);
		$ext = in_array($ext, ['jpg', 'gif', 'png', 'bmp', 'jpeg']) ? strtolower($ext) : 'jpg';
		$imagename = $uuid . '.' . $ext;
		$img0->fit(100, 100);
		$img0->save($imagepath . '/' . '0_' . $imagename);
		$resultimagname = $adir . '/' . $bdir . '/' . $cdir.'/'.'0_'.$imagename;
		$data = ['meta'=>['code'=>200,'message'=> '成功'],'data'=>['pic'=>$resultimagname]];
		echo json_encode($data);
			
			
	}*/
	
	public function conllection(Request $request)
	{
		$rules = array(
				'uc_image_token' => 'required',
				'uid' => 'required', // a/b/c
		);
	
		$validator = \Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			//$data = ['code' => 'A0400', 'msg' => $validator->messages()];
			$data = ['meta'=>['code'=>400,'message'=> '失败']];
			return $data; //返回错误信息
		}
	
		//验证token
		$token = $request->input('uc_image_token');
	
		if ($token != env("UC_IMAGE_TOKEN",'NO')) {
			//return ['code' => 'A0401', 'msg' => __METHOD__ . 'oauth failed'];
			return $data = ['meta'=>['code'=>400,'message'=> __METHOD__ .'失败']];
		}
		$uid = $request->input('uid');
		$img0 = Image::make($_FILES['image']['tmp_name']);
		if (!in_array($img0->mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp'])) {
			$data = ['meta'=>['code'=>200],'message'=> '图片mime不符合规定更新成功'];
			echo json_encode($data);
		}
	
		$md5Id = strtolower(md5(intval($uid)));
		$adir = substr($md5Id, 0, 2);
		$bdir = substr($md5Id, 2, 2);
		$cdir = substr($md5Id, 4, 2);
	
		$paths = env('UC_COLLECTION','/upload');
		$realpath = $_SERVER ['DOCUMENT_ROOT'].'/'.$paths;
		$imagepath =  $realpath.'/' . $adir . '/' . $bdir . '/' . $cdir;
		is_dir($imagepath) ? '' : mkdir($imagepath, 0777, true);
		$uuid = microtime();
		$ext = substr(strrchr($_FILES['image']['name'], '.'), 1);
		$ext = in_array($ext, ['jpg', 'gif', 'png', 'bmp', 'jpeg']) ? strtolower($ext) : 'jpg';
		$imagename = $uuid . '.' . $ext;
		$img0->fit(100, 100);
		$img0->save($imagepath . '/' . '0_' . $imagename);
		$resultimagname = $adir . '/' . $bdir . '/' . $cdir.'/'.'0_'.$imagename;
		$data = ['meta'=>['code'=>200,'message'=> '成功'],'data'=>['pic'=>$resultimagname]];
		echo json_encode($data);
			
			
	}
	

	public function dome(Request $request)
	{
		 echo 111;die;
		 
			
	}
	
}
