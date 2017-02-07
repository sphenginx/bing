<?php
/**
 * 抓取bing背景图片
 *
 * @package default
 * @author Sphenginx
 **/
class Bing
{
	//获取bing背景图片的url
	const BG_PIC_XHR_URL = 'http://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&pid=hp&video=1';

	const PIC_FILE_PATH = 'bingPic.ini';

	private $_bg_img = '';

	private $_bg_name = '';

	private $_bg_video = '';

	/**
	 * 检测是否下载了今天的图片
	 *
	 * @return void
	 * @author Sphenginx
	 **/
	private function _isDownload()
	{
		$bingDate = filemtime(self::PIC_FILE_PATH);
		$bingYmd  = date('Y-m-d', $bingDate);
		$todayYmd = date('Y-m-d');
		return $bingYmd == $todayYmd;
	}

	/**
	 * 记录需要下载的信息
	 *
	 * @return void
	 * @author Sphenginx
	 **/
	private function _record()
	{
		$context = date('Y-m-d').' : '.$this->_bg_name . ' | '.$this->_bg_img . "\r\n";
		file_put_contents(self::PIC_FILE_PATH, $context, FILE_APPEND);
	}

	/**
	 * 下载图片和视频
	 *
	 * @return void
	 * @author Sphenginx
	 **/
	private function _download()
	{
		$this->_saveFile($this->_bg_img, 'images/');
		if ($this->_bg_video) {
			$this->_saveFile($this->_bg_video, 'video/');
		}
	}

	/**
	 * 保存文件
	 *
	 * @return void
	 * @author Sphenginx
	 **/
	private function _saveFile($url, $saveDir)
	{
		$file_name = substr($url, strrpos($url, '/'));
		$opts = array(
            'http' => array(
	            'method'  => 'GET',
	            'header'  => '',
	            'timeout' => 60
			)
        );
        $context = stream_context_create($opts);
        if (!copy($url, $saveDir . $file_name, $context)) {
        	echo 'failed copy file ' . $url . ' to ' . $saveDir.$file_name . '<br/> ';
        }
	}

	/**
	 * 下载bing图片
	 *
	 * @return void
	 * @author Sphenginx
	 **/
	public function run()
	{
		if ($this->_isDownload()) {
			exit('今天的图片已经下载过了');
		}
		$picObj = file_get_contents(self::BG_PIC_XHR_URL);
		$picObj = json_decode($picObj, true);
		if (!$picObj) {
			exit('获取bing背景信息失败！');
		}

		$this->_bg_name = $picObj['images'][0]['copyright'];
		//获取背景图片
		if (isset($picObj['images'][0]['vid']['image'])) {
			$this->_bg_img = "http:". $picObj['images'][0]['vid']['image'];
		} else {
			$this->_bg_img = "http://cn.bing.com".$picObj['images'][0]['url'];
		}

		//获取背景视频
		if (isset($picObj['images'][0]['vid']['sources'][0])) {
			$this->_bg_video = "http:". $picObj['images'][0]['vid']['sources'][0][2];
		}
		$this->_download();
		$this->_record();
		echo 'download  success!';
	}

} // END class Bing

//为防止网速慢影响，设置下最大执行时间
set_time_limit(100);
$bingObj = new Bing();
$bingObj->run();