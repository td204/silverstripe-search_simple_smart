<?php

namespace Sunnysideup\SearchSimpleSmart\Filters;

use SilverStripe\ORM\SS_List;

class SearchEngineFilterForDataList extends SearchEngineFilterForClassNameAndIDs
{
    /**
     * @return string
     */
    public function getShortTitle()
    {
        return _t('SearchEngineFilterForDataList.TITLE', 'Custom Selection');
    }

    /**
     * list of
     * e.g.
     *    LARGE => Large Pages
     *    SMALL => Small Pages
     *    RED => Red Pages.
     *
     * @return array
     */
    public function getFilterList()
    {
        return ['CUSTOM' => 'Custom selection of items'];
    }

    /**
     * returns the filter statement that is addeded to search
     * query prior to searching the SearchEngineDataObjects.
     *
     * return an array like
     *     "ClassName" => array("A", "B", "C"),
     *     "LastEdited:GreaterThan" => "10-10-2001"
     *
     * @param null|array|SS_List $filterArray
     */
    public function getSqlFilterArray($filterArray): ?array
    {
        if ($filterArray instanceof SS_List) {
            if (0 === $filterArray->count()) {
                return ['ID' => -1];
            }

            $ids = $filterArray->column('ID');
            $classNames = $filterArray->column('ClassName');
            $preFilter = [];
            foreach ($ids as $position => $id) {
                $className = $classNames[$position];
                if (! isset($preFilter[$className])) {
                    $preFilter[$className] = [];
                }

                $preFilter[$className][$id] = $id;
            }

            return parent::getSqlFilterArray($preFilter);
        }

        return null;
    }

    /**
     * do we need to do custom filtering
     * the filter array are the items selected by the
     * user, based on the filter options listed above.
     *
     * @see: getFilterList
     *
     * @param null|array|SS_List $filterArray
     */
    public function hasCustomFilter($filterArray): bool
    {
        return false;
    }
}
