<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

use Hyperf\HttpServer\Annotation\Controller;

#[Attribute(Attribute::TARGET_CLASS)]
class ApiController extends Controller
{
    public $tag;

    /**
     * @var string|null
     */
    public string|null $prefix = '';

    /**
     * @var string
     */
    public string $server = 'http';

    /**
     * @var string
     */
    public string $description = '';
}
