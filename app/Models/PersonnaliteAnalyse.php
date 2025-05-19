<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnaliteAnalyse extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidat_id',
        'offre_id',
        'personnalite',
    ];

    // Si nécessaire, ajouter des relations avec les tables candidats et offres
    public function candidat()
    {
        return $this->belongsTo(Candidat::class);
    }

    public function offre()
    {
        return $this->belongsTo(Offre::class);
    }
}
