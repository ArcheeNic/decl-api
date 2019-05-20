<?php namespace DeclApi\Core;

use Illuminate\Validation;
use Illuminate\Translation;
use Illuminate\Filesystem\Filesystem;

class ValidatorFactory
{
    private $factory;

    public function __construct(Validation\Factory $factory = null)
    {
        if(!$factory){
            $this->factory = new Validation\Factory(
                $this->loadTranslator()
            );
        }else{
            $this->factory = $factory;
        }
    }

    protected function loadTranslator($locale = 'en', $url = null)
    {
        $file       = dirname(__FILE__).'/lang';
        $filesystem = new Filesystem();
        $loader     = new Translation\FileLoader($filesystem, $file);
        $loader->addNamespace('lang', $file);

        $loader->load('en', 'validation', 'lang');

        return new Translation\Translator($loader, 'en');
    }

    public function __call($method, $args)
    {
        return call_user_func_array(
            [$this->factory, $method],
            $args
        );
    }
}