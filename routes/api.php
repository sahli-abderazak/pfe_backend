<?php
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\MatchingScoreController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OffreScoreController;
use App\Http\Controllers\TemoignageController;
use App\Http\Controllers\TypingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\OffreController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
// Route pour la vérification du code de vérification
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCodeLogin']);



//mot de passe oublie
Route::post('/forgot-password', [AuthController::class, 'sendVerificationCode']);
Route::post('/verifyForgetCode', [AuthController::class, 'verifyCoderest']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


Route::middleware('auth:sanctum')->post('/update-password', [AuthController::class, 'updatePassword']);


Route::middleware('auth:sanctum')->get('users', [UserController::class, 'index']);

Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
Route::delete('users/{id}', [UserController::class, 'destroy']);
Route::middleware('auth:sanctum')->put('/user/update/{id}', [AuthController::class, 'updateAdmin']);


Route::middleware('auth:sanctum')->put('users/archive/{id}', [UserController::class, 'archiveUser']);

Route::middleware('auth:sanctum')->get('users/archived', [UserController::class, 'getArchivedUsers']);

Route::middleware('auth:sanctum')->get('/user/info', [UserController::class, 'getCurrentUserInfo']);
Route::middleware('auth:sanctum')->put('users/unarchive/{id}', [UserController::class, 'unarchiveUser']);
Route::middleware('auth:sanctum')->get('users/profile', [AuthController::class, 'showProfile']);
Route::middleware('auth:sanctum')->put('/user/updateRec/{id}', [AuthController::class, 'updateRec']);
Route::middleware('auth:sanctum')->put('/user/updatePassword/{id}', [AuthController::class, 'updatePassword']);
//contact
Route::post('/contacts', [ContactController::class, 'store']);
Route::middleware('auth:sanctum')->get('/showcontacts', [ContactController::class, 'index']);
Route::middleware('auth:sanctum')->delete('/deleteContact/{id}',[ContactController::class,'deleteContact']);
Route::middleware('auth:sanctum')->put('/markasreplied/{id}', [ContactController::class, 'markAsReplied']);

//temoingage
Route::middleware('auth:sanctum')->post('/temoiniage', [TemoignageController::class, 'store']);
Route::get('/temoignagesValides', [TemoignageController::class, 'showTemoin']);
Route::middleware('auth:sanctum')->get('/temoiniages_admin', [TemoignageController::class, 'getAllTemoiniages']);
Route::middleware('auth:sanctum')->put('temoiniages/valider/{id}', [TemoignageController::class, 'validerTemoiniage']);
Route::middleware('auth:sanctum')->delete('/temoignageSupp/{id}', [TemoignageController::class, 'deleteTemoignage']);




//offre
Route::middleware('auth:sanctum')->post('/addOffres', [OffreController::class, 'ajoutOffre']); // Ajouter une offre
Route::middleware('auth:sanctum')->get('/Alloffresnvalide', [OffreController::class, 'afficheOffreNValider']); // Afficher toutes les offres non validée
Route::middleware('auth:sanctum')->get('/AlloffresValide', [OffreController::class, 'afficheOffreValide']); // Afficher toutes les offres validée
Route::middleware('auth:sanctum')->get('/offres-societe', [OffreController::class, 'offresParSociete']);
Route::middleware('auth:sanctum')->put('/validerOffre/{id}', [OffreController::class, 'validerOffre']);
Route::middleware('auth:sanctum')->delete('/supprimerOffre/{id}', [OffreController::class, 'supprimerOffre']);
Route::middleware('auth:sanctum')->put('/offres-departement/{id}', [OffreController::class, 'modifierOffre']);
Route::middleware('auth:sanctum')->put('/prolonger-offre/{id}', [OffreController::class, 'prolongerOffre']);
Route::middleware('auth:sanctum')->get('/AlloffresExpiree', [OffreController::class, 'afficheOffreExpiree']); // Afficher toutes les offres expirées
Route::middleware('auth:sanctum')->get('/offres-recruteur-valides', [OffreController::class, 'offreValideRecruteur']);
Route::middleware('auth:sanctum')->get('/offres-expirees-societe', [OffreController::class, 'afficheOffreExpireeRec']);
Route::middleware('auth:sanctum')->get('/recherche-offre/{poste}', [OffreController::class, 'rechercheOffre']);
Route::get('/showMatchingScore/{candidat_id}', [MatchingScoreController::class, 'showMatchingScore']);




//offre-candidat
Route::get('/offres-candidat', [OffreController::class, 'afficherOffreCandidat']);
Route::get('/villes-domaines', [OffreController::class, 'afficheVillesEtDomainesDistincts']);
Route::post('/offresRecherche', [OffreController::class, 'rechercheOffresss']);
Route::post('/recherche-acceuil', [OffreController::class, 'rechercheAcceuil']);
Route::get('/departements-domaines', [OffreController::class, 'afficheDepartementsEtDomainesDistincts']);
Route::get('/offreDetail/{id}', [OffreController::class, 'showDetail']);
Route::get('/offres_domaine/{domaine}', [OffreController::class, 'getByDepartement']);
Route::get('/recherche-candidat', action: [CandidatController::class, 'rechercheCandidat']);

//affichage candidat par offre
Route::middleware('auth:sanctum')->get('/candidatsByOffre/{offre_id}', [CandidatController::class, 'getCandidatsByOffre']);

Route::middleware('auth:sanctum')->get('/candidatsByOffreStatus/{offre_id}', [CandidatController::class, 'getCandidatsByOffreStatus']);


//PostulerCandidat
Route::post('/candidatStore', [CandidatController::class, 'storeCandidat']);
Route::get('/recruteurs_acceuil', [UserController::class, 'recruteurAcceuil']);

//affichageCandidatOffre
Route::middleware('auth:sanctum')->get('/candidats-offre', [CandidatController::class, 'showcandidatOffre']);
//archiverCandidat
Route::middleware('auth:sanctum')->put('/candidats/archiver/{id}', [CandidatController::class, 'archiverCandidat']);
Route::middleware('auth:sanctum')->get('/candidats_archived_societe', [CandidatController::class, 'getArchivedCandidatesByCompany']);
Route::middleware('auth:sanctum')->put('/candidats_desarchiver/{id}', [CandidatController::class, 'desarchiverCandidat']);

//supprimerCandidat
Route::middleware('auth:sanctum')->delete('/candidatSupp/{id}', [CandidatController::class, 'deleteCandidat']);


//notif

Route::middleware('auth:sanctum')->get('/notifications', [NotificationController::class, 'index']);
Route::middleware('auth:sanctum')->patch('/notifications/{notification}', [NotificationController::class, 'markAsRead']);
Route::middleware('auth:sanctum')->patch('/notifications', [NotificationController::class, 'markAllAsRead']);


// Messages entre admin et recruteur
Route::middleware('auth:sanctum')->get('/contactable-users', [MessageController::class, 'getContactableUsers']);
Route::middleware('auth:sanctum')->get('/messages/{userId}', [MessageController::class, 'getMessages']);
Route::middleware('auth:sanctum')->post('/messages', [MessageController::class, 'sendMessage']);
Route::middleware('auth:sanctum')->patch('/messages/{messageId}/read', [MessageController::class, 'markAsRead']);
Route::middleware('auth:sanctum')->patch('/messages/read-all/{userId}', [MessageController::class, 'markAllAsRead']);
Route::middleware('auth:sanctum')->get('/messages/unread-counts', [MessageController::class, 'getUnreadCounts']);
Route::middleware('auth:sanctum')->get('/messages/unread-total', [MessageController::class, 'getUnreadTotal']);

Route::middleware('auth:sanctum')->get('/recherche-recruteur', action: [UserController::class, 'rechercheRecruteur']);
//recherche
Route::get('/candidats/offres/{email}', [CandidatController::class, 'offresParCandidat']);

Route::middleware('auth:sanctum')->get('/recherche-candidat-archive', action: [UserController::class, 'rechercheCandidatArchive']);
Route::middleware('auth:sanctum')->get('/recherche-candidat', action: [UserController::class, 'rechercheCandidat']);
Route::middleware('auth:sanctum')->get('/recruteurs-archives/recherche', [UserController::class, 'searchArchivedRecruiters']);

//model ai
use App\Http\Controllers\TestAIController;

Route::post('/generate-test', [TestAIController::class, 'generateTest']);
Route::post('/store-score', [TestAIController::class, 'storeScore']);
Route::post('/candidat-by-email', [CandidatController::class, 'getCandidatByEmail']);
Route::post('/generate-image-question', [TestAIController::class, 'generateImageQuestion']);
Route::post('/analyze-personality', [TestAIController::class, 'analyzePersonality']);
Route::post('/matching-score', [MatchingScoreController::class, 'calculateMatchingScore']);
Route::get('/test-responses/{candidat_id}/{offre_id}', [TestAIController::class, 'getTestResponses']);

//calendrie

Route::post('/schedule-interview', [InterviewController::class, 'scheduleInterview'])->middleware('auth:sanctum');
Route::get('/interviews', [InterviewController::class, 'getInterviews'])->middleware('auth:sanctum');
Route::get('/interview/available-hours', [InterviewController::class, 'getAvailableHours'])->middleware('auth:sanctum');

// Routes pour la gestion des entretiens
Route::put('/interviews/{id}/cancel', [InterviewController::class, 'cancelInterview'])->middleware('auth:sanctum');
Route::put('/interviews/{id}/complete', [InterviewController::class, 'completeInterview'])->middleware('auth:sanctum');
Route::put('/interviews/{id}/reschedule', [InterviewController::class, 'rescheduleInterview'])->middleware('auth:sanctum');
Route::get('/candidat/{id}/can-schedule', [InterviewController::class, 'canScheduleInterview'])->middleware('auth:sanctum');
Route::post('/offre-score', [OffreScoreController::class, 'store']);
Route::put('/update-offre-score', [OffreScoreController::class, 'update']);


use App\Http\Controllers\DashboardController;

Route::middleware('auth:sanctum')->group(function () {
    // Routes pour le dashboard admin
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getAdminStats']);
        Route::get('/candidats-par-departement', [DashboardController::class, 'getCandidatsParDepartement']);
        Route::get('/candidats-par-mois', [DashboardController::class, 'getCandidatsParMois']);
        Route::get('/offres-par-departement', [DashboardController::class, 'getOffresParDepartement']);
        Route::get('/entretiens-par-statut', [DashboardController::class, 'getEntretiensParStatut']);
        Route::get('/candidats-par-niveau', [DashboardController::class, 'getCandidatsParNiveau']);
      
    });
    
    // Routes pour le dashboard recruteur
    Route::prefix('recruteur')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getRecruteurStats']);
        Route::get('/mes-offres', [DashboardController::class, 'getMesOffres']);
        Route::get('/mes-entretiens', [DashboardController::class, 'getMesEntretiens']);
        Route::get('/candidats-par-offre', [DashboardController::class, 'getCandidatsParOffre']);
        Route::get('/entretiens-par-jour', [DashboardController::class, 'getEntretiensParJour']);
        Route::get('/candidats-par-departementRec', [DashboardController::class, 'getCandidatsParDepartementRec']);
        Route::get('/candidats-par-moisRec', [DashboardController::class, 'getCandidatsParMoisRec']);
        Route::get('/offres-par-departementRec', [DashboardController::class, 'getOffresParDepartementRec']);
        Route::get('/entretiens-par-statutRec', [DashboardController::class, 'getEntretiensParStatutRec']);
        Route::get('/candidats-par-niveauRec', [DashboardController::class, 'getCandidatsParNiveauRec']);
        Route::get('/candidats-par-niveauExpRec', [DashboardController::class, 'getCandidatsParNiveauExpRec']);
        Route::get('/candidats-par-poste', [DashboardController::class, 'getCandidatsParPoste']);
        Route::get('/stats-chart', [DashboardController::class, 'getRecruteurStatsChart']);

    });
});