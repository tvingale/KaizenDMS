<?php
/**
 * documents API Endpoints
 */

require_once '../config.php';
require_once '../includes/database.php';
require_once '../includes/kaizen_sso.php';
require_once '../includes/AccessControl.php';

header('Content-Type: application/json');

try {
    $sso = new KaizenSSO([
        'auth_domain' => KAIZEN_AUTH_URL,
        'app_id' => KAIZEN_APP_ID,
        'app_secret' => KAIZEN_APP_SECRET
    ]);
    
    if (!$sso->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    $user = $sso->getUserInfo();
    $db = getDB();
    $accessControl = new AccessControl($db, $user);
    
    if (!$accessControl->checkAccess()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // List documents
            $stmt = $db->query("SELECT * FROM dms_documents ORDER BY created_at DESC");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $items]);
            break;
            
        case 'POST':
            // Create document
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode(['success' => true, 'message' => 'Create endpoint - implement your logic here']);
            break;
            
        case 'PUT':
            // Update document
            echo json_encode(['success' => true, 'message' => 'Update endpoint - implement your logic here']);
            break;
            
        case 'DELETE':
            // Delete document
            echo json_encode(['success' => true, 'message' => 'Delete endpoint - implement your logic here']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>