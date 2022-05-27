<?php

namespace Abordage\OpenGraphImages;

use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;

class OpenGraphImages
{
    protected string $backgroundColor;

    protected ?string $fontPath = null;
    protected ?string $appNameFontPath = null;

    protected int $fontSize;
    protected string $textColor;
    protected string $textAlignment;
    protected string $textSticky;

    protected ?string $appName;

    protected int $appNameFontSize;
    protected string $appNameColor;
    protected string $appNamePosition;

    protected ?string $appNameDecorationStyle;
    protected string $appNameDecorationColor;

    private string $text;

    private int $imageWidth;
    private int $imageHeight;

    private int $maxTextWidth;
    private int $maxTextHeight;
    private int $textBoxWidth;
    private int $textBoxHeight;

    private int $textStartX;
    private int $textStartY;

    private int $appNameBoxWidth;
    private int $appNameBoxHeight;

    private int $appNameStartX;
    private int $appNameStartY;

    private int $appNameDefaultPaddingY = 30;
    private int $appNameDefaultPaddingX = 60;

    private int $rectangleX1;
    private int $rectangleX2;
    private int $rectangleY1;
    private int $rectangleY2;


    private Imagick $image;
    private ?string $imageBlob = null;

    private string $font = __DIR__ . '/../fonts/Roboto/Roboto-Regular.ttf';
    private string $appNameFont = __DIR__ . '/../fonts/Roboto/Roboto-Medium.ttf';

    private array $config = [
        /*
        |--------------------------------------------------------------------------
        | Background Color
        |--------------------------------------------------------------------------
        |
        | Supported: HEX, RGB or RGBA format
        |
        */
        'background_color' => '#474761',

        /*
        |--------------------------------------------------------------------------
        | Text Color
        |--------------------------------------------------------------------------
        |
        | Supported: HEX, RGB or RGBA format
        |
        */
        'text_color' => '#eee',

        /*
        |--------------------------------------------------------------------------
        | App Name
        |--------------------------------------------------------------------------
        |
        | Set null to disable
        |
        | Supported: string or null
        |
        */
        'app_name' => null,

        /*
        |--------------------------------------------------------------------------
        | App Name Text Color
        |--------------------------------------------------------------------------
        |
        | Supported: HEX, RGB or RGBA format
        |
        */
        'app_name_color' => '#eee',

        /*
        |--------------------------------------------------------------------------
        | App Name Decoration Color
        |--------------------------------------------------------------------------
        |
        | Supported: HEX, RGB or RGBA format
        |
        */
        'app_name_decoration_color' => '#fb3361',

        /*
        |--------------------------------------------------------------------------
        | Text Alignment
        |--------------------------------------------------------------------------
        |
        | Multiline text alignment
        |
        | Supported: "left", "center", "right"
        |
        */
        'text_alignment' => 'left',

        /*
        |--------------------------------------------------------------------------
        | Text Sticky
        |--------------------------------------------------------------------------
        |
        | Supported: "left", "center", "right"
        |
        */
        'text_sticky' => 'center',

        /*
        |--------------------------------------------------------------------------
        | App Name Position
        |--------------------------------------------------------------------------
        |
        | Supported: "top-left", "top-center", "top-right",
        |            "bottom-left", "bottom-center", "bottom-right"
        |
        */
        'app_name_position' => 'bottom-center',

        /*
        |--------------------------------------------------------------------------
        | App Name Decoration Style
        |--------------------------------------------------------------------------
        |
        | Set null to disable
        |
        | Supported: "line", "label", "rectangle", null
        |
        */
        'app_name_decoration_style' => 'line',

        /*
        |--------------------------------------------------------------------------
        | Font Size
        |--------------------------------------------------------------------------
        |
        */
        'font_size' => 55,

        /*
        |--------------------------------------------------------------------------
        | App Name Font Size
        |--------------------------------------------------------------------------
        |
        */
        'app_name_font_size' => 30,

        /*
        |--------------------------------------------------------------------------
        | Text Font
        |--------------------------------------------------------------------------
        |
        | If set null, will be used Preset Font (Roboto Regular)
        |
        | Supported: "absolute/path/to/your/font.ttf", null
        |
        */
        'font_path' => null,

        /*
        |--------------------------------------------------------------------------
        | App Name Font
        |--------------------------------------------------------------------------
        |
        | If set null, will be used Preset Font (Roboto Medium)
        |
        | Supported: "absolute/path/to/your/font.ttf", null
        |
        */
        'app_name_font_path' => null,
    ];

