<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $fillable = ['novel_id', 'chapter_number', 'title', 'content'];

    public function novel()
    {
        return $this->belongsTo(Novel::class);
    }
}