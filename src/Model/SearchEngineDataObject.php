<?php

namespace Sunnysideup\SearchSimpleSmart\Model;

use Psr\SimpleCache\CacheInterface;

use SilverStripe\Assets\Folder;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use Sunnysideup\SearchSimpleSmart\Abstractions\SearchEngineSortByDescriptor;
use Sunnysideup\SearchSimpleSmart\Api\SearchEngineDataObjectApi;
use Sunnysideup\SearchSimpleSmart\Api\SearchEngineSourceObjectApi;
use Sunnysideup\SearchSimpleSmart\Api\SearchEngineMakeSearchableApi;

/**
 * List of dataobjects that are indexed.
 */

class SearchEngineDataObject extends DataObject
{
    #############################################
    # CONFIG
    #############################################

    /**
     * List of Fields that are level one (most important)
     * e.g. Title, Name, etc...
     * @var array
     */
    private static $search_engine_default_level_one_fields = [];

    /**
     * List of fields that should not be included by default
     * @var array
     */
    private static $search_engine_default_excluded_db_fields = [
        'ReportClass',
        'CanViewType',
        'ExtraMeta',
        'CanEditType',
        'Password',
    ];

    /**
     * Order of fields that can be used to establish a SORT date for the
     * source object.
     * @var array
     */
    private static $search_engine_date_fields_for_sorting = [
        'PublishDate',
        'Created',
        'LastEdited',
    ];

    /**
     * @var array
     */
    private static $classes_to_include = [];

    /**
     * @var array
     */
    private static $classes_to_exclude = [
        ErrorPage::class,
        VirtualPage::class,
        RedirectorPage::class,
        Folder::class,
    ];

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'SearchEngineDataObject';

    /**
     * @var string
     */
    private static $singular_name = 'Searchable Item';

    /**
     * @var string
     */
    private static $plural_name = 'Searchable Items';

    /**
     * @var array
     */
    private static $db = [
        'DataObjectClassName' => 'Varchar(150)',
        'DataObjectID' => 'Int',
        'Recalculate' => 'Boolean',
        'DataObjectDate' => 'Datetime',
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'SearchEngineDataObjectToBeIndexed' => SearchEngineDataObjectToBeIndexed::class,
        'SearchEngineFullContents' => SearchEngineFullContent::class,
    ];

    //should work but does not
    //private static $belongs_many_many = array(
    //	'SearchEngineKeywords_Level1' => 'SearchEngineKeyword.SearchEngineDataObjects_Level1',
    //	'SearchEngineKeywords_Level2' => 'SearchEngineKeyword.SearchEngineDataObjects_Level2',
    //	'SearchEngineKeywords_Level3' => 'SearchEngineKeyword.SearchEngineDataObjects_Level3'
    //);

    /**
     * @var array
     */
    private static $belongs_many_many = [
        'SearchEngineKeywords_Level1' => SearchEngineKeyword::class,
    ];

    /**
     * @var array
     */
    private static $many_many = [
        'SearchEngineKeywords_Level2' => SearchEngineKeyword::class,
    ];

    /**
     * @var array
     */
    private static $many_many_extraFields = [
        'SearchEngineKeywords_Level2' => ['Count' => 'Int'],
        //'SearchEngineDataObjects_Level3' => array('Count' => 'Int')
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'DataObjectClassName' => true,
        'DataObjectID' => true,
        'DataObjectDate' => true,
    ];

    /**
     * @var array
     */
    private static $default_sort = [
        'DataObjectClassName' => 'ASC',
        'DataObjectID' => 'ASC',
    ];

    /**
     * @var array
     */
    private static $required_fields = [
        'DataObjectClassName' => true,
        'DataObjectID' => true,
    ];

    /**
     * @var array
     */
    private static $casting = [
        'Title' => 'Varchar',
        'HTMLOutput' => 'HTMLText',
        'HTMLOutputMoreDetails' => 'HTMLText',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'LastEdited.Nice' => 'Last Updated',
        'DataObjectDate.Nice' => 'Sort Date',
        'SearchEngineDataObjectToBeIndexed.Count' => 'Indexed Times',
        'SearchEngineKeywords_Level1.Count' => 'Level1 Keywords',
        'SearchEngineKeywords_Level2.Count' => 'Level2 Keywords',
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'DataObjectClassName' => 'PartialMatchFilter',
    ];

