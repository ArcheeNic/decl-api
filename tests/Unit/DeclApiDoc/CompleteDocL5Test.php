<?php

namespace Tests\Unit\DeclApiDoc;

use DeclApi\Documentor\Frameworks\Laravel5;
use DeclApi\Documentor\ScanFiles;

class DocLaravel5Test extends \Tests\TestCase
{

    /**
     * @throws \Exception
     */
    public function testScanFiles()
    {
        $this->markTestSkipped('Пропущено. Laravel не установлен');

        $docRoot = __DIR__.'/../DeclApi';
        $scanRoot = __DIR__.'/../DeclApi';
        $data = (new Laravel5($docRoot,$scanRoot))->getScanFiles();
        $this->assertIsArray($data);
    }
}
