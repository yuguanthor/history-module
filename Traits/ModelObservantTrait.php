<?php
namespace Modules\History\Traits;

/*
This Trait is for Model with created_at, created_by , updated_at, updated_by

it is used to update the respective value when updated / created.
 */

use Modules\History\Models\HistoryLog;

trait ModelObservantTrait
{
    //boot[hook_class_name]
    public static function bootModelObservantTrait()
    {
        static::observe(new ModelObserver);
    }

    public function getUserFootprint()
    {
        return $this->userFootprint;
    }

}

class ModelObserver
{
    public function __construct()
    {

        $this->userID = \Auth::id();
    }

    public function updating($model)
    {
        $model->updated_by = $this->userID;
        $model->updated_at = date('Y-m-d H:i:s');
        HistoryLog::getDifferent($model,'Update');
    }

    public function created($model)
    {
        /*
        if ($model->getUserFootprint()) {
            $table_name = $model->getTable();
            $columns = \Schema::getColumnListing($table_name);
            if (in_array('created_by', $columns)) {
                $model->created_by = $this->userID;
            }
        }
        */
        $model->created_by = $this->userID;
        $model->created_at = date('Y-m-d H:i:s');
        HistoryLog::getDifferent($model,'Insert');
    }

    public function deleting($model)
    {
        HistoryLog::getDifferent($model,'Delete');
    }

    /*
public function retrieved($model){}
public function creating($model){}
public function created($model){}
public function updating($model){}
public function updated($model){}
public function saving($model){}
public function saved($model){}
public function deleting($model){}
public function deleted($model){}
public function restoring($model){}
public function restored($model){}
 */

}
