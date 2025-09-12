<?php
/**
 * SMS API Endpoint for Dms Management
 * Sends SMS notifications related to documents
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../config.php';
require_once '../includes/database.php';
require_once '../includes/kaizen_sso.php';
require_once '../includes/AccessControl.php';

try {
    // Authentication check
    $sso = new KaizenSSO([
        'auth_domain' => KAIZEN_AUTH_URL,
        'app_id' => KAIZEN_APP_ID,
        'app_secret' => KAIZEN_APP_SECRET
    ]);

    if (!$sso->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }

    $user = $sso->getUserInfo();
    $db = getDB();
    $accessControl = new AccessControl($db, $user);

    // Check permissions
    if (!$accessControl->checkAccess()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Validate required parameters
    $recipientId = $input['recipient_id'] ?? null;
    $mobileNumber = $input['mobile_number'] ?? null;
    $messageType = $input['message_type'] ?? 'notification';
    $message = $input['message'] ?? null;
    $entityId = $input['entity_id'] ?? null;

    if (!$recipientId || !$mobileNumber) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters: recipient_id and mobile_number']);
        exit;
    }

    // Validate mobile number format (Indian format)
    if (!preg_match('/^[6-9]\d{9}$/', $mobileNumber)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid mobile number format. Use 10-digit Indian mobile number']);
        exit;
    }

    // Prepare SMS content based on message type
    $smsContent = '';
    $entityName = '';
    
    if ($entityId) {
        try {
            $stmt = $db->prepare("SELECT name FROM dms_documents WHERE id = ?");
            $stmt->execute([$entityId]);
            $entity = $stmt->fetch(PDO::FETCH_ASSOC);
            $entityName = $entity ? $entity['name'] : 'document #' . $entityId;
        } catch (Exception $e) {
            $entityName = 'document #' . $entityId;
        }
    }

    switch ($messageType) {
        case 'assignment':
            $smsContent = "Dms Management: New document assigned to you - {$entityName}. Please check your dashboard.";
            break;
        case 'status_update':
            $status = $input['status'] ?? 'updated';
            $smsContent = "Dms Management: document '{$entityName}' status changed to {$status}.";
            break;
        case 'reminder':
            $smsContent = "Dms Management: Reminder for document '{$entityName}'. Please take action.";
            break;
        case 'deadline':
            $deadline = $input['deadline'] ?? 'soon';
            $smsContent = "Dms Management: document '{$entityName}' deadline is {$deadline}. Please complete urgently.";
            break;
        case 'approval':
            $smsContent = "Dms Management: document '{$entityName}' requires your approval. Please review.";
            break;
        case 'custom':
            $smsContent = $message ?? "Dms Management: You have a new notification.";
            break;
        default:
            $smsContent = "Dms Management: You have a new document notification - {$entityName}.";
    }

    // Placeholder SMS sending logic
    // In a real implementation, integrate with SMS gateway like MSG91, Twilio, etc.
    $smsResult = sendSMS($mobileNumber, $smsContent);

    if ($smsResult['success']) {
        // Log the SMS in activity log
        try {
            $stmt = $db->prepare("
                INSERT INTO dms_activity_log 
                (entity_type, entity_id, action, user_id, details, created_at)
                VALUES (?, ?, 'sms_sent', ?, ?, NOW())
            ");
            $stmt->execute([
                'sms',
                $entityId,
                $user['id'],
                json_encode([
                    'recipient_id' => $recipientId,
                    'mobile_number' => substr($mobileNumber, 0, 6) . '****',
                    'message_type' => $messageType,
                    'message_length' => strlen($smsContent),
                    'gateway_response' => $smsResult['message_id'] ?? null
                ])
            ]);
        } catch (Exception $e) {
            error_log("Failed to log SMS activity: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'SMS sent successfully',
            'message_id' => $smsResult['message_id'] ?? null,
            'recipient' => substr($mobileNumber, 0, 6) . '****'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send SMS: ' . $smsResult['error']
        ]);
    }

} catch (Exception $e) {
    error_log("SMS API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}

/**
 * Placeholder SMS sending function
 * Replace with actual SMS gateway integration
 */
function sendSMS($mobileNumber, $message) {
    // Placeholder implementation
    // In production, integrate with SMS gateway
    
    if (DEBUG_MODE) {
        error_log("SMS to {$mobileNumber}: {$message}");
        return [
            'success' => true,
            'message_id' => 'DEBUG_' . uniqid(),
            'status' => 'sent'
        ];
    }
    
    // Example integration with MSG91 or Twilio
    /*
    $apiKey = 'YOUR_SMS_GATEWAY_API_KEY';
    $senderId = 'YOUR_SENDER_ID';
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.msg91.com/api/sendhttp.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query([
            'authkey' => $apiKey,
            'mobiles' => $mobileNumber,
            'message' => $message,
            'sender' => $senderId,
            'route' => '4'
        ])
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200) {
        return ['success' => true, 'message_id' => $response];
    } else {
        return ['success' => false, 'error' => 'Gateway error: ' . $response];
    }
    */
    
    return [
        'success' => false,
        'error' => 'SMS gateway not configured. Please implement SMS sending logic.'
    ];
}
?>