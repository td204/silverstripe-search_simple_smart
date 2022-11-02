<?php

namespace Sunnysideup\SearchSimpleSmart\Model;

use SilverStripe\Security\Permission;
use Sunnysideup\SearchSimpleSmart\Model\SearchEngineDataObject;
use SilverStripe\Core\Config\Config;
use Sunnysideup\SearchSimpleSmart\Model\SearchEngineFullContent;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Forms\ReadonlyField;

/**
 * Full Content for each dataobject, separated by level of importance.
 *
 * Adding the content here, will also add it to the Keywords.
 *
 * Todo: consider breaking it up in sentences.
 */

class SearchEngineFullContent extends DataObject
{

    private static $default_punctuation_to_be_removed = [
        '\'',
        '"',
        ';',
        '.',
        ',',
        '&nbsp'
    ];

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'SearchEngineFullContent';

    /*
     * @var string
     */
    private static $singular_name = "Full Content";
    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    /*
     * @var string
     */
    private static $plural_name = "Full Contents";
    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    /*
     * @var array
     */
    private static $db = array(
        "Level" => "Int(1)",
        "Content" => "Varchar(9999)"
    );

    /*
     * @var array
     */
    private static $has_one = array(
        "SearchEngineDataObject" => SearchEngineDataObject::class
    );

    /*
     * @var array
     */
    private static $indexes = array(
        "Level" => true,
        'SearchFields' => array(
            'type' => 'fulltext',
            'name' => 'SearchFields',
            'columns' => ['Content']
        )
    );

    /*
     * @var string
     */
    private static $default_sort = "\"Level\" ASC, \"Content\" ASC";

    /*
     * @var array
     */
    private static $required_fields = array(
        "Level",
        "Content"
    );