    /**
     * @var array
     */
    private static $field_labels = [
        'DataObjectClassName' => 'Object',
        'DataObjectID' => 'ID',
        'DataObjectDate' => 'Sort Date',
        'SearchEngineDataObjectToBeIndexed' => 'Listed for indexing',
    ];

    private $recalculateCount = 0;


    /**
     * used for caching...
     * @var array
     */
    private static $_object_class_name = [];

    private static $_source_objects = [];

    private static $_source_objects_exists = [];

    private static $_special_sort_group = [];

    #####################
    # do stuff
    #####################

    private $timeMeasure = [];

    #############################################
    # CRUD
    #############################################

    /**
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return parent::canEdit($member) && Permission::check('SEARCH_ENGINE_ADMIN');
    }

    /**
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return parent::canDelete($member) && Permission::check('SEARCH_ENGINE_ADMIN');
    }

    /**
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canView($member = null)
    {
        return parent::canView() && Permission::check('SEARCH_ENGINE_ADMIN');
    }

    /**
     * make sure all the references are deleted as well
     */
    public function onBeforeDelete()
    {
        ///DataObject to be Indexed
        $this->flushCache();
        $objects = SearchEngineDataObjectToBeIndexed::get()
            ->filter(['SearchEngineDataObjectID' => $this->ID]);
        foreach ($objects as $object) {
            $object->delete();
        }
        //keywords
        $this->SearchEngineKeywords_Level1()->removeAll();
        $this->SearchEngineKeywords_Level2()->removeAll();
        //full content
        $objects = $this->SearchEngineFullContents();
        foreach ($objects as $object) {
            $object->delete();
        }
        parent::onBeforeDelete();
        $this->flushCache();
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->Recalculate) {
            //in databas object, make sure onAfterWrite runs!
            $this->forceChange();
        }
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->Recalculate && $this->recalculateCount < 2) {
            $this->recalculateCount++;
            $this->doSearchEngineIndex();
            $this->write();
        } elseif ($this->Recalculate) {
            $this->Recalculate = false;
            $this->write();
        }
    }

    public function SearchEngineSourceObjectSortDate($sourceObject = null)
    {
        if (! $sourceObject) {
            $sourceObject = $this->SourceObject();
        }
        return Injector::inst()->get(SearchEngineSourceObjectApi::class)
            ->SearchEngineSourceObjectSortDate($sourceObject);
    }

    /**
     * returns array like this:
     * 1 => array('Title', 'MenuTitle')
     * 2 => array('Content')
     * @return array
     */
    public function SearchEngineFieldsForIndexing($sourceObject = null)
    {
        if (! $sourceObject) {
            $sourceObject = $this->SourceObject();
        }
        return Injector::inst()->get(SearchEngineSourceObjectApi::class)
            ->SearchEngineFieldsForIndexing($sourceObject);
    }

    public function getObjectClassName() : string
    {
        $className = $this->DataObjectClassName;
        if (! class_exists($className)) {
            return 'ERROR - class not found';
        }
        if (isset(self::$_object_class_name[$className])) {
            $objectClassName = self::$_object_class_name[$className];
        } else {
            $objectClassName = Injector::inst()->get($className)->singular_name();
            self::$_object_class_name[$className] = $objectClassName;
        }
        return self::$_object_class_name[$className];

    }

    /**
     * @casted variable
     * @return string
     */
    public function getTitle() : string
    {
        $objectClassName = $this->getObjectClassName();
        $object = $this->SourceObject();
        if ($object) {
            $objectName = $object->getTitle();
        } else {
            $objectName = 'ERROR: NOT FOUND';
        }

        return $objectClassName . ': ' . $objectName;
    }

    /**
     * @return DataObject|null
     */
    public function SourceObject()
    {
        $key = $this->getKey();
        if (! isset(self::$_source_objects[$key])) {
            $className = $this->DataObjectClassName;
            $id = $this->DataObjectID;
            self::$_source_objects[$key] = $className::get()->byID($id);
        }

        return self::$_source_objects[$key];
    }

    /**
     * @return bool
     */
    public function SourceObjectExists()
    {
        $key = $this->getKey();
        if (! isset(self::$_source_objects_exists[$key])) {
            $className = $this->DataObjectClassName;
            $id = $this->DataObjectID;
            self::$_source_objects_exists[$key] = $className::get()->filter(['ID' => $id])->count() === 1 ? true : false;
        }

        return self::$_source_objects_exists[$key];
    }

    /**
     * @return string
     */
    public function RecordClickLink()
    {
        return 'searchenginerecordclick/add/' . $this->ID . '/';
    }

    /**
     * if there are special sorts groups this method helps to
     * show them in the templates.
     *
     * In the templates you do:
     *     <h2>Results</h2>
     *     <% loop $Results.GroupedBy(SpecialSortGroup) %>
     *         <ul>
     *         <% loop $Children %>
     *             <li>$Title ($Created.Nice)</li>
     *         <% end_loop %>
     *         </ul>
     *     <% end_loop %>
     *
     * @return string
     */
    public function SpecialSortGroup()
    {
        $className = $this->getKey(true);
        if (! isset(self::$_special_sort_group[$className])) {
            self::$_special_sort_group[$className] = '';
            $classGroups = Config::inst()->get(SearchEngineSortByDescriptor::class, 'class_groups');
            if (is_array($classGroups) && count($classGroups)) {
                self::$_special_sort_group[$className] = 'SortGroup999';
                foreach ($classGroups as $level => $classes) {
                    if (in_array($this->DataObjectClassName, $classes, true)) {
                        self::$_special_sort_group[$className] = 'SortGroup' . $level;
                        break;
                    }
                }
            }
        }

        return self::$_special_sort_group[$className];
    }

    #############################################
    # CMS
    #############################################

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField(
            'DataObjectClassName',
            ReadonlyField::create(
                'DataObjectClassName',
                'Class Name'
            )
        );
        $fields->replaceField(
            'DataObjectID',
            ReadonlyField::create(
                'DataObjectID',
                'Record ID'
            )
        );
        $fields->addFieldToTab(
            'Root.Main',
            ReadonlyField::create(
                'Title',
                'Title'
            ),
            'DataObjectClassName'
        );
        $object = $this->SourceObject();
        if ($object && $object->hasMethod('CMSEditLink')) {
            $fields->addFieldToTab(
                'Root.Main',
                ReadonlyField::create(
                    'CMSLink',
                    'Open in CMS',
                    DBField::create_field('HTMLText', '<a href="' . $object->CMSEditLink() . '" target="_blank">open to edit</a>')
                )
            );
        }
        if ($object && ($object->hasMethod('Link'))) {
            $fields->addFieldToTab(
                'Root.Main',
                ReadonlyField::create(
                    'FrontEndLink',
                    'Open on front-end',
                    DBField::create_field('HTMLText', '<a href="' . $object->Link() . '" target="_blank">open to view</a>')
                )
            );
        }

        if ($myTab = $fields->fieldByName('Root.SearchEngineKeywords_Level2')) {
            $fields->removeFieldFromTab('Root', 'SearchEngineKeywords_Level2');
            $fields->fieldByName('Root')->push($myTab);
        }

        return $fields;
    }

    public function CMSEditLink()
    {
        $className = str_replace('\\', '-', self::class);

        return '/admin/searchengine/'.$className.'/EditForm/field/'.$className.'/item/' . $this->ID . '/edit';
    }

    #####################
    # display
    #####################

    /**
     * @param boolean $moreDetails
     * @return html
     */
    public function getHTMLOutput($moreDetails = false)
    {
        $obj = $this->SourceObject();
        if ($obj) {
            $arrayOfTemplates = $obj->SearchEngineResultsTemplates($moreDetails);
            $cacheKey = 'SearchEngine_' . $obj->ClassName . '_' . abs($obj->ID) . '_' . ($moreDetails ? 'MOREDETAILS' : 'NOMOREDETAILS');

            $cache = Injector::inst()->get(CacheInterface::class . '.SearchEngine');

            $templateRender = null;
            if ($cache->has($cacheKey)) {
                $templateRender = $cache->get($cacheKey);
            }
            if ($templateRender) {
                $templateRender = unserialize($templateRender);
            } else {
                $templateRender = $obj->renderWith($arrayOfTemplates);
                $cache->set($cacheKey, serialize($templateRender));
            }
            return $templateRender;
        }
    }

    /**
     * @return html
     */
    public function getHTMLOutputMoreDetails()
    {
        return $this->getHTMLOutput(true);
    }

    /**
     * returns a template for formatting the object
     * in the search results.
     *
     * @param boolean $moreDetails
     *
     * @return array
     */
    public function SearchEngineResultsTemplates($sourceObject = null, $moreDetails = false)
    {
        if (! $sourceObject) {
            $sourceObject = $this->SourceObject();
        }
        return Injector::inst()->get(SearchEngineSourceObjectApi::class)
            ->SearchEngineResultsTemplates(
                $sourceObject,
                $moreDetails
            );
    }

    public function SearchEngineFieldsToBeIndexedHumanReadable($sourceObject = null, $includeExample = false)
    {
        if (! $sourceObject) {
            $sourceObject = $this->SourceObject();
        }
        return Injector::inst()->get(SearchEngineSourceObjectApi::class)
            ->SearchEngineFieldsToBeIndexedHumanReadable(
                $sourceObject,
                $includeExample
            );
    }


    /**
     * deletes cached search results
     * sets stage to LIVE
     * indexes the current object.
     * @param SearchEngineDataObject $sourceObject
     * @param DataObject $sourceObject
     * @param bool $withModeChange  - Setting this to false means the stage
     * @param bool $timeMeasure  - do time measure?
     */
    public function doSearchEngineIndex($sourceObject = null, $withModeChange = true, $timeMeasure = false)
    {
        if ($timeMeasure) {
            $this->timeMeasure = [];
        }
        if (! $sourceObject) {
            $sourceObject = $this->SourceObject();
        }
        if ($sourceObject) {
            if ($withModeChange) {
                SearchEngineDataObjectApi::start_indexing_mode();
            }

            //add date!
            $this->DataObjectDate = $this->SearchEngineSourceObjectSortDate($sourceObject);
            $this->write();

            if ($timeMeasure) {
                $startTime = microtime(true);
            }

            //get the full content
            $fullContentArray = $this->SearchEngineFullContentForIndexingBuild($sourceObject);
            if ($timeMeasure) {
                $endTime = microtime(true);
                $this->timeMeasure['FullContentBuild'] = $endTime - $startTime;
                $startTime = microtime(true);
            }

            //add the full content
            SearchEngineFullContent::add_data_object_array($this, $fullContentArray);

            if ($timeMeasure) {
                $endTime = microtime(true);
                $this->timeMeasure['AddContent'] = $endTime - $startTime;
            }
            if ($withModeChange) {
                SearchEngineDataObjectApi::end_indexing_mode();
            }
        }
    }

    public function getTimeMeasure()
    {
        return $this->timeMeasure;
    }

    /**
     * returns a full-text version of an object like this:
     * array(
     *   1 => 'bla',
     *   2 => 'foo',
     * );
     * where 1 and 2 are the levels of importance of each string.
     *
     * @return array
     */
    public function SearchEngineFullContentForIndexingBuild($sourceObject = null)
    {
        if (! $sourceObject) {
            $sourceObject = $this->SourceObject();
        }
        return Injector::inst()->get(SearchEngineSourceObjectApi::class)
            ->SearchEngineFullContentForIndexingBuild($sourceObject);
    }

    #############################################
    # DEFINITIONS
    #############################################

    /**
     *
     * @param  bool $classNameOnly
     * @return string
     */
    public function getKey($classNameOnly = false) : string
    {
        if ($classNameOnly) {
            return $this->DataObjectClassName;
        }
        return $this->DataObjectID . '_' . $this->DataObjectClassName;
    }
}
