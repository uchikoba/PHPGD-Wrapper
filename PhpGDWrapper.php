<?php
/**
 * 画像処理（GD）へのラッパークラス
 *
 * @version     1.0 2018/11/20 Created
 * @access      public
 * @since       PHP 7.2.0 , Dependency php-gd 2.1.0 or later
 * @author      M2G.Uchikoba <uchikoba@gmail.com>
 */
class PhpGDWrapper {
    /** @var resource 画像リソース */
    private $image;

    /** @var int 画像の幅 */
    public $width;

    /** @var int 画像の高さ */
    public $height;

    /**
     * Constructor
     *
     * @params string $image_path path to image file
     * @throw UnSupportedFileTypeException サポートしていないファイル形式（JPG、GIF、PNG、XBM、WBMP、BMP、WEBPのみサポート）
     */
    public function __construct($image_path) {
        list($width, $height, $type) = getimagesize($image_path);
        switch ($type) {
            case IMAGETYPE_JPEG:
            case IMAGETYPE_JPEG2000:
                $this->image = imagecreatefromjpeg($image_path);
                break;
            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($image_path);
                break;
            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($image_path);
                break;
            case IMAGETYPE_XBM:
                $this->image = imagecreatefromxbm($image_path);
                break;
            case IMAGETYPE_WBMP:
                $this->image = imagecreatefromwbmp($image_path);
                break;
            case IMAGETYPE_BMP:
                $this->image = imagecreatefrombmp($image_path);
                break;
            case IMAGETYPE_WEBP:
                $this->image = imagecreatefromwebp($image_path);
                break;
            default:
                $image_type = image_type_to_extension($type);
                $error_message = $image_type . ' is unsupported file type.';
                throw new PhpGDWrapper_UnsupportedFileTypeException($error_message);
                break;
        }
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * 画像のサイズ変更
     *
     * サイズ変更時、幅、高さのいずれか指定値に達したら、残る片方はアスペクト比を維持したサイズまでしか変更しません
     * 幅のみを変更したい場合（例： `convert -geometry 128x` ）は、$heightにゼロ（0）を指定
     * 高さのみを変更したい場合（例： `convert -geometry x128` ）は、$widthにゼロ（0）を指定
     *
     * @params int $width 変換後の画像の幅
     * @params int $height 変換後の画像の高さ
     * @return boolean サイズ変更に成功した場合はTrue、それ以外はFalse
     */
    public function resize($width, $height) {
        $width = ($width == 0) ? $this->width : $width;
        $height = ($height == 0) ? $this->height : $height;
        if (floatval($width / $this->width - 1) <= floatval($height / $this->height - 1)) {
            $rate = floatval($width / $this->width);
            $resize_width = $width;
            $resize_height = (int)min(intval($this->height * $rate), $height);
        } else {
            $rate = floatval($height / $this->height);
            $resize_width = (int)min(intval($this->width * $rate), $width);
            $resize_height = $height;
        }

        $new_image = imagecreatetruecolor($resize_width, $resize_height);
        if (imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $resize_width, $resize_height, $this->width, $this->height)) {
            $this->image = $new_image;
            $this->width = $resize_width;
            $this->height = $resize_height;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 画像ファイルを保存
     *
     * @params string $new_file_path 保存先のファイルパス
     * @return boolean 画像の保存に成功した場合はTrue、それ以外はFalse
     */
    public function saveAsFile($new_file_path) {
        preg_match('/\.\w+?$/u', $new_file_path, $match);
        switch (mb_strtolower($match[0])) {
            case '.jpg':
            case '.jpeg':
                $result = imagejpeg($this->image, $new_file_path, 100);
                break;
            case '.gif':
                $result = imagegif($this->image, $new_file_path);
                break;
            case '.png':
                $result = imagepng($this->image, $new_file_path);
                break;
            case '.xbm':
                $result = imagexbm($this->image, $new_file_path);
                break;
            case '.wbmp':
                $result = imagewbmp($this->image, $new_file_path);
                break;
            case '.bmp':
                $result = imagebmp($this->image, $new_file_path);
                break;
            case '.webp':
                $result = imagewebp($this->image, $new_file_path);
                break;
        }
        chmod($new_file_path, 0666);
        return $result;
    }
}

/**
 * PhpGDWrapperでサポートしていないファイル形式
 *
 * @version     1.0 2018/11/20 Created
 * @access      public
 * @since       PHP 7.2.0 , Dependency php-gd 2.1.0 or later
 * @author      M2G.Uchikoba <uchikoba@gmail.com>
 */
class PhpGDWrapper_UnsupportedFileTypeException extends Exception {
    /**
     * Constructor
     *
     * @params string $message exception message
     * @params int $code exception number
     * @params Exception $previous previous exception if nested exception
     */
    public function __construct($message = null, $code = 0, Exception $previous = null) {
        $message = ($message == null) ? 'Unsupported file type.' : $message;
        parent::__construct($message, $code, $previous);
    }
}

