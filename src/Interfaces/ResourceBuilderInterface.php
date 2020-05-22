<?php
namespace CarloNicora\Minimalism\Services\ResourceBuilder\Interfaces;

use CarloNicora\JsonApi\Objects\ResourceObject;

interface ResourceBuilderInterface {
    /**
     * @return ResourceObject
     */
    public function buildResource(): ResourceObject;
}