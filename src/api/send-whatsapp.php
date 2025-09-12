<?php
/**
 * WhatsApp API Endpoint for Dms Management
 * Sends WhatsApp notifications related to documents
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
    $templateName = $input['template_name'] ?? null;

    if (!$recipientId || !$mobileNumber) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters: recipient_id and mobile_number']);
        exit;
    }

    // Validate mobile number format (international format for WhatsApp)
    if (!preg_match('/^\+91[6-9]\d{9}$/', $mobileNumber)) {
        // Try to format Indian mobile number
        if (preg_match('/^[6-9]\d{9}$/', $mobileNumber)) {
            $mobileNumber = '+91' . $mobileNumber;
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid mobile number format. Use +91 followed by 10-digit number']);
            exit;
        }
    }

    // Get entity details if provided
    $entityName = '';
    $entityUrl = '';
    
    if ($entityId) {
        try {
            $stmt = $db->prepare("SELECT name FROM dms_documents WHERE id = ?");
            $stmt->execute([$entityId]);
            $entity = $stmt->fetch(PDO::FETCH_ASSOC);
            $entityName = $entity ? $entity['name'] : 'document #' . $entityId;
            $entityUrl = APP_URL . '/document_view.php?id=' . $entityId;
        } catch (Exception $e) {
            $entityName = 'document #' . $entityId;
        }
    }

    // Prepare WhatsApp content based on message type
    $whatsappContent = '';
    $templateData = [];
    
    switch ($messageType) {
        case 'assignment':
            if ($templateName) {
                // Use WhatsApp template
                $templateData = [
                    'entity_name' => $entityName,
                    'module_name' => 'Dms Management',
                    'url' => $entityUrl
                ];
            } else {
                // Plain text message
                $whatsappContent = "🔔 *Dms Management Notification*\n\n";
                $whatsappContent .= "New document assigned to you:\n";
                $whatsappContent .= "📋 *{$entityName}*\n\n";
                $whatsappContent .= "Please check your dashboard for details.";
                if ($entityUrl) {
                    $whatsappContent .= "\n\n🔗 View: {$entityUrl}";
                }
            }
            break;
            
        case 'status_update':
            $status = $input['status'] ?? 'updated';
            $whatsappContent = "📢 *Dms Management Update*\n\n";
            $whatsappContent .= "document status changed:\n";
            $whatsappContent .= "📋 *{$entityName}*\n";
            $whatsappContent .= "🔄 Status: *{$status}*";
            if ($entityUrl) {
                $whatsappContent .= "\n\n🔗 View: {$entityUrl}";
            }
            break;
            
        case 'reminder':
            $whatsappContent = "⏰ *Dms Management Reminder*\n\n";
            $whatsappContent .= "Don't forget about:\n";
            $whatsappContent .= "📋 *{$entityName}*\n\n";
            $whatsappContent .= "Please take action as needed.";
            if ($entityUrl) {
                $whatsappContent .= "\n\n🔗 View: {$entityUrl}";
            }
            break;
            
        case 'deadline':
            $deadline = $input['deadline'] ?? 'soon';
            $whatsappContent = "⚠️ *Dms Management Deadline Alert*\n\n";
            $whatsappContent .= "Urgent attention required:\n";
            $whatsappContent .= "📋 *{$entityName}*\n";
            $whatsappContent .= "📅 Deadline: *{$deadline}*\n\n";
            $whatsappContent .= "Please complete this urgently!";
            if ($entityUrl) {
                $whatsappContent .= "\n\n🔗 View: {$entityUrl}";
            }
            break;
            
        case 'approval':
            $whatsappContent = "✅ *Dms Management Approval Request*\n\n";
            $whatsappContent .= "Your approval is needed for:\n";
            $whatsappContent .= "📋 *{$entityName}*\n\n";
            $whatsappContent .= "Please review and approve/reject.";
            if ($entityUrl) {
                $whatsappContent .= "\n\n🔗 Review: {$entityUrl}";
            }
            break;
            
        case 'welcome':
            $whatsappContent = "🎉 *Welcome to Dms Management!*\n\n";
            $whatsappContent .= "You now have access to our document management system.\n\n";
            $whatsappContent .= "Login at: " . APP_URL;
            break;
            
        case 'custom':
            $whatsappContent = $message ?? "📱 *Dms Management Notification*\n\nYou have a new notification.";
            break;
            
        default:
            $whatsappContent = "🔔 *Dms Management Notification*\n\n";
            $whatsappContent .= "You have a new document notification:\n";
            $whatsappContent .= "📋 *{$entityName}*";
            if ($entityUrl) {
                $whatsappContent .= "\n\n🔗 View: {$entityUrl}";
            }
    }

    // Send WhatsApp message
    if ($templateName && !empty($templateData)) {
        // Send template message
        $whatsappResult = sendWhatsAppTemplate($mobileNumber, $templateName, $templateData);
    } else {
        // Send text message
        $whatsappResult = sendWhatsAppMessage($mobileNumber, $whatsappContent);
    }

    if ($whatsappResult['success']) {
        // Log the WhatsApp message in activity log
        try {
            $stmt = $db->prepare("
                INSERT INTO dms_activity_log 
                (entity_type, entity_id, action, user_id, details, created_at)
                VALUES (?, ?, 'whatsapp_sent', ?, ?, NOW())
            ");
            $stmt->execute([
                'whatsapp',
                $entityId,
                $user['id'],
                json_encode([
                    'recipient_id' => $recipientId,
                    'mobile_number' => substr($mobileNumber, 0, 7) . '****',
                    'message_type' => $messageType,
                    'template_name' => $templateName,
                    'message_length' => strlen($whatsappContent),
                    'gateway_response' => $whatsappResult['message_id'] ?? null
                ])
            ]);
        } catch (Exception $e) {
            error_log("Failed to log WhatsApp activity: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp message sent successfully',
            'message_id' => $whatsappResult['message_id'] ?? null,
            'recipient' => substr($mobileNumber, 0, 7) . '****'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send WhatsApp message: ' . $whatsappResult['error']
        ]);
    }

} catch (Exception $e) {
    error_log("WhatsApp API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}

/**
 * Send WhatsApp text message
 * Replace with actual WhatsApp Business API integration
 */
