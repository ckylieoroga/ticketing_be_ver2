<?php

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class FieldValidation {
    public int $table_id;
    public string $validationCode, $field_type;
    public array $fieldValidationDetails, $data;
    public string $mode;
    public \stdClass $object;

    public function __construct(array $fieldValidationDetails, string $validationCode, string $field_type, string $mode, array $data)
    {
        $this->validationCode = $validationCode;
        $this->field_type = $field_type;
        $this->fieldValidationDetails = $fieldValidationDetails;
        $this->data = $data;
        $this->mode = $mode;
        $this->instantiateObject();
    }

    private function instantiateObject() {
        $this->object = new \stdClass;
        foreach($this->fieldValidationDetails as $detail) {
            $key = $detail->type;
            $this->object->$key = $detail->value;
        }
    }

    public function parse(): mixed
    {
        $validationObject = DB::connection("sys_base")
        ->table("validations")->where([
            "code" => $this->validationCode
        ])->first();

        if ($validationObject->type === "length_validation") {
            return $validationObject->rule . ":" . $this->object->value;
        } else if ($validationObject->type === "compound_validation") {
            return $validationObject->rule . ":" . $this->object->column . "," . $this->object->value;
        } else if ($validationObject->type === "unique_validation") {
            if ($this->mode === "update") {
                //for modification need to validate if column is unique in column

                if ($this->field_type === "object_list") {
                    return Rule::forEach(function(string|null $value, string $attribute) {
                        return [
                            Rule::unique($this->object->table)->ignore($value, $this->object->keyColumn)
                        ];
                    });
                } else {
                    $keyValue = data_get($this->data, $this->object->keyColumn);
                    return Rule::unique($this->object->table)->ignore($keyValue, $this->object->keyColumn);
                }
            }
            else return $validationObject->rule . ":" . $this->object->table . "," . $this->object->value;
        } else if ($validationObject->type === "custom_validation") {
            $rule = $validationObject->rule;
            $namespace = DB::connection("sys_base")->table("namespace")
                ->where("id", $validationObject->namespace_id)->first()->namespace;

            if ($validationObject->with_args) {
                $params = (array) $this->object;
                $params["mode"] = $this->mode;
                if($namespace)  return new (str_replace('.', '\\', $namespace) . $rule($params));
            }

            return new $rule;
        } else {
            return $validationObject->rule;
        }
    }
}
