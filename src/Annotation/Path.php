<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

#[Attribute(Attribute::TARGET_METHOD)]
class Path extends Param
{
    public $in = 'path';
}
