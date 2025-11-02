<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentationPage extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'order'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
