<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *     @OA\Property(property="numTel", type="string", example="0123456789"),
 *     @OA\Property(property="adresse", type="string", example="123 Main St"),
 *     @OA\Property(property="role", type="string", example="admin"),
 *     @OA\Property(property="image", type="string", example="profile.jpg"),
 *     @OA\Property(property="archived", type="boolean", example=false),
 *     @OA\Property(property="nom_societe", type="string", example="Example Company"),
 *     @OA\Property(property="active", type="boolean", example=true),
 *     @OA\Property(property="apropos", type="string", example="About the company"),
 *     @OA\Property(property="lien_site_web", type="string", example="https://example.com"),
 *     @OA\Property(property="fax", type="string", example="0123456789"),
 *     @OA\Property(property="domaine_activite", type="string", example="Technology"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'numTel',
        'adresse',
        'role',
        'image',
        'archived',
        'nom_societe',
        'active',
        'code_verification',
        "apropos",
        "lien_site_web",
        "fax",
        "domaine_activite",
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function notifications()
{
    return $this->hasMany(Notification::class);
}


}
