<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


/**
 * @OA\Schema(
 *     schema="Candidat",
 *     required={"nom", "prenom", "email"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenom", type="string", example="John"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="pays", type="string", example="France"),
 *     @OA\Property(property="ville", type="string", example="Paris"),
 *     @OA\Property(property="codePostal", type="string", example="75001"),
 *     @OA\Property(property="tel", type="string", example="0102030405"),
 *     @OA\Property(property="niveauEtude", type="string", example="Bac+5"),
 *     @OA\Property(property="niveauExperience", type="string", example="3 ans"),
 *     @OA\Property(property="cv", type="string", example="https://example.com/cvs/cv.pdf"),
 *     @OA\Property(property="offre_id", type="integer", example=1),
 *     @OA\Property(property="archived", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Candidat extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 'prenom', 'email', 'pays', 'ville', 'codePostal', 'niveauExperience', 'tel', 'niveauEtude', 'cv', 'offre_id'
    ];


    /**
     * Relation avec l'offre
     */
    public function offre()
    {
        return $this->belongsTo(Offre::class);
    }
    public function scoreTest()
{
    return $this->hasOne(ScoreTest::class);
}

}