    public function __construct(array $config = [])
    {
        $config = array_merge($this->config, $config);

        $this->backgroundColor = $config['background_color'];
        $this->fontPath = $config['font_path'];
        $this->appNameFontPath = $config['app_name_font_path'];
        $this->fontSize = $config['font_size'];
        $this->textColor = $config['text_color'];
        $this->textAlignment = $config['text_alignment'];
        $this->textSticky = $config['text_sticky'];
        $this->appName = $config['app_name'];
        $this->appNameFontSize = $config['app_name_font_size'];
        $this->appNameColor = $config['app_name_color'];
        $this->appNamePosition = $config['app_name_position'];
        $this->appNameDecorationStyle = $config['app_name_decoration_style'];
        $this->appNameDecorationColor = $config['app_name_decoration_color'];

        if (!is_null($this->fontPath) && file_exists($this->fontPath)) {
            $this->font = $this->fontPath;
        }

        if (!is_null($this->appNameFontPath) && file_exists($this->appNameFontPath)) {
            $this->appNameFont = $this->appNameFontPath;
        }
    }

    public function make(string $text, int $width = 1200, int $height = 630): OpenGraphImages
    {
        // https://developers.facebook.com/docs/sharing/webmasters/images/
        $this->imageWidth = $width;
        $this->imageHeight = $height;

        $this->createImage($text);

        return $this;
    }

    public function makeTwitter(string $text): OpenGraphImages
    {
        // https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/summary-card-with-large-image
        $this->imageWidth = 1200;
        $this->imageHeight = 600;

        $this->createImage($text);

        return $this;
    }

    public function makeVk(string $text): OpenGraphImages
    {
        // https://dev.vk.com/api/posts
        $this->imageWidth = 1200;
        $this->imageHeight = 536;

        $this->createImage($text);

        return $this;
    }

    public function get(): ?string
    {
        return $this->imageBlob;
    }

    public function save(string $path): bool
    {
        if (is_null($this->imageBlob)) {
            return false;
        }

        $info = pathinfo($path);
        $dirname = $info['dirname'];
        $filename = $info['filename'];

        if (!is_dir($dirname)) {
            if (!mkdir($dirname, 0755, true)) {
                return false;
            }
        }

        return (bool)@file_put_contents($dirname . '/' . $filename . '.png', $this->imageBlob);
    }

