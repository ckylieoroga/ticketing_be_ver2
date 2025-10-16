<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrailController 
{

    public ?string $status;
    public ?int $is_internal;
    public ?string $remarks;

    protected ?string $code;
    protected ?string $assignee;
    protected ?int $sort;

    private ?string $table = "tbl_ticket_trail";
    public function __construct($code,$assignee,$status,$is_internal = true,$remarks = '') {
        $this->code = $code;
        $this->assignee = $assignee;
        $this->status = $status;
        $this->remarks = $remarks;
        $this->is_internal = $is_internal;
    }
    public function executeTrail(): void{
        try {
            if(!$this->assginee) throw new Exception("Assigned to is required", 401);
            $this->query();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
    private function query(){
        return DB::table($this->table)
                ->where('ticket_code',$this->code)
                ->insert($this->generateParams());
    }
    private function generateParams(): array {
        return [
             'ticket_code' => $this->code,
            'assignee' => $this->assignee,
            'status' => $this->status,
            'sort' => $this->getTrailCount(),
            'remarks' => $this->remarks,
            'is_internal' => $this->is_internal,
        ];
    }
    private function getTrailCount(): int{
        return DB::table($this->table)->where('ticket_code',$this->code)->count() + 1;
    }
    
}