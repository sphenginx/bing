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
        preg_match('/((\w|-)+\.jpg)/i', $url, $matchs);
        // echo "<pre>";print_r($match);echo "</pre>";
        if ($matchs) {
            $file_name = $saveDir . current($matchs);
        } else {
            $file_name = $saveDir . substr($url, strrpos($url, '/'));
        }
        // echo "$url<br/>$file_name";exit;
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
     * 执行git 提交命令
     *
     * @return void
     * @author Sphenginx
     **/
    private function _commit()
    {
        // 执行git 命令
        $this->_shell("git add .");
        preg_match("/(?:\(©)(.*)(?:\))/i", $this->_bg_name, $matchs);
        $msg = "add: ".date("Y-m-d").' bing image';
        if ($this->_bg_video) {
            $msg .= '、 video ';
        }
        if ($matchs) {
            $msg .= $matchs[1];
        }
        $this->_shell("git commit -m '" . $msg . "'");
        $this->_shell("git push origin master");
    }

    /**
     * 执行并输出 shell 命令
     *
     * @return void
     * @author Sphenginx
     **/
    private function _shell($shell)
    {
        $msg = nl2br(shell_exec($shell));
        // git 默认是 gb2312 编码，这里需要转换成 utf8， 否则页面输出会乱码
        if ($msg) {
            echo iconv("GB2312", "UTF-8//IGNORE", $msg); 
        }
    }

    /**
     * 设置header信息
     *
     * @return void
     * @author Sphenginx
     **/
    private function _setHeader()
    {
        header("Content-type: text/html; charset=utf-8");
    }

    /**
     * 下载bing图片
     *
     * @return mixed
     * @author Sphenginx
     **/
    public function run()
    {
        $this->_setHeader();
        try {
            $this->_shell("git pull");
            $picObj = file_get_contents(self::BG_PIC_XHR_URL);
            $picObj = json_decode($picObj, true);
            if (!$picObj) {
                throw new \Exception('获取bing背景信息失败！');
            }
            $image = current($picObj['images']);
            $this->_bg_name = $image['copyright'];
            //获取背景图片
            if (isset($image['vid']['image'])) {
                $this->_bg_img = "http:". $image['vid']['image'];
            } else {
                $this->_bg_img = "http://cn.bing.com".$image['url'];
            }

            //修改是否下载的方法，可能是Git更新的文件时间，而不是下载更新的文件时间
            if ($this->_isDownload()) {
                throw new \Exception('今天的图片已经下载过了');
            }

            //获取背景视频
            if (isset($image['vid']['sources'][0])) {
                $this->_bg_video = "http:". $image['vid']['sources'][0][2];
            }
            $this->_download();
            $this->_record();
            $this->_commit();
            echo $this->_bg_name.'download  success!';
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }

} // END class Bing

//为防止网速慢影响，设置下最大执行时间
set_time_limit(100);
$bing = new Bing();
$bing->run();