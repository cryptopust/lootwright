<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Analysis extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['*'];
}
