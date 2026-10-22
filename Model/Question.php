<?php

declare(strict_types=1);

namespace App\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable('question')]
class Question extends Model
{
    /**
     * Defines the many-to-many relationship between this model and answer model.
     *
     * @return BelongsToMany<Answer>
     */
    public function answers(): BelongsToMany
    {
        return $this->belongsToMany(Answer::class);
    }
}
