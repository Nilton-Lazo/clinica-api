<?php

namespace App\Core\audit;

use Illuminate\Database\Eloquent\Model;

abstract class AuditableModel extends Model
{
    use Auditable;
}
