<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="Offre",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="departement", type="string"),
 *     @OA\Property(property="poste", type="string"),
 *     @OA\Property(property="datePublication", type="string", format="date"),
 *     @OA\Property(property="dateExpiration", type="string", format="date"),
 *     @OA\Property(property="valider", type="boolean")
 * )
 */

class Offre extends Model
{
    use HasFactory;

    protected $fillable = [
        'departement',
        'poste',
        'description',
        'datePublication',
        'dateExpiration',
        'valider',
        'typePoste',
        'typeTravail',
        'heureTravail',
        'niveauExperience',
        'niveauEtude',
        'pays',
        'ville',
        'societe',
        'domaine',
        'responsabilite',
        'experience',
        'matching',
        'poids_ouverture',
        'poids_conscience',
        'poids_extraversion',
        'poids_agreabilite',
        'poids_stabilite',
    ];


    public function candidats()
{
    return $this->hasMany(\App\Models\Candidat::class, 'offre_id');
}
}
