<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable('question')]
class Question extends Model
{
    public function answers(): BelongsToMany
    {
        return $this->belongsToMany(Answer::class);
    }
}