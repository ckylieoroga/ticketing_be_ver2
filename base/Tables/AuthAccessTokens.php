<?php

namespace Base\Tables;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthAccessTokens extends Model
{
    use HasFactory;
    protected $table = 'oauth_access_tokens';
}
