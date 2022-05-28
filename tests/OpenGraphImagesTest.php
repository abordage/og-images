<?php

namespace Abordage\OpenGraphImages\Tests;

use Abordage\OpenGraphImages\OpenGraphImages;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class OpenGraphImagesTest extends TestCase
{
    private OpenGraphImages $openGraphImages;
    private string $directoryPath;

    protected function setUp(): void
    {
        $this->openGraphImages = new OpenGraphImages(['app_name' => 'website.test']);

        $rootDirName = 'virtualDir';
        vfsStream::setup($rootDirName);
        $this->directoryPath = vfsStream::url($rootDirName);
    }

    /**
     * @dataProvider textProvider
     */
    public function testMake(string $text): void
    {
        $result = $this->openGraphImages->make($text);
        $this->assertInstanceOf(OpenGraphImages::class, $result);
        $this->assertEquals('image/png', $this->getMimeTypeFromString($result->get()));
    }

    /**
     * @dataProvider textProvider
     */
    public function testMakeTwitter(string $text): void
    {
        $result = $this->openGraphImages->makeTwitter($text);
        $this->assertInstanceOf(OpenGraphImages::class, $result);
        $this->assertEquals('image/png', $this->getMimeTypeFromString($result->get()));
    }

    /**
     * @dataProvider textProvider
     */
    public function testMakeVk(string $text): void
    {
        $result = $this->openGraphImages->makeVk($text);
        $this->assertInstanceOf(OpenGraphImages::class, $result);
        $this->assertEquals('image/png', $this->getMimeTypeFromString($result->get()));
    }

    /**
     * @dataProvider textProvider
     */
    public function testSave(string $text): void
    {
        $path = $this->directoryPath . '/test1/test2/test-image.png';

        $openGraphImages = new OpenGraphImages(['app_name' => 'website.test']);
        $result = $openGraphImages->save($path);
        $this->assertFalse($result);

        $openGraphImages = new OpenGraphImages(['app_name' => 'website.test']);
        $result = $openGraphImages->make($text)->save($path);
        $this->assertTrue($result);
        $this->assertEquals('image/png', mime_content_type($path));
    }

    /**
     * @dataProvider textProvider
     * @throws ReflectionException
     */
    public function testMultiLine(string $text): void
    {
        $class = new ReflectionClass(OpenGraphImages::class);
        $method = $class->getMethod('multiLine');
        $method->setAccessible(true);
        $obj = new OpenGraphImages();

        $width = 10;
        /** @var string $result */
        $result = $method->invoke($obj, $text, $width);
        $this->assertIsString($result);

        $lines = explode("\n", $result);
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual($width, mb_strlen($line));
        }
    }

    /**
     * @dataProvider configProvider
     */
    public function testConfig(array $config): void
    {
        $openGraphImages = new OpenGraphImages($config);
        $result = $openGraphImages->make('The Open Graph protocol enables any web page to become a rich object');
        $this->assertInstanceOf(OpenGraphImages::class, $result);
        $this->assertEquals('image/png', $this->getMimeTypeFromString($result->get()));
    }

    /**
     * @param string|null $sting
     * @return false|string
     */
    private function getMimeTypeFromString(?string $sting)
    {
        if (is_null($sting)) {
            return false;
        }

        /** @var resource $finfo */
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_buffer($finfo, $sting);
    }

    public function textProvider(): array
    {
        return [
            [
                "The nonprofit Wikimedia Foundation provides the essential infrastructure for free knowledge. 
            We host Wikipedia, the free online encyclopedia, created, edited, and verified by volunteers 
            around the world, as well as many other vital community projects",
            ],
            ["The Open Graph protocol enables any web page to become a rich object in a social graph"],
            ["Another week of job slashes and crypto crashes"],
        ];
    }

    public function configProvider(): array
    {
        $appNames = [
            'website.test',
            null,
        ];

        $positions = [
            'top-left',
            'top-center',
            'top-right',
            'bottom-left',
            'bottom-center',
            'bottom-right',
        ];

        $styles = [
            'line',
            'label',
            'rectangle',
        ];

        $alignments = [
            'left',
            'center',
            'right',
        ];

        $stickyPositions = [
            'left',
            'center',
            'right',
        ];

        $configs = [];

        foreach ($positions as $position) {
            foreach ($styles as $style) {
                $configs[] = [
                    [
                        'app_name' => $appNames[0],
                        'app_name_position' => $position,
                        'app_name_decoration_style' => $style,
                    ],
                ];
            }
        }

        foreach ($alignments as $alignment) {
            foreach ($stickyPositions as $stickyPosition) {
                $configs[] = [
                    [
                        'app_name' => $appNames[0],
                        'text_alignment' => $alignment,
                        'text_sticky' => $stickyPosition,
                    ],
                ];
            }
        }

        foreach ($appNames as $appName) {
            $configs[] = [
                [
                    'app_name' => $appName,
                ],
            ];
        }

        $configs[] = [
            [
                'font_path' => __DIR__ . '/../fonts/Roboto/Roboto-Regular.ttf',
                'app_name_font_path' => __DIR__ . '/../fonts/Roboto/Roboto-Regular.ttf',
            ],
        ];


        return $configs;
    }
}