    /**
     * @param string $text
     * @return void
     * @throws ImagickDrawException
     * @throws ImagickException
     * @throws ImagickPixelException
     */
    protected function createImage(string $text): void
    {
        $this->image = new Imagick();
        $this->image->newImage($this->imageWidth, $this->imageHeight, $this->backgroundColor);
        $this->text = $text;

        $this->setParameters();
        $this->createText();
        $this->createAppNameDecoration();
        $this->createAppName();

        $format = 'png';
        $compression = Imagick::COMPRESSION_ZIP;
        $this->image->setFormat($format);
        $this->image->setImageFormat($format);
        $this->image->setCompression($compression);
        $this->image->setImageCompression($compression);

        $this->imageBlob = $this->image->getImagesBlob();
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    protected function getTextBoxSize(string $text, string $font, int $size): array
    {
        $draw = new ImagickDraw();
        $draw->setStrokeAntialias(true);
        $draw->setTextAntialias(true);
        $draw->setFont($font);
        $draw->setFontSize($size);
        $dimensions = (new Imagick())->queryFontMetrics($draw, $text);

        $box = [];
        $box['width'] = intval(abs($dimensions['textWidth']));
        $box['height'] = intval(abs($dimensions['textHeight']));

        return $box;
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    protected function fitTextToBox(): void
    {
        for ($wordwrap = 50; $wordwrap >= 20; $wordwrap--) {
            $text = $this->multiLine($this->text, $wordwrap);
            $textBoxSize = $this->getTextBoxSize($text, $this->font, $this->fontSize);

            $this->textBoxWidth = $textBoxSize['width'];
            $this->textBoxHeight = $textBoxSize['height'];

            if ($this->textBoxWidth <= $this->maxTextWidth) {
                if ($this->textBoxHeight > $this->maxTextHeight) {
                    for ($countLines = count(explode("\n", $text)); $countLines > 1; $countLines--) {
                        $position = mb_strrpos($text, "\n") ?: null;
                        $text = mb_substr($text, 0, $position);

                        $textBoxSize = $this->getTextBoxSize($text, $this->font, $this->fontSize);
                        $this->textBoxHeight = $textBoxSize['height'];
                        if ($textBoxSize['height'] <= $this->maxTextHeight) {
                            break;
                        }
                    }
                }

                break;
            }
        }
        $this->text = $text;
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     */
    protected function createText(): void
    {
        $draw = new ImagickDraw();
        $draw->setStrokeAntialias(true);
        $draw->setTextAntialias(true);
        $draw->setFont($this->font);
        $draw->setFontSize($this->fontSize);
        $draw->setFillColor(new ImagickPixel($this->textColor));

        switch (strtolower($this->textAlignment)) {
            case 'center':
                $align = Imagick::ALIGN_CENTER;

                break;
            case 'right':
                $align = Imagick::ALIGN_RIGHT;

                break;
            default:
                $align = Imagick::ALIGN_LEFT;

                break;
        }
        $draw->setTextAlignment($align);

        // corrections
        $dimensions = $this->image->queryFontMetrics($draw, $this->text);
        $this->textStartY = $this->textStartY + $dimensions['characterHeight'];

        $this->image->annotateImage($draw, $this->textStartX, $this->textStartY, 0, $this->text);
    }

    /**
     * @throws ImagickException
     * @throws ImagickPixelException
     * @throws ImagickDrawException
     */
    protected function createAppName(): void
    {
        if (is_null($this->appName)) {
            return;
        }

        $draw = new ImagickDraw();
        $draw->setStrokeAntialias(true);
        $draw->setTextAntialias(true);
        $draw->setFont($this->appNameFont);
        $draw->setFontSize($this->appNameFontSize);
        $draw->setFillColor(new ImagickPixel($this->appNameColor));

        // corrections
        $dimensions = $this->image->queryFontMetrics($draw, $this->appName, false);
        $this->appNameStartY = $this->appNameStartY + $dimensions['characterHeight'];

        $this->image->annotateImage($draw, $this->appNameStartX, $this->appNameStartY, 0, $this->appName);
    }

    /**
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     * @throws ImagickException
     */
    protected function createAppNameDecoration(): void
    {
        if (is_null($this->appName) || is_null($this->appNameDecorationStyle)) {
            return;
        }

        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel($this->appNameDecorationColor));
        $draw->rectangle($this->rectangleX1, $this->rectangleY1, $this->rectangleX2, $this->rectangleY2);
        $this->image->drawImage($draw);
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    protected function setParameters(): void
    {
        $this->setTextParameters();

        if (is_string($this->appName)) {
            $this->setAppNameBox();
            $this->setAppNameCoordinates();

            switch ($this->appNameDecorationStyle) {
                case 'line':
                    $this->setLineCoordinates();

                    break;
                case 'label':
                    $this->setLabelCoordinates();

                    break;
                case 'rectangle':
                    $this->setRectangleCoordinates();

                    break;
            }
        }
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    protected function setTextParameters(): void
    {
        $this->maxTextWidth = intval($this->imageWidth * 0.8);
        $this->maxTextHeight = intval($this->imageHeight * 0.6);

        $this->fitTextToBox();

        $paddingX = intval(($this->imageWidth - $this->maxTextWidth) / 2);

        switch (strtolower($this->textAlignment)) {
            case 'left':
                switch (strtolower($this->textSticky)) {
                    case 'left':
                        $this->textStartX = $paddingX;

                        break;
                    case 'right':
                        $this->textStartX = $this->imageWidth - $this->textBoxWidth - $paddingX;

                        break;
                    default:
                        $this->textStartX = intval(($this->imageWidth - $this->textBoxWidth) / 2);
                }

                break;
            case 'right':
                switch (strtolower($this->textSticky)) {
                    case 'left':
                        $this->textStartX = $this->textBoxWidth + $paddingX;

                        break;
                    case 'right':
                        $this->textStartX = $this->imageWidth - $paddingX;

                        break;
                    default:
                        $this->textStartX = intval(($this->imageWidth / 2) + ($this->textBoxWidth / 2));
                }

                break;
            default:
                $this->textStartX = intval($this->imageWidth / 2);
        }

        $this->textStartY = intval(($this->imageHeight - $this->textBoxHeight) / 2);
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     */
    protected function setAppNameBox(): void
    {
        $maxAppNameWidth = intval($this->imageWidth * 0.4);
        $maxAppNameHeight = intval($this->imageHeight * 0.2);

        if ($this->appName) {
            for (; $this->appNameFontSize >= 8; $this->appNameFontSize--) {
                $appNameBoxSize = $this->getTextBoxSize($this->appName, $this->appNameFont, $this->appNameFontSize);

                $this->appNameBoxWidth = $appNameBoxSize['width'];
                $this->appNameBoxHeight = $appNameBoxSize['height'];

                if ($this->appNameBoxWidth <= $maxAppNameWidth && $this->appNameBoxHeight <= $maxAppNameHeight) {
                    break;
                }
            }
        }
    }

    protected function setAppNameCoordinates(): void
    {
        switch ($this->appNamePosition) {
            case 'top-left':
            case 'left-top':
                $this->appNameStartY = $this->appNameDefaultPaddingY;
                $this->appNameStartX = $this->appNameDefaultPaddingX;

                break;
            case 'bottom-left':
            case 'left-bottom':
                $this->appNameStartY = $this->imageHeight - $this->appNameDefaultPaddingY - $this->appNameBoxHeight;
                $this->appNameStartX = $this->appNameDefaultPaddingX;

                break;
            case 'top-center':
            case 'center-top':
                $this->appNameStartY = $this->appNameDefaultPaddingY;
                $this->appNameStartX = intval(($this->imageWidth / 2) - ($this->appNameBoxWidth / 2));

                break;
            case 'bottom-center':
            case 'center-bottom':
                $this->appNameStartY = $this->imageHeight - $this->appNameDefaultPaddingY - $this->appNameBoxHeight;
                $this->appNameStartX = intval(($this->imageWidth / 2) - ($this->appNameBoxWidth / 2));

                break;
            case 'top-right':
            case 'right-top':
                $this->appNameStartY = $this->appNameDefaultPaddingY;
                $this->appNameStartX = $this->imageWidth - $this->appNameDefaultPaddingX - $this->appNameBoxWidth;

                break;
            case 'bottom-right':
            case 'right-bottom':
                $this->appNameStartY = $this->imageHeight - $this->appNameDefaultPaddingY - $this->appNameBoxHeight;
                $this->appNameStartX = $this->imageWidth - $this->appNameDefaultPaddingX - $this->appNameBoxWidth;

                break;
            default:
                $this->appNameStartY = $this->appNameDefaultPaddingY;
                $this->appNameStartX = $this->imageWidth - $this->appNameDefaultPaddingX - $this->appNameBoxWidth;
        }
    }

    protected function setLineCoordinates(): void
    {
        $defaultPaddingLine = 7;
        $rectangleHeight = intval(round($this->imageWidth / 200));

        switch ($this->appNamePosition) {
            case 'top-left':
            case 'top-center':
            case 'top-right':
            case 'left-top':
            case 'center-top':
            case 'right-top':
                $this->rectangleX1 = $this->appNameStartX;
                $this->rectangleY1 = $this->appNameStartY + $this->appNameBoxHeight + $defaultPaddingLine;

                break;
            case 'bottom-left':
            case 'bottom-center':
            case 'bottom-right':
            case 'center-bottom':
            case 'right-bottom':
            case 'left-bottom':
            default:
                $this->rectangleX1 = $this->appNameStartX;
                $this->rectangleY1 = $this->appNameStartY - $defaultPaddingLine - $rectangleHeight;
        }

        $this->rectangleX2 = $this->rectangleX1 + $this->appNameBoxWidth;
        $this->rectangleY2 = $this->rectangleY1 + $rectangleHeight;
    }

    protected function setLabelCoordinates(): void
    {
        $rectangleWidth = 30;
        $rectangleHeight = $this->appNameBoxHeight + ($this->appNameDefaultPaddingY * 2);

        switch ($this->appNamePosition) {
            case 'top-left':
            case 'left-top':
                $this->rectangleX1 = 0;
                $this->rectangleY1 = 0;

                $this->rectangleX2 = $rectangleWidth;
                $this->rectangleY2 = $rectangleHeight;

                break;
            case 'bottom-left':
            case 'left-bottom':
                $this->rectangleX1 = 0;
                $this->rectangleY1 = $this->imageHeight - $rectangleHeight;

                $this->rectangleX2 = $rectangleWidth;
                $this->rectangleY2 = $this->imageHeight;

                break;
            case 'top-center':
            case 'center-top':
            case 'bottom-center':
            case 'center-bottom':
            default:
                $this->rectangleX1 = 0;
                $this->rectangleY1 = 0;

                $this->rectangleX2 = 0;
                $this->rectangleY2 = 0;

                break;
            case 'top-right':
            case 'right-top':
                $this->rectangleX1 = $this->imageWidth - $rectangleWidth;
                $this->rectangleY1 = 0;

                $this->rectangleX2 = $this->imageWidth;
                $this->rectangleY2 = $rectangleHeight;

                break;
            case 'bottom-right':
            case 'right-bottom':
                $this->rectangleX1 = $this->imageWidth - $rectangleWidth;
                $this->rectangleY1 = $this->imageHeight - $rectangleHeight;

                $this->rectangleX2 = $this->imageWidth;
                $this->rectangleY2 = $this->imageHeight;

                break;
        }
    }

    protected function setRectangleCoordinates(): void
    {
        $defaultPaddingCenterLabel = 30;
        $rectangleWidth = (($this->appNameDefaultPaddingX) * 2) + $this->appNameBoxWidth;
        $rectangleHeight = $this->appNameBoxHeight + ($this->appNameDefaultPaddingY * 2);

        switch ($this->appNamePosition) {
            case 'top-left':
            case 'left-top':
                $this->rectangleX1 = 0;
                $this->rectangleY1 = 0;

                $this->rectangleX2 = $rectangleWidth;
                $this->rectangleY2 = $rectangleHeight;

                break;
            case 'bottom-left':
            case 'left-bottom':
                $this->rectangleX1 = 0;
                $this->rectangleY1 = $this->imageHeight - $rectangleHeight;

                $this->rectangleX2 = $rectangleWidth;
                $this->rectangleY2 = $this->imageHeight;

                break;
            case 'top-center':
            case 'center-top':
                $this->rectangleX1 = $this->appNameStartX - $defaultPaddingCenterLabel;
                $this->rectangleY1 = 0;

                $this->rectangleX2 = $this->rectangleX1 + $this->appNameBoxWidth + ($defaultPaddingCenterLabel * 2);
                $this->rectangleY2 = $rectangleHeight;

                break;
            case 'bottom-center':
            case 'center-bottom':
            default:
                $this->rectangleX1 = $this->appNameStartX - $defaultPaddingCenterLabel;
                $this->rectangleY1 = $this->imageHeight - $rectangleHeight;

                $this->rectangleX2 = $this->rectangleX1 + $this->appNameBoxWidth + ($defaultPaddingCenterLabel * 2);
                $this->rectangleY2 = $this->imageHeight;

                break;
            case 'top-right':
            case 'right-top':
                $this->rectangleX1 = $this->imageWidth - $rectangleWidth;
                $this->rectangleY1 = 0;

                $this->rectangleX2 = $this->imageWidth;
                $this->rectangleY2 = $rectangleHeight;

                break;
            case 'bottom-right':
            case 'right-bottom':
                $this->rectangleX1 = $this->imageWidth - $rectangleWidth;
                $this->rectangleY1 = $this->imageHeight - $rectangleHeight;

                $this->rectangleX2 = $this->imageWidth;
                $this->rectangleY2 = $this->imageHeight;

                break;
        }
    }

    protected function multiLine(string $string, int $width = 75, bool $cut = true): string
    {
        /** @var string $string */
        $string = preg_replace('~\s+~', ' ', $string);
        $break = "\n";

        $lines = explode($break, $string);
        foreach ($lines as &$line) {
            $line = rtrim($line);
            if (mb_strlen($line) <= $width) {
                continue;
            }
            $words = explode(' ', $line);
            $line = '';
            $actual = '';
            foreach ($words as $word) {
                if (mb_strlen($actual . $word) <= $width) {
                    $actual .= $word . ' ';
                } else {
                    if ($actual != '') {
                        $line .= rtrim($actual) . $break;
                    }
                    $actual = $word;
                    if ($cut) {
                        while (mb_strlen($actual) > $width) {
                            $line .= mb_substr($actual, 0, $width) . $break;
                            $actual = mb_substr($actual, $width);
                        }
                    }
                    $actual .= ' ';
                }
            }
            $line .= trim($actual);
        }

        return implode($break, $lines);
    }
}
