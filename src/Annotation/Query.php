<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Query extends Param
{
    public $in = 'query';
}
