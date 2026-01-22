<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
    protected $table = 'instructions';
    protected $primaryKey = 'instruction_id';

    protected $fillable = [
        'title',
        'body',
        'created_by',
        'is_active',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
