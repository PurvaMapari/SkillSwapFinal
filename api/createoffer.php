<?php

require_once 'config.php';

$conn = getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$userId = intval($data['sender_id']);
$skillOffered = trim($data['skill_offered']);
$skillWanted = trim($data['skill_wanted']);
$skillLevel = trim($data['skill_level']);
$category = trim($data['category']);
$message = trim($data['message']);

$stmt = $conn->prepare("
INSERT INTO public_skill_offers
(user_id, skill_offered, skill_wanted, skill_level, category, message)
VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
'isssss',
$userId,
$skillOffered,
$skillWanted,
$skillLevel,
$category,
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