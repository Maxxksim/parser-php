<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable('answer', 'number_characters')]
class Answer extends Model
{
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }
}