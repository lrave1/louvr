<?php
namespace App\Controllers;

use App\Database;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\User;

/**
 * API controller for external integrations.
 * Authenticated via X-API-Key header.
 */
class ApiController
{
    private Database $db;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
    }

    /** POST /api/leads - Create lead from web form */
    public function createLead(): void
    {
        header('Content-Type: application/json');

        $user = $this->authenticate();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or missing API key.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON body.']);
            exit;
        }

        // Validate required fields
        $name = trim($input['customer_name'] ?? '');
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'customer_name is required.']);
            exit;
        }

        $data = [
            'customer_name'      => $name,
            'customer_email'     => trim($input['customer_email'] ?? ''),
            'customer_phone'     => trim($input['customer_phone'] ?? ''),
            'address'            => trim($input['address'] ?? ''),
            'suburb'             => trim($input['suburb'] ?? ''),
            'state'              => trim($input['state'] ?? ''),
            'postcode'           => trim($input['postcode'] ?? ''),
            'property_type'      => $input['property_type'] ?? 'residential',
            'products_interested' => trim($input['products_interested'] ?? ''),
            'source'             => 'web_form',
            'notes'              => trim($input['notes'] ?? ''),
            'created_by'         => $user['id'],
        ];

        // Validate enums
        if (!in_array($data['property_type'], Lead::PROPERTY_TYPES)) {
            $data['property_type'] = 'residential';
        }

        $leadId = Lead::create($this->db, $data);
        LeadHistory::record($this->db, $leadId, 'created', null, null, 'Created via API');

        http_response_code(201);
        echo json_encode(['success' => true, 'lead_id' => $leadId]);
        exit;
    }

    /** Authenticate via X-API-Key header */
    private function authenticate(): ?array
    {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($key === '') {
            return null;
        }
        return User::findByApiKey($this->db, $key);
    }
}
