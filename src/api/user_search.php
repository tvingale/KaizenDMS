<?php
/**
 * User Search API Proxy
 * Proxies requests to KaizenAuth API using server-side JWT token
 */

require_once '../config.php';
require_once '../includes/database.php';
require_once '../includes/kaizen_sso.php';
require_once '../includes/KaizenAuthAPI.php';

header('Content-Type: application/json');

// Check authentication
$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

$sso = new KaizenSSO($ssoConfig);

if (!$sso->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user = $sso->getUserInfo();

// Only allow admin users to search for other users
$db = getDB();
$stmt = $db->prepare("
    SELECT r.name FROM dms_user_roles ur
    JOIN dms_roles r ON ur.role_id = r.id
    WHERE ur.user_id = ? AND r.name = 'admin'
");
$stmt->execute([$user['id']]);
$isAdmin = $stmt->fetchColumn();

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

// Get search parameters
$query = $_GET['query'] ?? '';
$limit = min(intval($_GET['limit'] ?? 10), 50); // Max 50 results

if (strlen($query) < 2) {
    echo json_encode(['error' => 'Query must be at least 2 characters']);
    exit;
}

try {
    // Use our updated KaizenAuth API client with new gateway
    $api = new KaizenAuthAPI();
    $result = $api->searchUsers($query, $limit);
    
    // Return the result as-is
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search failed: ' . $e->getMessage()
    ]);
}
?>