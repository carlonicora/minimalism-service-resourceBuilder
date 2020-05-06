<?php
namespace carlonicora\minimalism\services\resourceBuilder\abstracts;

use carlonicora\minimalism\core\services\exceptions\serviceNotFoundException;
use carlonicora\minimalism\core\services\factories\servicesFactory;
use carlonicora\minimalism\services\jsonapi\resources\resourceObject;
use carlonicora\minimalism\services\jsonapi\resources\resourceRelationship;
use carlonicora\minimalism\services\encrypter\encrypter;
use carlonicora\minimalism\services\resourceBuilder\interfaces\resourceBuilderInterface;
use carlonicora\minimalism\services\resourceBuilder\resourceBuilder;
use RuntimeException;

abstract class abstractResourceBuilder implements resourceBuilderInterface {
    /** @var servicesFactory  */
    protected servicesFactory $services;

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

    /** @var resourceObject */
    public resourceObject $resource;

    /** @var array  */
    protected array $data;

    /**
     * abstractBusinessObject constructor.
     * @param servicesFactory $services
     * @param array $data
     * @throws serviceNotFoundException
     */
    public function __construct(servicesFactory $services, array $data) {
        $this->services = $services;

        $this->data = $data;

        $resourceArray = [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'attributes' => $this->getAttributes(),
        ];
        $this->resource = new resourceObject($resourceArray);

        $this->resource->addMetas($this->buildMeta());
        $this->resource->addLinks($this->buildLinks());

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
     * @return resourceObject
     * @throws serviceNotFoundException
     */
    public function buildResource(): resourceObject {
        $meta = $this->getMeta();
        if (false === empty($meta)) {
            $this->resource->addMetas($meta);
        }

        $relationships = $this->getRelationships();
        if (false === empty($relationships)) {
            $this->resource->addRelationshipList($relationships);
        }

        return $this->resource;
    }

    /**
     * @return string
     * @throws serviceNotFoundException
     */
    protected function getId(): ?string {
        foreach ($this->fields as $fieldName => $fieldAttributes){
            if (array_key_exists('id', $fieldAttributes) && $fieldAttributes['id'] === true){
                if (array_key_exists('encrypted', $fieldAttributes) && $fieldAttributes['encrypted'] === true){
                    /** @var encrypter $encrypter */
                    $encrypter = $this->services->service(encrypter::class);
                    return $encrypter->encryptId((int)$this->data[$fieldName]);
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
        /** @var encrypter $encrypter */
        $encrypter = $this->services->service(encrypter::class);

        $attributes = [];
        foreach ($this->fields as $fieldName => $fieldAttributes){
            if (false === is_array($fieldAttributes)) {
                throw new RuntimeException($fieldName . ' field of ' . static::class . ' is not configured properly', 500);
            }

            if (!array_key_exists('method', $fieldAttributes) && (false === empty($fieldAttributes['id']) || false === isset($this->data[$fieldName])  || null === $this->data[$fieldName])) {
                continue;
            }

            if (array_key_exists('encrypted', $fieldAttributes) && $fieldAttributes['encrypted'] === true){
                $attributes[$fieldName] = $encrypter->encryptId((int)$this->data[$fieldName]);
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
     * @return array
     * @throws serviceNotFoundException
     */
    protected function getRelationships(): ?array {
        /** @var resourceBuilder $resourceBuilder */
        $resourceBuilder = $this->services->service(resourceBuilder::class);

        $relationships = [];
        foreach ($this->oneToOneRelationFields as $relationFieldName => $config) {
            if (false === empty($this->data[$relationFieldName])) {
                /** @var abstractResourceBuilder $relatedResourceBuilder */
                $relatedResourceBuilder = $resourceBuilder->create($config['class'], $this->data[$relationFieldName]);

                $relationship = new resourceRelationship($relatedResourceBuilder->resource);

                $relationshipMeta = $this->getRelationshipMeta($relationFieldName);
                if (false === empty($relationshipMeta)) {
                    $relationship->addMetas($relationshipMeta);
                }

                $relationships[$relationFieldName] []= $relationship;
            }
        }

        foreach ($this->toManyRelationFields as $relationFieldName => $config) {
            if (false === empty($this->data[$relationFieldName])) {
                /** @var abstractResourceBuilder $relatedResourceBuilder */
                foreach ($this->data[$relationFieldName] as $relatedData) {
                    $relatedResourceBuilder = $resourceBuilder->create($config['class'], $relatedData);

                    $relationship = new resourceRelationship($relatedResourceBuilder->resource);

                    $relationshipMeta = $this->getRelationshipMeta($relationFieldName);
                    if (false === empty($relationshipMeta)) {
                        $relationship->addMetas($relationshipMeta);
                    }

                    $relationships[$relationFieldName] []= $relationship;
                }
            }
        }

        return $relationships ?? null;
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