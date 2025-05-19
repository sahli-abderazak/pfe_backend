<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Candidat;
use App\Models\Offre;
use App\Models\Interview;
use App\Models\User;

class DashboardController extends Controller
{
    // Stats pour Admin
public function getAdminStats(Request $request)
{
    $period = $request->query('period', 'week');
    
    $totalCandidats = Candidat::count();
    $totalOffres = Offre::count();
    $totalEntretiens = Interview::count();
    $totalRecruteurs = User::where('role', 'recruteur')->count();
    
    // Tendances selon la période sélectionnée
    $candidatsTendance = $this->getTendance('candidats', $period);
    $offresTendance = $this->getTendance('offres', $period);
    $entretiensTendance = $this->getTendance('interviews', $period);
    
    return response()->json([
        'totalCandidats' => $totalCandidats,
        'totalOffres' => $totalOffres,
        'totalEntretiens' => $totalEntretiens,
        'totalRecruteurs' => $totalRecruteurs,
        'candidatsTendance' => $candidatsTendance,
        'offresTendance' => $offresTendance,
        'entretiensTendance' => $entretiensTendance
    ]);
}

private function getTendance($table, $period = 'week')
{
    switch ($period) {
        case 'week':
            $startDate = Carbon::now()->subDays(7);
            $endDate = Carbon::now();
            break;
        case 'month':
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            break;
        case 'year':
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now()->endOfYear();
            break;
        default:
            $startDate = Carbon::now()->subDays(7);
            $endDate = Carbon::now();
    }
    
    $query = DB::table($table)
        ->whereBetween('created_at', [$startDate, $endDate]);
    
    if ($period === 'year') {
        $query->select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mois'),
            DB::raw('count(*) as total')
        )
        ->groupBy('mois');
        
        $data = $query->get()->map(function ($item) {
            $mois = [
                '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', 
                '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Août', 
                '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'
            ];
            
            $parts = explode('-', $item->mois);
            $monthKey = $parts[1];
            
            return [
                'name' => $mois[$monthKey],
                'value' => $item->total
            ];
        });
        
        // Assurer que tous les mois sont présents
        $allMonths = collect([
            ['name' => 'Jan', 'value' => 0], ['name' => 'Fév', 'value' => 0],
            ['name' => 'Mar', 'value' => 0], ['name' => 'Avr', 'value' => 0],
            ['name' => 'Mai', 'value' => 0], ['name' => 'Juin', 'value' => 0],
            ['name' => 'Juil', 'value' => 0], ['name' => 'Août', 'value' => 0],
            ['name' => 'Sep', 'value' => 0], ['name' => 'Oct', 'value' => 0],
            ['name' => 'Nov', 'value' => 0], ['name' => 'Déc', 'value' => 0]
        ]);
        
        $dataByName = $data->keyBy('name');
        
        $data = $allMonths->map(function ($month) use ($dataByName) {
            if ($dataByName->has($month['name'])) {
                return $dataByName->get($month['name']);
            }
            return $month;
        });
    } else if ($period === 'month') {
        $query->select(
            DB::raw('DAY(created_at) as jour'),
            DB::raw('count(*) as total')
        )
        ->groupBy('jour');
        
        $data = $query->get()->map(function ($item) {
            return [
                'name' => sprintf('%02d', $item->jour),
                'value' => $item->total
            ];
        });
        
        // Assurer que tous les jours du mois sont présents
        $daysInMonth = Carbon::now()->daysInMonth;
        $allDays = collect(range(1, $daysInMonth))->map(function ($day) {
            return ['name' => sprintf('%02d', $day), 'value' => 0];
        });
        
        $dataByName = $data->keyBy('name');
        
        $data = $allDays->map(function ($day) use ($dataByName) {
            if ($dataByName->has($day['name'])) {
                return $dataByName->get($day['name']);
            }
            return $day;
        });
    } else {
        // Semaine (comportement par défaut)
        $query->select(
            DB::raw('DATE(created_at) as jour'),
            DB::raw('count(*) as total')
        )
        ->groupBy('jour');
        
        $data = $query->get()->map(function ($item) {
            $joursSemaine = [
                0 => 'Dim', 1 => 'Lun', 2 => 'Mar', 3 => 'Mer',
                4 => 'Jeu', 5 => 'Ven', 6 => 'Sam'
            ];
            
            $date = Carbon::parse($item->jour);
            
            return [
                'name' => $joursSemaine[$date->dayOfWeek],
                'value' => $item->total
            ];
        });
        
        // Assurer que tous les jours de la semaine sont présents
        $allDays = collect([
            ['name' => 'Lun', 'value' => 0], ['name' => 'Mar', 'value' => 0],
            ['name' => 'Mer', 'value' => 0], ['name' => 'Jeu', 'value' => 0],
            ['name' => 'Ven', 'value' => 0], ['name' => 'Sam', 'value' => 0],
            ['name' => 'Dim', 'value' => 0]
        ]);
        
        $dataByName = $data->keyBy('name');
        
        $data = $allDays->map(function ($day) use ($dataByName) {
            if ($dataByName->has($day['name'])) {
                return $dataByName->get($day['name']);
            }
            return $day;
        });
    }
    
