<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class MultiStmtUlidModel extends Model
{
    use HasFactory, Notifiable;
    use HasUlids;

    protected $fillable = [
        'name',
    ];
}
