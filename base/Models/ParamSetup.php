<?php

namespace Base\Models;

abstract class ParamSetup
{
    private string $action;
    private ?string $table;
    private ?array $values;
    private ?array $conditions;
    private ?string $sort;
    private ?int $limit;
    private ?string $key;

    protected $response;

    public abstract function setParams();

    public function generateParams() : bool {
        try {
            $request = $this->setParams();
            $params = $request['request'];
            $this->table = $request['table'];
            $this->action = DBActions($params['type']);
            $this->values = $params['values'] ?? null;
            $this->conditions = $params['conditions'] ?? null;
            $this->sort = $params['sort'] ?? null;
            $this->limit = $params['limit'] ?? null;
            $this->key = $params['key'] ?? null;
            return true;
        }
        catch (\Exception){return false;}
    }

    public function execQuery() : void
    {
        try{
            $action = $this->action;
            $this->action = $action;
            $query = new DBQueries($this->table,$this->values,$this->conditions,$this->sort,$this->limit,$this->key);
            $this->response = $query->$action();
        }
        catch (\Exception $ex){
            $this->response = ['msg' => $ex->getMessage()];
            throw $ex;
        }
    }
}
