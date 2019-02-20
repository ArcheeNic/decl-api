<?php

namespace Tests\Unit\DeclApiDoc;

use DeclApi\Documentor\ScanFiles;
use PHPUnit\Framework\TestCase;

class ScanFolderTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testHandle()
    {
        $path = __DIR__.'/../DeclApi';
        $classesInfo = (new ScanFiles($path))->getInfo();
        $this->assertIsArray($classesInfo);
    }
}
