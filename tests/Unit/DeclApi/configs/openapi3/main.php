<?php
use DeclApi\Documentor\OpenApi3\Specification\Property;
return [
    'documentor' => [
        'savePath' => sys_get_temp_dir().'/test_openapi_main.json',
        'replaceSchemas' => [
            ['!Tests\\\\Unit\\\\DeclApi\\\\TestedBlank\\\\(.*?)!','$1'],
        ]
    ],
    'openapi'    => '3.0.1',
    'servers'    => [
        [
            'url'       => '{scheme}://{host}',
            'variables' => [
                'scheme' => [
                    'description' => 'The Data Set API is accessible via https and http',
                    'enum'        => ['https', 'http'],
                    'default'     => 'https'
                ],
                'host'   => [
                    'description' => 'Host developer or worked',
                    'enum'        => ['test.example.loc', 'dev.example.loc'],
                    'default'     => 'test.example.loc'
                ],
            ]
        ]
    ],
    'info'       => [
        'description' => 'The Data Set API (DSAPI) allows the public users to discover and search
    USPTO exported data sets. This is a generic API that allows USPTO users to
    make any CSV based data files searchable through API. With the help of GET
    call, it returns the list of data fields that are searchable. With the help
    of POST call, data can be fetched based on the filters on the field names.
    Please note that POST call is used to search the actual data. The reason for
    the POST call is that it allows users to specify any complex search criteria
    without worry about the GET size limitations as well as encoding of the
    input parameters.',
        'version'     => '1.0.0',
        'title'       => 'USPTO Data Set API',
        'contact'     => [
            'name'  => 'Open Data Portal',
            'url'   => 'https://developer.uspto.gov',
            'email' => 'developer@uspto.gov'
        ]
    ],
    'tags'       => [],
    'paths'      => [],
    'components' => [
        'schemas' => [
            'error' => [
                'description' => 'Вовзвращаемая информация об ошибки',
                'properties'=>[
                    'code'=>[
                        'type'=>Property::$formatAndTypeMap['int32'],
                        'format'=>'int32'
                    ],
                    'title'=>[
                        'type'=>Property::$formatAndTypeMap['string'],
                        'format'=>'string'
                    ],
                    'description'=>[
                        'type'=>Property::$formatAndTypeMap['string'],
                        'format'=>'string'
                    ]
                ]
            ]
        ]
    ]
];