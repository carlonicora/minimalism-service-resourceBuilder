<?php
namespace CarloNicora\Minimalism\Services\ResourceBuilder\Events;

use CarloNicora\Minimalism\Core\Events\Abstracts\AbstractErrorEvent;
use CarloNicora\Minimalism\Core\Events\Interfaces\EventInterface;
use Exception;

class ResourceBuilderErrorEvents extends AbstractErrorEvent
{
    /** @var string  */
    protected string $serviceName = 'resource-builder';

    public static function LINK_GENERATION_ERROR(Exception $e) : EventInterface
    {
        return new self(1, 500, 'Error generating self link', [], $e);
    }
}