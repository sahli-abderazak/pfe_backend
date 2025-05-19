<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Models\Candidat;
use App\Models\Notification;
use App\Models\Offre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class InterviewController extends Controller
{
  public function scheduleInterview(Request $request)
{
    try {
        $request->validate([
            'candidat_id' => 'required|exists:candidats,id',
            'offre_id' => 'required|exists:offres,id',
            'date_heure' => 'required|date',
            'candidat_email' => 'required|email',
            'candidat_nom' => 'required|string',
            'candidat_prenom' => 'required|string',
            'poste' => 'required|string',
            'type' => 'required|in:en ligne,présentiel',
            'lien_ou_adresse' => 'nullable|string'
        ]);

        $recruteur = Auth::user();

        if (!$recruteur) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        // Chercher un entretien existant pour ce candidat et cette offre
        $interview = Interview::where('candidat_id', $request->candidat_id)
            ->where('offre_id', $request->offre_id)
            ->first();

        if ($interview) {
            if (in_array($interview->status, ['pending', 'completed'])) {
                return response()->json([
                    'error' => 'Ce candidat a déjà un entretien programmé pour cette offre.'
                ], 409);
            }

            // Cas où l'entretien est annulé => on met à jour la même ligne
            $interview->update([
                'recruteur_id' => $recruteur->id,
                'date_heure' => $request->date_heure,
                'candidat_nom' => $request->candidat_nom,
                'candidat_prenom' => $request->candidat_prenom,
                'candidat_email' => $request->candidat_email,
                'poste' => $request->poste,
                'type' => $request->type,
                'lien_ou_adresse' => $request->type === 'en ligne' 
                    ? $request->lien_ou_adresse 
                    : ($request->lien_ou_adresse ?? $recruteur->adresse),
                'status' => 'pending'
            ]);

            $this->sendInterviewEmail($interview, $recruteur);

            return response()->json([
                'message' => 'Entretien reprogrammé avec succès',
                'interview' => $interview
            ], 200);
        }

        // Aucun entretien trouvé => créer un nouveau
        $interview = Interview::create([
            'candidat_id' => $request->candidat_id,
            'offre_id' => $request->offre_id,
            'recruteur_id' => $recruteur->id,
            'date_heure' => $request->date_heure,
            'candidat_nom' => $request->candidat_nom,
            'candidat_prenom' => $request->candidat_prenom,
            'candidat_email' => $request->candidat_email,
            'poste' => $request->poste,
            'type' => $request->type,
            'lien_ou_adresse' => $request->type === 'en ligne' 
                ? $request->lien_ou_adresse 
                : ($request->lien_ou_adresse ?? $recruteur->adresse),
            'status' => 'pending'
        ]);

        $this->sendInterviewEmail($interview, $recruteur);

        return response()->json([
            'message' => 'Entretien planifié avec succès',
            'interview' => $interview
        ], 201);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'Une erreur est survenue',
            'details' => $e->getMessage()
        ], 500);
    }
}

    


    public function getInterviews()
    {
        $recruteur = Auth::user();

        // Récupérer les entretiens du recruteur
        $interviews = Interview::where('recruteur_id', $recruteur->id)
            ->orderBy('date_heure', 'asc')
            ->get();

        return response()->json($interviews);
    }

    private function sendInterviewEmail($interview, $recruteur)
    {
        try {
            $subject = 'Invitation à un entretien pour le poste de ' . $interview->poste;

            Mail::send('emails.raw', [
                'interview' => $interview,
                'recruteur' => $recruteur
            ], function ($mail) use ($interview, $recruteur, $subject) {
                $mail->to($interview->candidat_email)
                    ->subject($subject)
                    ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });

            $interview->email_sent = true;
            $interview->save();
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoi de l\'email d\'entretien : ' . $e->getMessage());
        }
    }
    public function getAvailableHours(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'offre_id' => 'required|exists:offres,id'
        ]);

        $date = $request->input('date');
        $offreId = $request->input('offre_id');

        // Création des créneaux de 15 minutes entre 08:00 et 18:00
        $start = Carbon::createFromFormat('Y-m-d H:i', "$date 08:00");
        $end = Carbon::createFromFormat('Y-m-d H:i', "$date 18:00");

        $timeSlots = [];
        while ($start < $end) {
            $timeSlots[] = $start->format('H:i');
            $start->addMinutes(15);
        }
        $recruteurId = Auth::id(); // Recruteur connecté
        // Récupération des entretiens du jour POUR CETTE OFFRE
        $interviews = Interview::whereDate('date_heure', $date)
            ->where('recruteur_id', $recruteurId)
            ->where('status', 'pending') 
            ->get();

        // Créneaux déjà réservés (en format 'H:i')
        $unavailable = $interviews->map(function ($interview) {
            return Carbon::parse($interview->date_heure)->format('H:i');
        })->toArray();

        // Créneaux disponibles = tous - ceux déjà pris
        $availableSlots = array_values(array_diff($timeSlots, $unavailable));

        return response()->json([
            'date' => $date,
            'offre_id' => $offreId,
            'available_hours' => $availableSlots
        ]);
    }


