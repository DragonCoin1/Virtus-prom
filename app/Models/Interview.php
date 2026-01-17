<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Interview extends Model
{
    protected $table = 'interviews';
    protected $primaryKey = 'interview_id';

    protected $fillable = [
        'interview_date',
        'candidate_name',
        'candidate_phone',
        'source',
        'status',
        'comment',
        'created_by',
    ];

    protected $casts = [
        'interview_date' => 'date',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (Schema::hasColumn($this->getTable(), 'interview_time')) {
            $this->fillable[] = 'interview_time';
            $this->casts['interview_time'] = 'string';
        }
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
