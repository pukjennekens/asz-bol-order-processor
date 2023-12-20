<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BolAccount extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'client_id',
        'client_secret',
    ];

    /**
     * Encrypt the client_id before storing it in the database.
     * @param $value
     * @return void
     */
    public function setClientIdAttribute($value)
    {
        $this->attributes['client_id'] = encrypt($value);
    }

    /**
     * Decrypt the client_id when retrieving it from the database.
     * @param $value
     * @return string
     */
    public function getClientIdAttribute($value)
    {
        return decrypt($value);
    }

    /**
     * Encrypt the client_secret before storing it in the database.
     * @param $value
     * @return void
     */
    public function setClientSecretAttribute($value)
    {
        $this->attributes['client_secret'] = encrypt($value);
    }

    /**
     * Decrypt the client_secret when retrieving it from the database.
     * @param $value
     * @return string
     */
    public function getClientSecretAttribute($value)
    {
        return decrypt($value);
    }
}
