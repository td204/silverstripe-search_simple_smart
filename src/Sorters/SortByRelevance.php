<?php

namespace Sunnysideup\SearchSimpleSmart\Sorters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DB;
use Sunnysideup\SearchSimpleSmart\Model\SearchEngineDataObject;
use Sunnysideup\SearchSimpleSmart\Abstractions\SearchEngineSortByDescriptor;

/**
 * default sort option
 *
 *
 */

class SearchEngineSortByRelevance extends SearchEngineSortByDescriptor {


	/**
	 * @return string
	 */
	function getShortTitle(){
		return _t("SearchEngineSortByRelevance.TITLE", "Relevance");
	}

	/**
	 * returns the description - e.g. "sort by the last Edited date"
	 * @return String
	 */
	public function getDescription() {
		return $this->getShortTitle();
	}

	/**
	 * returns the sort statement that is addeded to search
	 * query prior to searching the SearchEngineDataObjects
	 *
	 * return an array like
	 *     Date => ASC
	 *     Title => DESC
	 *
	 * @param boolean $debug
	 *
	 * @return array
	 */
	public function getSqlSortArray($debug = false) {
		return array();
	}

	/**
	 *
	 * @return boolean
	 */
	public function hasCustomSort(){
		return true;
	}

	/**
	 * Do any custom sorting
	 *
	 * @param SS_List $objects
	 * @param SearchEngineSearchRecord $searchRecord
	 * @param boolean $debug
	 *
	 * @return SS_List
	 */
	public function doCustomSort($objects, $searchRecord, $debug = false){
		if($objects->count() < 2) {
			//do nothing
		}
		else {
			$array = array(0 => 0);

			//look for complete phrase if there is more than one word.
			//exact full match of search phrase using relevance, level 1 first
			//and further upfront in text as second sort by.
            $parts = explode(" ",$searchRecord->Phrase);
			if(count($parts) > 1) {
				$sql = '
					SELECT
						"SearchEngineDataObjectID" AS ItemID,
						"DataObjectClassName" AS ItemClassName,
						LOCATE(\''.Convert::raw2sql($searchRecord->Phrase).'\',"Content") AS FIRSTPOSITION
					FROM "SearchEngineFullContent"
						INNER JOIN "SearchEngineDataObject"
							ON "SearchEngineDataObject"."ID" = "SearchEngineFullContent"."SearchEngineDataObjectID"
					WHERE
						"Content" LIKE \'%'.Convert::raw2sql($searchRecord->Phrase).'%\'
						AND "SearchEngineDataObjectID" IN ('.$searchRecord->ListOfIDsCUSTOM.')
					ORDER BY
						"Level" ASC,
						FIRSTPOSITION ASC;';
				$rows = DB::query($sql);
				foreach($rows as $row) {
					if(!isset($array[$row["ItemID"]])) {
						$array[$row["ItemID"]] = $row["ItemClassName"];
					}
				}
			}
			//fulltext using relevance, level 1 first.
			$sql = '
				SELECT "SearchEngineDataObjectID" AS ItemID,"DataObjectClassName" AS ItemClassName, MATCH ("Content") AGAINST (\''.$searchRecord->FinalPhrase.'\') AS RELEVANCE
				FROM "SearchEngineFullContent"
						INNER JOIN "SearchEngineDataObject"
							ON "SearchEngineDataObject"."ID" = "SearchEngineFullContent"."SearchEngineDataObjectID"
				WHERE "SearchEngineDataObjectID" IN ('.$searchRecord->ListOfIDsCUSTOM.')
					AND "SearchEngineDataObjectID" NOT IN ('.implode(",", array_keys($array)).')
				ORDER BY "Level", RELEVANCE DESC';
			$rows = DB::query($sql);
			foreach($rows as $row) {
				if(!isset($array[$row["ItemID"]])) {
					$array[$row["ItemID"]] = $row["ItemClassName"];
				}
			}
			$finalArray = $this->makeClassGroups(
				$array,
				$debug
			);
			//retrieve objects
			$objects = SearchEngineDataObject::get()
				->filter(array("ID" => array_keys($finalArray)))
				->sort("FIELD(\"ID\", ".implode(",",array_keys($finalArray)).")");
		}
		return $objects;
	}
}
