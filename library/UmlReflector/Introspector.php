<?php
namespace UmlReflector;

class Introspector
{
    private $examinedClassNames = array();
    private $skippedNamespaces = array();

    public function addSkippedNamespace($namespace)
    {
        $this->skippedNamespaces[] = $namespace;
    }

    /**
     * @param object $rootObject
     * @return string yUML code
     */
    public function visualize($rootObject, Directives $directives)
    {
        $reflectionObject = new \ReflectionObject($rootObject);
        if (in_array($reflectionObject->getName(), $this->examinedClassNames)) {
            return;
        }
        if ($this->isInSkippedNamespace($reflectionObject->getName())) {
            return;
        }
        $this->examinedClassNames[] = $reflectionObject->getName();
        $this->classNameToDirectives($directives, $reflectionObject);
        $this->propertiesToDirectives($directives, $reflectionObject, $rootObject);
        $this->hierarchyToDirectives($directives, $reflectionObject);
    }

    private function getBasename($fullyQualifiedClassName)
    {
        $position = strrpos($fullyQualifiedClassName, '\\');
        if ($position) {
            return substr($fullyQualifiedClassName, $position + 1);
        } else {
            return $fullyQualifiedClassName;
        }
    }

    /**
     * @return bool
     */
    private function isInSkippedNamespace($className)
    {
        foreach ($this->skippedNamespaces as $namespace) {
            if (strstr($className, $namespace) == $className) {
                return true;
            }
        }
        return false;
    }

    private function classNameToDirectives(Directives $directives, \ReflectionObject $reflectionObject)
    {
        $fullyQualifiedClassName = $reflectionObject->getName();
        $baseClassName = $this->getBasename($fullyQualifiedClassName);
        $directives->addClass($baseClassName);
    }

    private function propertiesToDirectives(Directives $directives, \ReflectionObject $reflectionObject, $rootObject)
    {
        $baseClassName = $this->getBasename($reflectionObject->getName());
        foreach ($reflectionObject->getProperties() as $property) {
            $property->setAccessible(true);
            $collaborator = $property->getValue($rootObject);
            if (is_object($collaborator)) {
                $propertyClass = $this->getBasename(get_class($collaborator));
                $directives->addComposition($baseClassName, $propertyClass);
                $this->visualize($collaborator, $directives);
            } elseif ($this->getAggregatedClass($property)) {
                $aggregatedClass = $this->getAggregatedClass($property);
                $directives->addAggregation($baseClassName, $aggregatedClass);
                $this->visualize($rootObject, $directives);
            }
        }
    }

    protected function getAggregatedClass(\ReflectionProperty $property)
    {
        return $this->extractContainedClass($property->getDocComment());
    }

    protected function extractContainedClass($docComment)
    {
        $matches = array();
        $docComment = str_replace(PHP_EOL, ' ', $docComment);
        $matches = array();
        if (preg_match('#@var (.*?)\[\]#', $docComment, $matches)) {
            return $matches[1];
        }
    }


    private function hierarchyToDirectives(Directives $directives, \ReflectionObject $object)
    {
        $parentClass = $object->getParentClass();
        $currentClass = $object;
        while ($parentClass) {
            $parentClassName = $this->getBasename($parentClass->getName());
            $childClassName = $this->getBasename($currentClass->getName());
            $directives->addInheritance($parentClassName, $childClassName); 
            $currentClass = $parentClass;
            $parentClass = $parentClass->getParentClass();
        }
    }
}
