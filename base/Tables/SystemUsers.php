<?php

namespace Base\Tables;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemUsers extends Model
{
    use HasFactory;
    protected $table = 'system_users';

    protected $hidden = ['secret_key','status','deleted_at','created_at','updated_at'];
}
