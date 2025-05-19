<?php

// app/Models/OffreScore.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffreScore extends Model
{
    use HasFactory;

    protected $table = 'offre_score';

    protected $fillable = [
        'offre_id',
        'candidat_id',
        'score',
    ];

    public function offre()
    {
        return $this->belongsTo(Offre::class);
    }

    public function candidat()
    {
        return $this->belongsTo(Candidat::class);
    }
}