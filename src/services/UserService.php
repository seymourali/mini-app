<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Mailer.php';

class UserService
{
    // Register a new user and send an email notification
    public function registerUser(string $fullName, string $email, string $company = null): array
    {
        try {
            // Check if user already exists
            Database::query('SELECT id FROM registrations WHERE email = :email');
            Database::bind(':email', $email);
            $userExists = Database::resultOne();

            if ($userExists) {
                return [
                    'data' => [
                        'status' => 'fail', 
                        'fields' => ['email' => 'This email is already registered'], 
                        'message' => 'Validation failed',
                    ],
                    'code' => 400
                ];
            }

            // Insert user into the database
            Database::query('INSERT INTO registrations (full_name, email, company) VALUES (:full_name, :email, :company)');
            Database::bind(':full_name', $fullName);
            Database::bind(':email', $email);
            Database::bind(':company', $company);
            Database::execute();

            // Send email notification
            $mailer = new Mailer();
            $mailer->sendNotification($fullName, $email, $company);

            return [
                'data' => [
                    'status' => 'success', 
                    'message' => 'Qeydiyyat tamamlandı',
                ],
                'code' => 201
            ];

        } catch (Exception $e) {
            // Log the error and return a generic message
            error_log('Registration failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred.'];
        }
    }

    // Fetch users for DataTable server-side processing
    public function fetchUsers(array $params): array
    {
        $draw = intval($params['draw']);
        $start = intval($params['start']);
        $length = intval($params['length']);
        $search = $params['search']['value'];
        $orderColumn = $params['columns'][$params['order'][0]['column']]['data'];
        $orderDir = $params['order'][0]['dir'];

        // Get total records
        Database::query('SELECT COUNT(*) AS total FROM registrations');
        $totalRecords = Database::resultOne()['total'];

        // Build query with search, order, and limit
        $sql = 'SELECT * FROM registrations';
        $where = [];
        $binds = [];
        
        if (!empty($search)) {
            $where[] = '(full_name LIKE :full_name_search OR email LIKE :email_search OR company LIKE :company_search)';
            $binds[':full_name_search'] = '%' . $search . '%';
            $binds[':email_search'] = '%' . $search . '%';
            $binds[':company_search'] = '%' . $search . '%';
        }

        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY ' . $orderColumn . ' ' . $orderDir;
        $sql .= ' LIMIT :start, :length';

        // Prepare and execute the query
        Database::query($sql);
        Database::bind(':start', $start, PDO::PARAM_INT);
        Database::bind(':length', $length, PDO::PARAM_INT);
        if (!empty($binds)) {
            foreach ($binds as $param => $value) {
                Database::bind($param, $value);
            }
        }
        $data = Database::resultAll();

        // Get filtered records count
        $recordsFiltered = $totalRecords;
        if (!empty($search)) {
            $sql = 'SELECT COUNT(*) AS total FROM registrations';
            if (count($where) > 0) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            Database::query($sql);
            if (!empty($binds)) {
                foreach ($binds as $param => $value) {
                    Database::bind($param, $value);
                }
            }
            $recordsFiltered = Database::resultOne()['total'];
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    // Export user data to XLSX
    public function exportToExcel(): void
    {
        // Get all registrations
        Database::query('SELECT id, full_name, email, company, created_at FROM registrations ORDER BY created_at DESC');
        $users = Database::resultAll();

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Full Name');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Company');
        $sheet->setCellValue('E1', 'Created At');

        // Fill data
        $row = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user['id']);
            $sheet->setCellValue('B' . $row, $user['full_name']);
            $sheet->setCellValue('C' . $row, $user['email']);
            $sheet->setCellValue('D' . $row, $user['company']);
            $sheet->setCellValue('E' . $row, $user['created_at']);
            $row++;
        }

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="users.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // Export user data to PDF
    public function exportToPdf(): void
    {
        // Get all users
        Database::query('SELECT id, full_name, email, company, created_at FROM registrations ORDER BY created_at DESC');
        $users = Database::resultAll();

        // Generate HTML for PDF
        $html = '<h1 style="text-align: center;">Registered Users</h1>';
        $html .= '<table style="width:100%; border-collapse: collapse;">';
        $html .= '<thead><tr style="background-color: #f2f2f2;">';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">ID</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">Full Name</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">Email</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">Company</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px;">Created At</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($users as $user) {
            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($user['id']) . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($user['full_name']) . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($user['email']) . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($user['company']) . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($user['created_at']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Output the PDF to the browser
        $dompdf->stream('users.pdf', ['Attachment' => true]);
    }
}