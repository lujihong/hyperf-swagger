<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

use Hyperf\HttpServer\Annotation\Mapping;

#[Attribute(Attribute::TARGET_METHOD)]
class PutApi extends Mapping
{
    public $path;

    public $summary;

    public $description;

    public $deprecated;

    public $methods = ['PUT'];

    public function __construct($value = null)
    {
        parent::__construct($value);
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $val;
                }
            }
        }
    }
}