    return $data->values();
}
    
    public function getCandidatsParDepartement()
    {
        $data = DB::table('candidats')
            ->join('offres', 'candidats.offre_id', '=', 'offres.id')
            ->select('offres.departement', DB::raw('count(*) as total'))
            ->groupBy('offres.departement')
            ->get();
            
        return response()->json($data);
    }
    
    public function getCandidatsParMois()
    {
        $data = DB::table('candidats')
            ->select(
                DB::raw('MONTH(created_at) as mois'),
                DB::raw('YEAR(created_at) as annee'),
                DB::raw('count(*) as total')
            )
            ->whereYear('created_at', date('Y'))
            ->groupBy('mois', 'annee')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get()
            ->map(function ($item) {
                $moisNoms = [
                    1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
                    5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
                    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
                ];
                
                return [
                    'name' => $moisNoms[$item->mois],
                    'Candidats' => $item->total
                ];
            });
            
        return response()->json($data);
    }
    
    public function getOffresParDepartement()
    {
        $data = DB::table('offres')
            ->select('departement', DB::raw('count(*) as total'))
            ->groupBy('departement')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->departement,
                    'value' => $item->total
                ];
            });
            
        return response()->json($data);
    }
    
    public function getEntretiensParStatut()
    {
        $data = DB::table('interviews')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->status,
                    'value' => $item->total
                ];
            });
            
        return response()->json($data);
    }
    
    public function getCandidatsParNiveau()
    {
        $data = DB::table('candidats')
            ->select('niveauEtude', DB::raw('count(*) as total'))
            ->groupBy('niveauEtude')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->niveauEtude,
                    'value' => $item->total
                ];
            });
            
        return response()->json($data);
    }
           public function getCandidatsParNiveauExpRec(Request $request)
    {
        $societe = $request->user()->nom_societe;
    
        $data = DB::table('candidats')
            ->join('offres', 'candidats.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->select('candidats.niveauExperience', DB::raw('count(*) as total'))
            ->groupBy('candidats.niveauExperience')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->niveauExperience,
                    'value' => $item->total
                ];
            });
    
        return response()->json($data);
    }

     // Stats pour Recruteur
        public function getCandidatsParDepartementRec(Request $request)
        {
            $societe = $request->user()->nom_societe;
        
            $data = DB::table('candidats')
                ->join('offres', 'candidats.offre_id', '=', 'offres.id')
                ->where('offres.societe', $societe)
                ->select('offres.departement', DB::raw('count(*) as total'))
                ->groupBy('offres.departement')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->departement,
                        'value' => $item->total
                    ];
                });
        
            return response()->json($data);
        }
    
        public function getCandidatsParMoisRec(Request $request)
        {
            $societe = $request->user()->nom_societe;
        
            $data = DB::table('candidats')
                ->join('offres', 'candidats.offre_id', '=', 'offres.id')
                ->where('offres.societe', $societe)
                ->select(
                    DB::raw('MONTH(candidats.created_at) as mois'),
                    DB::raw('YEAR(candidats.created_at) as annee'),
                    DB::raw('count(*) as total')
                )
                ->whereYear('candidats.created_at', date('Y'))
                ->groupBy('mois', 'annee')
                ->orderBy('annee')
                ->orderBy('mois')
                ->get()
                ->map(function ($item) {
                    $moisNoms = [
                        1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
                        5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
                        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
                    ];
                    return [
                        'name' => $moisNoms[$item->mois],
                        'Candidats' => $item->total
                    ];
                });
        
            return response()->json($data);
        }
    
        public function getOffresParDepartementRec(Request $request)
        {
            $societe = $request->user()->nom_societe;
        
            $data = DB::table('offres')
                ->where('societe', $societe)
                ->select('departement', DB::raw('count(*) as total'))
                ->groupBy('departement')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->departement,
                        'value' => $item->total
                    ];
                });
        
            return response()->json($data);
        }
    
    public function getEntretiensParStatutRec(Request $request)
    {
        $societe = $request->user()->id;
        $data = DB::table('interviews')
        ->where('recruteur_id', $societe)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->status,
                    'value' => $item->total
                ];
            });
            
        return response()->json($data);
    }
    
    public function getCandidatsParNiveauRec(Request $request)
    {
        $societe = $request->user()->nom_societe;
    
        $data = DB::table('candidats')
            ->join('offres', 'candidats.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->select('candidats.niveauEtude', DB::raw('count(*) as total'))
            ->groupBy('candidats.niveauEtude')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->niveauEtude,
                    'value' => $item->total
                ];
            });
    
        return response()->json($data);
    }
    
    // Stats pour Recruteur
    public function getRecruteurStats(Request $request)
    {
        $societe = $request->user()->nom_societe;
        $totalMesCandidats = DB::table('candidats')
            ->join('offres', 'candidats.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->count();
    
        $totalMesOffres = Offre::where('societe', $societe)->count();
    
        $totalMesEntretiens = DB::table('interviews')
            ->join('offres', 'interviews.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->count();
    
        $entretiensPending = DB::table('interviews')
            ->join('offres', 'interviews.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->where('interviews.status', 'pending')
            ->count();
    
        $candidatsTendance = $this->getTendanceRecruteurCandidats($societe);
        $entretiensTendance = $this->getTendanceRecruteurEntretiens($societe);
    
        return response()->json([
            'totalMesCandidats' => $totalMesCandidats,
            'totalMesOffres' => $totalMesOffres,
            'totalMesEntretiens' => $totalMesEntretiens,
            'entretiensPending' => $entretiensPending,
            'candidatsTendance' => $candidatsTendance,
            'entretiensTendance' => $entretiensTendance,
            'ste'=> $societe
        ]);
    }
    
    // Tendance candidats pour une société (7 derniers jours)
    private function getTendanceRecruteurCandidats($societe)
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();
    
        $data = DB::table('candidats')
            ->join('offres', 'candidats.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->whereBetween('candidats.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(candidats.created_at) as jour'),
                DB::raw('count(*) as total')
            )
            ->groupBy('jour')
            ->get();
    
        $joursSemaine = [
            0 => 'Dim', 1 => 'Lun', 2 => 'Mar', 3 => 'Mer',
            4 => 'Jeu', 5 => 'Ven', 6 => 'Sam'
        ];
    
        return $data->map(function ($item) use ($joursSemaine) {
            $date = Carbon::parse($item->jour);
            return [
                'name' => $joursSemaine[$date->dayOfWeek],
                'value' => $item->total
            ];
        });
    }
    
    // Tendance entretiens pour une société (7 derniers jours)
    private function getTendanceRecruteurEntretiens($societe)
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();
    
        $data = DB::table('interviews')
            ->join('offres', 'interviews.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->whereBetween('interviews.created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(interviews.created_at) as jour'),
                DB::raw('count(*) as total')
            )
            ->groupBy('jour')
            ->get();
    
        $joursSemaine = [
            0 => 'Dim', 1 => 'Lun', 2 => 'Mar', 3 => 'Mer',
            4 => 'Jeu', 5 => 'Ven', 6 => 'Sam'
        ];
    
        return $data->map(function ($item) use ($joursSemaine) {
            $date = Carbon::parse($item->jour);
            return [
                'name' => $joursSemaine[$date->dayOfWeek],
                'value' => $item->total
            ];
        });
    }
    
    public function getMesOffres(Request $request)
{

    $societe = $request->user()->nom_societe;

    $offres = Offre::where('societe', $societe)  ->withCount('candidats')
    ->get()
        ->map(function ($offre) {
            return [
                'id' => $offre->id,
                'poste' => $offre->poste,
                'nbrCandidat' => $offre->candidats_count,
                'expiration' => $offre->dateExpiration,
            ];
        });

    return response()->json($offres);
}

    
public function getMesEntretiens(Request $request)
{
    $entretiens = DB::table('interviews')
        ->join('candidats', 'interviews.candidat_id', '=', 'candidats.id')
        ->join('offres', 'interviews.offre_id', '=', 'offres.id')
        ->where('offres.societe', $request->user()->nom_societe)
        ->where('interviews.status', 'pending')
        ->where('interviews.date_heure', '>=', now())
        ->select(
            'interviews.id',
            'candidats.nom as candidat_nom',
            'candidats.prenom as candidat_prenom',
            'offres.poste as poste',
            'interviews.date_heure',
            'interviews.type',
            'interviews.lien_ou_adresse',
            'interviews.status'
        )
        ->orderBy('interviews.date_heure', 'asc')
        ->limit(3)
        ->get();

    return response()->json($entretiens);
}

    
    public function getCandidatsParOffre(Request $request)
    {
        $societe = $request->user()->nom_societe;
        
        $data = DB::table('candidats')
            ->join('offres', 'candidats.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->select('offres.poste', DB::raw('count(*) as total'))
            ->groupBy('offres.poste')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->poste,
                    'value' => $item->total
                ];
            });
            
        return response()->json($data);
    }
    public function getCandidatsParPoste(Request $request){
        $societe = $request->user()->nom_societe;
        
        $data = DB::table('candidats')
            ->join('offres', 'candidats.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->select('offres.poste', DB::raw('count(*) as total'))
            ->groupBy('offres.poste')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->poste,
                    'value' => $item->total
                ];
            });
            
        return response()->json($data);
    }
    
    public function getEntretiensParJour(Request $request)
    {
        $recruteurId = $request->user()->id;
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        
        $data = DB::table('interviews')
            ->where('recruteur_id', $recruteurId)
            ->whereBetween('date_heure', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(date_heure) as jour'),
                DB::raw('count(*) as total')
            )
            ->groupBy('jour')
            ->get()
            ->map(function ($item) {
                $joursSemaine = [
                    0 => 'Dim', 1 => 'Lun', 2 => 'Mar', 3 => 'Mer',
                    4 => 'Jeu', 5 => 'Ven', 6 => 'Sam'
                ];
                
                $date = Carbon::parse($item->jour);
                
                return [
                    'name' => $joursSemaine[$date->dayOfWeek],
                    'value' => $item->total
                ];
            });
            
        return response()->json($data);
    }
    



