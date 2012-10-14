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
                $aggregatedReflectionClass = $this->getAggregatedClass($property);
                $directives->addAggregation($baseClassName, $this->getBasename($aggregatedReflectionClass->getName()));
                $this->visualize($rootObject, $directives);
                $this->subclassesToDirectives($directives, $aggregatedReflectionClass);
            }
        }
    }

    protected function getAggregatedClass(\ReflectionProperty $property)
    {
        return new \ReflectionClass($this->removeTrailingBackslash($this->extractContainedClass($property->getDocComment())));
    }

    protected function removeTrailingBackslash($string)
    {
        return preg_replace('/^\\\/', '', $string);
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

    /**
     * Warning, it assumes that derrived classes are located in same directory and subdirectories (PSR-0). It doesn't look for subclasses outside itself dir
     * 
     * @param Directives $directives 
     * @param \ReflectionClass $rootObject 
     * @access protected
     * @return void
     */
    protected function subclassesToDirectives(Directives $directives, \ReflectionClass $rootObject)
    {
        $this->includeRecursivelyPHPFiles(dirname($rootObject->getFileName()));
        foreach (get_declared_classes() as $className) {
            if (is_subclass_of($className, $rootObject->getName())) {
                $directives->addInheritance($this->getBasename($rootObject->getName()), $this->getBasename($className));
            }
        }
    }

    /**
     * @todo, use Finder component here
     */
    protected function includeRecursivelyPHPFiles($startingDir)
    {
        $path[] = $startingDir . '/*';

        while(count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_dir($item)) {
                    $path[] = $item . '/*';
                } elseif (is_file($item)) {
                    include_once($item);
                }
            }
        }
    }

    private function hierarchyToDirectives(Directives $directives, \ReflectionClass $object)
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
