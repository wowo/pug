<?php
namespace UmlReflector;

class Directives
{
    private $classes = array();
    private $compositionsSources = array();
    private $compositionTargets = array();
    private $inheritanceParents = array();
    private $inheritanceChildren = array();
    private $aggregations = array();

    /**
     * @param string $className     Class that should be represented in the diagram
     * @return void
     */
    public function addClass($className)
    {
        $this->classes[] = $className;
    }

    /**
     * @param string $sourceClassName   class containing the field
     * @param string $targetClassName   class pointed
     * @return void
     */
    public function addComposition($sourceClassName, $targetClassName)
    {
        $this->compositionsSources[] = $sourceClassName;
        $this->compositionTargets[] = $targetClassName;
    }

    public function addAggregation($sourceClassName, $targetClassName)
    {
        $this->aggregations[] = array('source' => $sourceClassName, 'target' => $targetClassName);
    }

    public function addInheritance($parentClassName, $childClassName)
    {
        $this->inheritanceParents[] = $parentClassName;
        $this->inheritanceChildren[] = $childClassName;
    }

    public function toString()
    {
        return implode("\n", array_merge(
            $this->classesDirectives(),
            $this->compositionDirectives(),
            $this->inheritanceDirectives(),
            $this->aggergationDirectives()

        ));
    }

    private function classesDirectives()
    {
        return array_map(function($className) {
            return "[$className]";
        }, array_filter($this->classes, array($this, 'isNotAlreadyPresentInRelationships')));
    }

    public function isNotAlreadyPresentInRelationships($className) {
        $aggergationsMembers = array();
        foreach (array_values($this->aggregations) as $aggregation) {
            $aggergationsMembers[] = $aggregation['source'];
            $aggergationsMembers[] = $aggregation['target'];
        }
        return !(in_array($className, $this->compositionsSources)
              || in_array($className, $this->compositionTargets)
              || in_array($className, $this->inheritanceParents)
              || in_array($className, $this->inheritanceChildren)
              || in_array($className, array_values($aggergationsMembers)));
    }

    private function compositionDirectives()
    {
        $compositionDirectives = array();
        foreach ($this->compositionsSources as $i => $sourceClassName) {
            $targetClassName = $this->compositionTargets[$i];
            $compositionDirectives[] = "[$sourceClassName]->[$targetClassName]";
        }
        return $compositionDirectives;
    }

    private function aggergationDirectives()
    {
        $drawableResults = array();
        foreach ($this->aggregations as $aggregation) {
            $drawableResults[] = sprintf('[%s]+->[%s]', $aggregation['source'], $aggregation['target']);
        }

        return $drawableResults;
    }

    private function inheritanceDirectives()
    {
        $inheritanceDirectives = array();
        foreach ($this->inheritanceParents as $i => $parentClassName) {
            $childClassName = $this->inheritanceChildren[$i];
            $inheritanceDirectives[] = "[$parentClassName]^-[$childClassName]";
        }
        return $inheritanceDirectives;
    }
}
