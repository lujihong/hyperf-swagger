## 基于 composer daodao97/apidog 修改优化更好的适配后台前端使用

## Api 生成
一个 [Hyperf](https://github.com/hyperf/hyperf) 框架的 Api 参数校验及 swagger 文档生成组件

1.  根据注解自动进行Api参数的校验, 业务代码更纯粹.
2.  根据注解自动生成Swagger文档, 让接口文档维护更省心.
3.  基于daodao97/apidog组件修改，新增RequestApi，注解自定义规则，修改返回的验证结果message换行符修改未增加对应的字段名

> 在 1.2 版本后, 本扩展移除了内部自定义的验证器, 只保留的 hyperf 原生验证器, 以保持验证规则的统一

## 安装

```
composer require lujihong/hyperf-swagger
```
## 使用

#### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish lujihong/hyperf-swagger

# hyperf/validation 的依赖发布

php bin/hyperf.php vendor:publish hyperf/translation

php bin/hyperf.php vendor:publish hyperf/validation
```

### 2. 修改配置文件

> 注意 与1.2及之前的版本相比, 配置文件结构及文件名 略有不同
> 
> (1) 配置文件结构的优化, 增加了swagger外的整体配置
>
> (2) 配置文件的名称 由 swagger.php 改为 apidoc.php

根据需求修改 `config/autoload/apidoc.php`

```php
<?php

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
    'field_error_message' => 'message',
    'exception_enable' => false,
    // swagger 的基础配置
    'swagger' => [
        'swagger' => '2.0',
        'info' => [
            'description' => 'hyperf swagger api desc',
            'version' => '1.0.0',
            'title' => 'HYPERF API DOC',
        ],
        'host' => 'apidoc.cc',
        'schemes' => ['http', 'https'],
    ],
    'templates' => [
        // {template} 字面变量  替换 schema 内容
//        // 默认 成功 返回
//        'success' => [
//            "code|code"    => '0',
//            "result"  => '{template}',
//            "message|message" => 'Success',
//        ],
//        // 分页
//        'page' => [
//            "code|code"    => '0',
//            "result"  => [
//                'pageSize' => 10,
//                'total' => 1,
//                'totalPage' => 1,
//                'list' => '{template}'
//            ],
//            "message|message" => 'Success',
//        ],
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
```

### 3. 启用 Api参数校验中间件

```php
// config/autoload/middlewares.php

Hyperf\Apidoc\Middleware\ApiValidationMiddleware::class
```

### 4. 校验规则的定义

规则列表参见 [hyperf/validation 文档](https://hyperf.wiki/#/zh-cn/validation?id=%e9%aa%8c%e8%af%81%e8%a7%84%e5%88%99)

更详细的规则支持列表可以参考 [laravel/validation 文档](https://learnku.com/docs/laravel/6.x/validation/5144#c58a91)

扩展在原生的基础上进行了封装, 支持方便的进行 `自定义校验` 和 `控制器回调校验`

## 实现思路

api参数的自动校验: 通过中间件拦截 http 请求, 根据注解中的参数定义, 通过 `valiation` 自动验证和过滤, 如果验证失败, 则拦截请求. 其中`valiation` 包含 规则校验, 参数过滤, 自定义校验 三部分. 

swagger文档生成: 在`php bin/hyperf.php start` 启动 `http-server` 时, 通过监听 `BootAppConfListener` 事件, 扫码控制器注解, 通过注解中的 访问类型, 参数格式, 返回类型 等, 自动组装 `swagger.json` 结构, 最后输出到 `config/autoload/apidoc.php` 定义的文件路径中

## 支持的注解 

#### Api类型
`GetApi`, `PostApi`, `PutApi`, `DeleteApi`, `RequestApi`

### 参数类型
`Header`, `Query`, `Body`, `FormData`, `Path`

### 自定义验证规则
`ValidationRule`

### 其他
`ApiController`, `ApiResponse`, `ApiVersion`, `ApiServer`, `ApiDefinitions`, `ApiDefinition`

```php
/**
 * @ApiVersion(version="v1")
 * @ApiServer(name="http")
 */
class UserController {} 
```

`ApiServer` 当你在 `config/autoload.php/server.php servers` 中配置了多个 `http` 服务时, 如果想不同服务生成不同的`swagger.json` 可以在控制器中增加此注解.

`ApiVersion` 当你的统一个接口存在不同版本时, 可以使用此注解, 路由注册时会为每个木有增加版本号, 如上方代码注册的实际路由为 `/v1/user/***`

`ApiDefinition` 定义一个 `Definition`，用于Response的复用。 *swagger* 的difinition是以引用的方式来嵌套的，如果需要嵌套另外一个(值为object类型就需要嵌套了)，可以指定具体 `properties` 中的 `$ref` 属性

`ApiDefinitions` 定义一个组`Definition`

`ApiResponse` 响应体的`schema`支持为key设置简介. `$ref` 属性可以引用 `ApiDefinition` 定义好的结构(该属性优先级最高)
```php
@ApiResponse(code="0", description="删除成功", schema={"id|这里是ID":1})
@ApiResponse(code="0", description="删除成功", schema={"$ref": "ExampleResponse"})
```

具体使用方式参见下方样例

## 【新增】自定义规则创建
```php
<?php
declare(strict_types=1);
namespace App\Test;

use Hyperf\Apidoc\Annotation\ValidationRule;

/**
 * 在任意位置创建规则类
 */

#[ValidationRule()]
class TestRute
{
    /**
     * @param $attribute 属性
     * @param $value 属性值
     * @return bool|string 校验错误则返回错误信息, 正确则返回 true
     */
    public function test($attribute, $value)
    {
        return '这是test';
    }
}
```

## 样例
```php
<?php
declare(strict_types=1);
namespace App\Controller;

use Hyperf\Apidoc\Annotation\ApiController;
use Hyperf\Apidoc\Annotation\ApiResponse;
use Hyperf\Apidoc\Annotation\ApiVersion;
use Hyperf\Apidoc\Annotation\Body;
use Hyperf\Apidoc\Annotation\DeleteApi;
use Hyperf\Apidoc\Annotation\FormData;
use Hyperf\Apidoc\Annotation\GetApi;
use Hyperf\Apidoc\Annotation\Header;
use Hyperf\Apidoc\Annotation\PostApi;
use Hyperf\Apidoc\Annotation\RequestApi;
use Hyperf\Apidoc\Annotation\Query;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\ApplicationContext;

/**
 * @ApiVersion(version="v1")
 * @ApiController(tag="demo管理", description="demo的新增/修改/删除接口")
 * @ApiDefinitions({
 *  @ApiDefinition(name="DemoOkResponse", properties={
 *     "code|响应码": 200,
 *     "msg|响应信息": "ok",
 *     "data|响应数据": {"$ref": "DemoInfoData"}
 *  }),
 *  @ApiDefinition(name="DemoInfoData", properties={
 *     "userInfo|用户数据": {"$ref": "DemoInfoDetail"}
 *  }),
 *  @ApiDefinition(name="DemoInfoDetail", properties={
 *     "id|用户ID": 1,
 *     "mobile|用户手机号": { "default": "13545321231", "type": "string" },
 *     "nickname|用户昵称": "nickname",
 *     "avatar": { "default": "avatar", "type": "string", "description": "用户头像" },
 *  })
 * })
 */
class DemoController extends AuthController
{

    /**
     * 默认不定义 methods 默认值 post, get, put, delete 不区分大小写
     * @RequestApi(path="/test", summary="测试", methods="post, get, put, delete")
     * @ApiResponse(code="200", description="ok", schema={{
     *     "a|aa": {{
     *          "a|aaa":"b","c|ccc":5.2
     *      }},
     *     "b|ids": {1,2,3},
     *     "c|strings": {"a","b","c"},
     *     "d|dd": {"a":"b","c":"d"},
     *     "e|ee": "f"
     * }})
     */
    public function test()
    {
    
    }

    /**
     * @PostApi(path="/demo", description="添加一个用户")
     * @Header(key="token|接口访问凭证", rule="required")
     * @FormData(key="a.name|名称", rule="required|max:10|cb_checkName")
     * @FormData(key="a.sex|年龄", rule="integer|in:0,1")
     * @FormData(key="aa|aa", rule="required|array")
     * @FormData(key="file|文件", rule="file")
     * @ApiResponse(code="-1", description="参数错误", template="page")
     * @ApiResponse(code="0", description="请求成功", schema={"id":"1"})
     */
    public function add()
    {
        return [
            'code'   => 0,
            'id'     => 1,
            'params' => $this->request->post(),
        ];
    }

    // 自定义的校验方法 rule 中 cb_*** 方式调用
    public function checkName($attribute, $value)
    {
        if ($value === 'a') {
            return "拒绝添加 " . $value;
        }

        return true;
    }

    /**
     * 请注意 body 类型 rules 为数组类型
     * @DeleteApi(path="/demo", description="删除用户")
     * @Body(rules={
     *     "id|用户id":"required|integer|max:10",
     *     "deepAssoc|深层关联":{
     *        "name_1|名称": "required|integer|max:20"
     *     },
     *     "deepUassoc|深层索引":{{
     *         "name_2|名称": "required|integer|max:20"
     *     }},
     *     "a.b.c.*.e|aa":"required|integer|max:10",
     * })
     * @ApiResponse(code="-1", description="参数错误")
     * @ApiResponse(code="0", description="删除成功", schema={"id":1})
     */
    public function delete()
    {
        $body = $this->request->getBody()->getContents();
        return [
            'code'  => 0,
            'query' => $this->request->getQueryParams(),
            'body'  => json_decode($body, true),
        ];
    }

    /**
     * @GetApi(path="/demo", description="获取用户详情")
     * @Query(key="id", rule="required|integer|max:0")
     * @ApiResponse(code="-1", description="参数错误")
     * @ApiResponse(code="0", schema={"id":1,"name":"张三","age":1}, template="success")
     */
    public function get()
    {
        return [
            'code' => 0,
            'id'   => 1,
            'name' => '张三',
            'age'  => 1,
        ];
    }

    /**
     * schema中可以指定$ref属性引用定义好的definition
     * @GetApi(path="/demo/info", description="获取用户详情")
     * @Query(key="id", rule="required|integer|max:0")
     * @ApiResponse(code="-1", description="参数错误")
     * @ApiResponse(code="0", schema={"$ref": "DemoOkResponse"})
     */
    public function info()
    {
        return [
            'code' => 0,
            'id'   => 1,
            'name' => '张三',
            'age'  => 1,
        ];
    }

    /**
     * @GetApi(path="/demos", summary="用户列表")
     * @ApiResponse(code="200", description="ok", schema={{
     *     "a|aa": {{
     *          "a|aaa":"b","c|ccc":5.2
     *      }},
     *     "b|ids": {1,2,3},
     *     "c|strings": {"a","b","c"},
     *     "d|dd": {"a":"b","c":"d"},
     *     "e|ee": "f"
     * }})
     */
    public function list()
    {
        return [
            [
                "a" => [
                    ["a" => "b", "c" => "d"],
                ],
                "b" => [1, 2, 3],
                "c" => ["a", "b", "c"],
                "d" => [
                    "a" => "b",
                    "c" => "d",
                ],
                "e" => "f",
            ],
        ];
    }

}
```

## Swagger UI启动

本组件提供了两种方式来启用`SwaggerUI`
, 当`config/autoload/apidoc.php enable = true` 时

#### 方式一 

系统启动时, `swagger.json` 会自动输出到配置文件中定义的 `output_file`中, 此时我们到`swagger ui`的前端文件结合`nginx`启动web服务

#### 方式二

也可以使用组件提供的快捷命令, 快速启动一个 `swagger ui`.

```bash
php bin/hyperf.php apidoc:ui

php bin/hyperf.php apidoc:ui --port 8888
```

![hMvJnQ](https://gitee.com/daodao97/asset/raw/master/imgs/hMvJnQ.jpg)

## Swagger展示

![AOFVzI](https://gitee.com/daodao97/asset/raw/master/imgs/AOFVzI.jpg)
