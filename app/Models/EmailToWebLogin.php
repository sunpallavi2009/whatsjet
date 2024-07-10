<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailToWebLogin extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'email_to_web_login';
}
