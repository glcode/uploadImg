<?php
/**
 * 基本的图片处理，包括图片缩放，裁剪，打水印等
 * 
 * 单纯缩略图
 * 用法：
 * 
 * //其他调用方式
 * include('doImagick.php'); 
 * $image = new doImagick('test.jpg');
 * 
 * //压缩质量，默认100
 * $image->setQuality(80);
 * //缩放类型 默认是0，则等比缩放，1为指定大小裁剪
 * $image->setType(1);
 * //设置保存路径
 * $image->setDstImage('thumb_test.jpg');
 * //按比例生成宽为1000的图片
 * $image->thumbImage(1000,0);
 * 
 * 单纯打水印
 * 用法：
 * $image = new doImagick('test.jpg');
 * //设置保存路径
 * $image->setDstImage('water_test.jpg');
 * //设置水印的位置    默认是9，右下角；其中1左上，2中上，3右上，4左中，5正中，6右中，7左下，8中下，9右下
 * $image->setPos(5);
 * //生成水印
 * $image->water('mark.gif');
 * 
 * 缩略+水印
 * $image = new doImagick();
 *	$image->setSrcImage('test.jpg');
 *	$image->setDstImage('dest.jpg');
 *	$image->setWaterImg('water.png');
 *	$image->createImage(1200,900);
 */
class doImagick {
	
	private $_srcDir = null;
	private $_dstDir = null;
	private $_quality = 100;
	private $_debug = false;
	private $_waterImg = null;
	private $_srcImg = null;
	private $_cutType = 0;
	private $_pos = 9;

	public  $src_w = 0;
	public  $src_h = 0;
	public  $dst_w = 0;
	public  $dst_h = 0;
	
	
	public function __construct($fileName=''){
		if(true !== extension_loaded('imagick')){ 
			throw new Exception('Imagick extension is not loaded.'); 
		} 
		if(true !== class_exists('Imagick')){ 
			throw new Exception('Imagick class does not exist.'); 
		}
		if($fileName != ''){
			$this->_srcImg = new Imagick($fileName);
		}
		
		if($this->_srcImg != null){
			$this->src_w = $this->_srcImg->getImageWidth();
			$this->src_h = $this->_srcImg->getImageHeight();
		}
		if(defined('DEBUG')){
			$this->_debug = DEBUG;
		}
	}
	
	/**
	 * 设置原图
	 * @param    string    $fileName   导入图片路径
	 */
	public function setSrcImage($fileName){
		$this->checkImage($fileName);
		$this->_srcImg = new Imagick($fileName);
		if($this->_srcImg != null){
			$this->src_w = $this->_srcImg->getImageWidth();
			$this->src_h = $this->_srcImg->getImageHeight();
		}
	}

	/**
	 * 保存文件
	 * @param    String    $fileName   图片生成路径
	 */
	public function setDstImage($fileName){
		//$this->checkImage($fileName);
		$this->_dstDir = $fileName;
	}

	/**
	 * 设置生成图片质量
	 * @param    Interge    $quality   图片生成质量
	 */
	public function setQuality($quality){
		$this->_quality = $quality;
	}

	/**
	 * 等比缩放  或 缩裁到指定大小
	 * @param    $file      String    生成图片路径
	 * @param    Interge    $width    生成缩略图宽度
	 * @param    Interge    $height   生成缩略图高度
	 * 
	 */
	public function thumbImage($width,$height,$file=''){
		if($this->_srcImg == null)return;
		try{
			$image = $this->_srcImg;
			if($this->_cutType == 0){//等比缩放
				$ret = $image->thumbnailImage($width,$height,true);
			}elseif($this->_cutType == 1){//缩裁到指定大小
				//$image->cropThumbnailImage($width, $height);
				$ret = $this->cropThumbnailImage($image, $width, $height);
			}
			if($ret !== true) return $ret;

			$image->setCompression($this->_quality);
			$this->dst_w = $image->getImageWidth();
			$this->dst_h = $image->getImageHeight();
			$savePath = $file?$file:($this->_dstDir == null ? $this->_srcDir:$this->_dstDir);
			return $image->writeImage($savePath);
		}catch (Exception $e){
			$this->error('缩图失败!');
		}
	}

