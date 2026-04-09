<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OptionExclusion extends Pivot
{
    use HasFactory;

    protected $table = 'option_exclusions';

    protected $fillable = [
        'option_id',
        'excluded_option_id',
    ];
}
