<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Admin\Controller;

use Laminas\ApiTools\Admin\Model\ValidatorsModel;

class ValidatorsController extends AbstractPluginManagerController
{
    /** @var string */
    protected $property = 'validators';

    public function __construct(ValidatorsModel $model)
    {
        $this->model = $model;
    }

    /** @return ApiProblemResponse|JsonModel */
    public function validatorsAction()
    {
        return $this->handleRequest();
    }
}
