<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $table = 'interviews';
    protected $primaryKey = 'interview_id';

    protected $fillable = [
        'interview_date',
        'interview_time',
        'candidate_name',
        'candidate_phone',
        'source',
        'status',
        'comment',
        'created_by',
    ];

    protected $casts = [
        'interview_date' => 'date',
        'interview_time' => 'string',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
