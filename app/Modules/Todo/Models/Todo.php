<?php
// app/Models/Todo.php

namespace App\Modules\Todo\Models;

use App\Models\Company;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    use Tenantable;

    protected $fillable = [
        'title',
        'description',
        'completed',
        'completed_at',
        'category_id',
        'company_id'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Modules\Category\Models\Category::class);
    }
}
