<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $guarded = [];

    protected $dates = [ 'datetime' ];
}
