<?php

namespace Lukeraymonddowning\Poser\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
