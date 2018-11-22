<?php namespace ElementVip\UEditor\Uploader;

use ElementVip\UEditor\Uploader\Upload;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Storage;

/**
 * Class UploadCatch
 * 图片远程抓取
 *
 * @package ElementVip\UEditor\Uploader
 */
class UploadCatch extends Upload
{
    use UploadQiniu;

    public function doUpload()
    {
        $imgUrl = $this->config['imgUrl'];
        $imgUrl = $this->filterUrl($imgUrl);

        $client = new Client(['verify' => false]);
        $img = $client->request('get', $imgUrl)->getBody()->getContents();

        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

        $this->oriName = $m ? $m[1] : "";   //原始文件名
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = basename($this->filePath);
        $dirname = dirname($this->filePath);

        /*fullName:/uploads/ueditor/php/upload/image/20171002/1506927889762354.png
          filePath:uploads/ueditor/php/upload/image/20171002/1506927889762354.png
          fileName:1506927889762354.png  */

        if (Storage::put('public/' . $this->filePath, $img)) {
            $this->stateInfo = $this->stateMap[0];
            $prefix = config('UEditorUpload.upload.catcherUrlPrefix');

            $this->fullName = $prefix ? '/storage' . $this->fullName : env('APP_URL') . '/storage' . $this->fullName;
            return true;
        } else {
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
            return false;
        }


        /*$imgUrl = strtolower(str_replace("&amp;", "&", $this->config['imgUrl']));
        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_LINK");
            return false;
        }
        //获取请求头并检测死链
        $heads = get_headers($imgUrl);

        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            $this->stateInfo = $this->getStateInfo("ERROR_DEAD_LINK");
            return false;
        }

        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl, '.'));
        if (!in_array($fileType, $this->config['allowFiles']) ) {
            $this->stateInfo = $this->getStateInfo("ERROR_HTTP_CONTENTTYPE");
            return false;
        }

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();

        ob_end_clean();

        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);


        $this->oriName = $m ? $m[1]:"";
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName =  basename($this->filePath);
        $dirname = dirname($this->filePath);





        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return false;
        }


        if(config('UEditorUpload.core.mode')=='local'){
            //创建目录失败
            if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
                $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
                return false;
            } else if (!is_writeable($dirname)) {
                $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
                return false;
            }

            //移动文件
            if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                return false;
            } else { //移动成功
                $this->stateInfo = $this->stateMap[0];
                return true;
            }
        }else if(config('UEditorUpload.core.mode')=='qiniu'){

            return $this->uploadQiniu($this->filePath,$img);

        }else{
            $this->stateInfo = $this->getStateInfo("ERROR_UNKNOWN_MODE");
            return false;
        }
*/


    }

    /**
     * 获取文件扩展名
     * @return string
     */
    protected function getFileExt()
    {
        if (str_contains($this->oriName, 'wx_fmt')) {
            return '.' . $this->convertUrlQuery($this->oriName)['wx_fmt'];
        }

        return strtolower(strrchr($this->oriName, '.'));
    }


    /**
     * 过滤URL中的问号
     * @param $url
     * @return mixed
     */
    protected function filterUrl($url)
    {
        if (str_contains($url, 'wx_fmt')) {    //如果是微信图片
            return $url;
        }

        if (str_contains($url, '?')) {
            return strstr($url, '?', true);
        }

        return $url;
    }

    /**
     * 参数变数组
     * @param $query
     */
    protected function convertUrlQuery($url)
    {
        $query = parse_url($url)['query'];

        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }
}