function sendWhatsAppMessage($mobileNumber, $message) {
    if (DEBUG_MODE) {
        error_log("WhatsApp to {$mobileNumber}: {$message}");
        return [
            'success' => true,
            'message_id' => 'DEBUG_WA_' . uniqid(),
            'status' => 'sent'
        ];
    }
    
    // Example integration with WhatsApp Business API
    /*
    $accessToken = 'YOUR_WHATSAPP_ACCESS_TOKEN';
    $phoneNumberId = 'YOUR_PHONE_NUMBER_ID';
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://graph.facebook.com/v17.0/{$phoneNumberId}/messages",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            'messaging_product' => 'whatsapp',
            'to' => $mobileNumber,
            'type' => 'text',
            'text' => ['body' => $message]
        ]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && isset($responseData['messages'])) {
        return [
            'success' => true,
            'message_id' => $responseData['messages'][0]['id'],
            'status' => 'sent'
        ];
    } else {
        return [
            'success' => false,
            'error' => 'WhatsApp API error: ' . ($responseData['error']['message'] ?? $response)
        ];
    }
    */
    
    return [
        'success' => false,
        'error' => 'WhatsApp Business API not configured. Please implement WhatsApp sending logic.'
    ];
}

/**
 * Send WhatsApp template message
 * Replace with actual WhatsApp Business API integration
 */
function sendWhatsAppTemplate($mobileNumber, $templateName, $templateData) {
    if (DEBUG_MODE) {
        error_log("WhatsApp Template to {$mobileNumber}: {$templateName} with data: " . json_encode($templateData));
        return [
            'success' => true,
            'message_id' => 'DEBUG_WAT_' . uniqid(),
            'status' => 'sent'
        ];
    }
    
    // Placeholder for template message implementation
    return [
        'success' => false,
        'error' => 'WhatsApp template messaging not configured. Please implement template logic.'
    ];
}
?>