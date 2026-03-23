<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\Lead;
use App\Models\User;

class DashboardController
{
    private Database $db;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        Auth::requireAuth();

        $pipeline = Lead::pipelineCounts($this->db);
        $recentLeads = Lead::recent($this->db, 10);
        $sources = Lead::sourceCounts($this->db);
        $conversionRate = Lead::conversionRate($this->db);

        // Rep performance (all reps)
        $reps = User::activeReps($this->db);
        $repPerformance = [];
        foreach ($reps as $rep) {
            $repPerformance[] = array_merge($rep, User::repStats($this->db, $rep['id']));
        }

        // Sort by conversion rate descending
        usort($repPerformance, fn($a, $b) => $b['conversion_rate'] <=> $a['conversion_rate']);

        $totalLeads = array_sum($pipeline);

        require __DIR__ . '/../../templates/dashboard.php';
    }
}
