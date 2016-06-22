<?php
namespace Vendor\Hiland\Utils\IO;

use Vendor\Hiland\Utils\Data\StringHelper;

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
            case 'wbmp':
                $image = imagecreatefromwbmp($imageFileName);
                break;
            case 'gif':
                $image = imagecreatefromgif($imageFileName);
                break;
            case 'jpg':
                $image = imagecreatefromjpeg($imageFileName);
                break;
//            case 'bmp':
//                $image= imagecreatefrom
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
        if (extension_loaded('exif')) {
            return self::getImageTypeFromExif($imageFileName);
        } else {
            return self::getImageTypeFromImageSize($imageFileName);
        }
    }

    /**
     * 获取图片的类型
     *
     * @param string $imageFileName
     *            文件全路径
     * @return string
     *
     * php.ini中需要开通这个两个扩展模块
     * extension=php_mbstring.dll
     * extension=php_exif.dll
     */
    private static function getImageTypeFromExif($imageFileName)
    {
        $result = 'jpg';
        $out = exif_imagetype($imageFileName);

        switch ($out) {
            case 1://IMAGETYPE_GIF
                $result = 'gif';
                break;
            case 2://	IMAGETYPE_JPEG
                $result = 'jpg';
                break;
            case 3://	IMAGETYPE_PNG
                $result = 'png';
                break;
            case 4:// 	IMAGETYPE_SWF
                $result = 'swf';
                break;
            case 5:// 	IMAGETYPE_PSD
                $result = 'psd';
                break;
            case 6 ://	IMAGETYPE_BMP
                $result = 'bmp';
                break;
            case 7 ://	IMAGETYPE_TIFF_II（Intel 字节顺序）
                $result = 'tiff';
                break;
            case 8 ://	IMAGETYPE_TIFF_MM（Motorola 字节顺序）
                $result = 'tiff';
                break;
            case 9:// 	IMAGETYPE_JPC
                $result = 'jpc';
                break;
            case 10 ://	IMAGETYPE_JP2
                $result = 'jp2';
                break;
            case 11 ://	IMAGETYPE_JPX
                $result = 'jpx';
                break;
            case 12 ://	IMAGETYPE_JB2
                $result = 'gb2';
                break;
            case 13:// 	IMAGETYPE_SWC
                $result = 'swc';
                break;
            case 14 ://	IMAGETYPE_IFF
                $result = 'iff';
                break;
            case 15 ://	IMAGETYPE_WBMP
                $result = 'wbmp';
                break;
            case 16:// 	IMAGETYPE_XBM
                $result = 'xbm';
                break;
        }

        return $result;
    }

    /**
     * 获取图片的类型
     *
     * @param string $imageFileName
     *            文件全路径
     * @return string
     */
    private static function getImageTypeFromImageSize($imageFileName)
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

    /**
     * 保存到sae中一个临时文件并获得文件的物理绝对路径(仅在当前请求期间有效，跨请求本数据无效)
     * @param resource $image
     * @param string $physicalRootPath 要保持图片的物理根路径
     * @param string $savingImageRelativePhysicalPathFullName 要保存的图片的带相对物理路径的全名称（物理路径、文件名和扩展名）
     * @return string 被保存的图片的带相对物理路径的全名称（物理路径、文件名和扩展名）
     */
    public static function saveImageResource($image, $physicalRootPath, $savingImageRelativePhysicalPathFullName)
    {
        $fileextionname = strtolower(FileHelper::getFileExtensionName($savingImageRelativePhysicalPathFullName));

        if (StringHelper::isEndWith($physicalRootPath, '\\')) {
            $physicalRootPath = StringHelper::subString($physicalRootPath, 0, strlen($physicalRootPath) - 1);
        }

        if (StringHelper::isStartWith($savingImageRelativePhysicalPathFullName, '\\')) {
            $savingImageRelativePhysicalPathFullName = StringHelper::subString($savingImageRelativePhysicalPathFullName, 1);
        }

        $filefullname = $physicalRootPath . '\\' . $savingImageRelativePhysicalPathFullName;

        switch ($fileextionname) {
            case 'png':
                imagepng($image, $filefullname);
                break;
            case 'gif':
                imagegif($image, $filefullname);
                break;
            case 'bmp':
                imagexbm($image, $filefullname);
                break;
            default:
                imagejpeg($image, $filefullname);
                break;
        }

        return $savingImageRelativePhysicalPathFullName;
    }

    public static function imageCreateFromBMP($imageFileName)
    {
        //Ouverture du fichier en mode binaire
        if (!$f1 = fopen($imageFileName, "rb")) {
            return FALSE;
        }

        //1 : Chargement des ent�tes FICHIER
        $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
        if ($FILE['file_type'] != 19778) {
            return FALSE;
        }

        //2 : Chargement des ent�tes BMP
        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
            '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
            '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0) {
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        }
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ($BMP['decal'] == 4) {
            $BMP['decal'] = 0;
        }

        //3 : Chargement des couleurs de la palette
        $PALETTE = array();
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }

        //4 : Cr�ation de l'image
        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                if ($BMP['bits_per_pixel'] == 24)
                    $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
                elseif ($BMP['bits_per_pixel'] == 16) {
                    $COLOR = unpack("n", substr($IMG, $P, 2));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 8) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 4) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 2) % 2 == 0)
                        $COLOR[1] = ($COLOR[1] >> 4);
                    else
                        $COLOR[1] = ($COLOR[1] & 0x0F);
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 1) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 8) % 8 == 0)
                        $COLOR[1] = $COLOR[1] >> 7;
                    elseif (($P * 8) % 8 == 1)
                        $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    elseif (($P * 8) % 8 == 2)
                        $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    elseif (($P * 8) % 8 == 3)
                        $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    elseif (($P * 8) % 8 == 4)
                        $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    elseif (($P * 8) % 8 == 5)
                        $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    elseif (($P * 8) % 8 == 6)
                        $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    elseif (($P * 8) % 8 == 7)
                        $COLOR[1] = ($COLOR[1] & 0x1);
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } else
                    return FALSE;
                imagesetpixel($res, $X, $Y, $COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P += $BMP['decal'];
        }

        //Fermeture du fichier
        fclose($f1);

        return $res;
    }
}

?>