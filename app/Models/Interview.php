<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'candidat_id',
        'offre_id',
        'recruteur_id',
        'date_heure',
        'candidat_nom',
        'candidat_prenom',
        'candidat_email',
        'poste',
        'email_sent',
        'type',
    'lien_ou_adresse',
    'status'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'date_heure' => 'datetime',
        'email_sent' => 'boolean',
    ];

    /**
     * Relation avec le modèle Candidat
     */
    public function candidat(): BelongsTo
    {
        return $this->belongsTo(Candidat::class);
    }

    /**
     * Relation avec le modèle Offre
     */
    public function offre(): BelongsTo
    {
        return $this->belongsTo(Offre::class);
    }

    /**
     * Relation avec le modèle User (recruteur)
     */
    public function recruteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruteur_id');
    }
}