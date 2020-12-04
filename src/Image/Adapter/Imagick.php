<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Image\Adapter;

use Imagick as ImagickNative;
use ImagickException as ImagickExceptionNative;
use ImagickPixel as ImagickPixelNative;
use Phalcon\Image\Enum;
use Phalcon\Image\Exception;

use function class_exists;
use function defined;
use function is_bool;
use function is_float;
use function is_int;
use function pathinfo;

use const PATHINFO_EXTENSION;

/**
 * Phalcon\Image\AdapterImagickNative
 *
 * Image manipulation support. Allows images to be resized, cropped, etc.
 *
 *```php
 * $image = new \Phalcon\Image\AdapterImagickNative("upload/test.jpg");
 *
 * $image->resize(200, 200)->rotate(90)->crop(100, 100);
 *
 * if ($image->save()) {
 *     echo "success";
 * }
 *```
 */
class Imagick extends AbstractAdapter
{
    protected int $version = 0;

    /**
     * Imagick constructor.
     *
     * @param string   $file
     * @param int|null $width
     * @param int|null $height
     *
     * @throws Exception
     * @throws ImagickExceptionNative
     */
    public function __construct(string $file, int $width = null, int $height = null)
    {
        if (true !== class_exists('imagick')) {
            throw new Exception(
                'Imagick is not installed, or the extension is not loaded'
            );
        }

        if (defined('Imagick::IMAGICK_EXTNUM')) {
            $this->version = ImagickNative::IMAGICK_EXTNUM;
        }

        $this->file  = $file;
        $this->image = new ImagickNative();

        if (true === file_exists($file)) {
            $this->realpath = realpath($file);

            if (true !== $this->image->readImage($this->realpath)) {
                throw new Exception('Imagick::readImage ' . $file . ' failed');
            }

            if (true !== $this->image->getImageAlphaChannel()) {
                $this->image->setImageAlphaChannel(ImagickNative::ALPHACHANNEL_SET);
            }

            if (1 === $this->type) {
                $image = $this->image->coalesceImages();

                $this->image->clear();
                $this->image->destroy();

                $this->image = $image;
            }
        } else {
            if (null !== $width || null !== $height) {
                throw new Exception(
                    'Failed to create image from file ' . $file
                );
            }

            $this->image->newImage(
                $width,
                $height,
                new ImagickPixelNative('transparent')
            );

            $this->image->setFormat("png");
            $this->image->setImageFormat("png");

            $this->realpath = $file;
        }

        $this->width  = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();
        $this->type   = $this->image->getImageType();
        $this->mime   = 'image/' . $this->image->getImageFormat();
    }

    /**
     * Destroys the loaded image to free up resources.
     */
    public function __destruct()
    {
        if ($this->image instanceof ImagickNative) {
            $this->image->clear();
            $this->image->destroy();
        }
    }

    /**
     * Get instance
     *
     * @return ImagickNative
     */
    public function getInternalImInstance(): ImagickNative
    {
        return $this->image;
    }

    /**
     * Sets the limit for a particular resource in megabytes
     *
     * @param int $type
     * @param int $limit
     *
     * @link http://php.net/manual/ru/imagick.constants.php#imagick.constants.resourcetypes
     */
    public function setResourceLimit(int $type, int $limit): void
    {
        $this->image->setResourceLimit($type, $limit);
    }

