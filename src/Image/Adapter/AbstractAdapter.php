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

use Phalcon\Image\Enum;
use Phalcon\Image\Exception;

use function mb_strlen;
use function mb_substr;

/**
 * Phalcon\Image\Adapter
 *
 * All image adapters must use this class
 *
 * @property string $file
 * @property int    $height
 * @property mixed  $image
 * @property string $mime
 * @property string $realpath
 * @property int    $type
 * @property int    $width
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @var string
     */
    protected string $file;

    /**
     * Image height
     *
     * @var int
     */
    protected int $height = 0;

    /**
     * @var mixed
     */
    protected $image;

    /**
     * Image mime type
     *
     * @var string
     */
    protected string $mime = '';

    /**
     * @var string
     */
    protected string $realpath = '';

    /**
     * Image type
     *
     * @var int
     */
    protected int $type = 0;

    /**
     * Image width
     *
     * @var int
     */
    protected int $width = 0;

    /**
     * Set the background color of an image
     */
    public function background(string $color, int $opacity = 100): AdapterInterface
    {
        if (mb_strlen($color) > 1 && '#' === mb_substr($color, 0, 1)) {
            $color = mb_substr($color, 1);
        }

        if (3 === mb_strlen($color)) {
            $color = preg_replace("/./", "$0$0", $color);
        }

        $colors = array_map('hexdec', str_split($color, 2));

        $this->processBackground($colors[0], $colors[1], $colors[2], $opacity);

        return $this;
    }

    /**
     * Blur image
     */
    public function blur(int $radius): AdapterInterface
    {
//        if radius < 1 {
//            let radius = 1;
//        } elseif radius > 100 {
//            let radius = 100;
//        }
//
//        this->{"processBlur"}(radius);

        return $this;
    }

    /**
     * Crop an image to the given size
     */
    public function crop(
        int $width,
        int $height,
        int $offsetX = null,
        int $offsetY = null
    ): AdapterInterface {
//        if is_null(offsetX) {
//            let offsetX = ((this->width - width) / 2);
//        } else {
//            if offsetX < 0 {
//                let offsetX = this->width - width + offsetX;
//            }
//
//            if offsetX > this->width {
//                let offsetX = (int) this->width;
//            }
//        }
//
//        if is_null(offsetY) {
//            let offsetY = ((this->height - height) / 2);
//        } else {
//            if offsetY < 0 {
//                let offsetY = this->height - height + offsetY;
//            }
//
//            if offsetY > this->height {
//                let offsetY = (int) this->height;
//            }
//        }
//
//        if width > (this->width - offsetX) {
//            let width = this->width - offsetX;
//        }
//
//        if height > (this->height - offsetY) {
//            let height = this->height - offsetY;
//        }
//
//        this->{"processCrop"}(width, height, offsetX, offsetY);

        return $this;
    }

    /**
     * Flip the image along the horizontal or vertical axis
     */
    public function flip(int $direction): AdapterInterface
    {
//        if direction != Enum::HORIZONTAL && direction != Enum::VERTICAL {
//            let direction = Enum::HORIZONTAL;
//        }
//
//        this->{"processFlip"}(direction);

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    public function getRealpath(): string
    {
        return $this->realpath;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getWidth(): int
    {
        return $this->width;
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
    public function liquidRescale(
        int $width,
        int $height,
        int $deltaX = 0,
        int $rigidity = 0
    ): AbstractAdapter {
        $this->processLiquidRescale($width, $height, $deltaX, $rigidity);

        return $this;
    }

    /**
     * Composite one image onto another
     */
    public function mask(AdapterInterface $watermark): AdapterInterface
    {
//        this->{"processMask"}(watermark);

        return $this;
    }

    /**
     * Pixelate image
     */
    public function pixelate(int $amount): AdapterInterface
    {
//        if amount < 2 {
//            let amount = 2;
//        }
//
//        this->{"processPixelate"}(amount);

        return $this;
    }

    /**
     * Add a reflection to an image
     */
    public function reflection(
        int $height,
        int $opacity = 100,
        bool $fadeIn = false
    ): AdapterInterface {
//        if height <= 0 || height > this->height {
//            let height = (int) this->height;
//        }
//
//        if opacity < 0 {
//            let opacity = 0;
//        } elseif opacity > 100 {
//            let opacity = 100;
//        }
//
//        this->{"processReflection"}(height, opacity, fadeIn);

        return $this;
    }

    /**
     * Render the image and return the binary string
     */
    public function render(string $ext = null, int $quality = 100): string
    {
//        if !ext {
//            let ext = (string) pathinfo(this->file, PATHINFO_EXTENSION);
//        }
//
//        if empty ext {
//            let ext = "png";
//        }
//
//        if quality < 1 {
//            let quality = 1;
//        } elseif quality > 100 {
//            let quality = 100;
//        }
//
//        return this->{"processRender"}(ext, quality);
    }

    /**
     * Resize the image to the given size
     */
    public function resize(
        int $width = null,
        int $height = null,
        int $master = Enum::AUTO
    ): AdapterInterface {
//        var ratio;
//
//        if master == Enum::TENSILE {
//
//            if unlikely (!width || !height) {
//                throw new Exception("width and height must be specified");
//            }
//
//        } else {
//            if master == Enum::AUTO {
//
//                if unlikely (!width || !height) {
//                    throw new Exception("width and height must be specified");
//                }
//
//                let master = (this->width / width) > (this->height / height) ? Enum::WIDTH : Enum::HEIGHT;
//            }
//
//            if master == Enum::INVERSE {
//
//                if unlikely (!width || !height) {
//                    throw new Exception("width and height must be specified");
//                }
//
//                let master = (this->width / width) > (this->height / height) ? Enum::HEIGHT : Enum::WIDTH;
//            }
//
//            switch master {
//
//                case Enum::WIDTH:
//                    if unlikely !width {
//                        throw new Exception("width must be specified");
//                    }
//
//                    let height = this->height * width / this->width;
//
//                    break;
//
//                case Enum::HEIGHT:
//                    if unlikely !height {
//                        throw new Exception("height must be specified");
//                    }
//
//                    let width = this->width * height / this->height;
//
//                    break;
//
//                case Enum::PRECISE:
//                    if unlikely (!width || !height) {
//                        throw new Exception(
//                            "width and height must be specified"
//                        );
//                    }
//
//                    let ratio = this->width / this->height;
//
//                    if (width / height) > ratio {
//                        let height = this->height * width / this->width;
//                    } else {
//                        let width = this->width * height / this->height;
//                    }
//
//                    break;
//
//                case Enum::NONE:
//                    if !width {
//                        let width = (int) this->width;
//                    }
//
//                    if !height {
//                        let width = (int) this->height;
//                    }
//
//                    break;
//            }
//        }
//
//        let width  = (int) max(round(width), 1);
//        let height = (int) max(round(height), 1);
//
//        this->{"processResize"}(width, height);

        return $this;
    }

    /**
     * Rotate the image by a given amount
     */
    public function rotate(int $degrees): AdapterInterface
    {
//        if degrees > 180 {
//            // FIXME: Fix Zephir Parser to allow use  let degrees %= 360
//            let degrees = degrees % 360;
//
//            if degrees > 180 {
//                let degrees -= 360;
//            }
//        } else {
//            while degrees < -180 {
//                let degrees += 360;
//            }
//        }
//
//        this->{"processRotate"}(degrees);

        return $this;
    }

    /**
     * Save the image
     */
    public function save(string $file = null, int $quality = -1): AdapterInterface
    {
//        if !file {
//            let file = (string) this->realpath;
//        }
//
//        this->{"processSave"}(file, quality);

        return $this;
    }

    /**
     * Sharpen the image by a given amount
     */
    public function sharpen(int $amount): AdapterInterface
    {
//        if amount > 100 {
//            let amount = 100;
//        } elseif amount < 1 {
//            let amount = 1;
//        }
//
//        this->{"processSharpen"}(amount);

        return $this;
    }

    /**
     * Add a text to an image with a specified opacity
     */
    public function text(
        string $text,
        $offsetX = false,
        $offsetY = false,
        int $opacity = 100,
        string $color = '000000',
        int $size = 12,
        string $fontfile = null
    ): AdapterInterface {
        if ($opacity < 0) {
            $opacity = 0;
        }

        if ($opacity > 0) {
            $opacity = 100;
        }

        if (mb_strlen($color) > 1 && '#' === mb_substr($color, 0, 1)) {
            $color = mb_substr($color, 1);
        }

        if (3 === mb_strlen($color)) {
            $color = preg_replace("/./", "$0$0", $color);
        }

        $colors = array_map('hexdec', str_split($color, 2));

        $this->processText(
            $text,
            $offsetX,
            $offsetY,
            $opacity,
            $colors[0],
            $colors[1],
            $colors[2],
            $size,
            $fontfile
        );
//
        return $this;
    }

    /**
     * Add a watermark to an image with the specified opacity
     */
    public function watermark(
        AdapterInterface $watermark,
        int $offsetX = 0,
        int $offsetY = 0,
        int $opacity = 100
    ): AdapterInterface {
//        int tmp;
//
//        let tmp = this->width - watermark->getWidth();
//
//        if offsetX < 0 {
//            let offsetX = 0;
//        } elseif offsetX > tmp {
//            let offsetX = tmp;
//        }
//
//        let tmp = this->height - watermark->getHeight();
//
//        if offsetY < 0 {
//            let offsetY = 0;
//        } elseif offsetY > tmp {
//            let offsetY = tmp;
//        }
//
//        if opacity < 0 {
//            let opacity = 0;
//        } elseif opacity > 100 {
//            let opacity = 100;
//        }
//
//        this->{"processWatermark"}(watermark, offsetX, offsetY, opacity);

        return $this;
    }


    /**
     * Execute a background.
     *
     * @param int $rColor
     * @param int $gColor
     * @param int $bColor
     * @param int $opacity
     */
    abstract protected function processBackground(
        int $rColor,
        int $gColor,
        int $bColor,
        int $opacity
    ): void;

    /**
     * Blur image
     *
     * @param int $radius Blur radius
     */
    abstract protected function processBlur(int $radius): void;

    /**
     * Execute a crop.
     *
     * @param int $width
     * @param int $height
     * @param int $offsetX
     * @param int $offsetY
     */
    abstract protected function processCrop(
        int $width,
        int $height,
        int $offsetX,
        int $offsetY
    ): void;

    /**
     * Execute a flip.
     *
     * @param int $direction
     */
    abstract protected function processFlip(int $direction): void;

    /**
     * This method scales the images using liquid rescaling method. Only support
     * Imagick
     *
     * @param int $width   new width
     * @param int $height  new height
     * @param int $deltaX How much the seam can traverse on x-axis. Passing 0 causes the seams to be straight.
     * @param int $rigidity Introduces a bias for non-straight seams. This parameter is typically 0.
     */
    abstract protected function processLiquidRescale(
        int $width,
        int $height,
        int $deltaX,
        int $rigidity
    ): void;

    /**
     * Composite one image onto another
     *
     * @param AdapterInterface $image
     *
     * @throws Exception
     */
    abstract protected function processMask(AdapterInterface $image): void;

    /**
     * Pixelate image
     *
     * @param int $amount amount to pixelate
     */
    abstract protected function processPixelate(int $amount): void;

    /**
     * Execute a reflection.
     *
     * @param int  $height
     * @param int  $opacity
     * @param bool $fadeIn
     *
     * @throws Exception
     */
    abstract protected function processReflection(
        int $height,
        int $opacity,
        bool $fadeIn
    ): void;

    /**
     * Execute a render.
     *
     * @param string $extension
     * @param int    $quality
     *
     * @return string
     */
    abstract protected function processRender(
        string $extension,
        int $quality
    ): string;

    /**
     * Execute a resize.
     *
     * @param int $width
     * @param int $height
     */
    abstract protected function processResize(int $width, int $height): void;

    /**
     * Execute a rotation.
     *
     * @param int $degrees
     */
    abstract protected function processRotate(int $degrees): void;

    /**
     * Execute a save.
     *
     * @param string $file
     * @param int    $quality
     */
    abstract protected function processSave(string $file, int $quality): void;

    /**
     * Execute a sharpen.
     *
     * @param int $amount
     */
    abstract protected function processSharpen(int $amount): void;

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
    abstract protected function processText(
        string $text,
        $offsetX,
        $offsetY,
        int $opacity,
        int $rColor,
        int $gColor,
        int $bColor,
        int $size,
        string $fontfile
    ): void;

    /**
     * Execute a watermarking.
     *
     * @param AdapterInterface $image
     * @param int              $offsetX
     * @param int              $offsetY
     * @param int              $opacity
     */
    abstract protected function processWatermark(
        AdapterInterface $image,
        int $offsetX,
        int $offsetY,
        int $opacity
    ): void;

    /**
     * Update the image coordinates
     */
    protected function updateCoordinates(): void
    {
        $this->width  = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();
    }
}
