<?php

namespace Base\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class ParamSetup
{
    protected string $action;
    protected ?string $table = null;
    protected ?array $values = null;
    protected ?array $conditions = null;
    protected ?string $sort = null;
    protected ?int $limit = null;
    protected ?string $key = null;
    protected ?int $page = null;

    protected $response;

    public abstract function setParams();

    public function generateParams(): bool
    {
        try {
            $params = $this->setParams();
            $this->table = $params['table'];
            $this->action = DBActions($params['type']);
            $this->values = $params['values'] ?? null;
            $this->conditions = $params['conditions'] ?? null;
            $this->sort = $params['sort'] ?? null;
            $this->limit = $params['limit'] ?? null;
            $this->key = $params['key'] ?? null;
            $this->page = $params['page'] ?? null;
            return true;
        } catch (\Exception) {
            return false;
        }
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

    public function saveDetails($tableDetails, $keyLabel, $mainKey, $details)
    {
        DB::table($tableDetails)->where([$keyLabel => $mainKey])->delete();

        foreach ($details as $key => $object) {
            if (is_array($object)) {
                foreach ($object as $key2 => $object2) {
                    if (is_array($object2)) {
                        foreach ($object2 as $key3 => $value)
                            $this->saveDetail($key3, $value, $mainKey, $keyLabel, $tableDetails, $key, $key2);
                    } else $this->saveDetail($key2, $object2, $mainKey, $keyLabel, $tableDetails, $key);
                }
            } else $this->saveDetail($key, $object, $mainKey, $keyLabel, $tableDetails);
        }
    }

    public function saveDetail($type, $value, $key, $keyLabel, $tableDetails, $category = null, $sub_category = null)
    {
        $values["value"] = $value;
        $conditions = [];

        $values[$keyLabel] = $key;
        $values["category"] = $category;
        $values["type"] = $type;
        $values["sub_category"] = $sub_category;
        if (Schema::hasColumn($tableDetails, "uuid")) $values["uuid"] = Str::uuid();

        return (new DBQueries($tableDetails, $values, $conditions))->dbInsert();
    }

    public function execQuery(): void
    {
        try {
            $action = $this->action;
            $query = new DBQueries($this->table, $this->values, $this->conditions, $this->sort, $this->limit, $this->key);
            $this->response = $query->$action();
        } catch (\Exception $ex) {
            $this->response = ['msg' => $ex->getMessage()];
            throw $ex;
        }
    }
}
