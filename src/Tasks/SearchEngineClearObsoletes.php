<?php

namespace Sunnysideup\SearchSimpleSmart\Tasks;

use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DB;
use Sunnysideup\SearchSimpleSmart\Model\SearchEngineDataObject;
use SilverStripe\CMS\Model\SiteTree;
use Sunnysideup\SearchSimpleSmart\Model\SearchEngineDataObjectToBeIndexed;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Environment;

class SearchEngineClearObsoletes extends SearchEngineBaseTask
{

    /**
     * Set a custom url segment (to follow dev/tasks/)
     *
     * @config
     * @var string
     */
    private static $segment = 'searchengineclearobsoletes';

    /**
     * title of the task
     * @var string
     */
    protected $title = 'Remove obsolete entries';

    /**
     * description of the task
     * @var string
     */
    protected $description = 'Go through all searchable objects and remove obsolete ones';

    /**
     * this function runs the SearchEngineRemoveAll task
     * @param var $request
     */
    public function run($request)
    {
        //set basics
        $this->runStart($request);

        $count = SearchEngineDataObject::get()
            ->count();
        $sort = null;
        if($count > $this->limit) {
            $count = $this->limit;
            $sort = DB::get_conn()->random().' ASC';
        }
        $this->flushNow('<h4>Found entries: '.$count.'</h4>');
        for ($i = 0; $i <= $count; $i = $i + $this->step) {
            $objects = SearchEngineDataObject::get()->limit($this->step, $i);
            if($sort) {
                $objects = $objects->sort($sort);
            }
            foreach ($objects as $obj) {
                if($obj->SourceObjectExists() === false) {
                    $this->flushNow('DELETING '.$obj->ID);
                    $obj->delete();
                } else {
                    $this->flushNow('OK ... '.$obj->ID);
                }
            }
        }

        $this->runEnd($request);

    }



}