<?php

require_once 'config.php';

$conn = getConnection();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

$userId = intval($data['sender_id'] ?? 0);
$receiverId = intval($data['receiver_id'] ?? 0);
$skillOffered = trim($data['skill_offered'] ?? '');
$skillWanted = trim($data['skill_wanted'] ?? '');
$message = trim($data['message'] ?? '');

if (!$userId || !$receiverId || !$skillOffered || !$skillWanted) {
    echo json_encode([
        'success' => false,
        'message' => 'sender_id, receiver_id, skill_offered, and skill_wanted are required.'
    ]);
    exit;
}

$stmt = $conn->prepare("
INSERT INTO swap_requests
(sender_id, receiver_id, skill_offered, skill_wanted, message)
VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
'iisss',
$userId,
$receiverId,
$skillOffered,
$skillWanted,
$message
);

if($stmt->execute()) {
    echo json_encode([
        'success' => true
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $stmt->error
    ]);
}