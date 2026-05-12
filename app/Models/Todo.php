<?php

// app/Models/Todo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $fillable = [
        'title',
        'description',
        'completed',
        'completed_at',
        'category_id',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }
}
