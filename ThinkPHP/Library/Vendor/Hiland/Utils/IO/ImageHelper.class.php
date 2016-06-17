<?php
namespace Vendor\Hiland\Utils\IO;

/**
 *
 * @author devel
 *
 */
class ImageHelper
{
    /**
     * 实现等比例不失真缩放图片缩放
     * (在本函数调用的地方，使用完成后请使用imagedestroy($newimage)对新资源进行销毁)
     *
     * @param resource $sourceimage
     *            原来的图片资源
     * @param int $targetmaxwidth
     *            图片放缩后允许的最多宽度
     * @param int $targetmaxheight
     *            图片放缩后允许的最多高度
     * @return resource 按比例放缩后的图片
     */
    public static function resizedImage($sourceimage, $targetmaxwidth, $targetmaxheight)
    {
        $sourcewidth = imagesx($sourceimage);
        $sourceheight = imagesy($sourceimage);

        if (($targetmaxwidth && $sourcewidth > $targetmaxwidth) || ($targetmaxheight && $sourceheight > $targetmaxheight)) {

            $resizeWidthTag = false;
            $resizeHeightTag = false;

            if ($targetmaxwidth && $sourcewidth > $targetmaxwidth) {
                $widthratio = $targetmaxwidth / $sourcewidth;
                $resizeWidthTag = true;
            }

            if ($targetmaxheight && $sourceheight > $targetmaxheight) {
                $heightratio = $targetmaxheight / $sourceheight;
                $resizeHeightTag = true;
            }

            if ($resizeWidthTag && $resizeHeightTag) {
                if ($widthratio < $heightratio)
                    $ratio = $widthratio;
                else
                    $ratio = $heightratio;
            }

            if ($resizeWidthTag && !$resizeHeightTag)
                $ratio = $widthratio;
            if ($resizeHeightTag && !$resizeWidthTag)
                $ratio = $heightratio;

            $newwidth = $sourcewidth * $ratio;
            $newheight = $sourceheight * $ratio;

            if (function_exists("imagecopyresampled")) {
                $newimage = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($newimage, $sourceimage, 0, 0, 0, 0, $newwidth, $newheight, $sourcewidth, $sourceheight);
            } else {
                $newimage = imagecreate($newwidth, $newheight);
                imagecopyresized($newimage, $sourceimage, 0, 0, 0, 0, $newwidth, $newheight, $sourcewidth, $sourceheight);
            }
            return $newimage;
        } else {
            return $sourceimage;
        }
    }

    /**
     * 裁剪图片
     *
     * @param resource $sourceimage
     *            待操作的图片资源
     * @param int $topremovevalue
     *            图片上部清除的数值（像素）
     * @param int $buttomremovevalue
     *            图片下部清除的数值（像素）
     * @param int $leftremovevalue
     *            图片左部清除的数值（像素）
     * @param int $rightremovevalue
     *            图片右部清除的数值（像素）
     * @return resource
     */
    public static function cropImage($sourceimage, $topremovevalue, $buttomremovevalue = 0, $leftremovevalue = 0, $rightremovevalue = 0)
    {
        $sourcewidth = imagesx($sourceimage);
        $sourceheight = imagesy($sourceimage);

        if ($topremovevalue >= $sourceheight) {
            $topremovevalue = 0;
        }

        if ($leftremovevalue >= $sourcewidth) {
            $leftremovevalue = 0;
        }

        if ($buttomremovevalue >= $sourceheight - $topremovevalue) {
            $buttomremovevalue = 0;
        }

        if ($rightremovevalue >= $sourcewidth - $leftremovevalue) {
            $rightremovevalue = 0;
        }

        $newwidth = $sourcewidth - $leftremovevalue - $rightremovevalue;
        $newheight = $sourceheight - $topremovevalue - $buttomremovevalue;
        $croppedimage = imagecreatetruecolor($newwidth, $newheight);

        imagecopy($croppedimage, $sourceimage, 0, 0, $leftremovevalue, $topremovevalue, $newwidth, $newheight);

        return $croppedimage;
    }

