<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Revolution\Bluesky\Casts\AtBytesObject;

class Label extends Model
{
    protected $fillable = [
        'src',
        'uri',
        'cid',
        'val',
        'neg',
        'cts',
        'exp',
        'sig',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'neg' => 'boolean',
            'cts' => 'datetime',
            'exp' => 'datetime',
            'sig' => AtBytesObject::class,
        ];
    }
}
