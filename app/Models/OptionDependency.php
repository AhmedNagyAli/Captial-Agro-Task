<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OptionDependency extends Pivot
{
    use HasFactory;

    protected $table = 'option_dependencies';

    protected $fillable = [
        'option_id',
        'depends_on_option_id',
    ];
}