<?php
namespace CarloNicora\Minimalism\Services\ResourceBuilder\Abstracts;

use CarloNicora\JsonApi\Objects\Relationship;
use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\Core\Services\Exceptions\ServiceNotFoundException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\Encrypter\Encrypter;
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

    /** @var array */
    protected array $oneToOneRelationFields = [];
    /** @var array */
    protected array $toManyRelationFields = [];

    /** @var ResourceObject */
    public ResourceObject $resource;

    /** @var array  */
    protected array $data;

    /** @var Encrypter */
    protected Encrypter $encrypter;

    /** @var ResourceBuilder */
    protected ResourceBuilder $resourceBuilder;

    /**
     * AbstractResourceBuilder constructor.
     * @param ResourceBuilder $resourceBuilder
     * @param Encrypter $encrypter
     * @param array $data
     * @throws Exception
     */
    public function __construct(ResourceBuilder $resourceBuilder, Encrypter $encrypter, array $data) {
        $this->resourceBuilder = $resourceBuilder;
        $this->encrypter = $encrypter;

        $this->data = $data;

        $this->resource = new ResourceObject($this->getType(), $this->getId());
        $this->resource->attributes->importArray($this->getAttributes());

        $this->resource->meta->importArray($this->buildMeta());
        $this->resource->links->importArray($this->buildLinks());

        foreach ($this->oneToOneRelationFields as $toOneResourceBuilderKey => $toOneResourceBuilderClass) {
            if (false === is_array($toOneResourceBuilderClass)) {
                $this->oneToOneRelationFields[$toOneResourceBuilderKey] = ['id' => $toOneResourceBuilderClass . 'Id', 'class' => $toOneResourceBuilderClass];
            }
        }

        foreach ($this->toManyRelationFields as $toManyResourceBuilderKey => $toManyResourceBuilderClass) {
            if (false === is_array($toManyResourceBuilderClass)) {
                $this->toManyRelationFields[$toManyResourceBuilderKey] = ['id' => $toManyResourceBuilderClass . 'Id', 'class' => $toManyResourceBuilderClass];
            }
        }
    }

    /**
     * @return array
     */
    protected function buildLinks() : array {
        return [];
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
        $meta = $this->getMeta();
        if (false === empty($meta)) {
            $this->resource->meta->importArray($meta);
        }

        $relationships = $this->getRelationships();
        if (false === empty($relationships)) {
            $this->resource->relationships = $relationships;
        }

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
                    return $this->encrypter->encryptId((int)$this->data[$fieldName]);
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
        return substr(strrchr(static::class, '\\'), 1, -2);
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

    /**
     * @return array|null
     * @throws Exception
     */
    protected function getRelationships(): ?array {
        $relationships = [];
        foreach ($this->oneToOneRelationFields as $relationFieldName => $config) {
            if (false === empty($this->data[$relationFieldName])) {
                /** @var AbstractResourceBuilder $relatedResourceBuilder */
                $relatedResourceBuilder = $this->resourceBuilder->create($config['class'], $this->data[$relationFieldName]);
                $relationships[$relationFieldName] []= $this->buildRelationship($relatedResourceBuilder, $relationFieldName);
            }
        }

        foreach ($this->toManyRelationFields as $relationFieldName => $config) {
            if (false === empty($this->data[$relationFieldName])) {
                /** @var AbstractResourceBuilder $relatedResourceBuilder */
                foreach ($this->data[$relationFieldName] as $relatedData) {
                    $relatedResourceBuilder = $this->resourceBuilder->create($config['class'], $relatedData);
                    $relationships[$relationFieldName] []= $this->buildRelationship($relatedResourceBuilder, $relationFieldName);
                }
            }
        }

        return $relationships ?? null;
    }

    /**
     * @param AbstractResourceBuilder $relatedResourceBuilder
     * @param string $relationFieldName
     * @return Relationship
     * @throws Exception
     */
    private function buildRelationship(AbstractResourceBuilder $relatedResourceBuilder, string $relationFieldName): Relationship {
        $relationship = new Relationship();
        $relationship->resourceLinkage->add($relatedResourceBuilder->resource);

        $relationshipMeta = $this->getRelationshipMeta($relationFieldName);
        if (false === empty($relationshipMeta)) {
            $relationship->meta->importArray($relationshipMeta);
        }

        return $relationship;
    }

    /**
     * @return array|null
     */
    protected function getMeta(): ?array {
        return null;
    }

    /**
     * @param string $relationFieldName
     * @return array|null
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getRelationshipMeta(string $relationFieldName): ?array {
        return null;
    }
}