	/**
	 * 等比缩放后空白位置透明  或 缩裁到指定大小
	 * @param    $file      String    生成图片路径
	 * @param    Interge    $width    生成缩略图宽度
	 * @param    Interge    $height   生成缩略图高度
	 * 
	 */
	public function thumbImageScaleFill($width,$height,$file=''){
		if($this->_srcImg == null)return;
		$image = $this->_srcImg;
		$img_type = strtolower($image->getImageFormat());
		$src_width = $image->getImageWidth();
		$src_height = $image->getImageHeight();

		$x = 0;
		$y = 0;

		$dst_width = $width;
		$dst_height = $height;

		//计算图片放正中间
		if($src_width*$height > $src_height*$width)
		{
			$dst_height = intval($width*$src_height/$src_width);
			$y = intval( ($height-$dst_height)/2 );
		}
		else
		{
			$dst_width = intval($height*$src_width/$src_height);
			$x = intval( ($width-$dst_width)/2 );
		}

		$canvas = new Imagick();
		
		//背景颜色,默认是白色透明
		$color = 'rgba(255,255,255,170)';

		try{
			$image->thumbnailImage( $width, $height, true );

			$draw = new ImagickDraw();
			$draw->composite($image->getImageCompose(), $x, $y, $dst_width, $dst_height, $image);

			$canvas->newImage($width, $height, $color , $img_type );
			$canvas->drawImage($draw);
			$canvas->setImagePage($width, $height, 0, 0);

			$image = $canvas;	
			$image->setCompression($this->_quality);


			$savePath = $file?$file:($this->_dstDir == null ? $this->_srcDir:$this->_dstDir);
			return $image->writeImage($savePath);
		}catch (Exception $e){
			$this->error('缩图失败!');
		}
	}

	/**
	* 生成图片水印
	* @param  String $sourceImg  原图路径
	* @param  String $water  水印路径
	* @param  Interge $pos  水印生成的位置 1-9
	* @return String 保存图片路径
	*/
	public function water($water=null, $sourceImg=null) {
		if($water != null){
			$this->checkImage($water);
			$water = new Imagick($water);
		}elseif($this->_waterImg){
			$water = new Imagick($this->_waterImg);
		}
		if(!isset($water) || $water == null)return;
		if($sourceImg != null){
			$image = new Imagick($sourceImg);
		}elseif($this->_srcImg){
			$image = $this->_srcImg;
		}
		if(!isset($image) || $image == null)return;
		list($fileWidth, $fileHeight) = array_values($image->getImageGeometry());
		list($waterWidth, $waterHeight) = array_values($water->getImageGeometry());
		if($fileWidth < $waterWidth || $fileHeight < $waterHeight) {
			return false;
		}
		$xypos = $this->getXyPos($this->_pos,$fileWidth,$fileHeight,$waterWidth,$waterHeight);
		list($x,$y) = array_values($xypos);
		try {
			$composite = false;
			$tryTimes = 0;
			while ($composite === false && $tryTimes < 3){
				$composite = @$image->compositeImage($water, Imagick::COMPOSITE_DEFAULT, $x, $y);
				$tryTimes += 1;
			}
			if(!$composite)return;
			//$image->compositeImage($water, $water->getImageCompose(), $x, $y);
			$image->setCompression($this->_quality);
			$savePath = $this->_dstDir == null ? $this->_srcDir:$this->_dstDir;
			return $image->writeImage($savePath);
		} catch(Exception $e) {
			$this->error('生成水印图失败！');
		}
	}