//

  // Nouvelle méthode pour annuler un entretien
  public function cancelInterview($id)
  {
      try {
          $interview = Interview::findOrFail($id);
          
          // Vérifier que l'utilisateur est bien le recruteur de cet entretien
          if (Auth::id() !== $interview->recruteur_id) {
              return response()->json(['error' => 'Non autorisé'], 403);
          }
          
          // Mettre à jour le statut de l'entretien
          $interview->status = 'cancelled';
          $interview->save();
          
          // Envoyer un email d'annulation
          $this->sendCancellationEmail($interview);
          
          return response()->json([
              'message' => 'Entretien annulé avec succès',
              'interview' => $interview
          ]);
      } catch (\Exception $e) {
          return response()->json([
              'error' => 'Une erreur est survenue',
              'details' => $e->getMessage()
          ], 500);
      }
  }
  
  // Nouvelle méthode pour marquer un entretien comme terminé
  public function completeInterview($id)
  {
      try {
          $interview = Interview::findOrFail($id);
          
          // Vérifier que l'utilisateur est bien le recruteur de cet entretien
          if (Auth::id() !== $interview->recruteur_id) {
              return response()->json(['error' => 'Non autorisé'], 403);
          }
          
          // Mettre à jour le statut de l'entretien
          $interview->status = 'completed';
          $interview->save();
          
          return response()->json([
              'message' => 'Entretien marqué comme terminé',
              'interview' => $interview
          ]);
      } catch (\Exception $e) {
          return response()->json([
              'error' => 'Une erreur est survenue',
              'details' => $e->getMessage()
          ], 500);
      }
  }
  
  // Nouvelle méthode pour replanifier un entretien
  public function rescheduleInterview(Request $request, $id)
  {
      try {
          $request->validate([
              'date_heure' => 'required|date',
          ]);
          
          $interview = Interview::findOrFail($id);
          
          // Vérifier que l'utilisateur est bien le recruteur de cet entretien
          if (Auth::id() !== $interview->recruteur_id) {
              return response()->json(['error' => 'Non autorisé'], 403);
          }
          
          // Sauvegarder l'ancienne date pour l'email
          $oldDateTime = $interview->date_heure;
          
          // Mettre à jour la date et l'heure
          $interview->date_heure = $request->date_heure;
          $interview->save();
          
          // Envoyer un email de notification pour le changement de date
          $this->sendReschedulingEmail($interview, $oldDateTime);
          
          return response()->json([
              'message' => 'Entretien replanifié avec succès',
              'interview' => $interview
          ]);
      } catch (\Exception $e) {
          return response()->json([
              'error' => 'Une erreur est survenue',
              'details' => $e->getMessage()
          ], 500);
      }
  }
  
  // Méthode pour envoyer un email d'annulation
  private function sendCancellationEmail($interview)
  {
      try {
          $recruteur = Auth::user();
          $subject = 'Annulation de votre entretien pour le poste de ' . $interview->poste;
          
          Mail::send('emails.interview-cancelled', [
              'interview' => $interview,
              'recruteur' => $recruteur
          ], function ($mail) use ($interview, $recruteur, $subject) {
              $mail->to($interview->candidat_email)
                  ->subject($subject)
                  ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
          });
      } catch (\Exception $e) {
          \Log::error('Erreur lors de l\'envoi de l\'email d\'annulation : ' . $e->getMessage());
      }
  }
  
  // Méthode pour envoyer un email de replanification
  private function sendReschedulingEmail($interview, $oldDateTime)
  {
      try {
          $recruteur = Auth::user();
          $subject = 'Modification de la date de votre entretien pour le poste de ' . $interview->poste;
          
          Mail::send('emails.interview-rescheduled', [
              'interview' => $interview,
              'recruteur' => $recruteur,
              'oldDateTime' => $oldDateTime
          ], function ($mail) use ($interview, $recruteur, $subject) {
              $mail->to($interview->candidat_email)
                  ->subject($subject)
                  ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
          });
      } catch (\Exception $e) {
          \Log::error('Erreur lors de l\'envoi de l\'email de replanification : ' . $e->getMessage());
      }
  }

  public function canScheduleInterview($idCandidat)
{
    // Vérifie s'il existe déjà un entretien à venir ou en attente pour ce candidat
    $hasInterview = Interview::where('candidat_id', $idCandidat)
        ->whereIn('status', ['pending', 'completed']) // selon les statuts que tu utilises
        ->exists();

    if ($hasInterview) {
        return response()->json([
            'can_schedule' => false,
            'message' => 'Ce candidat a déjà un entretien prévu.'
        ]);
    } else {
        return response()->json([
            'can_schedule' => true,
            'message' => 'Ce candidat peut passer un entretien.'
        ]);
    }
}

