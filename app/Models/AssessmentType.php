<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