//stats pour candidat

public function getRecruteurStatsChart(Request $request)
{
    $period = $request->query('period', 'week');
    $societe = $request->user()->nom_societe;
    $annee = $request->query('annee'); // exemple: 2025

    // Totaux
    $totalCandidats = DB::table('candidats')
        ->join('offres', 'candidats.offre_id', '=', 'offres.id')
        ->where('offres.societe', $societe)
        ->when($annee, function ($query, $annee) {
            return $query->whereYear('candidats.created_at', $annee);
        })
        ->count();

    $totalOffres = DB::table('offres')
        ->where('societe', $societe)
        ->when($annee, function ($query, $annee) {
            return $query->whereYear('created_at', $annee);
        })
        ->count();

    // Tendances
    $candidatsTendance = $this->getTendanceSociete('candidats', $period, $societe, $annee);
    $offresTendance = $this->getTendanceSociete('offres', $period, $societe, $annee);

    return response()->json([
        'totalCandidats' => $totalCandidats,
        'totalOffres' => $totalOffres,
        'candidatsTendance' => $candidatsTendance,
        'offresTendance' => $offresTendance
    ]);
}

/**
 * Tendance filtrée par société et année pour candidats ou offres
 */
private function getTendanceSociete($table, $period, $societe, $annee = null)
{
    switch ($period) {
        case 'week':
            $startDate = Carbon::now()->subDays(7);
            $endDate = Carbon::now();
            break;
        case 'month':
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            break;
        case 'year':
            if ($annee) {
                $startDate = Carbon::create($annee, 1, 1);
                $endDate = Carbon::create($annee, 12, 31, 23, 59, 59);
            } else {
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
            }
            break;
        default:
            $startDate = Carbon::now()->subDays(7);
            $endDate = Carbon::now();
    }

    if ($table === 'candidats') {
        $query = DB::table('candidats')
            ->join('offres', 'candidats.offre_id', '=', 'offres.id')
            ->where('offres.societe', $societe)
            ->whereBetween('candidats.created_at', [$startDate, $endDate]);
        if ($annee) {
            $query->whereYear('candidats.created_at', $annee);
        }
        $createdField = 'candidats.created_at';
    } else {
        $query = DB::table('offres')
            ->where('societe', $societe)
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($annee) {
            $query->whereYear('created_at', $annee);
        }
        $createdField = 'created_at';
    }

    if ($period === 'year') {
        $query->select(
            DB::raw('DATE_FORMAT(' . $createdField . ', "%Y-%m") as mois'),
            DB::raw('count(*) as total')
        )->groupBy('mois');

        $data = $query->get()->map(function ($item) {
            $mois = [
                '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr',
                '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Août',
                '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'
            ];
            $parts = explode('-', $item->mois);
            $monthKey = $parts[1];
            return [
                'name' => $mois[$monthKey],
                'value' => $item->total
            ];
        });

        // Assurer que tous les mois sont présents
        $allMonths = collect([
            ['name' => 'Jan', 'value' => 0], ['name' => 'Fév', 'value' => 0],
            ['name' => 'Mar', 'value' => 0], ['name' => 'Avr', 'value' => 0],
            ['name' => 'Mai', 'value' => 0], ['name' => 'Juin', 'value' => 0],
            ['name' => 'Juil', 'value' => 0], ['name' => 'Août', 'value' => 0],
            ['name' => 'Sep', 'value' => 0], ['name' => 'Oct', 'value' => 0],
            ['name' => 'Nov', 'value' => 0], ['name' => 'Déc', 'value' => 0]
        ]);
        $dataByName = $data->keyBy('name');
        $data = $allMonths->map(function ($month) use ($dataByName) {
            return $dataByName->get($month['name'], $month);
        });
    } elseif ($period === 'month') {
        $query->select(
            DB::raw('DAY(' . $createdField . ') as jour'),
            DB::raw('count(*) as total')
        )->groupBy('jour');

        $data = $query->get()->map(function ($item) {
            return [
                'name' => sprintf('%02d', $item->jour),
                'value' => $item->total
            ];
        });

        $daysInMonth = Carbon::now()->daysInMonth;
        $allDays = collect(range(1, $daysInMonth))->map(function ($day) {
            return ['name' => sprintf('%02d', $day), 'value' => 0];
        });
        $dataByName = $data->keyBy('name');
        $data = $allDays->map(function ($day) use ($dataByName) {
            return $dataByName->get($day['name'], $day);
        });
    } else {
        // Semaine
        $query->select(
            DB::raw('DATE(' . $createdField . ') as jour'),
            DB::raw('count(*) as total')
        )->groupBy('jour');

        $data = $query->get()->map(function ($item) {
            $joursSemaine = [
                0 => 'Dim', 1 => 'Lun', 2 => 'Mar', 3 => 'Mer',
                4 => 'Jeu', 5 => 'Ven', 6 => 'Sam'
            ];
            $date = Carbon::parse($item->jour);
            return [
                'name' => $joursSemaine[$date->dayOfWeek],
                'value' => $item->total
            ];
        });

        $allDays = collect([
            ['name' => 'Lun', 'value' => 0], ['name' => 'Mar', 'value' => 0],
            ['name' => 'Mer', 'value' => 0], ['name' => 'Jeu', 'value' => 0],
            ['name' => 'Ven', 'value' => 0], ['name' => 'Sam', 'value' => 0],
            ['name' => 'Dim', 'value' => 0]
        ]);
        $dataByName = $data->keyBy('name');
        $data = $allDays->map(function ($day) use ($dataByName) {
            return $dataByName->get($day['name'], $day);
        });
    }

    return $data->values();
}
   
}