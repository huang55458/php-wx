<?php

namespace app\test;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function testImage(): void
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read('E:/test/tmp/1.png');
        $image->resize(height: 300);
        $encoded = $image->toGif();
        $save_path = 'E:/test/tmp/2.gif';
        $encoded->save($save_path);
        $this->assertFileExists($save_path);
    }
}