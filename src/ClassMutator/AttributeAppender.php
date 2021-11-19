<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\SchemaGenerator\ClassMutator;

use ApiPlatform\SchemaGenerator\AttributeGenerator\AttributeGeneratorInterface;
use ApiPlatform\SchemaGenerator\Model\Class_;

final class AttributeAppender implements ClassMutatorInterface
{
    /** @var AttributeGeneratorInterface[] */
    private array $attributeGenerators;
    /** @var Class_[] */
    private array $classes;

    /**
     * @param AttributeGeneratorInterface[] $attributeGenerators
     */
    public function __construct(array $classes, array $attributeGenerators)
    {
        $this->attributeGenerators = $attributeGenerators;
        $this->classes = $classes;
    }

    public function __invoke(Class_ $class): Class_
    {
        $class = $this->generateClassUses($class);
        $class = $this->generateClassAttributes($class);
        $class = $this->generatePropertiesAttributes($class);

        return $class;
    }

    private function generateClassUses(Class_ $class): Class_
    {
        $interfaceNamespace = isset($this->classes[$class->name()]) ? $this->classes[$class->name()]->interfaceNamespace() : null;
        if ($interfaceNamespace && $class->interfaceNamespace() !== $class->namespace()) {
            $class->addUse(sprintf('%s\\%s', $class->interfaceNamespace(), $class->interfaceName()));
        }

        foreach ($class->properties() as $property) {
            if (isset($this->classes[$property->rangeName]) && $this->classes[$property->rangeName]->interfaceName()) {
                $class->addUse(sprintf(
                    '%s\\%s',
                    $this->classes[$property->rangeName]->interfaceNamespace(),
                    $this->classes[$property->rangeName]->interfaceName()
                ));
            }
        }

        foreach ($this->attributeGenerators as $generator) {
            foreach ($generator->generateUses($class) as $use) {
                $class->addUse($use);
            }
        }

        return $class;
    }

    private function generateClassAttributes(Class_ $class): Class_
    {
        foreach ($this->attributeGenerators as $generator) {
            foreach ($generator->generateClassAttributes($class) as $attribute) {
                $class->addAttribute($attribute);
            }
        }

        return $class;
    }

    private function generatePropertiesAttributes(Class_ $class): Class_
    {
        foreach ($class->properties() as $name => &$property) {
            foreach ($this->attributeGenerators as $attributeGenerator) {
                foreach ($attributeGenerator->generatePropertyAttributes($property, $class->name()) as $propertyAttribute) {
                    $property->addAttribute($propertyAttribute);
                }
            }
        }

        return $class;
    }
}
