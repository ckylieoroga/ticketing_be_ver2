<?php

namespace Base\Validation;

use FieldValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FormValidator {

    protected string $table;
    protected array $data;
    protected string $form;
    protected string $mode;
    protected array $validators;
    protected array $messages;

    public function __construct(string $table, string $form, string $mode,  array $data = [])
    {
        $this->table = $table;
        $this->data = $data;
        $this->form = $form;
        $this->mode = $mode;
        $this->validators = [];
        $this->messages = [];
    }

    public function validate(){
        if (empty($this->validators) && empty($this->messages)) $this->prepareValidatorParameters();
        $validator = Validator::make($this->data, $this->validators, $this->messages);

        return $validator->fails();
    }

    private function prepareValidatorParameters() {
        $form_code = DB::connection("sys_base")->table("forms")->where([
            "base_table" => $this->table,
            "form_name" => $this->form
        ])->first()->code;

        $fields = DB::connection("sys_base")->table("form_fields")->where([
            "form_code" => $form_code
        ])->get();

        $this->validators = [];
        $this->messages = [];

        foreach($fields as $field_info) {
            $fieldValidations = DB::connection("sys_base")->table("field_validations")->where(["field_code" => $field_info->code])->get();
            $parsedFieldValidations = [];
            $validationKey = "";

            foreach($fieldValidations as $fieldValidation) {
                $validationObject = DB::connection("sys_base")->table("validations")->where(["code" => $fieldValidation->validation_code])->first();
                $fieldvalidationDetails = DB::connection("sys_base")->table("field_validation_details")->where(["field_validation_code" => $fieldValidation->code])->get();

                if (in_array($field_info->field_object_type, ["object_list", "object"])) {
                    $key = "";
                    if ($field_info->field_object_type === "object_list") $key = $field_info->sub_field . ".*." . $field_info->field_name;
                    else if  ($field_info->field_object_type === "object") $key = $field_info->sub_field . "." . $field_info->field_name;
                    $validationKey = $key;

                    $fieldValidationObject = new FieldValidation($fieldvalidationDetails->toArray(), $fieldValidation->validation_code, $field_info->field_object_type, $this->mode, $this->data);
                    $key .= "." . $validationObject->rule;
                    $this->messages[$key] = $validationObject->failed_message;
                } else {
                    $fieldValidationObject = new FieldValidation($fieldvalidationDetails->toArray(), $fieldValidation->validation_code,$field_info->field_object_type, $this->mode, $this->data);
                    $validationKey = $field_info->field_name;
                    $this->messages[$field_info->field_name . "." . $validationObject->rule] = $validationObject->failed_message;
                }

                $parsedFieldValidations[] = $fieldValidationObject->parse();
            }

            $this->validators[$validationKey] = $parsedFieldValidations;
        }
    }


    /*public function getValidated(): mixed
    {
        if (empty($this->validators) && empty($this->messages)) $this->prepareValidatorParameters();
        if (! $this->validate()) return false;

        $validator = Validator::make($this->data, $this->validators, $this->messages);

        return $validator->validate();
    }*/
}
