<?php

namespace Sunnysideup\SearchSimpleSmart\Sorters;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\SS_List;
use Sunnysideup\SearchSimpleSmart\Abstractions\SearchEngineSortByDescriptor;
use Sunnysideup\SearchSimpleSmart\Api\FasterIDLists;
use Sunnysideup\SearchSimpleSmart\Model\SearchEngineDataObject;
use Sunnysideup\SearchSimpleSmart\Model\SearchEngineSearchRecord;

/**
 * default sort option.
 */
class SearchEngineSortByRelevance extends SearchEngineSortByDescriptor
{
    /**
     * @return string
     */
    public function getShortTitle()
    {
        return _t('SearchEngineSortByRelevance.TITLE', 'Relevance');
    }

    /**
     * returns the description - e.g. "sort by the last Edited date".
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getShortTitle();
    }

    /**
     * returns the sort statement that is addeded to search
     * query prior to searching the SearchEngineDataObjects.
     *
     * return an array like
     *     Date => ASC
     *     Title => DESC
     *
     * @param mixed $sortProviderValues
     *
     * @return array
     */
    public function getSqlSortArray($sortProviderValues = null)
    {
        return [];
    }

    /**
     * @param null|mixed $sortProviderValues
     *
     * @return bool
     */
    public function hasCustomSort($sortProviderValues = null)
    {
        return true;
    }

    /**
     * Do any custom sorting.
     *
     * @param SS_List|DataList         $objects
     * @param SearchEngineSearchRecord $searchRecord
     *
     * @return SS_List|DataList
     */
    public function doCustomSort($objects, $searchRecord)
    {
        if ($objects->count() < 2) {
            //do nothing
        } else {
            $array = [0 => -1];
            $fromSQL = '
                FROM "SearchEngineFullContent"
                    INNER JOIN "SearchEngineDataObject"
                        ON "SearchEngineDataObject"."ID" = "SearchEngineFullContent"."SearchEngineDataObjectID"
            ';
            $sortSQL = '
                ORDER BY
                    "Level",
                    RELEVANCE DESC
            ';

            // look for complete phrase if there is more than one word.
            // exact full match of search phrase becomes relevance, level 1 first
            // and further upfront in text increases relevance.
            $phrase = (string) Convert::raw2sql($searchRecord->FinalPhrase);
            if (strpos(trim($phrase), ' ')) {
                $sql = '
                    SELECT
                        "SearchEngineDataObject"."ID" AS MyID,
                        (9999999 - LOCATE(\'' . $phrase . '\',"Content")) AS RELEVANCE
                    ' . $fromSQL . '
                    WHERE
                        "Content" LIKE \'%' . $phrase . '%\'
                        AND "SearchEngineDataObjectID" IN (' . $searchRecord->ListOfIDsCUSTOM . ')
                    HAVING
                        RELEVANCE > 0
                    ' . $sortSQL . '
                ;';
                $rows = DB::query($sql);
                foreach ($rows as $row) {
                    $id = $row['MyID'];
                    if (! isset($array[$id])) {
                        $array[$id] = $row['RELEVANCE'];
                    }
                }
            }

            // for the ones not found yet, we do a Mysql "Match" query with higher relevance first.
            $sql = '
                SELECT
                    "SearchEngineDataObject"."ID" AS MyID,
                    MATCH ("Content") AGAINST (\'' . $searchRecord->FinalPhrase . '\') AS RELEVANCE
                ' . $fromSQL . '
                WHERE
                    "SearchEngineDataObjectID" IN (' . $searchRecord->ListOfIDsCUSTOM . ')
                    AND "SearchEngineDataObjectID" NOT IN (' . implode(',', array_keys($array)) . ')
                HAVING
                    RELEVANCE > 0
                ' . $sortSQL . '
                ;';
            $rows = DB::query($sql);
            foreach ($rows as $row) {
                $id = $row['MyID'];
                if (! isset($array[$id])) {
                    $array[$id] = $row['RELEVANCE'];
                }
            }

            $ids = array_keys($array);

            //retrieve objects
            $objects = Injector::inst()->create(
                FasterIDLists::class,
                SearchEngineDataObject::class,
                $objects->columnUnique()
            )->filteredDatalist();
            $objects = $objects->sort('FIELD("ID", ' . implode(',', $ids) . ')');

            //group results!
            $objects = $this->makeClassGroups($objects);
        }

        return $objects;
    }


                // $objects = SearchEngineDataObject::get()
                //     ->filter(['ID' => $ids])
                //     ->sort('FIELD("ID", ' . implode(',', $ids) . ')');
                //
                // TO TEST!
                // $sql = '
                //     SELECT
                //         "SearchEngineDataObject"."ID" AS MyID,
                //         MATCH ("Content") AGAINST (\'' . $searchRecord->FinalPhrase . '\'  WITH QUERY EXPANSION) AS RELEVANCE
                //     ' . $fromSQL . '
                //     WHERE
                //         "SearchEngineDataObjectID" IN (' . $searchRecord->ListOfIDsCUSTOM . ')
                //         AND "SearchEngineDataObjectID" NOT IN (' . implode(',', array_keys($array)) . ')
                //     HAVING
                //         RELEVANCE > 0
                //     ' . $sortSQL . '
                //     ;';
                // $rows = DB::query($sql);
                // foreach ($rows as $row) {
                //     $id = $row['MyID'];
                //     if (! isset($array[$id])) {
                //         $array[$id] = $row['RELEVANCE'];
                //     }
                // }
}
