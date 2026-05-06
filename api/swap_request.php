<?php
// ============================================================
//  api/swap_request.php  —  Create / List swap requests
//  POST: Create a new swap request
//  GET : ?user_id=X  → fetch requests for that user
// ============================================================
require_once 'config.php';

$conn = getConnection();

// ── POST — create swap request ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) $data = $_POST;

    $senderId     = intval($data['sender_id']     ?? 0);
    $receiverId   = intval($data['receiver_id']    ?? 0);
    $skillOffered = trim($data['skill_offered']    ?? '');
    $skillWanted  = trim($data['skill_wanted']     ?? '');
    $message      = trim($data['message']         ?? '');
    $action       = trim($data['action']          ?? '');

    // ── UPDATE STATUS ──
    if ($action === 'update_status') {
        $requestId = intval($data['request_id'] ?? 0);
        $status    = trim($data['status']       ?? ''); // 'accepted' or 'declined'
        
        if (!$requestId || !in_array($status, ['accepted', 'declined'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters for status update.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE swap_requests SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $requestId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // ── CREATE NEW REQUEST ──
    if (!$senderId || !$receiverId || !$skillOffered || !$skillWanted) {
        echo json_encode(['success' => false, 'message' => 'sender_id, receiver_id, skill_offered, and skill_wanted are required.']);
        exit;
    }


  $stmt = $conn->prepare("
INSERT INTO swap_requests (
    sender_id,
    receiver_id,
    skill_offered,
    skill_wanted,
    message
)
VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    'iisss',
    $senderId,
    $receiverId,
    $skillOffered,
    $skillWanted,
    $message
);

if ($stmt->execute()) {

    echo json_encode([
        'success' => true,
        'message' => 'Swap request sent successfully!'
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => $stmt->error
    ]);
}
    exit;
}

// ── GET — list requests for a user ──────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['user_id'])) {
        $uid = intval($_GET['user_id']);
        $stmt = $conn->prepare(" 
            SELECT sr.*, us.name AS sender_name, ur.name AS receiver_name
            FROM swap_requests sr
            JOIN users us ON sr.sender_id = us.id
            JOIN users ur ON sr.receiver_id = ur.id
            WHERE sr.sender_id = ? OR sr.receiver_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->bind_param('ii', $uid, $uid);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $stmt = $conn->prepare(" 
            SELECT sr.*, us.name AS sender_name, ur.name AS receiver_name
            FROM swap_requests sr
            JOIN users us ON sr.sender_id = us.id
            JOIN users ur ON sr.receiver_id = ur.id
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
    }

    $requests = [];

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'data' => $requests
    ]);

    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
