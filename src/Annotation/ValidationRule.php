<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class ValidationRule extends AbstractAnnotation
{

}
