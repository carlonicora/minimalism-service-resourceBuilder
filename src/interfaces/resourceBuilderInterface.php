<?php
namespace carlonicora\minimalism\services\resourceBuilder\interfaces;

use carlonicora\minimalism\modules\jsonapi\resources\resourceObject;

interface resourceBuilderInterface {
    /**
     * @return resourceObject
     */
    public function buildResource(): resourceObject;
}