<?php
declare(strict_types=1);

return [
    // enable false 将不会生成 swagger 文件
    'enable' => env('APP_ENV') !== 'prod',
    // swagger 配置的输出文件
    // 当你有多个 http server 时, 可以在输出文件的名称中增加 {server} 字面变量
    // 比如 /public/swagger/swagger_{server}.json
    'output_file' => BASE_PATH . '/public/swagger/swagger.json',
    // 忽略的hook, 非必须 用于忽略符合条件的接口, 将不会输出到上定义的文件中
    'ignore' => function ($controller, $action) {
        return false;
    },
    // 自定义验证器错误码、错误描述字段
    'error_code' => 400,
    'http_status_code' => 200,
    'field_error_code' => 'code',
    'field_error_message' => 'errors', //错误返回信息字段名
    'exception_enable' => false,
    // swagger 的基础配置
    'swagger' => [
        'swagger' => '2.0',
        'info' => [
            'description' => 'hyperf swagger api desc',
            'version' => '1.0.0',
            'title' => 'HYPERF API DOC',
        ],
        'host' => env('DOMAIN', ''),
        'schemes' => ['http', 'https'],
    ],
    'templates' => [
        // // {template} 字面变量  替换 schema 内容
        // // 默认 成功 返回
        // 'success' => [
        //     "code|code" => '0',
        //     "result" => '{template}',
        //     "message|message" => 'Success',
        // ],
        // // 分页
        // 'page' => [
        //     "code|code" => '0',
        //     "result" => [
        //         'pageSize' => 10,
        //         'total' => 1,
        //         'totalPage' => 1,
        //         'list' => '{template}'
        //     ],
        //     "message|message" => 'Success',
        // ],
    ],
    // golbal 节点 为全局性的 参数配置
    // 跟注解相同, 支持 header, path, query, body, formData
    // 子项为具体定义
    // 模式一: [ key => rule ]
    // 模式二: [ [key, rule, defautl, description] ]
    'global' => [
        // 'header' => [
        //     "x-token|验签" => "required|cb_token"
        // ],
        // 'query' => [
        //     [
        //         'key' => 'xx|cc',
        //         'rule' => 'required',
        //         'default' => 'abc',
        //         'description' => 'description'
        //     ]
        // ]
    ]
];
