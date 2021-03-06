<?php
/**
 * DateTime: 2020/1/13 9:41
 */
namespace chesterlyd\imagePoster;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;

class QrcodeServer
{
    protected $_qr;
    protected $_encoding        = 'UTF-8';              // 编码类型
    protected $_size            = 300;                  // 二维码大小
    protected $_qrCode_x        = 300;                  // 二维码X位置
    protected $_qrCode_y        = 300;                  // 二维码Y位置
    protected $_logo            = false;                // 是否需要带logo的二维码
    protected $_logo_url        = '';                   // logo图片路径
    protected $_logo_size       = 80;                   // logo大小
    protected $_title           = false;                // 是否需要二维码title
    protected $_title_content   = '';                   // title内容
    protected $_generate        = 'display';            // display-直接显示  writefile-写入文件
    protected $_file_name       = './static/qrcode';    // 写入文件路径
    protected $_back_img        = false;                // 是否需要带背景
    protected $_back_img_url    = '';                   // 背景图路径
    protected $_font            = '';                   // 字体路径
    protected $_poster_text_size= '';                   // 海报字体大小
    protected $_poster_text     = '';                   // 海报文字内容
    protected $_poster_text_rgb = [];                   // 海报文字颜色
    protected $_poster_text_x   = '';                   // 海报文字x位置
    protected $_poster_text_y   = '';                   // 海报文字y位置
    protected $_poster_img_name = '';                   // 海报文件名
    const MARGIN           = 10;                        // 二维码内容相对于整张图片的外边距
    const WRITE_NAME       = 'png';                     // 写入文件的后缀名
    const FOREGROUND_COLOR = ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];          // 前景色
    const BACKGROUND_COLOR = ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0];    // 背景色

    public function __construct($config) {
        isset($config['generate'])          &&  $this->_generate          = $config['generate'];
        isset($config['encoding'])          &&  $this->_encoding          = $config['encoding'];
        isset($config['size'])              &&  $this->_size              = $config['size'];
        isset($config['qrCode_x'])          &&  $this->_qrCode_x          = $config['qrCode_x'];
        isset($config['qrCode_y'])          &&  $this->_qrCode_y          = $config['qrCode_y'];
        isset($config['logo'])              &&  $this->_logo              = $config['logo'];
        isset($config['logo_url'])          &&  $this->_logo_url          = $config['logo_url'];
        isset($config['logo_size'])         &&  $this->_logo_size         = $config['logo_size'];
        isset($config['title'])             &&  $this->_title             = $config['title'];
        isset($config['title_content'])     &&  $this->_title_content     = $config['title_content'];
        isset($config['file_name'])         &&  $this->_file_name         = $config['file_name'];
        isset($config['back_img'])          &&  $this->_back_img          = $config['back_img'];
        isset($config['back_img_url'])      &&  $this->_back_img_url      = $config['back_img_url'];
        isset($config['font'])              &&  $this->_font              = $config['font'];
        isset($config['poster_text_size'])  &&  $this->_poster_text_size  = $config['poster_text_size'];
        isset($config['poster_text'])       &&  $this->_poster_text       = $config['poster_text'];
        isset($config['poster_text_rgb'])   &&  $this->_poster_text_rgb   = $config['poster_text_rgb'];
        isset($config['poster_text_x'])     &&  $this->_poster_text_x     = $config['poster_text_x'];
        isset($config['poster_text_y'])     &&  $this->_poster_text_y     = $config['poster_text_y'];
        isset($config['poster_img_name'])   &&  $this->_poster_img_name   = $config['poster_img_name'];
    }

    /**
     * 生成二维码
     * @param $content //需要写入的内容
     * @return array
     * @throws \Endroid\QrCode\Exception\InvalidPathException
     */
    public function createServer($content) {
        $this->_qr = new QrCode($content);
        $this->_qr->setSize($this->_size);
        $this->_qr->setWriterByName(self::WRITE_NAME);
        $this->_qr->setMargin(self::MARGIN);
        $this->_qr->setEncoding($this->_encoding);
        $this->_qr->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());   // 容错率
        $this->_qr->setForegroundColor(self::FOREGROUND_COLOR);
        $this->_qr->setBackgroundColor(self::BACKGROUND_COLOR);
        // 是否需要title
        if ($this->_title) {
            $this->_qr->setLabel($this->_title_content, 16, null, LabelAlignment::CENTER);
        }
        // 是否需要logo
        if ($this->_logo) {
            $this->_qr->setLogoPath($this->_logo_url);
            $this->_qr->setLogoWidth($this->_logo_size);
        }

        $this->_qr->setValidateResult(false);

        if ($this->_generate == 'display') {
            // 展示二维码
            // 前端调用 例：<img src="http://localhost/qr.php?url=base64_url_string">
            header('Content-Type: ' . $this->_qr->getContentType());
            return $this->_qr->writeString();
        } else if ($this->_generate == 'writefile') {
            // 写入文件
            $file_name = $this->_file_name;
            return $this->generateImg($file_name);
        } else if ($this->_generate == 'postimage') {
            $file_name = $this->_file_name;
            $src_im = $this->generateImg($file_name);
            $poster_file_name = $this->generatePosterImage($this->_back_img_url, $src_im['data']['url']);
            return ['success' => false, 'message' => 'the generate type not found', 'data' => $poster_file_name];
        } else {
            return ['success' => false, 'message' => 'the generate type not found', 'data' => ''];
        }
    }

    /**
     * 生成文件
     * @param $file_name //目录文件 例: /tmp
     * @return array
     */
    public function generateImg($file_name) {
        $file_path = $file_name . DIRECTORY_SEPARATOR . uniqid() . '.' . self::WRITE_NAME;

        if (!file_exists($file_name)) {
            mkdir($file_name, 0777, true);
        }

        try {
            $this->_qr->writeFile($file_path);
            $data = [
                'url' => $file_path,
                'ext' => self::WRITE_NAME,
            ];
            return ['success' => true, 'message' => 'write qrimg success', 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => ''];
        }
    }

    public function generatePosterImage($dst_im, $src_im)
    {
        $dst_type = getimagesize($dst_im);
        list($src_w, $src_h) = getimagesize($src_im);
        $dst_x = $this->_qrCode_x;
        $dst_y = $this->_qrCode_y;
        $dst = imagecreatefromstring(file_get_contents($dst_im));
        $src = imagecreatefromstring(file_get_contents($src_im));
//imagecopymerge($dst, $src, 10, 10, 0, 0, $src_w, $src_h, 50);
        $black = imagecolorallocate($dst, $this->_poster_text_rgb['r'], $this->_poster_text_rgb['g'], $this->_poster_text_rgb['b']);
        imagettftext($dst, $this->_poster_text_size, 0, $this->_poster_text_x, $this->_poster_text_y, $black, $this->_font, $this->_poster_text);
        imagecopy($dst, $src, $dst_x, $dst_y, 0, 0, $src_w, $src_h);

        if ($this->_back_img) {
            $file_name = '';

            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }

            if (!file_exists('uploads/'. date('Ymd'))) {
                mkdir('uploads/'. date('Ymd'), 0777, true);
            }

            switch ($dst_type[2]) {
                case 1://GIF
//                header('Content-Type: image/gif');
//                header('Content-Disposition: inline; filename="image.gif"');
                    imagegif($dst, 'uploads/'.date('Ymd'). '/'. $this->_poster_img_name . '.gif');
                    $file_name = 'uploads/'.date('Ymd'). '/'. $this->_poster_img_name . '.gif';
                    break;
                case 2://JPG
//                header('Content-Type: image/jpeg');
//                header('Content-Disposition: inline; filename="image.jpg"');
                    imagejpeg($dst, 'uploads/'.date('Ymd'). '/'. $this->_poster_img_name . '.jpg');
                    $file_name = 'uploads/'.date('Ymd'). '/'. $this->_poster_img_name . '.jpg';
                    break;
                case 3://PNG
//                header('Content-Type: image/png');
//                header('Content-Disposition: inline; filename="image.png"');
                    imagepng($dst, 'uploads/'.date('Ymd'). '/'. $this->_poster_img_name . '.png');
                    $file_name = 'uploads/'.date('Ymd'). '/'. $this->_poster_img_name . '.png';
                    break;
                default:
                    break;
            }
            imagedestroy($dst);
            imagedestroy($src);
            return $file_name;
        } else {
            switch ($dst_type[2]) {
                case 1://GIF
                    header('Content-Type: image/gif');
                    header('Content-Disposition: inline; filename="image.gif"');
                    imagegif($dst);
                    break;
                case 2://JPG
                    header('Content-Type: image/jpeg');
                    header('Content-Disposition: inline; filename="image.jpg"');
                    imagejpeg($dst);
                    break;
                case 3://PNG
                    header('Content-Type: image/png');
                    header('Content-Disposition: inline; filename="image.png"');
                    imagepng($dst);
                    break;
                default:
                    break;
            }

            imagedestroy($dst);
            imagedestroy($src);
            exit();
        }

    }
}