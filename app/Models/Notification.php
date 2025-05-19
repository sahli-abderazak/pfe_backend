<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 *     @OA\Schema(
 *         schema="Notification",
 *         type="object",
 *         required={"id", "type", "message", "read", "created_at", "user_id"},
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="type", type="string", example="new_contact"),
 *         @OA\Property(property="message", type="string", example="Nouveau message de contact: Problème technique"),
 *         @OA\Property(property="read", type="boolean", example=false),
 *         @OA\Property(property="data", type="object", additionalProperties=true, example={"key": "value"}),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-19T12:34:56"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-19T12:34:56"),
 *         @OA\Property(property="user_id", type="integer", example=1)
 * )
 */

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'message',
        'data',
        'read',
        'user_id'
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

