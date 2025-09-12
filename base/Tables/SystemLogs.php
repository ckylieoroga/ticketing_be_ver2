<?php

namespace Base\Tables;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLogs extends Model
{
    use HasFactory;
    protected $connection = 'sys_base';
    protected $table = 'system_logs';
    protected $fillable = ['user_id','endpoint','request','response'];

    public function user() {
        return $this->belongsTo(SystemUsers::class, 'user_id', 'id');
    }
}
