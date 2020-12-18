<?php
/**
 * @Author: pizepei
 * @Date:   2018-08-10 15:20:07
 * @Last Modified by:   pizepei
 * @Last Modified time: 2018-08-10 15:28:58
 * @title 纯真数据库自动更新
 */
namespace normphpCore\terminalInfo;

use normphpCore\helper\Helper;

class UpdateQqwry{
    /**
     * 缓存目录
     * @var string
     */
    public $path = '..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    /**
     * 缓存时间 单位天
     * @var int
     */
    public $updateTime = 3;
    /**
     * @var null | Helper
     */
    private $Helper = null;
    /**
     * cdn 配置中心
     * @var string
     */
    public $cdnUrl = 'http://dev.heil.red/normphp/terminal-info/cdn/url/qqwry.json';
    /**
     * UpdateQqwry constructor.
     * @param bool $update
     * @param string $path
     * @param string $environment环境 general 为普通环境  normphp为normphp框架环境（部分函数使用方式不同）
     * @throws \Exception
     */
    public function __construct(bool $update=false,$path,$environment='general')
    {
        if ($environment ==='normphp'){
            $this->Helper = Helper();
        }else{
            $this->Helper = Helper::init();
        }
        #环境 general 为普通环境  normphp为normphp框架环境（部分函数使用方式不同）
        $this->path = $path;
        if(!is_file($this->path)){
            $pathArray = explode(DIRECTORY_SEPARATOR, $this->path);
            array_pop($pathArray);
            $this->Helper->file()->createDir(implode(DIRECTORY_SEPARATOR,$pathArray));
            $this->getQqwry();
            return true;
        }
        if(!@filemtime($this->path)){
            $this->Helper->file()->createDir($this->path);
            $this->getQqwry();
            return true;
        }
        /**
         * 更新Qqwry文件
         */
        if ($update){
            # 默认3天86400*3 触发一次更新
            if((@filemtime($this->path) + (86400*$this->updateTime)) < time() ){
                $this->getQqwry();
                return true;
            }else{
                return false;
            }
        }

    }

    /**
     * 请求头
     */
    const header = [
        'User-Agent: Mozilla/3.0 (compatible; Indy Library)',
        'Accept: text/html, */*'
    ];
    /**
     * @Author 皮泽培
     * @Created 2019/8/13 11:01
     * @title  更新qqwry.dat文件
     * @throws \Exception
     */
    protected function getQqwry()
    {
        #纯真数据库自动更新原理实_FILE__ http://update.cz88.net/soft/setup.zip
        #$copywrite = file_get_contents("http://update.cz88.net/ip/copywrite.rar");
        #$qqwry = file_get_contents("http://update.cz88.net/ip/qqwry.rar");
        $copywrite = $this->Helper->httpRequest('http://update.cz88.net/ip/copywrite.rar','',[
            'header'=>self::header,
        ])['body'];
        $qqwry = $this->Helper->httpRequest('http://update.cz88.net/ip/qqwry.rar','',[
            'header'=>self::header,
        ])['body'];

        if ($this->Helper->is_empty($copywrite) || $this->Helper->is_empty($qqwry)){
            $this->getCdnQqwry();
        }
        //函数从二进制字符串对数据进行解包。
        $key = unpack("V6", $copywrite)[6];
        for($i=0; $i<0x200; $i++)
        {
            $key *= 0x805;
            $key ++;
            $key = $key & 0xFF;
            $qqwry[$i] = chr( ord($qqwry[$i]) ^ $key );
        }
        //此函数解压缩压缩字符串。
        $qqwry = gzuncompress($qqwry);
        # 创建qqwry.dat
        $fp = fopen($this->path, "wb");
        if($fp)
        {
            # 函数写入文件（可安全用于二进制文件）。
            fwrite($fp, $qqwry);
            # fclose() 函数关闭一个打开文件。
            fclose($fp);
        }

    }

    /**
     * @Author 皮泽培
     * @Created 2019/8/13 11:01
     * @title  通过cdn更新qqwry.dat文件
     * @throws \Exception
     */
    public function getCdnQqwry()
    {
        # 请求api中心获取cdn地址
        $url = $this->Helper->httpRequest($this->cdnUrl);
        $body = $this->Helper->json_decode($url['body']);
        if ($this->Helper->is_empty($body['data'])){
            return false;
        }
        # 通过cdn地址 请求qqwry.dat文件（依次）
        foreach ($body['data'] as $value){
            $dat = $this->Helper->httpRequest($value);
            if (!$this->Helper->is_empty($dat['body'])){
                file_put_contents($this->path,$dat['body']);
                return true;
            }
        }
    }

}

 