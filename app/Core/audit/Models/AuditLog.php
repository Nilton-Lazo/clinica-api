<?php

namespace App\Core\audit\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
