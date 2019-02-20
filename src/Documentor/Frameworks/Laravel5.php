<?php namespace DeclApi\Documentor\Frameworks;

use DeclApi\Documentor\ScanFiles;
use Illuminate\Support\Facades\Route;
use Tests\Unit\DeclApi\TestedBlank\TestedPoint;

class Laravel5
{
    protected $allowMethods = ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS'];

    /**
     * @var string
     */
    protected $docRoot;

    /**
     * @var string
     */
    protected $scanRoot;

    /**
     * @var array
     */
    protected $scanFiles;

    /**
     * @return array
     */
    public function getScanFiles(): array
    {
        return $this->scanFiles;
    }

    /**
     * Laravel5 constructor.
     *
     * @param $docRoot
     * @param $scanRoot
     *
     * @throws \Exception
     */
    public function __construct($docRoot, $scanRoot)
    {
        $this->docRoot  = $docRoot;
        $this->scanRoot = $scanRoot;
        $this->generate();
    }

    /**
     * @throws \Exception
     */
    protected function generate()
    {
        $scanFiles           = new ScanFiles($this->scanRoot);
        $scanFiles           = $scanFiles->getInfo();
        $scanFiles['route'] = $this->getRoutes($scanFiles['point']);
        $this->scanFiles = $scanFiles;
    }

    /**
     * @param array $points
     *
     * @return array
     */
    protected function getRoutes(array $points)
    {
        Route::get('declApi/test', TestedPoint::class)->name('declApi.test');

        $routes = Route::getRoutes();

        $actionPoints = [];
        foreach ($points as $file => $pointItems) {
            foreach ($pointItems as $key => $pointItem) {
                $action = $routes->getByAction($pointItem->getClassname());
                if (!$action) {
                    continue;
                }
                $methods = $action->methods();
                foreach ($methods as $method) {
                    if (in_array($method, $this->allowMethods)) {
                        $actionPoints[$file][$method.':'.$action->uri()] = [
                            'method' => $method,
                            'action' => $action->uri(),
                            'name'   => $action->getAction('as'),
                            'class'  => $pointItem->getClassname(),
                        ];
                    }
                }
            }
        }

        return $actionPoints;
    }
}