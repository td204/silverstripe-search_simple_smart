<?php

namespace Sunnysideup\SearchSimpleSmart\Filters;

use Sunnysideup\SearchSimpleSmart\Model\SearchEngineDataObject;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\SS_List;
use Sunnysideup\SearchSimpleSmart\Abstractions\SearchEngineFilterForDescriptor;

class SearchEngineFilterForClassNameAndIDs extends SearchEngineFilterForDescriptor
{

    /**
     * @return String
     */
    public function getShortTitle()
    {
        return _t("SearchEngineFilterForClassNameAndIDs.TITLE", "Seleted Items from Type");
    }

    /**
     * list of
     * e.g.
     *    LARGE => Large Pages
     *    SMALL => Small Pages
     *    RED => Red Pages
     * @return Array
     */
    public function getFilterList()
    {
        return array("TYPE" => "Selection of items");
    }

    /**
     * returns the filter statement that is addeded to search
     * query prior to searching the SearchEngineDataObjects
     *
     * return an array like
     *     "ClassName" => array("A", "B", "C"),
     *     "LastEdited:GreaterThan" => "10-10-2001"
     *
     * @param array|SS_List $list
     *
     * @return array| null
     */
    public function getSqlFilterArray($list)
    {
        if(! $filterArray) {
            return null;
        }
        elseif(is_array($filterArray) && count($filterArray) === 0) {
            return null;
        }
        elseif($filterArray instanceof SS_List) {
            $ids = $list->column('ID');
            $classNames = $list->column('ClassName');
            $preFilter = [];
            foreach($ids as $position => $id) {
                $className = $classNames[$position];
                if(! isset($preFilter[$className])) {
                    $preFilter[$className] = [];
                }
                $preFilter[$className][$id] = $id;
            }
            return $this->getSqlFilterArray($preFilter);
        }
        $array = [];

        foreach ($filterArray as $className => $ids) {
            $classNames = ClassInfo::subclassesFor($className);
            $data = SearchEngineDataObject::get()
                ->filter(['DataObjectClassName' => $classNames, 'DataObjectID' => $ids]);
            $array = array_merge($array, $data->column('ID'));
        }
        $array = array_unique($array);

        return ['ID' => $array];
    }

    /**
     * do we need to do custom filtering
     * the filter array are the items selected by the
     * user, based on the filter options listed above
     * @see: getFilterList
     * @param array $filterArray
     * @return boolean
     */
    public function hasCustomFilter($filterArray)
    {
        return false;
    }

}
