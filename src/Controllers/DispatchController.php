<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\User;
use App\Middleware\CsrfMiddleware;

class DispatchController
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /** Admin dispatch board */
    public function board(): void
    {
        Auth::requireAdmin();

        $reps = User::activeReps($this->db);
        $options = Lead::getOptions($this->db);

        // Load default appointment duration from settings
        $defaultDuration = $this->db->fetchColumn(
            "SELECT value FROM settings WHERE key = :k",
            [':k' => 'default_appointment_duration']
        );
        $defaultDuration = $defaultDuration ? (int)$defaultDuration : 60;

        $pageTitle = 'Dispatch Board';
        ob_start();
        require __DIR__ . '/../../templates/dispatch/board.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/app.php';
    }

    /** Rep calendar view */
    public function myCalendar(): void
    {
        Auth::requireAuth();

        $options = Lead::getOptions($this->db);

        $defaultDuration = $this->db->fetchColumn(
            "SELECT value FROM settings WHERE key = :k",
            [':k' => 'default_appointment_duration']
        );
        $defaultDuration = $defaultDuration ? (int)$defaultDuration : 60;

        $pageTitle = 'My Calendar';
        ob_start();
        require __DIR__ . '/../../templates/dispatch/my_calendar.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/app.php';
    }

    /** AJAX: Get events for dispatch board */
    public function events(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $date = $_GET['date'] ?? date('Y-m-d');
        $view = $_GET['view'] ?? 'day';
        $repId = $_GET['rep_id'] ?? null;

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['error' => 'Invalid date format']);
            return;
        }

        if ($view === 'week') {
            // Get Monday of the week containing $date
            $dt = new \DateTime($date);
            $dayOfWeek = (int)$dt->format('N'); // 1=Mon, 7=Sun
            $dt->modify('-' . ($dayOfWeek - 1) . ' days');
            $weekStart = $dt->format('Y-m-d');
            $dt->modify('+6 days');
            $weekEnd = $dt->format('Y-m-d');

            $where = ['l.appointment_date >= :start', 'l.appointment_date <= :end'];
            $params = [':start' => $weekStart, ':end' => $weekEnd];
        } else {
            $where = ['l.appointment_date = :date'];
            $params = [':date' => $date];
        }

        // Filter by rep if specified (for rep view)
        if ($repId) {
            $where[] = 'l.assigned_to = :rep_id';
            $params[':rep_id'] = (int)$repId;
        }

        // Filter by status
        $statusFilter = $_GET['status'] ?? '';
        if ($statusFilter !== '') {
            $where[] = 'l.status = :status_filter';
            $params[':status_filter'] = $statusFilter;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $events = $this->db->fetchAll(
            "SELECT l.id, l.customer_name, l.suburb, l.products_interested, l.status,
                    l.appointment_date, l.appointment_time, l.appointment_duration,
                    l.assigned_to, u.name AS rep_name
             FROM leads l
             LEFT JOIN users u ON l.assigned_to = u.id
             $whereClause
             AND l.appointment_date IS NOT NULL
             AND l.appointment_time IS NOT NULL
             ORDER BY l.appointment_time ASC",
            $params
        );

        echo json_encode(['events' => $events]);
    }

    /** AJAX: Get unassigned leads */
    public function unassigned(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');

        $search = $_GET['search'] ?? '';

        $where = ["LOWER(l.status) = 'new'", "(l.assigned_to IS NULL OR l.assigned_to = 0)"];
        $params = [];

        if ($search !== '') {
            $where[] = '(l.customer_name LIKE :search OR l.suburb LIKE :search2)';
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $leads = $this->db->fetchAll(
            "SELECT l.id, l.customer_name, l.suburb, l.source, l.products_interested, l.created_at
             FROM leads l
             $whereClause
             ORDER BY l.created_at DESC
             LIMIT 50",
            $params
        );

        echo json_encode(['leads' => $leads]);
    }

    /** AJAX: Book an appointment (assign + book) */
    public function book(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // For JSON requests, populate $_POST so CSRF middleware can validate
        if (!empty($input['_csrf_token'])) {
            $_POST['_csrf_token'] = $input['_csrf_token'];
        }

        if (!CsrfMiddleware::validate()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $leadId = (int)($input['lead_id'] ?? 0);
        $repId = (int)($input['rep_id'] ?? 0);
        $date = $input['date'] ?? '';
        $time = $input['time'] ?? '';
        $duration = (int)($input['duration'] ?? 60);

        if (!$leadId || !$repId || !$date || !$time) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date or time format']);
            return;
        }

        $lead = Lead::findById($this->db, $leadId);
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
            return;
        }

        $rep = User::findById($this->db, $repId);
        if (!$rep) {
            http_response_code(404);
            echo json_encode(['error' => 'Rep not found']);
            return;
        }

        // Check for conflicts
        $conflicts = $this->checkConflicts($repId, $date, $time, $duration, $leadId);

        // Update lead: assign rep + book appointment + set status
        Lead::update($this->db, $leadId, [
            'assigned_to' => $repId,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'appointment_duration' => $duration,
            'status' => 'booked',
        ]);

        // Log history
        LeadHistory::record($this->db, $leadId, 'assigned', $lead['rep_name'] ?? 'None', $rep['name']);
        LeadHistory::record($this->db, $leadId, 'appointment_booked', null, "$date $time ({$duration}min)");
        if ($lead['status'] !== 'booked') {
            LeadHistory::record($this->db, $leadId, 'status_changed', $lead['status'], 'booked');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked for ' . $lead['customer_name'],
            'conflicts' => $conflicts,
        ]);
    }

    /** AJAX: Move/reschedule an appointment */
    public function move(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        if (!empty($input['_csrf_token'])) {
            $_POST['_csrf_token'] = $input['_csrf_token'];
        }

        if (!CsrfMiddleware::validate()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            return;
        }

        $leadId = (int)($input['lead_id'] ?? 0);
        $newRepId = (int)($input['new_rep_id'] ?? 0);
        $newDate = $input['new_date'] ?? '';
        $newTime = $input['new_time'] ?? '';

        if (!$leadId || !$newRepId || !$newDate || !$newTime) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate) || !preg_match('/^\d{2}:\d{2}$/', $newTime)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date or time format']);
            return;
        }

        $lead = Lead::findById($this->db, $leadId);
        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
            return;
        }

        $rep = User::findById($this->db, $newRepId);
        if (!$rep) {
            http_response_code(404);
            echo json_encode(['error' => 'Rep not found']);
            return;
        }

        $duration = (int)($lead['appointment_duration'] ?: 60);
        $conflicts = $this->checkConflicts($newRepId, $newDate, $newTime, $duration, $leadId);

        $oldInfo = $lead['appointment_date'] . ' ' . $lead['appointment_time'] . ' (Rep: ' . ($lead['rep_name'] ?? 'None') . ')';
        $newInfo = $newDate . ' ' . $newTime . ' (Rep: ' . $rep['name'] . ')';

        Lead::update($this->db, $leadId, [
            'assigned_to' => $newRepId,
            'appointment_date' => $newDate,
            'appointment_time' => $newTime,
        ]);

        LeadHistory::record($this->db, $leadId, 'appointment_moved', $oldInfo, $newInfo);

        if ((int)$lead['assigned_to'] !== $newRepId) {
            LeadHistory::record($this->db, $leadId, 'assigned', $lead['rep_name'] ?? 'None', $rep['name']);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Appointment rescheduled for ' . $lead['customer_name'],
            'conflicts' => $conflicts,
        ]);
    }

    /** Check for scheduling conflicts */
    private function checkConflicts(int $repId, string $date, string $time, int $duration, int $excludeLeadId = 0): array
    {
        $params = [':rep_id' => $repId, ':date' => $date];
        $excludeClause = '';
        if ($excludeLeadId) {
            $excludeClause = 'AND l.id != :exclude_id';
            $params[':exclude_id'] = $excludeLeadId;
        }

        $existing = $this->db->fetchAll(
            "SELECT l.id, l.customer_name, l.appointment_time, l.appointment_duration
             FROM leads l
             WHERE l.assigned_to = :rep_id
             AND l.appointment_date = :date
             AND l.appointment_time IS NOT NULL
             $excludeClause",
            $params
        );

        $conflicts = [];
        $newStart = $this->timeToMinutes($time);
        $newEnd = $newStart + $duration;

        foreach ($existing as $event) {
            $existStart = $this->timeToMinutes($event['appointment_time']);
            $existEnd = $existStart + ((int)$event['appointment_duration'] ?: 60);

            if ($newStart < $existEnd && $newEnd > $existStart) {
                $conflicts[] = [
                    'lead_id' => $event['id'],
                    'customer_name' => $event['customer_name'],
                    'time' => $event['appointment_time'],
                    'duration' => $event['appointment_duration'],
                ];
            }
        }

        return $conflicts;
    }

    /** Convert HH:MM to minutes since midnight */
    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return ((int)$parts[0]) * 60 + ((int)($parts[1] ?? 0));
    }
}
