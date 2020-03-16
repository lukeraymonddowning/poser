<?php

namespace Lukeraymonddowning\Poser\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
