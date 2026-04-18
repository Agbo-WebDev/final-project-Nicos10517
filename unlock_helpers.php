<?php
//This fnctions similarly to the achievemnts notification, it just notifies you that you hvae reached the next achievement and thus that
//you have unlocked a new item, and then stores $newly_unlocked, which I use for the profile cards and the achivement notifs in the sandbox
function grantItemsForAchievements(PDO $pdo, int $user_id): array {
    // Find items tied to achievements the user has earned
    // that they don't already own.
    $stmt = $pdo->prepare("
        SELECT i.id, i.name, i.icon
        FROM items i
        JOIN user_achievements ua
          ON ua.achievement_id = i.unlock_achievement_id
         AND ua.user_id = ?
        LEFT JOIN user_items ui
          ON ui.item_id = i.id
         AND ui.user_id = ?
        WHERE i.unlock_achievement_id IS NOT NULL
          AND ui.id IS NULL
    ");
    $stmt->execute([$user_id, $user_id]);
    $newly_unlocked = $stmt->fetchAll();

    if (empty($newly_unlocked)) {
        return [];
    }

    $insert = $pdo->prepare(
        "INSERT IGNORE INTO user_items (user_id, item_id) VALUES (?, ?)"
    );
    foreach ($newly_unlocked as $item) {
        $insert->execute([$user_id, $item['id']]);
    }

    return $newly_unlocked;
}

?>