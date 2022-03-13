<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class ApiDefinitions extends AbstractAnnotation
{
    /**
     * @var array
     */
    public array $definitions;

    /**
     * @param $value
     */
    public function __construct($value = null)
    {
        $this->bindMainProperty('definitions', $value);
    }
}
