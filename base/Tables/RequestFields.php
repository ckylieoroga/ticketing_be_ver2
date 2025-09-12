<?php

namespace Base\Tables;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestFields extends Model
{
    use HasFactory;
    protected $connection = 'sys_base';
    protected $table = 'request_fields';
}
