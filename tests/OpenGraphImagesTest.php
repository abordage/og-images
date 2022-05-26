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

        $openGraphImage = $this->openGraphImages->make($text);
        $result = $openGraphImage->save($path);
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
            ["Blockchain’s youngest billionaire roasts world’s biggest cryptocurrency"],
            ["Another week of job slashes and crypto crashes"],
        ];
    }
}
