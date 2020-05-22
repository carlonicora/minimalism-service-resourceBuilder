<?php
namespace CarloNicora\Minimalism\Services\ResourceBuilder\Factories;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractServiceFactory;
use CarloNicora\Minimalism\Core\Services\Exceptions\ConfigurationException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\ResourceBuilder\Configurations\ResourceBuilderConfigurations;
use CarloNicora\Minimalism\Services\ResourceBuilder\ResourceBuilder;

class ServiceFactory extends AbstractServiceFactory {
    /**
     * serviceFactory constructor.
     * @param ServicesFactory $services
     * @throws ConfigurationException
     */
    public function __construct(ServicesFactory $services) {
        $this->configData = new ResourceBuilderConfigurations();

        parent::__construct($services);
    }

    /**
     * @param ServicesFactory $services
     * @return ResourceBuilder
     */
    public function create(ServicesFactory $services) : ResourceBuilder {
        return new ResourceBuilder($this->configData, $services);
    }
}