    /**
     * Execute a background.
     *
     * @param int $rColor
     * @param int $gColor
     * @param int $bColor
     * @param int $opacity
     */
    protected function processBackground(
        int $rColor,
        int $gColor,
        int $bColor,
        int $opacity
    ): void {
        $color      = sprintf('rgb(%d, %d, %d)', $rColor, $gColor, $bColor);
        $pixel1     = new ImagickPixelNative($color);
        $opacity    = $opacity / 100;
        $pixel2     = new ImagickPixelNative('transparent');
        $background = new ImagickNative();

        $this->image->setIteratorIndex(0);
        while (true) {
            $background->newImage($this->width, $this->height, $pixel1);
            if (true !== $background->getImageAlphaChannel()) {
                $background->setImageAlphaChannel(ImagickNative::ALPHACHANNEL_SET);
            }

            $background->setImageBackgroundColor($pixel2);
            $background->evaluateImage(
                ImagickNative::EVALUATE_MULTIPLY,
                $opacity,
                ImagickNative::CHANNEL_ALPHA
            );

            $background->setColorspace($this->image->getColorspace());
            $return = $background->compositeImage(
                $this->image,
                ImagickNative::COMPOSITE_DISSOLVE,
                0,
                0
            );

            if (true !== $return) {
                throw new Exception('Imagick::compositeImage failed');
            }

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $this->image->clear();
        $this->image->destroy();

        $this->image = $background;
        $this->updateCoordinates();
    }

    /**
     * Blur image
     *
     * @param int $radius Blur radius
     */
    protected function processBlur(int $radius): void
    {
        $this->image->setIteratorIndex(0);

        while (true) {
            $this->image->blurImage($radius, 100);

            if (false === $this->image->nextImage()) {
                break;
            }
        }
    }

    /**
     * Execute a crop.
     *
     * @param int $width
     * @param int $height
     * @param int $offsetX
     * @param int $offsetY
     */
    protected function processCrop(int $width, int $height, int $offsetX, int $offsetY): void
    {
        $this->image->setIteratorIndex(0);
        while (true) {
            $this->image->cropImage($width, $height, $offsetX, $offsetY);
            $this->image->setImagePage($width, $height, 0, 0);

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $this->updateCoordinates();
    }

    /**
     * Execute a flip.
     *
     * @param int $direction
     */
    protected function processFlip(int $direction): void
    {
        $method = 'flipImage';
        if (Enum::HORIZONTAL === $direction) {
            $method = 'flopImage';
        }

        $this->image->setIteratorIndex(0);
        while (true) {
            $this->image->{$method}();

            if (false === $this->image->nextImage()) {
                break;
            }
        }
    }

    /**
     * This method scales the images using liquid rescaling method. Only
     * support
     * Imagick
     *
     * @param int $width    new width
     * @param int $height   new height
     * @param int $deltaX   How much the seam can traverse on x-axis. Passing 0
     *                      causes the seams to be straight.
     * @param int $rigidity Introduces a bias for non-straight seams. This
     *                      parameter is typically 0.
     */
    protected function processLiquidRescale(int $width, int $height, int $deltaX, int $rigidity): void
    {
        $this->image->setIteratorIndex(0);
        while (true) {
            $return = $this->image->liquidRescaleImage(
                $width,
                $height,
                $deltaX,
                $rigidity
            );

            if (true !== $return) {
                throw new Exception('Imagick::liquidRescale failed');
            }

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $this->updateCoordinates();
    }

    /**
     * Composite one image onto another
     *
     * @param AdapterInterface $image
     *
     * @throws Exception
     */
    protected function processMask(AdapterInterface $image): void
    {
        $mask = new ImagickNative();
        $mask->readImageBlob($image->render());

        $this->image->setIteratorIndex(0);
        while (true) {
            $return = $this->image->compositeImage(
                $mask,
                ImagickNative::COMPOSITE_DSTIN,
                0,
                0
            );

            if (true !== $return) {
                throw new Exception('Imagick::compositeImage failed');
            }

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $mask->clear();
        $mask->destroy();
    }

    /**
     * Pixelate image
     *
     * @param int $amount amount to pixelate
     */
    protected function processPixelate(int $amount): void
    {
        $width  = $this->width / $amount;
        $height = $this->height / $amount;

        $this->image->setIteratorIndex(0);
        while (true) {
            $this->image->scaleImage($width, $height);
            $this->image->scaleImage($this->width, $this->height);

            if (false === $this->image->nextImage()) {
                break;
            }
        }
    }

    /**
     * Execute a reflection.
     *
     * @param int  $height
     * @param int  $opacity
     * @param bool $fadeIn
     *
     * @throws Exception
     */
    protected function processReflection(int $height, int $opacity, bool $fadeIn): void
    {
        if ($this->version >= 30100) {
            $reflection = clone $this->image;
        } else {
            $reflection = clone $this->image->clone();
        }

        $this->image->setIteratorIndex(0);
        while (true) {
            $reflection->flipImage();

            $reflection->cropImage(
                $reflection->getImageWidth(),
                $height,
                0,
                0
            );

            $reflection->setImagePage(
                $reflection->getImageWidth(),
                $height,
                0,
                0
            );

            if (false === $reflection->nextImage()) {
                break;
            }
        }

        $pseudo = $fadeIn ? 'gradient:black-transparent' : 'gradient:transparent-black';
        $fade   = new ImagickNative();

        $fade->newPseudoImage(
            $reflection->getImageWidth(),
            $reflection->getImageHeight(),
            $pseudo
        );

        $opacity /= 100;
        $reflection->setIteratorIndex(0);

        while (true) {
            $return = $reflection->compositeImage(
                $fade,
                ImagickNative::COMPOSITE_DSTOUT,
                0,
                0
            );

            if (true !== $return) {
                throw new Exception('Imagick::compositeImage failed');
            }

            $reflection->evaluateImage(
                ImagickNative::EVALUATE_MULTIPLY,
                $opacity,
                ImagickNative::CHANNEL_ALPHA
            );

            if (false === $reflection->nextImage()) {
                break;
            }
        }

        $fade->destroy();

        $image  = new ImagickNative();
        $pixel  = new ImagickPixelNative();
        $height = $this->image->getImageHeight() + $height;

        $this->image->setIteratorIndex(0);
        while (true) {
            $image->newImage($this->width, $height, $pixel);

            $image->setImageAlphaChannel(ImagickNative::ALPHACHANNEL_SET);
            $image->setColorspace($this->image->getColorspace());
            $image->setImageDelay($this->image->getImageDelay());

            $return = $image->compositeImage(
                $this->image,
                ImagickNative::COMPOSITE_SRC,
                0,
                0
            );

            if (true !== $return) {
                throw new Exception('Imagick::compositeImage failed');
            }

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $image->setIteratorIndex(0);
        $reflection->setIteratorIndex(0);

        while (true) {
            $return = $image->compositeImage(
                $reflection,
                ImagickNative::COMPOSITE_OVER,
                0,
                $this->height
            );

            if (true !== $return) {
                throw new Exception('Imagick::compositeImage failed');
            }

            if (false === $image->nextImage() || false === $reflection->nextImage()) {
                break;
            }
        }

        $reflection->destroy();

        $this->image->clear();
        $this->image->destroy();

        $this->image = $image;
        $this->updateCoordinates();
    }

    /**
     * Execute a render.
     *
     * @param string $extension
     * @param int    $quality
     *
     * @return string
     */
    protected function processRender(string $extension, int $quality): string
    {
        $this->image->setFormat($extension);
        $this->image->setImageFormat($extension);
        $this->image->stripImage();

        $this->type = $this->image->getImageType();
        $this->mime = 'image/' . $this->image->getImageFormat();

        if (0 === strcasecmp($extension, 'gif')) {
            $this->image->optimizeImageLayers();
        } else {
            if (
                0 === strcasecmp($extension, 'jpg') ||
                0 === strcasecmp($extension, 'jpeg')
            ) {
                $this->image->setImageCompression(ImagickNative::COMPRESSION_JPEG);
            }

            $this->image->setImageCompressionQuality($quality);
        }

        return $this->image->getImageBlob();
    }

    /**
     * Execute a resize.
     *
     * @param int $width
     * @param int $height
     */
    protected function processResize(int $width, int $height): void
    {
        $this->image->setIteratorIndex(0);
        while (true) {
            $this->image->scaleImage($width, $height);

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $this->updateCoordinates();
    }

    /**
     * Execute a rotation.
     *
     * @param int $degrees
     */
    protected function processRotate(int $degrees): void
    {
        $this->image->setIteratorIndex(0);

        $pixel = new ImagickPixelNative();

        while (true) {
            $this->image->rotateImage($pixel, $degrees);

            $this->image->setImagePage(
                $this->width,
                $this->height,
                0,
                0
            );

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $this->updateCoordinates();
    }

    /**
     * Execute a save.
     *
     * @param string $file
     * @param int    $quality
     */
    protected function processSave(string $file, int $quality): void
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        $this->image->setFormat($extension);
        $this->image->setImageFormat($extension);

        $this->type = $this->image->getImageType();
        $this->mime = 'image/' . $this->image->getImageFormat();

        if (0 === strcasecmp($extension, 'gif')) {
            $this->image->optimizeImageLayers();

            $handle = fopen($file, 'w');
            $this->image->writeImagesFile($handle);

            fclose($handle);

            return;
        } else {
            if (
                0 === strcasecmp($extension, 'jpg') ||
                0 === strcasecmp($extension, 'jpeg')
            ) {
                $this->image->setImageCompression(ImagickNative::COMPRESSION_JPEG);
            }

            if ($quality <> 0) {
                $quality = ($quality < 1) ? 1 : $quality;
                $quality = ($quality > 100) ? 100 : $quality;

                $this->image->setImageCompressionQuality($quality);
            }

            $this->image->writeImage($file);
        }
    }

    /**
     * Execute a sharpen.
     *
     * @param int $amount
     */
    protected function processSharpen(int $amount): void
    {
        $amount = ($amount < 5) ? 5 : $amount;
        $amount = ($amount * 3.0) / 100;
        $this->image->setIteratorIndex(0);
        while (true) {
            $this->image->sharpenImage(0, $amount);

            if (false === $this->image->nextImage()) {
                break;
            }
        }
    }

    /**
     * Execute a text
     *
     * @param string $text
     * @param mixed  $offsetX
     * @param mixed  $offsetY
     * @param int    $opacity
     * @param int    $rColor
     * @param int    $gColor
     * @param int    $bColor
     * @param int    $size
     * @param string $fontfile
     *
     * @return mixed
     */
    protected function processText(
        string $text,
        $offsetX,
        $offsetY,
        int $opacity,
        int $rColor,
        int $gColor,
        int $bColor,
        int $size,
        string $fontfile
    ): void {
        $opacity = $opacity / 100;
        $draw    = new ImagickNativeDraw();
        $color   = sprintf('rgb(%d, %d, %d)', $rColor, $gColor, $bColor);

        $draw->setFillColor(new ImagickPixelNative($color));

        if (true !== empty($fontfile)) {
            $draw->setFont($fontfile);
        }

        if ($size > 0) {
            $draw->setFontSize($size);
        }

        if ($opacity > 0) {
            $draw->setfillopacity($opacity);
        }

        $gravity = null;

        if (true === is_bool($offsetX)) {
            if (true === is_bool($offsetY)) {
                $offsetX = 0;
                $offsetY = 0;
                $gravity = ImagickNative::GRAVITY_CENTER;
            } else {
                if (true === is_int($offsetY)) {
                    $y = (int) $offsetY;

                    if ($offsetX > 0) {
                        if ($y < 0) {
                            $offsetX = 0;
                            $offsetY = $y * -1;
                            $gravity = ImagickNative::GRAVITY_SOUTHEAST;
                        } else {
                            $offsetX = 0;
                            $gravity = ImagickNative::GRAVITY_NORTHEAST;
                        }
                    } else {
                        if ($y < 0) {
                            $offsetX = 0;
                            $offsetY = $y * -1;
                            $gravity = ImagickNative::GRAVITY_SOUTH;
                        } else {
                            $offsetX = 0;
                            $gravity = ImagickNative::GRAVITY_NORTH;
                        }
                    }
                }
            }
        } else {
            if (true === is_int($offsetX)) {
                $x = (int) $offsetX;

                if ($offsetX > 0) {
                    if (true === is_bool($offsetY)) {
                        if ($offsetY > 0) {
                            if ($x < 0) {
                                $offsetX = $x * -1;
                                $offsetY = 0;
                                $gravity = ImagickNative::GRAVITY_SOUTHEAST;
                            } else {
                                $offsetY = 0;
                                $gravity = ImagickNative::GRAVITY_SOUTH;
                            }
                        } else {
                            if ($x < 0) {
                                $offsetX = $x * -1;
                                $offsetY = 0;
                                $gravity = ImagickNative::GRAVITY_EAST;
                            } else {
                                $offsetY = 0;
                                $gravity = ImagickNative::GRAVITY_WEST;
                            }
                        }
                    } else {
                        if (true === is_float($offsetY)) {
                            $x = (int) $offsetX;
                            $y = (int) $offsetY;

                            if ($x < 0) {
                                if ($y < 0) {
                                    $offsetX = $x * -1;
                                    $offsetY = $y * -1;
                                    $gravity = ImagickNative::GRAVITY_SOUTHEAST;
                                } else {
                                    $offsetX = $x * -1;
                                    $gravity = ImagickNative::GRAVITY_NORTHEAST;
                                }
                            } else {
                                if ($y < 0) {
                                    $offsetX = 0;
                                    $offsetY = $y * -1;
                                    $gravity = ImagickNative::GRAVITY_SOUTHWEST;
                                } else {
                                    $offsetX = 0;
                                    $gravity = ImagickNative::GRAVITY_NORTHWEST;
                                }
                            }
                        }
                    }
                }
            }
        }

        $draw->setGravity($gravity);
        $this->image->setIteratorIndex(0);

        while (true) {
            $this->image->annotateImage($draw, $offsetX, $offsetY, 0, $text);

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $draw->destroy();
    }

    /**
     * Execute a watermarking.
     *
     * @param AdapterInterface $image
     * @param int              $offsetX
     * @param int              $offsetY
     * @param int              $opacity
     */
    protected function processWatermark(
        AdapterInterface $image,
        int $offsetX,
        int $offsetY,
        int $opacity
    ): void {
        $opacity   = $opacity / 100;
        $watermark = new ImagickNative();

        $watermark->readImageBlob($image->render());
        $watermark->evaluateImage(
            ImagickNative::EVALUATE_MULTIPLY,
            $opacity,
            ImagickNative::CHANNEL_ALPHA
        );

        $this->image->setIteratorIndex(0);
        while (true) {
            $return = $this->image->compositeImage(
                $watermark,
                ImagickNative::COMPOSITE_OVER,
                $offsetX,
                $offsetY
            );

            if (true !== $return) {
                throw new Exception('Imagick::compositeImage failed');
            }

            if (false === $this->image->nextImage()) {
                break;
            }
        }

        $watermark->clear();
        $watermark->destroy();
    }
}
