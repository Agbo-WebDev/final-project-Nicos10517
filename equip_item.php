<?php
//I ended up needing a bit of help to equip each of the unlockable items for the character, I probably could have done
//this a much simpler way, but oh well
session_start();
require 'db.php';

header('Content-Type: application/json');

//Ensures that you're logged in and have established a connection before anything else
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

//I added a databse clled user items, and that's what it will grab from to figure out which item we're equipping

$user_id = $_SESSION['user_id'];
$item_id = $_POST['item_id'] ?? '';

if (empty($item_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing item_id']);
    exit;
}

// This s all about the display, and seeing if the user owns the item at all, or if it should be behind the lock
$stmt = $pdo->prepare("SELECT equipped FROM user_items WHERE user_id = ? AND item_id = ?");
$stmt->execute([$user_id, $item_id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(403);
    echo json_encode(['error' => 'Item not owned']);
    exit;
}

$new_state = $row['equipped'] ? 0 : 1;
$stmt = $pdo->prepare("UPDATE user_items SET equipped = ? WHERE user_id = ? AND item_id = ?");
$stmt->execute([$new_state, $user_id, $item_id]);

echo json_encode([
    'success'  => true,
    'item_id'  => $item_id,
    'equipped' => (bool) $new_state,
]);