<?php
namespace App\Controllers;

use App\Database;
use App\Models\Lead;
use App\Models\LeadHistory;

/**
 * API controller for external integrations.
 * Web form submissions create leads directly - no auth required.
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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Handle CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            // Try form-encoded
            $input = $_POST;
        }

        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'No data provided.']);
            exit;
        }

        $name = trim($input['customer_name'] ?? '');
        if ($name === '') {
            http_response_code(422);
            echo json_encode(['error' => 'customer_name is required.']);
            exit;
        }

        $data = [
            'customer_name'       => $name,
            'customer_email'      => trim($input['customer_email'] ?? ''),
            'customer_phone'      => trim($input['customer_phone'] ?? ''),
            'address'             => trim($input['address'] ?? ''),
            'suburb'              => trim($input['suburb'] ?? ''),
            'state'               => trim($input['state'] ?? ''),
            'postcode'            => trim($input['postcode'] ?? ''),
            'property_type'       => trim($input['property_type'] ?? 'Residential'),
            'products_interested' => trim($input['products_interested'] ?? ''),
            'source'              => 'Web Form',
            'notes'               => trim($input['notes'] ?? ''),
        ];

        $leadId = Lead::create($this->db, $data);
        LeadHistory::record($this->db, $leadId, 'created', null, null, 'Created via web form');

        http_response_code(201);
        echo json_encode(['success' => true, 'lead_id' => $leadId]);
        exit;
    }
}
