<?php
namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\User;
use App\Middleware\CsrfMiddleware;

class LeadController
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /** List leads with filters and pagination */
    public function index(): void
    {
        Auth::requireAuth();

        $filters = [
            'status'      => $_GET['status'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? '',
            'source'      => $_GET['source'] ?? '',
            'date_from'   => $_GET['date_from'] ?? '',
            'date_to'     => $_GET['date_to'] ?? '',
            'search'      => $_GET['search'] ?? '',
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = Lead::list($this->db, $filters, $page, $this->config['per_page']);
        $reps = User::activeReps($this->db);
        $options = Lead::getOptions($this->db);

        require __DIR__ . '/../../templates/leads/index.php';
    }

    /** Show single lead detail */
    public function show(array $params): void
    {
        Auth::requireAuth();
        $lead = Lead::findById($this->db, (int)$params['id']);
        if (!$lead) {
            header('Location: /leads');
            exit;
        }
        $timeline = LeadHistory::timeline($this->db, $lead['id']);
        $reps = User::activeReps($this->db);
        $options = Lead::getOptions($this->db);

        require __DIR__ . '/../../templates/leads/detail.php';
    }

    /** New lead form */
    public function create(): void
    {
        Auth::requireAuth();
        $reps = User::activeReps($this->db);
        $options = Lead::getOptions($this->db);
        $errors = $_SESSION['lead_errors'] ?? [];
        $old = $_SESSION['lead_old'] ?? [];
        unset($_SESSION['lead_errors'], $_SESSION['lead_old']);

        require __DIR__ . '/../../templates/leads/create.php';
    }

    /** Store new lead */
    public function store(): void
    {
        Auth::requireAuth();
        CsrfMiddleware::enforce();

        $data = $this->validateLeadInput();
        if ($data === null) {
            header('Location: /leads/create');
            exit;
        }

        $data['created_by'] = Auth::id();
        $leadId = Lead::create($this->db, $data);
        LeadHistory::record($this->db, $leadId, 'created', null, null, 'Lead created');

        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Lead created successfully.'];
        header('Location: /leads/' . $leadId);
        exit;
    }

    /** Update lead (status, assignment, appointment, notes) */
    public function update(array $params): void
    {
        Auth::requireAuth();
        CsrfMiddleware::enforce();

        $id = (int)$params['id'];
        $lead = Lead::findById($this->db, $id);
        if (!$lead) {
            header('Location: /leads');
            exit;
        }

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_status':
                $newStatus = $_POST['status'] ?? '';
                $validStatuses = Lead::getOptions($this->db)['statuses'];
                if (in_array($newStatus, $validStatuses) && strtolower($newStatus) !== strtolower($lead['status'])) {
                    Lead::update($this->db, $id, ['status' => $newStatus]);
                    LeadHistory::record($this->db, $id, 'status_changed', $lead['status'], $newStatus);
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Status updated.'];
                }
                break;

            case 'assign_rep':
                $repId = (int)($_POST['assigned_to'] ?? 0);
                $rep = $repId ? User::findById($this->db, $repId) : null;
                Lead::update($this->db, $id, ['assigned_to' => $repId ?: null]);
                $repName = $rep ? $rep['name'] : 'Unassigned';
                LeadHistory::record($this->db, $id, 'assigned', $lead['rep_name'] ?? 'None', $repName);
                // Auto-set status to assigned if currently new
                if ($lead['status'] === 'new' && $repId) {
                    Lead::update($this->db, $id, ['status' => 'assigned']);
                    LeadHistory::record($this->db, $id, 'status_changed', 'new', 'assigned');
                }
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Rep assigned.'];
                break;

            case 'book_appointment':
                $date = $_POST['appointment_date'] ?? '';
                $time = $_POST['appointment_time'] ?? '';
                $duration = (int)($_POST['appointment_duration'] ?? 60);
                if ($date && $time) {
                    Lead::update($this->db, $id, [
                        'appointment_date'     => $date,
                        'appointment_time'     => $time,
                        'appointment_duration' => $duration,
                    ]);
                    LeadHistory::record($this->db, $id, 'appointment_booked', null, "$date $time ({$duration}min)");
                    // Auto-set status to booked
                    if (in_array($lead['status'], ['new', 'assigned'])) {
                        Lead::update($this->db, $id, ['status' => 'booked']);
                        LeadHistory::record($this->db, $id, 'status_changed', $lead['status'], 'booked');
                    }
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Appointment booked.'];
                }
                break;

            case 'add_note':
                $note = trim($_POST['note'] ?? '');
                if ($note !== '') {
                    LeadHistory::record($this->db, $id, 'note_added', null, null, $note);
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Note added.'];
                }
                break;

            case 'update_quote':
                $amount = $_POST['quoted_amount'] ?? '';
                if (is_numeric($amount)) {
                    Lead::update($this->db, $id, ['quoted_amount' => (float)$amount]);
                    LeadHistory::record($this->db, $id, 'status_changed', null, "Quote: $" . number_format((float)$amount, 2));
                    if (in_array($lead['status'], ['new', 'assigned', 'booked'])) {
                        Lead::update($this->db, $id, ['status' => 'quoted']);
                        LeadHistory::record($this->db, $id, 'status_changed', $lead['status'], 'quoted');
                    }
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Quote updated.'];
                }
                break;

            case 'update_details':
                $updateData = [];
                foreach (['customer_name', 'customer_email', 'customer_phone', 'address', 'suburb', 'state', 'postcode', 'property_type', 'products_interested', 'source'] as $field) {
                    if (isset($_POST[$field])) {
                        $updateData[$field] = trim($_POST[$field]);
                    }
                }
                if (!empty($updateData)) {
                    Lead::update($this->db, $id, $updateData);
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Lead details updated.'];
                }
                break;
        }

        header('Location: /leads/' . $id);
        exit;
    }

    /** Validate and sanitise lead input */
    private function validateLeadInput(): ?array
    {
        $data = [
            'customer_name'      => trim($_POST['customer_name'] ?? ''),
            'customer_email'     => trim($_POST['customer_email'] ?? ''),
            'customer_phone'     => trim($_POST['customer_phone'] ?? ''),
            'address'            => trim($_POST['address'] ?? ''),
            'suburb'             => trim($_POST['suburb'] ?? ''),
            'state'              => trim($_POST['state'] ?? ''),
            'postcode'           => trim($_POST['postcode'] ?? ''),
            'property_type'      => $_POST['property_type'] ?? 'residential',
            'products_interested' => trim($_POST['products_interested'] ?? ''),
            'source'             => $_POST['source'] ?? 'phone',
            'notes'              => trim($_POST['notes'] ?? ''),
            'assigned_to'        => $_POST['assigned_to'] ?? null,
        ];

        $errors = [];
        if ($data['customer_name'] === '') {
            $errors[] = 'Customer name is required.';
        }
        if ($data['customer_email'] !== '' && !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
        if (!in_array($data['property_type'], Lead::DEFAULT_PROPERTY_TYPES)) {
            $errors[] = 'Invalid property type.';
        }
        if (!in_array($data['source'], Lead::DEFAULT_SOURCES)) {
            $errors[] = 'Invalid source.';
        }

        if ($errors) {
            $_SESSION['lead_errors'] = $errors;
            $_SESSION['lead_old'] = $data;
            return null;
        }

        return $data;
    }
}
