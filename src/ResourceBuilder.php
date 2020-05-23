<?php
namespace CarloNicora\Minimalism\Services\ResourceBuilder;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractService;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Core\Services\Interfaces\ServiceConfigurationsInterface;
use CarloNicora\Minimalism\Interfaces\EncrypterInterface;
use CarloNicora\Minimalism\Services\ResourceBuilder\Configurations\resourceBuilderConfigurations;
use CarloNicora\Minimalism\Services\ResourceBuilder\Interfaces\ResourceBuilderInterface;

class ResourceBuilder extends AbstractService {
    /** @var resourceBuilderConfigurations  */
    private resourceBuilderConfigurations $configData;

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
     * @param EncrypterInterface|null $encrypter
     * @return ResourceBuilderInterface
     */
    public function create(string $objectName, array $data, ?EncrypterInterface $encrypter=null) : ResourceBuilderInterface {
        return new $objectName($this, $this->services, $encrypter, $data);
    }
}