    /*
     * @var array
     */
    private static $summary_fields = array(
        "LastEdited.Nice" => "Last Changed",
        "SearchEngineDataObject.Title" => "Searchable Object",
        "Level" => "Level",
        "ShortContent" => "Content"
    );

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'Level' => 'ExactMatchFilter'
    ];

    /*
     * @var array
     */
    private static $field_labels = array(
        "SearchEngineDataObject" => "Data Object"
    );

    /*
     * @var array
     */
    private static $casting = array(
        "ShortContent" => "Varchar"
    );


    /**
     * this is very important to allow Mysql FullText Searches
     * @var array
     */
    private static $create_table_options = array(
        MySQLSchemaManager::ID => 'ENGINE=MyISAM'
    );

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
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canDelete($member = null, $context = [])
    {
        return parent::canDelete() && Permission::check("SEARCH_ENGINE_ADMIN");
    }

    /**
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canView($member = null, $context = [])
    {
        return parent::canView() && Permission::check("SEARCH_ENGINE_ADMIN");
    }

    /**
     * @var bool
     */
    private static $remove_all_non_alpha_numeric = false;

    /**
     * @var bool
     */
    private static $remove_all_non_letters = false;

    /**
     *
     * @param SearchEngineDataObject
     * @param array
     *     1 => content
     *     2 => content
     *     3 => content
     *
     * You can specify up to three levels
     */
    public static function add_data_object_array($item, $fullAray)
    {
        foreach ($fullAray as $level => $content) {
            self::add_one($item, $level, $content);
        }
    }

    /**
     * @param SearchEngineDataObject $item
     * @param int $level
     * @param string $content
     * @return object
     */
    public static function add_one($item, $level, $content)
    {
        $level = SearchEngineKeyword::level_sanitizer($level);
        //you dont want to clean keywords now as this will remove all the spaces!
        //$content = SearchEngineKeyword::clean_keyword($content);
        $fieldArray = array("SearchEngineDataObjectID" => $item->ID, "Level" => $level);
        $obj = DataObject::get_one(SearchEngineFullContent::class, $fieldArray);
        if (!$obj) {
            $obj = SearchEngineFullContent::create($fieldArray);
        }
        $obj->Content = $content;
        $obj->write();
    }

    /**
     * @casted variable
     * @return string
     */
    public function getShortContent()
    {
        return substr($this->Content, 0, 50);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Level = SearchEngineKeyword::level_sanitizer($this->Level);
        $this->Content = self::clean_content($this->Content);
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $fullArray = [];
        $item = $this->SearchEngineDataObject();
        if ($item) {
            //todo: turn Content into Keywords
            //1. take full content.
            $content = $this->Content;
            //2. remove stuff that is not needed (e.g. strip_tags)
            $keywords = explode(" ", $content);
            foreach ($keywords as $keyword) {
                // we know content is clean already!
                // $keyword = SearchEngineKeyword::clean_keyword($keyword);
                if (strlen($keyword) > 1) {
                    //check if it is a valid keyword.
                    if (SearchEngineKeywordFindAndRemove::is_listed($keyword)) {
                        //not a valid keyword
                        continue;
                    }
                    $keywordObject = SearchEngineKeyword::add_keyword($keyword, $runClean = false);
                    if (!isset($fullArray[$keywordObject->ID])) {
                        $fullArray[$keywordObject->ID] = array(
                            "Object" => $keywordObject,
                            "Count" => 0
                        );
                    }
                    $fullArray[$keywordObject->ID]["Count"]++;
                }
            }
            //remove All previous entries
            $this->Level = SearchEngineKeyword::level_sanitizer($this->Level);
            $methodName = "SearchEngineKeywords_Level".$this->Level;
            $list = $item->$methodName();
            $list->removeAll();
            //add all keywords
            foreach ($fullArray as $keywordObjectID => $arrayItems) {
                $list->add( $a["Object"], array("Count" => $a["Count"]));
            }
        }
    }

    /*
     *
     */
    private static $_punctuation_objects = null;


    /**
     * cleans a string
     * @param string $content
     * @return string
     * @todo: cache using SS caching system.
     */
    public static function clean_content($content)
    {

        $content = strtolower($content);

        //important!!!! - create space around tags ....
        $content = str_replace('<', ' <', $content);
        $content = str_replace('>', '> ', $content);

        //remove tags!
        $content = strip_tags($content);

        //default punctuation removal
        $defaultPuncs = Config::inst()->get(SearchEngineFullContent::class, "default_punctuation_to_be_removed");
        foreach ($defaultPuncs as $defaultPunc) {
            $content = str_replace($defaultPunc, " ", $content);
        }

        //custom punctuation removal
        if (self::$_punctuation_objects === null) {
            self::$_punctuation_objects = SearchEnginePunctuationFindAndRemove::get();
            if(self::$_punctuation_objects->count() === 0) {
                self::$_punctuation_objects = false;
            }
        }
        if(self::$_punctuation_objects) {
            foreach (self::$_punctuation_objects as $punctuationObject) {
                $content = str_replace(self::$_punctuation_objects->Character, " ", $content);
            }
        }

        //remove non-alpha
        $removeNonAlphas = Config::inst()->get(SearchEngineFullContent::class, "remove_all_non_alpha_numeric");
        if ($removeNonAlphas == true) {
            $content = preg_replace("/[^a-zA-Z 0-9]+/", " ", $content);
        }

        //remove non letters
        //remove non-alpha
        $removeNonLetters = Config::inst()->get(SearchEngineFullContent::class, "remove_all_non_letters");
        if ($removeNonLetters == true) {
            $content = trim(
                strtolower(
                    //remove all white space with single space
                    //see: http://stackoverflow.com/questions/5059996/php-preg-replace-with-unicode-chars
                    //see: http://stackoverflow.com/questions/11989482/how-to-replace-all-none-alphabetic-characters-in-php-with-utf-8-support
                    preg_replace(
                        '/\P{L}+/u',
                        ' ',
                        $content
                    )
                )
            );
        }


        //remove multiple white space
        $content = trim(preg_replace( "/\s+/", " ", $content ));

        return $content;
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if($obj = $this->SearchEngineDataObject()) {
            $fields->replaceField(
                'SearchEngineDataObjectID',
                ReadonlyField::create(
                    'SearchEngineDataObjectTitle',
                    'Object',
                    DBField::create_field(
                        'HTMLText',
                        '<a href="'.$obj->CMSEditLink().'">'.$obj->getTitle().'</a>'
                    )
                )
            );
        }
        return $fields;
    }



}
