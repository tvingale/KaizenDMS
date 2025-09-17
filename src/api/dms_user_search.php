<?php
/**
 * DMS User Search API
 * Searches users from dms_user_roles table (users with app access)
 */

header('Content-Type: application/json');

require_once '../config.php';
require_once '../includes/database.php';
require_once '../includes/kaizen_sso.php';
require_once '../includes/AccessControl.php';

// Check authentication
$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

$sso = new KaizenSSO($ssoConfig);

if (!$sso->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user = $sso->getUserInfo();
$db = getDB();

// Check admin access for user search
try {
    AccessControl::requireAccess('admin');
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// Validate CSRF token
$csrfToken = $_GET['csrf_token'] ?? '';
if (!isset($_SESSION['user_search_csrf_token']) || !hash_equals($_SESSION['user_search_csrf_token'], $csrfToken)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Get search query
$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([
        'status' => 'success',
        'data' => ['users' => []]
    ]);
    exit;
}

try {
    // Search users from dms_user_roles table
    // Get users who have active access to DMS
    $searchQuery = '%' . $query . '%';

    $stmt = $db->prepare("
        SELECT DISTINCT
            ur.user_id,
            ur.granted_at,
            ur.status,
            ur.department,
            ur.notes,
            -- Try to get user details from notes or department fields
            CASE
                WHEN ur.notes LIKE '%name:%' THEN
                    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(ur.notes, 'name:', -1), ',', 1))
                ELSE
                    CONCAT('User ', ur.user_id)
            END as user_name,
            CASE
                WHEN ur.notes LIKE '%email:%' THEN
                    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(ur.notes, 'email:', -1), ',', 1))
                ELSE
                    ''
            END as user_email,
            r.display_name as role_name
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        WHERE ur.status = 'active'
        AND (
            ur.user_id LIKE ?
            OR ur.notes LIKE ?
            OR ur.department LIKE ?
            OR CONCAT('User ', ur.user_id) LIKE ?
        )
        ORDER BY ur.user_id, ur.granted_at DESC
        LIMIT 20
    ");

    $stmt->execute([$searchQuery, $searchQuery, $searchQuery, $searchQuery]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process results to create user objects
    $users = [];
    $seenUsers = [];

    foreach ($results as $row) {
        $userId = $row['user_id'];

        // Avoid duplicate users (take first occurrence with most recent grant)
        if (isset($seenUsers[$userId])) {
            continue;
        }
        $seenUsers[$userId] = true;

        // Extract user details
        $userName = $row['user_name'];
        $userEmail = $row['user_email'];

        // If no email found in notes, try to extract from other patterns
        if (empty($userEmail) && !empty($row['notes'])) {
            if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $row['notes'], $matches)) {
                $userEmail = $matches[0];
            }
        }

        // If no name found, try to extract from notes
        if ($userName === "User $userId" && !empty($row['notes'])) {
            if (preg_match('/name[:\s]*([^,\n\r]+)/i', $row['notes'], $matches)) {
                $userName = trim($matches[1]);
            }
        }

        $users[] = [
            'id' => (int)$userId,
            'name' => $userName,
            'email' => $userEmail,
            'username' => "user$userId", // Fallback username
            'department' => $row['department'],
            'role' => $row['role_name'],
            'granted_at' => $row['granted_at'],
            'status' => $row['status']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'users' => $users,
            'total' => count($users)
        ]
    ]);

} catch (Exception $e) {
    error_log("DMS User search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Search failed'
    ]);
}
?>