<?php

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

function returnResponse($data, $code) : JsonResponse {
    $parsedResponse = (new \Base\Component\ResponseParser($code))->response();
    $response = [
        'statusCode' => $parsedResponse['code'],
        'responseCode' => $parsedResponse['responseCode'],
        'responseStatus' => $parsedResponse['message'],
        'responseId' => session()->getId(),
        'responseTime' => date('Y-m-d H:i:s'),
        'data' => $data,
    ];
    return response()->json($response, $parsedResponse['httpCode']);
}

function exceptionMessage(Exception $ex, $errorCode = null)
{
    $error = [];
    $error["code"] = $ex->getCode();
    $error["response_message"] = $errorCode === 500 ? "Server Error" : $ex->getMessage();
    $error["message"] = $ex->getMessage();
    $error["file"] = $ex->getFile();
    $error["line"] = $ex->getLine();
    $error["trace"] = $ex->getTrace();

    return $error;
}

function tokenExpiry() : int {
   return env("TOKEN_EXPIRATION", 120);
}

function addToLogs($data, $type) : string {
    if($data['code'] === 0) unset($data['data']);
    return createLogs($data,$type);
}

function createLogs($request, $type) : string {
    $log = new \Base\Models\Logs($request, $type);
    return $log->addLog();
}

function requestException() : array{
    return [["none"],["change"]];
}

function parseToColumnName($column) : String {
    $column = preg_replace('/([a-z])([A-Z])/', '$1_$2', $column);
    return strtolower($column);
}

function parseColumns($sections): array {
    $parsedSection = [];
    foreach($sections as $key => $value) $parsedSection[parseToColumnName($key)] = $value;
    return $parsedSection;
}

function dateParse($date, $format) : string {
    return Carbon::parse($date)->format($format);
}

function getCurrencies() : array {
    return (new Base\Auth\AuthController)->currencies();
}

function createTransactionLogs($data) : ?string {
    $log = new \Base\Models\Logs($data, '');
    return $log->addTransactionLog();
}

const HIDDEN_RESPONSE_FIELDS = [
    'coa' => ['created_at', 'updated_at', 'deleted_at', 'id'],
    'tbl_entities' => ['created_at', 'created_by', 'deleted_at', 'updated_by', 'updated_at'],
    'tbl_entity_details' => ['created_at', 'created_by', 'deleted_at', 'updated_by', 'updated_at'],
    "tbl_general_options" => ["created_at", "created_by", "deleted_at", "updated_at", "updated_by", "id"],
    "tbl_taxes" => ['id', "created_by", "created_at", "updated_by", "updated_at", "id", "deleted_at", "remarks"],
    "tbl_operation_trans" => ["created_at", "created_by", "deleted_at", "updated_at", "updated_by"],
    "tbl_billing_list" => ["created_at", "created_by", "deleted_at", "updated_at", "updated_by"],
    "tbl_customer_group" => ['id', "created_at", "updated_at", 'deleted_at'],
    "tbl_payment_terms" => ['id', "created_at", "updated_at", "deleted_at"],
    'system_users' => ['last_login', 'max_tokens', 'deleted_at', 'created_at', 'updated_at'],
    'tbl_branch' => ['created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'],
    'tbl_department' => ['created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'],
    'tbl_widget' => ['created_at', 'updated_at', 'deleted_at'],
    'tbl_transaction_settings' => ['created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'],
    'tbl_operations' => ['created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'],
    'tbl_operations_relationship' => ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'],
    'tbl_subsidiary_ledger' => ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'],
    'tbl_subsidiary_relationship' => ['id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'],
    'tbl_billing_transaction_map' => ['id', 'created_at', 'updated_at', 'deleted_at'],
    'tbl_templates' => ['created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'],
    'tbl_diva_account_mapping' => ['created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'id'],
    'tbl_reference_nums' => ['created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'id'],
    'general_options' => ['id' , 'updated_at','created_at']
];

const HIDDEN_REQUEST_FIELDS = [
    'tbl_entities' => ['details', 'address'],
    'tbl_branch' => ['details'],
    'tbl_department' => ['details'],
    'tbl_transaction_settings' => ['details'],
    'tbl_operations' => ['details'],
    'tbl_templates' => ['details'],
    'tbl_journal' => ['entries'],
    'tbl_chart_of_accounts' => ['mapping'],
    'tbl_reference_nums' => ['details']
];

function DBActions($action): string
{
    $actions = [
        'add' => 'dbInsert',
        'update' => 'dbUpdate',
        'get' => 'dbGet',
        'all' => 'dbGetAll',
        'list' => 'dbGetAll',
        'delete' => 'dbDelete',
    ];
    return $actions[$action];
}

function filterResponseFields($table): array
{
    return HIDDEN_RESPONSE_FIELDS[$table];
}

function filterRequestFields($table): array
{
    return HIDDEN_REQUEST_FIELDS[$table];
}

function formatAccountingCurrency($value): string
{
    if ($value < 0) $value = '(' . number_format(abs($value), 2, '.', ',') . ')';
    else $value = number_format($value, 2, '.', ',');
    return $value;
}


