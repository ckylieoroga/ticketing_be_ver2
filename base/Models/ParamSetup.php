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
    public function filterResponse($response)
    {
        if (is_null($response)) return null;
        if (!is_array($response) && !is_object($response)) return $response;

        $commonFilters = ["created_at", "updated_at", "created_by", "updated_by",  "deleted_at"];
        $tableFilters = [];

        $filteredKeys = data_get($tableFilters, $this->table, []);
        $filteredKeys = empty($filteredKeys) ? $commonFilters : $filteredKeys;

        $filteredResponse = [];

        foreach ($response as $key => $value) {
            if ($key === "details") {
                $filteredResponse[$key] = $value;
                continue;
            }

            if ((is_array($value) && array_is_list($value)) || $value instanceof Collection) {
                $newArray = [];
                foreach ($value as $object) {
                    if (is_array($object) || is_object($object)) $newArray[] = (object) $this->filterResponse($object);
                    else $newArray[] = $object;
                }

                $filteredResponse[$key] = $newArray;
            } else if ($value instanceof Model) {
                $filteredResponse[$key] = $this->filterResponse($value->toArray());
            } else if (is_object($value) && !($value instanceof Collection)) {
                $filteredResponse[$key] = (object) $this->filterResponse($value);
            } else if (is_array($value) && !array_is_list($value)) {
                $filteredResponse[$key] = $this->filterResponse($value);
            } else if (!in_array($key, $filteredKeys)) {
                $filteredResponse[$key] = $value;
            }
        }

        return $filteredResponse;
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
