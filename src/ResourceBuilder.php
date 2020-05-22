<?php
namespace CarloNicora\Minimalism\Services\ResourceBuilder;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractService;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Core\Services\Interfaces\ServiceConfigurationsInterface;
use CarloNicora\Minimalism\Services\Encrypter\Encrypter;
use CarloNicora\Minimalism\Services\ResourceBuilder\Configurations\ResourceBuilderConfigurations;
use CarloNicora\Minimalism\Services\ResourceBuilder\Interfaces\ResourceBuilderInterface;

class ResourceBuilder extends AbstractService {
    /** @var ResourceBuilderConfigurations  */
    private ResourceBuilderConfigurations $configData;

    /**
     * abstractApiCaller constructor.
     * @param ServiceConfigurationsInterface $configData
     * @param ServicesFactory $services
     */
    public function __construct(ServiceConfigurationsInterface $configData, ServicesFactory $services) {
        parent::__construct($configData, $services);

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        /** @noinspection UnusedConstructorDependenciesInspection */
        $this->configData = $configData;
    }

    /**
     * @param string $objectName
     * @param array $data
     * @return ResourceBuilderInterface
     */
    public function create(string $objectName, array $data) : ResourceBuilderInterface {
        $encrypter = $this->services->service(Encrypter::class);
        return new $objectName($this, $encrypter, $data);
    }
}