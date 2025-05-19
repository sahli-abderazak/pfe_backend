<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temoignage extends Model
{
    use HasFactory;

    // Optionnellement, définis le nom de la table si ce n'est pas le nom par défaut 'temoignages'
    protected $table = 'temoignages';

    // Si tu ne veux pas que Laravel gère les timestamps automatiquement (created_at, updated_at)
    public $timestamps = true;

    // Si tu veux autoriser la masse d'attributs, définis les champs remplissables
    protected $fillable = ['nom', 'email', 'temoignage','valider','rate'];
}
