<?php

namespace Base\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as DBCollect;
use Illuminate\Support\Facades\DB;

class DBQueries extends CommonQueries
{

    use SoftDeletes;
    protected $table, $values, $condition ,$sort, $limit, $relate;

    public function __construct($table, $values = null, $conditions = null, $sort = null, $limit = null, $key= null){
        $this->table = $table ?? null;
        $this->values = !is_null($values) ? parseColumns($values) : null;
        $this->condition = !is_null($conditions) ? parseColumns($conditions) : null;
        $this->sort = $sort ?? 'asc';
        $this->limit = $limit ?? null;
        $this->key = $key ?? 'id';
    }

    public function dbInsert() : int
    {
        //TODO : Convert request param to table column counterpart (use parseToColumnName function)
        //TODO : Validation
        return $this->insert();
    }

    public function dbUpdate() : int
    {
        //TODO : Convert request param to table column counterpart (use parseToColumnName function)
        //TODO : Validation
        return $this->update();
    }

    public function dbGet() : ?\stdClass
    {
        //TODO : Convert request param to table column counterpart (use parseToColumnName function)
        //TODO : Validation
        return $this->relate ? $this->getRelationship($this->get()) : $this->get();
    }

    public function dbGetAll() : DBCollect
    {
        //return $this->getRelationship($this->list());
        return $this->relate === true ? $this->getRelationship($this->list()) : $this->list();
    }

    public function dbDelete() : int
    {
        //TODO : Convert request param to table column counterpart (use parseToColumnName function)
        //TODO : Add validation to determine if table has a specific column
        //TODO : Validation
        $this->values = [
            "deleted_at" => (new \DateTime("now", new \DateTimeZone("Asia/Manila")))->format("Y-m-d H:i:s")
        ];
        return $this->update();
    }

    public function hasRelation() : bool {
        $this->relate = true;
        return true;
    }

    public function setRelation() {
        $this->relate = true;
        return true;
    }

    private function getRelationship($data){
        return $data->map(function ($data){
            $relations = DB::table('table_relationship')->select()->where(['base' => $this->table])->get();
            if($relations){
                foreach($relations as $relation){
                    $columns = DB::table('table_relationship_columns')->select()->where(['table_relationship_id' => $relation->id])->get();
                    if($columns){
                        $conditions = array();
                        foreach($columns as $column){
                            if($column->type === 'column') $conditions[$column->value][$column->column_name] = $column->value;
                            else if($column->type === 'data') {
                                $value = $column->value;
                                $conditions[$column->value][$column->column_name] = $data->$value;
                            }
                        }
                        foreach($conditions as $key => $condition){
                            if($relation->relationship === 'one') $opt = DB::table($relation->lookup)->select()->where($condition)->first();
                            else $opt = DB::table($relation->lookup)->select()->where($condition)->get();
                            $data->$key = $opt;
                        }
                    }
                }
            }

            return $data;
        });
    }

    public function setParameters() : array
    {
        return [$this->table, $this->values, $this->condition, $this->sort, $this->limit, $this->key];
    }

}
