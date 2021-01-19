<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

use Hyperf\HttpServer\Annotation\Mapping;
use Hyperf\Utils\Str;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class RequestApi extends Mapping
{
    public $path;

    public $summary;

    public $description;

    public $deprecated;

    public const GET = 'GET';

    public const POST = 'POST';

    public const PUT = 'PUT';

    public const PATCH = 'PATCH';

    public const DELETE = 'DELETE';

    public const HEADER = 'HEADER';

    public const OPTIONS = 'OPTIONS';

    public $methods = ['GET', 'POST'];

    public function __construct($value = null)
    {
        parent::__construct($value);
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (property_exists($this, $key)) {
                    if($key === 'methods'){
                        if (is_string($val)) {
                            // Explode a string to a array
                            $val = explode(',', Str::upper(str_replace(' ', '', $val)));
                        }else {
                            $methods = [];
                            foreach ($value['methods'] as $method) {
                                $methods[] = Str::upper(str_replace(' ', '', $method));
                            }
                            $val = $methods;
                        }
                    }
                    $this->{$key} = $val;
                }
            }
        }
    }

}
