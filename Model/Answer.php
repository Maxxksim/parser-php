<?php

declare(strict_types=1);

namespace App\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable('answer', 'length')]
class Answer extends Model
{
    /**
     * Defines the many-to-many relationship between this model and question model.
     *
     * @return BelongsToMany<Answer>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }
}
