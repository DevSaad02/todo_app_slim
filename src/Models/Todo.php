<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $table = 'todo_list';

    protected $fillable = [
        'id',
        'description',
        'is_done',
        'item_position',
        'list_color'
    ];

    public $timestamps = false;
}