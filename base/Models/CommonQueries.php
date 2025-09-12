<?php

namespace Base\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection as DBCollect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

abstract class CommonQueries
{
    use SoftDeletes;
    protected $table, $values, $condition ,$sort, $limit, $key, $user;

    protected function params() {
        $param = $this->setParameters();
        $this->table = $param[0] ?? null;
        $this->values = $param[1] ?? null;
        $this->condition = $param[2] ?? null;
        $this->sort = $param[3] ?? null;
        $this->limit = $param[4] ?? null;
        $this->key = $param[5] ?? null;
        $this->getUser();
        return null;
    }

    public function insert() : int
    {
        $this->params();
        $params = $this->values;
        $params['created_at'] = date('Y-m-d H:i:s');
        $params['updated_at'] = date('Y-m-d H:i:s');
        if(Schema::hasColumn($this->table, 'created_by')) $params['created_by'] = $this->user->custom_user_name;
        return DB::table($this->table)->insertGetId($params);
    }

    public function update() : int
    {
        $this->params();
        if(Schema::hasColumn($this->table, 'updated_by')) DB::table($this->table)->where($this->condition)->update(['updated_by' => $this->user->custom_user_name]);
        if(Schema::hasColumn($this->table, 'updated_at')) DB::table($this->table)->where($this->condition)->update(['updated_at' => date('Y-m-d H:i:s')]);
        if(Schema::hasColumn($this->table, 'deleted_at')) return DB::table($this->table)->where($this->condition)->whereNull('deleted_at')->update($this->values);
        return DB::table($this->table)->where($this->condition)->update($this->values);
    }

    public function get() : ?\stdClass {
        $this->params();
        if(Schema::hasColumn($this->table, 'deleted_at')) return DB::table($this->table)->select()->where($this->condition)->whereNull('deleted_at')->get()->first();
        return DB::table($this->table)->select()->where($this->condition)->get()->first();
    }

    public function list() : DBCollect {
        $this->params();
        $query = DB::table($this->table)->select();
        if(Schema::hasColumn($this->table, 'deleted_at')) $query = $query->whereNull('deleted_at');
        if($this->condition) $query = $query->where($this->condition);
        if($this->limit) return $query->orderBy($this->key, $this->sort)->limit($this->limit)->get();
        else return $query->orderBy($this->key, $this->sort)->get();

    }

    public function delete() : int
    {
        $this->params();
        DB::table($this->table)->where($this->condition)->update(['deleted_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->user->custom_user_name]);
        return DB::table($this->table)->where($this->condition)->delete();
    }

    private function getUser() {
        $this->user = DB::table('system_users')->where('user_name', Auth::user()->name)->first();
    }

    public abstract function setParameters();


}
