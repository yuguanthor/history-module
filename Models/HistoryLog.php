<?php
namespace Modules\History\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Request;
use URL;

class HistoryLog extends Model
{

    protected $table = 'history_log';
    protected $guarded = ['id'];
    public $timestamps = false;

    /*
    Get changed attribute in text form
     */
    public static function getChangeText($model, $task)
    {
        $changes = [];
        $change_txt = '';

        if ($task == 'Delete') {
            foreach ($model->toArray() as $key => $value) {
                $changes[] = "[$key => $value]";
            }
        } else {
            foreach ($model->getDirty() as $key => $value) {
                $original = $model->getOriginal($key);
                $changes[] = "$key [$original => $value]";
            }
        }

        foreach ($changes as $v) {
            $change_txt .= $v . "\n";
        }

        return $change_txt;
    }

    public static function getSummary($model, $task)
    {
        $tbl = $model->getTable(); //table name
        $PK_Name = $model->getKeyName(); // primary key name
        $PK_Value = $model->getKey(); // primary key value

        $summary = "$task $tbl $PK_Name : $PK_Value";
        return $summary;
    }

    public static function getApplication($model, $app_name = null)
    {
        $tbl_alias = HistoryLog::tableAlias($model);
        return isset($app_name) ? "$app_name-$tbl_alias" : $tbl_alias;
    }

    public static function getDifferent($model, $task)
    {
        $change_txt = HistoryLog::getChangeText($model, $task); // CHANGE LOG
        $application = HistoryLog::getApplication($model);
        $summary = HistoryLog::getSummary($model, $task);
        $history_arr = [
            'task' => $task,
            'application' => $application,
            'summary' => $summary,
            'change_txt' => $change_txt,
        ];
        HistoryLog::insertHistory($history_arr);
    }

    /*
    For insert new history record

    logInterval:
    to determine how long the new log start
    logInterval usiong second to determine ( 1min = 60s ... )
     */
    public static function insertHistory($arr, $logInterval = null)
    {
        $user_id = \Auth::user()->id ?? null;
        $user_name = \Auth::user()->name ?? null;

        $history = new HistoryLog();
        $history->user_id = $user_id;
        $history->user = $user_name;
        $history->date = date('Y-m-d H:i:s');
        $history->action = $arr['task'];
        $history->type = $arr['application'];
        $history->summary = $arr['summary'];
        $history->log = $arr['summary'] . "\n" . $arr['change_txt'];
        $history->location = "Current: " . Request::url() . "\nPrevious: " . URL::previous();

        /**
         *
         * Check for old history
         * Allow history merge within specific interval of time
         * OLD history based on Summary Text, User ID, and Timestamp
         *
         */
        $old_history = parent::getOldHistory($arr, $logInterval);

        if ($user_id == null || $old_history == null) {
            $history->save();
        } else {
            $pre_txt = $old_history->log;
            $history->log = $pre_txt . "\n" . $history->log;
            $history = $history->toArray();
            HistoryLog::find($old_history->id)->fill($history)->save();
        }

    }

    public function getOldHistory($arr,$logInterval)
    {
        $user_id = \Auth::user()->id ?? null;
        $old_history = DB::table('history_log')
            ->where('user_id', $user_id)
            ->where('action', $arr['task']);

        if ($logInterval == null) { //default
            $old_history->where('date', date('Y-m-d H:i:s'));
        } else {
            $now = date('Y-m-d H:i:s');
            if (is_numeric($logInterval)) {
                $old_history->whereRaw("(
                      TIMESTAMPDIFF(SECOND, date, '" . $now . "' ) <= $logInterval
                    )");
            } else {
                if ($logInterval == 'day') {
                    $old_history->whereRaw("(
                          DATE_FORMAT(Date,'%Y-%m-%d') = DATE_FORMAT('" . $now . "','%Y-%m-%d')
                        )");
                }
            }
        }

        $summary = explode('|', $arr['summary']);
        if (isset($summary[1])) {
           // $old_history->whereRaw('TRIM(SUBSTR(Summary,POSITION("|" IN Summary)+1)) = "' . trim($summary[1]) . '"');
        }

        $old_history = $old_history->first();
        return $old_history;
    }

    /*
     *
     *  TABLE ALIAS
     *  Use for rename existing table to meaningful name
     */
    public static function tableAlias($model)
    {
        $table = $model->getTable();

        $alias = [
            //Careplan
            'careplan' => 'CBDC',
            'careplanhh' => 'HACC',
            'careplancacp' => 'HCP',
            'careplanagency' => 'AGENCY',
            'careplandlrc' => 'DLRC',

            //HHAssignment
            'hhassignment' => 'HH',

            //CarePlan
            'careplan' => 'CBDC',
            'careplanhh' => 'HACC',
            'careplancacp' => 'HCP',
            'careplanagency' => 'AGENCY',
            'careplandlrc' => 'DLRC',

            //Leave
            'swonleaverecord' => 'SW',
            'clientonleaverecord' => 'Client',

            //SW Duty Roster
            'swdutyroster' => 'SWDutyRoster',
            'swdutyrostermaster' => 'SWDutyRoster 1st Week',
            'swdutyrostermaster2ndwk' => 'SWDutyRoster 2nd Week',

            //Transport
            'driverroster' => 'DriverRoster',
            'transportassignmentmaster' => 'TransportAssignmentMaster',
            'transportassignment' => 'TransportAssignment',

            //DLRC
            'dlrcsrattendance' => 'DLRC SR Attendance',

            //CBDC
            'cbdcattenddummy' => 'CBDC Attendance Estimation',

            //SETTING
            'reckon_classes' => 'Reckon Classes',
        ];

        return isset($alias[$table]) ? $alias[$table] : $table;
    } // Table Alias

}
