<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchingScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidat_id',
        'offre_id',
        'matching_score',
        'evaluation',
        'points_forts',
        'ecarts'
    ];

    public function candidat()
    {
        return $this->belongsTo(Candidat::class);
    }

    public function offre()
    {
        return $this->belongsTo(Offre::class);
    }
    protected $casts = [
        'points_forts' => 'array',
        'ecarts' => 'array',
    ];

}
