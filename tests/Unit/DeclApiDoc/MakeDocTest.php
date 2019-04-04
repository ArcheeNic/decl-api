<?php

namespace Tests\Unit\DeclApiDoc;

use \DeclApi\Documentor\OpenApi3\MakeDoc;
use DeclApi\Documentor\ScanFiles;
use PHPUnit\Framework\TestCase;

class MakeDocTest extends TestCase
{
    protected $data;
    protected $configRoot;


    /**
     * @throws \Exception
     */
    protected function setUp()/*TODO downgrade - :void*/
    {
        parent::setUp();

        $this->configRoot = __DIR__.'/../DeclApi/configs/openapi3';
        $path             = __DIR__.'/../DeclApi';

        $this->data          = (new ScanFiles($path))->getInfo();
        $this->data['route'] = [
            'main' =>
                [
                    'GET:declApi/test' =>
                        [
                            'method' => 'GET',
                            'action' => 'declApi/test',
                            'name'   => 'declApi.test',
                            'class'  => 'Tests\\Unit\\DeclApi\\TestedBlank\\TestedPoint',
                        ],
                ],
        ];

    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testGenerate()
    {
        $makeDoc = new MakeDoc($this->configRoot);
        $configs = $makeDoc->generate($this->data);
        $this->assertTrue(is_array($configs));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function testGenerateAndSave()
    {
        $makeDoc = new MakeDoc($this->configRoot);
        $data    = $makeDoc->generate($this->data, ['main']);
        $this->assertCount(1, $data);
        $makeDoc->save($data['main']);
        $this->assertFileExists($data['main']['documentor']['savePath']);
    }
}