	/**
	* 生成动态gif图片水印
	* @param  String $sourceImg  原图路径
	* @param  String $water  水印路径
	* @param  Interge $pos  水印生成的位置 1-9
	* @return String 保存图片路径
	*/
	public function dsyWater($sourceImg=null, $water=null){
		if($water != null){
			$this->checkImage($water);
			$water = new Imagick($water);
		}elseif($this->_waterImg){
			$water = new Imagick($this->_waterImg);
		}
		if(!isset($water) || $water == null)return;
		if($sourceImg != null){
			$image = new Imagick($sourceImg);
		}else{
			$image = $this->_srcImg;
		}
		$animation = new Imagick();
		$animation->setFormat("gif");
		$image = $image->coalesceImages();
		$total = $image->getNumberImages();
		list($fileWidth, $fileHeight) = array_values($image->getImageGeometry());
		list($waterWidth, $waterHeight) = array_values($water->getImageGeometry());
		if($fileWidth < $waterWidth || $fileHeight < $waterHeight) {
			return false;
		}
		$xypos = $this->getXyPos($this->_pos,$fileWidth,$fileHeight,$waterWidth,$waterHeight);
		list($x,$y) = array_values($xypos);
		try {
			for($i=0;$i < $total;$i++){
				$image->setImageIndex($i);
				$tmpImage = new Imagick();
				$tmpImage->readImageBlob($image);
				$delay = $tmpImage->getImageDelay();
				$tmpImage->compositeImage($water, Imagick::COMPOSITE_DEFAULT, $x, $y);
				$animation->addImage($tmpImage);
				$animation->setImageDelay($delay);
			}
			$savePath = $this->_dstDir == null ? $this->_srcDir:$this->_dstDir;
			$animation->writeImages($savePath,true);
			$animation->destroy();
			//$image->destroy();
			return true;
	   } catch(Exception $e) {
			$this->error('生成水印图失败！');
	   }
	}

	/**
	 * 等比缩放
	 * 
	 * @param    Interge    $width    生成图宽度
	 * @param    Interge    $height   生成图高度
	 * @param    Interge     $type    压缩类型
	 * 
	 */
	public function createImage($width,$height,$file=''){
		if($this->_srcImg == null)return;
		try{
			$image = $this->_srcImg;
			if($this->_cutType == 1){
				//$image->cropThumbnailImage($cWidth, $cHeight);
				$ret = $this->cropThumbnailImage($image, $width, $height);
			}else{
				$ret = $image->thumbnailImage($width,$height,true);
			}
			if($ret !== true) return $ret;

			$this->dst_w = $image->getImageWidth();
			$this->dst_h = $image->getImageHeight();
			if($this->_waterImg){
				$water = new Imagick($this->_waterImg);
				list($fileWidth, $fileHeight) = array($this->dst_w,$this->dst_h);
				list($waterWidth, $waterHeight) = array_values($water->getImageGeometry());
				if($fileWidth >= $waterWidth && $fileHeight >= $waterHeight) {
					$xypos = $this->getXyPos($this->_pos,$fileWidth,$fileHeight,$waterWidth,$waterHeight);
					list($x,$y) = array_values($xypos);
					$composite = false;
					$tryTimes = 0;
					while ($composite === false && $tryTimes < 3){
						$composite = @$image->compositeImage($water, Imagick::COMPOSITE_DEFAULT, $x, $y);
						$tryTimes += 1;
					}
				}
			}
			$image->setCompression($this->_quality);
			$savePath = $file?$file:($this->_dstDir == null ? $this->_srcDir:$this->_dstDir);
			return $image->writeImage($savePath);
		}catch (Exception $e){
			$this->error('处理失败!');
		}
	}
	private function cropThumbnailImage($image,$width,$height){
		if($this->src_w < $width || $this->src_h < $height){
			$this->error('图片尺寸过小');
			return false;
		}
		/*
		if(($this->src_w/$width) < ($this->src_h/$height)){
			$image->cropImage($this->src_w, floor($height*$this->src_w/$width),  0, $this->src_h*0.1);
		}else{
			$image->cropImage(ceil($width*$this->src_h/$height), $this->src_h, (($this->src_w-($width*$this->src_h/$height))/2), 0);
		}
		*/
		if($this->src_h/$this->src_w > $height/$width){//高图
			$newHeight = floor($height*$this->src_w/$width);
			$offsetY = floor(($this->src_h - $newHeight)*0.1);
			$image->cropImage($this->src_w, $newHeight,  0, $offsetY);
		}else{//宽图
			$image->cropImage(ceil($width*$this->src_h/$height), $this->src_h, (($this->src_w-($width*$this->src_h/$height))/2), 0);
		}
		return $image->thumbnailImage($width,$height);
	}