    /**
     * 根据给定的图片全路径，将图片载入内存
     *
     * @param string $imageFileName
     *            图片全路径
     * @return resource 内存中的图片资源
     */
    public static function loadImage($imageFileName)
    {
        $imagetype = self::getImageType($imageFileName);
        switch ($imagetype) {
            case 'png':
                $image = imagecreatefrompng($imageFileName);
                break;
            case 'bmp':
                $image = imagecreatefromwbmp($imageFileName);
                break;
            case 'gif':
                $image = imagecreatefromgif($imageFileName);
                break;
            case 'jpg':
                $image = imagecreatefromjpeg($imageFileName);
                break;
            default:
                // file_get_contents函数要求php版本>4.3.0
                $srcData = '';
                if (function_exists("file_get_contents")) {
                    $srcData = file_get_contents($imageFileName);
                } else {
                    $handle = fopen($imageFileName, "r");
                    while (!feof($handle)) {
                        $srcData .= fgets($handle, 4096);
                    }
                    fclose($handle);
                }
                if (empty($srcData)) {
                    die("图片源为空");
                }
                $image = @ImageCreateFromString($srcData);
                break;
        }
        return $image;
    }

    /**
     * 获取图片的类型
     *
     * @param string $imageFileName
     *            文件全路径
     * @return string
     */
    public static function getImageType($imageFileName)
    {
        $result = 'jpg';
        $array = getimagesize($imageFileName);
        // 索引 2 给出的是图像的类型，返回的是数字，
        // 其中1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，
        // 6 = BMP，7 = TIFF(intel byte order)，
        // 8 = TIFF(motorola byte order)，9 = JPC，
        // 10 = JP2，11 = JPX，12 = JB2，13 = SWC，
        // 14 = IFF，15 = WBMP，16 = XBM

        // 索引 mime 给出的是图像的 MIME信息(例如image/jpeg)，此信息可以用来
        // 在 HTTP Content-type 头信息中发送正确的信息，如：
        // header("Content-type: image/jpeg");

        switch ($array[2]) {
            case 1:
                $result = 'gif';
                break;
            case 2:
                $result = 'jpg';
                break;
            case 3:
                $result = 'png';
                break;
            case 4:
                $result = 'swf';
                break;
            case 5:
                $result = 'psd';
                break;
            case 6:
            case 15:
                $result = 'bmp';
                break;
            case 7:
            case 8:
                $result = 'tiff';
                break;
            default:
                $result = 'jpg';
                break;
        }

        return $result;
    }

    /**
     * 在浏览器中显示图片
     *
     * @param resource $image
     * @param string $imageType
     * @param int $imageDisplayQuality
     */
    public static function display($image, $imageType = 'jpg', $imageDisplayQuality = 1)
    {
        $functionName = self::getImageOutputFunction($imageType);

        if (function_exists($functionName)) {
            // 判断浏览器,若是IE就不发送头
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $ua = strtoupper($_SERVER['HTTP_USER_AGENT']);
                if (!preg_match('/^.*MSIE.*\)$/i', $ua)) {
                    header("Content-type:$imageType");
                }
            }

            $functionName($image, null, $imageDisplayQuality);
        }
    }

    /**
     * 根据图片文件的扩展名称，确定图片的输出函数
     *
     * @param string $imageExtensionFileNameWithoutDot
     *            不带小数点的图片扩展名称
     * @return string
     */
    public static function getImageOutputFunction($imageExtensionFileNameWithoutDot)
    {
        $result = self::getImageFunction($imageExtensionFileNameWithoutDot, 'output');
        return $result;
    }

    /**
     * 根据图片文件的扩展名称，确定图片的载入函数
     *
     * @param string $imageExtensionFileNameWithoutDot
     *            不带小数点的图片扩展名称
     * @return string
     */
    public static function getImageCreateFunction($imageExtensionFileNameWithoutDot)
    {
        $result = self::getImageFunction($imageExtensionFileNameWithoutDot, 'create');
        return $result;
    }

    private static function getImageFunction($imageExtensionFileNameWithoutDot, $functionType)
    {
        $arrayFunctions = self::ImageFunctions();
        $extFunctions = $arrayFunctions[$imageExtensionFileNameWithoutDot];
        $result = $extFunctions[$functionType];
        return $result;
    }

    /**
     * 获取图片操作函数数组
     * @return array
     */
    private static function ImageFunctions()
    {
        $array = array(
            'jpg' => array(
                'output' => 'imagejpeg',
                'create' => 'imagecreatefromjpeg'
            ),
            'jpeg' => array(
                'output' => 'imagejpeg',
                'create' => 'imagecreatefromjpeg'
            ),
            'png' => array(
                'output' => 'imagepng',
                'create' => 'imagecreatefrompng'
            ),
            'gif' => array(
                'output' => 'imagegif',
                'create' => 'imagecreatefromgif'
            ),
            'bmp' => array(
                'output' => 'image2wbmp',
                'create' => 'imagecreatefromwbmp'
            ),
            'wbmp' => array(
                'output' => 'image2wbmp',
                'create' => 'imagecreatefromwbmp'
            )
        );

        return $array;
    }
}

?>