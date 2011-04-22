<?php
namespace UmlReflector;

class Introspector
{
    /**
     * @param object $rootObject
     * @return string yUML code
     */
    public function visualize($rootObject)
    {
        $fullyQualifiedClassName = get_class($rootObject);
        $reflectionObject = new \ReflectionObject($rootObject);
        $properties = $reflectionObject->getProperties();
        $directives = new Directives();
        $this->propertiesToDirectives($directives, $rootObject, $properties);
        $this->hierarchyToDirectives($directives, $reflectionObject);
        $baseClassName = $this->getBasename($fullyQualifiedClassName);
        $directives->addClass($baseClassName);
        return $directives->toString();
    }

    private function getBasename($fullyQualifiedClassName)
    {
        $position = strrpos($fullyQualifiedClassName, '\\');
        return substr($fullyQualifiedClassName, $position + 1);
    }

    private function propertiesToDirectives(Directives $directives, $object, $properties)
    {
        $baseClassName = $this->getBasename(get_class($object));
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyValue = $property->getValue($object);
            $propertyClass = $this->getBasename(get_class($propertyValue));
            $directives->addComposition($baseClassName, $propertyClass);
        }
    }

    private function hierarchyToDirectives(Directives $directives, \ReflectionObject $object)
    {
        $parentClass = $object->getParentClass();
        $currentClass = $object;
        while ($parentClass) {
            $classes = array($parentClass, $currentClass);
            $parentClassName = $this->getBasename($classes[0]->getName());
            $childClassName = $this->getBasename($classes[1]->getName());
            $directives->addInheritance($parentClassName, $childClassName); 
            $currentClass = $parentClass;
            $parentClass = $parentClass->getParentClass();
        }
    }
}
