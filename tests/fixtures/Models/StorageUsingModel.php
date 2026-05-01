<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StorageUsingModel extends Model
{
    public function logoUrl(): string
    {
        return Storage::url('logos/'.$this->id);
    }
}
