<?php
/**
 * 抓取bing背景图片
 *
 * @package default
 * @author Sphenginx
 **/

namespace Sphenginx\Bing;

class Bing
{
    //获取bing背景图片的url: format = js 为json格式数据(默认为xml数据)，idx=0表示获取今天的图片，-1为明天，1表示昨天
    const BG_PIC_XHR_URL = 'http://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&pid=hp&video=1';

    const PIC_FILE_PATH = 'bingPic.ini';

    private $_context = '';

    private $_bg_img = '';

    private $_bg_name = '';

    private $_bg_video = '';

    /**
     * 检测是否下载了今天的图片
     *
     * @return boolean
     * @author Sphenginx
     **/
    private function _isDownload()
    {
        $downloadInfo = file_get_contents(self::PIC_FILE_PATH);
        return strrpos($downloadInfo, $this->_getContext()) !== false;
    }

    /**
     * 记录需要下载的信息
     *
     * @return void
     * @author Sphenginx
     **/
    private function _record()
    {
        return file_put_contents(self::PIC_FILE_PATH, $this->_getContext(), FILE_APPEND);
    }

    /**
     * 获取要写入的文件内容
     *
     * @return string
     * @author Sphenginx
     **/
    private function _getContext()
    {
        if (!$this->_context) {
            $this->_context = date('Y-m-d').' : '.$this->_bg_name . ' | '.$this->_bg_img . "\r\n";
        }
        return $this->_context;
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
     * @return mixed
     * @author Sphenginx
     **/
    private function _saveFile($url, $saveDir)
    {
        $file_name = $saveDir . substr($url, strrpos($url, '/'));
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'header'  => '',
                'timeout' => 60
            )
        );
        $context = stream_context_create($opts);
        if (!copy($url, $file_name, $context)) {
            throw new \Exception('failed copy file ' . $url . ' to ' . $file_name);   
        }
    }

    /**
     * 下载bing图片
     *
     * @return mixed
     * @author Sphenginx
     **/
    public function run()
    {
        try {
            $picObj = file_get_contents(self::BG_PIC_XHR_URL);
            $picObj = json_decode($picObj, true);
            if (!$picObj) {
                throw new \Exception('获取bing背景信息失败！');
            }

            $this->_bg_name = $picObj['images'][0]['copyright'];
            //获取背景图片
            if (isset($picObj['images'][0]['vid']['image'])) {
                $this->_bg_img = "http:". $picObj['images'][0]['vid']['image'];
            } else {
                $this->_bg_img = "http://cn.bing.com".$picObj['images'][0]['url'];
            }

            //修改是否下载的方法，可能是Git更新的文件时间，而不是下载更新的文件时间
            if ($this->_isDownload()) {
                throw new \Exception('今天的图片已经下载过了');
            }

            //获取背景视频
            if (isset($picObj['images'][0]['vid']['sources'][0])) {
                $this->_bg_video = "http:". $picObj['images'][0]['vid']['sources'][0][2];
            }
            $this->_download();
            $this->_record();
            echo $this->_bg_name.'download  success!';
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }

} // END class Bing

//为防止网速慢影响，设置下最大执行时间
set_time_limit(100);
$bingObj = new Bing();
$bingObj->run();