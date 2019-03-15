<?php

namespace Sunnysideup\SearchSimpleSmart\Abstractions;

use SilverStripe\Core\Config\Config;
use Sunnysideup\SearchSimpleSmart\Abstractions\SearchEngineSortByDescriptor;
use SilverStripe\View\ViewableData;

/***
 * This is an interface that can be added
 * to any DataObject that is
 *
 *
 */


abstract class SearchEngineSortByDescriptor
{


    // use Extensible;
    // use Injectable;
    // use Configurable;

    /**
     * this is a metasorter, allowing you to always
     * put some classes on top or at the bottom
     * e.g. always put Product Pages at the top.
     * array is like this:
     *    1 => array(MyFirstClassName, MySecondClassName)
     *    2 => array(MyOtherClassName, MyFooBarClassName)
     *
     * @var array
     */
    private static $class_groups = [];

    /**
     * set the total number of results per class_group
     * e.g.
     * 1 => 12
     * 2 => 5
     *
     * @var array
     */
    private static $class_group_limits = [];

    /**
     * returns the name - e.g. "Date", "Relevance"
     * @return String
     */
    abstract public function getShortTitle();

    /**
     * returns the description - e.g. "sort by the last Edited date"
     * @return String
     */
    abstract public function getDescription();

    /**
     * returns the sort statement that is addeded to search
     * query prior to searching the SearchEngineDataObjects
     *
     * return an array like
     *     Date => ASC
     *     Title => DESC
     * @param boolean $debug
     *
     * @return array| null
     */
    abstract public function getSqlSortArray($debug = false);

    /**
     * Do we need to do custom sorting?
     * @return boolean
     */
    public function hasCustomSort()
    {
        $array = $this->getSqlSortArray();
        if (is_array($array) && count($array) > 0) {
            return false;
        }
        return true;
    }

    /**
     * Do any custom sorting
     *
     * @param boolean $debug
     *
     * @param array $array - id => ClassName
     * @return SS_List
     */
    abstract public function doCustomSort($objects, $searchRecord, $debug = false);

    protected function makeClassGroups($array, $debug = false)
    {
        $classGroupCounts = [];
        $classGroups = Config::inst()->get(SearchEngineSortByDescriptor::class, "class_groups");
        if (is_array($classGroups) && count($classGroups)) {
            $classGroupLimits = Config::inst()->get(SearchEngineSortByDescriptor::class, "class_group_limits");
            $newArray = [];
            foreach ($classGroups as $key => $classGroupGroup) {
                if (!isset($classGroupCounts[$key])) {
                    $classGroupCounts[$key] = 0;
                }

                foreach ($array as $id => $className) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: upgrade to SS4
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                    if (in_array($className, $classGroupGroup)) {
                        if ((!isset($classGroupLimits[$key]))  || (isset($classGroupLimits[$key]) && ($classGroupCounts[$key] <= $classGroupLimits[$key]))) {
                            $classGroupCounts[$key]++;

                            /**
                              * ### @@@@ START REPLACEMENT @@@@ ###
                              * WHY: upgrade to SS4
                              * OLD: $className (case sensitive)
                              * NEW: $className (COMPLEX)
                              * EXP: Check if the class name can still be used as such
                              * ### @@@@ STOP REPLACEMENT @@@@ ###
                              */
                            $newArray[$id] = $className;
                        }
                        unset($array[$id]);
                    }
                }
            }

            /**
              * ### @@@@ START REPLACEMENT @@@@ ###
              * WHY: upgrade to SS4
              * OLD: $className (case sensitive)
              * NEW: $className (COMPLEX)
              * EXP: Check if the class name can still be used as such
              * ### @@@@ STOP REPLACEMENT @@@@ ###
              */
            foreach ($array as $id => $className) {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: upgrade to SS4
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
                $newArray[$id] = $className;
            }
            return $newArray;
        } else {
            return $array();
        }
    }

    protected function hasClassGroups()
    {
        $classGroups = Config::inst()->get(SearchEngineSortByDescriptor::class, "class_groups");

        return is_array($classGroups) && count($classGroups) ? true : false;
    }

    protected function hasNoClassGroups()
    {
        return $this->hasClassGroups() ? false : true;
    }

    /**
     * retains debug information if turned on.
     * @var array
     */
    protected $debugArray = [];

    /**
     * @return string (html)
     */
    public function getDebugArray()
    {
        return "<ul><li>".implode("</li>li><li>", $this->debugArray)."</li></ul>";
    }
}