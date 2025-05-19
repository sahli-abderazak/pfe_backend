<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="Contact",
 *     required={"id", "nom", "email", "sujet", "message"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Jean Dupont"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
 *     @OA\Property(property="sujet", type="string", example="Problème technique"),
 *     @OA\Property(property="message", type="string", example="J'ai un problème avec mon compte.")
 * )
 */

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'email', 'sujet', 'message'];
}