// public function envoyerNotificationEntretiens()
// {
//     $now = Carbon::now();
//     $target = $now->copy()->addHour();

//     // Chercher les entretiens exactement dans 1 heure
//     $interviews = Interview::where('email_sent', false)
//         ->whereBetween('date_heure', [$target->copy()->subMinutes(1), $target->copy()->addMinutes(1)])
//         ->get();

//     foreach ($interviews as $interview) {
//         // Créer une notification pour le recruteur
//         Notification::create([
//             'type' => 'entretien',
//             'message' => "Rappel : Entretien dans 1 heure pour le poste '{$interview->poste}' avec le candidat {$interview->candidat_nom} {$interview->candidat_prenom}.",
//             'read' => false,
//             'user_id' => $interview->recruteur_id,
//             'data' => [
//                 'interview_id' => $interview->id,
//                 'heure' => $interview->date_heure->format('H:i'),
//                 'date' => $interview->date_heure->format('Y-m-d'),
//             ],
//         ]);

//         // Envoi email au recruteur
//         // $recruteurEmail = $interview->recruteur->email ?? null;
//         // if ($recruteurEmail) {
//         //     Mail::raw(
//         //         "Bonjour,\n\n" .
//         //         "Ceci est un rappel : un entretien est prévu dans 1 heure.\n\n" .
//         //         " Poste : {$interview->poste}\n" .
//         //         " Date & Heure : {$interview->date_heure->format('Y-m-d H:i')}\n" .
//         //         " Candidat : {$interview->candidat_nom} {$interview->candidat_prenom} ({$interview->candidat_email})\n\n" .
//         //         "Merci.",
//         //         function ($message) use ($recruteurEmail) {
//         //             $message->to($recruteurEmail)
//         //                     ->subject(' Rappel : Entretien dans 1 heure');
//         //         }
//         //     );
//         // }

//         // Marquer l'email comme envoyé
//         // $interview->update(['email_sent' => true]);
//     }

//     return response()->json([
//         'message' => 'Notifications envoyées avec succès.',
//         'entretiens_notifiés' => $interviews->count()
//     ]);
// }


public function envoyerNotificationEntretiens()
{
    $now = Carbon::now();
    $target = $now->copy()->addMinute(); // Entretien prévu dans 1 minute

    // Fenêtre de tolérance de ±30 secondes autour de cette minute
    $interviews = Interview::where('email_sent', false)
        ->whereBetween('date_heure', [$target->copy()->subSeconds(30), $target->copy()->addSeconds(30)])
        ->get();

    foreach ($interviews as $interview) {
        Notification::create([
            'type' => 'entretien',
            'message' => "Rappel : Entretien dans 1 minute pour le poste '{$interview->poste}' avec le candidat {$interview->candidat_nom} {$interview->candidat_prenom}.",
            'read' => false,
            'user_id' => $interview->recruteur_id,
            'data' => [
                'interview_id' => $interview->id,
                'heure' => $interview->date_heure->format('H:i'),
                'date' => $interview->date_heure->format('Y-m-d'),
            ],
        ]);

        // Marquer comme notifié
        $interview->update(['email_sent' => true]);
    }

    return response()->json([
        'message' => 'Notifications envoyées avec succès.',
        'entretiens_notifiés' => $interviews->count()
    ]);
}


}
