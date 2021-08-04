<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Validation;

use Hyperf\Apidoc\Annotation\ValidationRule;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use Hyperf\Validation\Contract\PresenceVerifierInterface;
use Hyperf\Validation\Contract\Rule;
use Hyperf\Validation\ValidatorFactory;

class Validation
{
    public $container;

    /** @var ValidatorFactory */
    public $factory;

    /**
     * 内置验证规则
     * @var ValidationCustomRule|mixed
     */
    public $customValidateRules;

    /**
     * 收集的自定义验证规则验证器
     * @var array
     */
    public $validationRules = [];

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        $this->factory = $this->container->get(ValidatorFactory::class);
        $this->customValidateRules = $this->container->get(ValidationCustomRule::class);
        $validationRules = AnnotationCollector::getClassByAnnotation(ValidationRule::class);
        foreach ($validationRules as $class => $obj) {
            $this->validationRules[] = new $class;
        }
    }

    public function check($rules, $data, $obj = null)
    {
        foreach ($data as $key => $val) {
            if (strpos((string)$key, '.') !== false) {
                Arr::set($data, $key, $val);
                unset($data[$key]);
            }
        }
        $map = [];
        $real_rules = [];
        $white_data = [];

        foreach ($rules as $key => $rule) {
            $field_extra = explode('|', $key);
            $field = $field_extra[0];
            if (!$rule && Arr::get($data, $field)) {
                $white_data[$field] = Arr::get($data, $field);
                continue;
            }
            $title = $field_extra[1] ?? $field_extra[0];
            if (is_array($rule)) {
                $has_required = Str::contains('required', json_encode($rule, JSON_UNESCAPED_UNICODE));
                $sub_data = Arr::get($data, $field, []);
                if ($has_required && !$sub_data) {
                    return [null, [$title . ' 子项是必须的']];
                }

                // rule : {"field|字段":"required|***"}
                if (Arr::isAssoc($rule)) {
                    $result = $this->check($rule, $sub_data, $obj);
                    $result[1] = array_map(function ($item) use ($title) {
                        return sprintf('%s中的%s', $title, $item);
                    }, $result[1]);
                    if ($result[1]) {
                        return $result;
                    }
                    continue;
                }   // rule : {{"field|字段":"required|***"}}
                foreach ($sub_data as $index => $part) {
                    $result = $this->check($rule[$index] ?? $rule[0], $part, $obj);
                    $result[1] = array_map(function ($item) use ($title, $index) {
                        return sprintf('%s中第%s项的%s', $title, $index + 1, $item);
                    }, $result[1]);
                    if ($result[1]) {
                        return $result;
                    }
                }
                continue;
            }
            $_rules = explode('|', $rule);
            foreach ($_rules as $index => &$item) {
                if ($item == 'json') {
                    $item = 'array';
                }
                if (method_exists($this, $item)) {
                    $item = $this->makeCustomRule($title, $item, $this);
                } elseif (method_exists($this->customValidateRules, $item)) {
                    $item = $this->makeCustomRule($title, $item, $this->customValidateRules);
                } elseif (is_string($item) && Str::startsWith($item, 'cb_')) {
                    $item = $this->makeObjectCallback($title, Str::replaceFirst('cb_', '', $item), $obj);
                } else {
                    foreach ($this->validationRules as $validationRule) {
                        if (method_exists($validationRule, $item)) {
                            $item = $this->makeCustomRule($title, $item, $validationRule);
                        }
                    }
                }

                unset($item);
            }
            $real_rules[$field] = $_rules;
            $map[$field] = $title;
        }

        $validator = $this->factory->make($data, $real_rules, [], $map);

        $verifier = $this->container->get(PresenceVerifierInterface::class);
        $validator->setPresenceVerifier($verifier);

        $fails = $validator->fails();

        $errors = [];
        if ($fails) {
            //$errors = $validator->errors()->all();
            foreach ($validator->errors()->getMessages() as $column => $messages) {
                // 多条错误信息
                /*$errors[$column] = array_map(function ($message) {
                    return $message;
                }, $messages);*/

                //单条错误
                $errors[$column] = $messages[0];
            }

            return [
                null,
                $errors,
            ];
        }

        $filter_data = array_merge($this->parseData($validator->validated()), $white_data);

        $real_data = [];
        foreach ($filter_data as $key => $val) {
            Arr::set($real_data, $key, $val);
        }

        $real_data = array_map_recursive(function ($item) {
            return is_string($item) ? trim($item) : $item;
        }, $real_data);

        return [
            $fails ? null : $real_data,
            $errors,
        ];
    }

    public function makeCustomRule($field, $custom_rule, $object)
    {
        return new class($field, $custom_rule, $object) implements Rule {
            public $custom_rule;

            public $validation;

            public $error = '%s ';

            public $attribute;

            public $field;

            public function __construct($field, $custom_rule, $validation)
            {
                $this->field = $field;
                $this->custom_rule = $custom_rule;
                $this->validation = $validation;
            }

            public function passes($attribute, $value): bool
            {
                $this->attribute = $attribute;
                $rule = $this->custom_rule;
                if (strpos($rule, ':') !== false) {
                    $rule = explode(':', $rule)[0];
                    $extra = explode(',', explode(':', $rule)[1]);
                    $ret = $this->validation->{$rule}($attribute, $value, $extra);
                    if (is_string($ret)) {
                        $this->error .= $ret;

                        return false;
                    }

                    return true;
                }
                $ret = $this->validation->{$rule}($attribute, $value);
                if (is_string($ret)) {
                    $this->error .= $ret;

                    return false;
                }

                return true;
            }

            public function message()
            {
                return sprintf($this->error, $this->field);
            }
        };
    }

    public function makeObjectCallback($field, $method, $object)
    {
        return new class($field, $method, $this, $object) implements Rule {
            public $custom_rule;

            public $validation;

            public $object;

            public $error = '%s ';

            public $attribute;

            public $field;

            public function __construct($field, $custom_rule, $validation, $object)
            {
                $this->field = $field;
                $this->custom_rule = $custom_rule;
                $this->validation = $validation;
                $this->object = $object;
            }

            public function passes($attribute, $value): bool
            {
                $this->attribute = $attribute;
                $rule = $this->custom_rule;
                if (strpos($rule, ':') !== false) {
                    $rule = explode(':', $rule)[0];
                    $extra = explode(',', explode(':', $rule)[1]);
                    $ret = $this->object->{$rule}($attribute, $value, $extra);
                    if (is_string($ret)) {
                        $this->error .= $ret;

                        return false;
                    }

                    return true;
                }
                $ret = $this->object->{$rule}($attribute, $value);
                if (is_string($ret)) {
                    $this->error .= $ret;

                    return false;
                }

                return true;
            }

            public function message()
            {
                return sprintf($this->error, $this->field);
            }
        };
    }

    /**
     * Parse the data array, converting -> to dots.
     */
    public function parseData(array $data): array
    {
        $newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseData($value);
            }

            if (Str::contains((string)$key, '->')) {
                $newData[str_replace('->', '.', $key)] = $value;
            } else {
                $newData[$key] = $value;
            }
        }

        return $newData;
    }
}