	/**
	* 获取水印在原图的相对位置坐标
	* 
	* @param  Interge $type  位置类型 默认是9，右下角；其中1左上，2中上，3右上，4左中，5正中，6右中，7左下，8中下，9右下
	* @param  Interge $sourceW  原图宽
	* @param  Interge $sourceH  原图高
	* @param  Interge $waterW   水印宽
	* @param  Interge $waterH   水印高
	* @return Array  坐标数据x，y
	*/
	private function getXyPos($type=9,$sourceW,$sourceH,$waterW,$waterH){
		$x = 0;
		$y = 0;
		switch($type) {
			case 1:
				$x = +5;
				$y = +5;
				break;
			case 2:
				$x = ($sourceW - $waterW) / 2;
				$y = +5;
				break;
			case 3:
				 $x = $sourceW - $waterW - 5;
				 $y = +5;
				 break;
			case 4:
				 $x = +5;
				 $y = ($sourceH - $waterH) / 2;
				 break;
			case 5:
				 $x = ($sourceW - $waterW) / 2;
				 $y = ($sourceH - $waterH) / 2;
				 break;
			case 6:
				 $x = $sourceW - $waterW;
				 $y = ($sourceH - $waterH) / 2;
				 break;
			case 7:
				 $x = +5;
				 $y = $sourceH - $waterH - 5;
				 break;
			case 8:
				 $x = ($sourceW - $waterW) / 2;
				 $y = $sourceH - $waterH - 5;
				 break;
			case 9:
				 $x = $sourceW - $waterW - 5;
				 $y = $sourceH - $waterH - 5;
				 break;
		}
		return array('x'=>$x,'y'=>$y);
	}

	/**
	 * 
	 * 设置水印
	 * @param String $water 水印文件路径
	 */
	public function setWaterImg($water){
		$this->checkImage($water);
		$this->_waterImg = $water;
		//if($this->_waterImg == null)
		//	$this->_waterImg = new Imagick($water);
	}
	
	/**
	 * 
	 * 设置压缩类型
	 * @param Interge $type 0为等比压缩，1为裁剪
	 */
	public function setCutType($type){
		$this->_cutType = $type;
		//if($this->_waterImg == null)
		//	$this->_waterImg = new Imagick($water);
	}

	/**
	 * 设置水印位置
	 * @param  Interge $type  位置类型 默认是9，右下角；其中1左上，2中上，3右上，4左中，5正中，6右中，7左下，8中下，9右下
	 */
	public function setPos($pos){
		$this->_pos = $pos;
	}

	/**
	 * 
	 * 检查文件路径合法
	 * @param String $fileName 文件路径
	 */
	private function checkImage($fileName){
		return;
		if(!file_exists($fileName)){
			$this->error("文件 {$fileName} 不存在！");
		}
	}

	/**
	 * 
	 * 错误处理
	 * @param String $msg 错误信息
	 */
	private function error($msg){
		if($this->_debug){
			//throw new Exception($msg);
			//echo $msg;
			//exit($msg);
			return $msg;
		}else{
			return false;
		}
	}

	/**
	 * 
	 * 析构函数，释放内存
	 */
	public function __destruct(){
		if($this->_srcImg){
			$this->_srcImg->destroy();
		}
	}

}
