<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

#[Attribute(Attribute::TARGET_METHOD)]
class FormData extends Param
{
    public $in = 'formData';
}
