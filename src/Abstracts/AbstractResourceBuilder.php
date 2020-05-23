<?php
namespace CarloNicora\Minimalism\Services\ResourceBuilder\Abstracts;

use CarloNicora\JsonApi\Objects\Link;
use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\Core\Services\Exceptions\ServiceNotFoundException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Interfaces\EncrypterInterface;
use CarloNicora\Minimalism\Services\ResourceBuilder\Events\ResourceBuilderErrorEvents;
use CarloNicora\Minimalism\Services\ResourceBuilder\Interfaces\ResourceBuilderInterface;
use CarloNicora\Minimalism\Services\ResourceBuilder\ResourceBuilder;
use Exception;
use RuntimeException;

abstract class AbstractResourceBuilder implements ResourceBuilderInterface {
    /** @var ServicesFactory  */
    protected ServicesFactory $services;

    /**
     * FIELD PARAMETERS AND DEFINITION
     *
     * 'key' | string | required name of the field
     *
     * 'values'
     * id | bool | if set, defines if the field is the id field for the object
     * encrypted | bool | if set, defines if the field is encrypted
     * method | string | if set, defines the method used to generate the custom field
     */

    /** @var array */
    protected array $fields = [];

    /** @var string  */
    protected string $linkBuilder='';

    /** @var ResourceObject */
    public ResourceObject $resource;

    /** @var array  */
    protected array $data;

    /** @var EncrypterInterface */
    protected ?EncrypterInterface $encrypter=null;

    /** @var ResourceBuilder */
    protected ResourceBuilder $resourceBuilder;

    /**
     * AbstractResourceBuilder constructor.
     * @param ResourceBuilder $resourceBuilder
     * @param ServicesFactory $services
     * @param EncrypterInterface|null $encrypter
     * @param array $data
     * @throws Exception
     */
    public function __construct(ResourceBuilder $resourceBuilder, ServicesFactory $services, ?EncrypterInterface $encrypter, array $data) {
        $this->resourceBuilder = $resourceBuilder;

        $this->services = $services;
        $this->encrypter = $encrypter;

        $this->data = $data;

        $this->resource = new ResourceObject($this->getType(), $this->getId());
        $this->resource->attributes->importArray($this->getAttributes());

        $this->resource->meta->importArray($this->buildMeta());
        $this->buildLinks();
    }

    /**
     * @return array
     */
    protected function buildLinks() : array {
        $response = [];

        if (!empty($this->linkBuilder)) {
            $link = $this->services->paths()->getUrl()
                . $this->linkBuilder
                . $this->getId();

            try {
                $this->resource
                    ->links
                    ->add(
                        new Link('self', $link)
                    );
            } catch (Exception $e) {
                $this->services
                    ->logger()
                    ->error()
                    ->log(
                        ResourceBuilderErrorEvents::LINK_GENERATION_ERROR($e)
                    );
            }
        }

        return $response;
    }

    /**
     * @return array
     */
    protected function buildMeta() : array {
        return [];
    }

    /**
     * @return ResourceObject
     * @throws Exception
     */
    public function buildResource(): ResourceObject {
        return $this->resource;
    }

    /**
     * @return string
     * @throws ServiceNotFoundException
     */
    protected function getId(): ?string {
        foreach ($this->fields as $fieldName => $fieldAttributes){
            if (array_key_exists('id', $fieldAttributes) && $fieldAttributes['id'] === true){
                if (array_key_exists('encrypted', $fieldAttributes) && $fieldAttributes['encrypted'] === true){
                    if ($this->encrypter !== null) {
                        return $this->encrypter->encryptId((int)$this->data[$fieldName]);
                    }

                    return (int)$this->data[$fieldName];
                }

                return $this->data[$fieldName];
            }
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getType(): string {
        return strtolower(substr(strrchr(static::class, '\\'), 1, -2));
    }

    /**
     * @return array
     * @throws serviceNotFoundException
     */
    protected function getAttributes(): ?array {
        $attributes = [];
        foreach ($this->fields as $fieldName => $fieldAttributes){
            if (false === is_array($fieldAttributes)) {
                throw new RuntimeException($fieldName . ' field of ' . static::class . ' is not configured properly', 500);
            }

            if (!array_key_exists('method', $fieldAttributes) && (false === empty($fieldAttributes['id']) || false === isset($this->data[$fieldName])  || null === $this->data[$fieldName])) {
                continue;
            }

            if (array_key_exists('encrypted', $fieldAttributes) && $fieldAttributes['encrypted'] === true){
                $attributes[$fieldName] = $this->encrypter->encryptId((int)$this->data[$fieldName]);
            } elseif (array_key_exists('method', $fieldAttributes)) {
                if (($fieldValue = $this->{$fieldAttributes['method']}($this->data)) !== null) {
                    $attributes[$fieldName] = $fieldValue;
                }
            } elseif (isset($this->data[$fieldName])) {
                $attributes[$fieldName] = $this->data[$fieldName];
            }
        }

        return $attributes ?? null;
    }
}