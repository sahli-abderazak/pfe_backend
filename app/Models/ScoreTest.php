<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreTest extends Model
{
    use HasFactory;

    protected $table = 'scores_tests';

    protected $fillable = [
        'candidat_id',
        'offre_id',
        'score_total',
        'status',
        'ouverture',
        'conscience',
        'extraversion',
        'agreabilite',
        'stabilite',
    ];

    // Relations
    public function candidat()
    {
        return $this->belongsTo(Candidat::class);
    }

    public function offre()
    {
        return $this->belongsTo(Offre::class);
    }
}
