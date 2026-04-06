<?php
session_start();
require 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$challenges = $pdo->query("SELECT * FROM challenges ORDER BY display_order")->fetchAll();

$completed_ids = $pdo->prepare("SELECT challenge_id FROM user_progress WHERE user_id = ?");
$completed_ids->execute([$user_id]);
$completed = array_column($completed_ids->fetchAll(), 'challenge_id');


$current = null;
foreach ($challenges as $c) {
    if (!in_array($c['id'], $completed)) {
        $current = $c;
        break;
    }
}

$user = $pdo->prepare("SELECT username, xp FROM users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch();


$query_result  = null;
$result_error  = null;
$success_msg   = null;
$new_achievements = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $raw_query = trim($_POST['query']);

    // Security: only allow SELECT, only allow movies table
    $upper = strtoupper($raw_query);
    if (!str_starts_with($upper, 'SELECT')) {
        $result_error = "Try using SELECT.";
    } elseif (!str_contains($upper, 'MOVIES')) {
        $result_error = "Remember you are trying to query the 'movies' table.";
    } elseif (preg_match('/(DROP|DELETE|INSERT|UPDATE|ALTER|CREATE|TRUNCATE)/i', $raw_query)) {
        $result_error = "That query type is not allowed.";
    } else {
        try {
            $stmt = $pdo->query($raw_query);
            $query_result = $stmt->fetchAll();

            // First query achievement
            $already = $pdo->prepare("SELECT id FROM user_achievements 
                JOIN achievements ON achievements.id = user_achievements.achievement_id 
                WHERE user_id = ? AND achievements.slug = 'first_query'");
            $already->execute([$user_id]);
            if (!$already->fetch()) {
                $ach = $pdo->query("SELECT id, title, icon FROM achievements WHERE slug = 'first_query'")->fetch();
                $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)")
                    ->execute([$user_id, $ach['id']]);
                $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?")
                    ->execute([$ach['xp_reward'] ?? 25, $user_id]);
                $new_achievements[] = $ach;
            }

            // Check if this completes the current challenge
            if ($current && !in_array($current['id'], $completed)) {
                $keyword = strtoupper($current['required_keyword']);
                if (empty($keyword) || str_contains($upper, $keyword)) {

                    // Mark complete
                    $pdo->prepare("INSERT IGNORE INTO user_progress (user_id, challenge_id) VALUES (?,?)")
                        ->execute([$user_id, $current['id']]);
                    $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?")
                        ->execute([$current['xp_reward'], $user_id]);
                    $completed[] = $current['id'];
                    $success_msg = "✅ Challenge complete! +" . $current['xp_reward'] . " XP";

                    // Keyword-based achievements
                    $keyword_achievements = [
                        'WHERE'    => 'used_where',
                        'ORDER BY' => 'used_order_by',
                        'COUNT'    => 'used_count',
                        'GROUP BY' => 'used_group_by',
                    ];
                    foreach ($keyword_achievements as $kw => $slug) {
                        if (str_contains($upper, $kw)) {
                            $ach = $pdo->query("SELECT id, title, icon, xp_reward FROM achievements WHERE slug = '$slug'")->fetch();
                            if ($ach) {
                                $inserted = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)");
                                $inserted->execute([$user_id, $ach['id']]);
                                if ($inserted->rowCount() > 0) {
                                    $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?")
                                        ->execute([$ach['xp_reward'], $user_id]);
                                    $new_achievements[] = $ach;
                                }
                            }
                        }
                    }

                    // Count-based achievements
                    $total_done = count($completed);
                    if ($total_done >= 5) {
                        $ach = $pdo->query("SELECT id, title, icon, xp_reward FROM achievements WHERE slug = 'five_done'")->fetch();
                        if ($ach) {
                            $inserted = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)");
                            $inserted->execute([$user_id, $ach['id']]);
                            if ($inserted->rowCount() > 0) {
                                $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?")
                                    ->execute([$ach['xp_reward'], $user_id]);
                                $new_achievements[] = $ach;
                            }
                        }
                    }
                    if ($total_done >= count($challenges)) {
                        $ach = $pdo->query("SELECT id, title, icon, xp_reward FROM achievements WHERE slug = 'all_done'")->fetch();
                        if ($ach) {
                            $inserted = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)");
                            $inserted->execute([$user_id, $ach['id']]);
                            if ($inserted->rowCount() > 0) {
                                $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?")
                                    ->execute([$ach['xp_reward'], $user_id]);
                                $new_achievements[] = $ach;
                            }
                        }
                    }

                    // Advance to next challenge
                    $current = null;
                    foreach ($challenges as $c) {
                        if (!in_array($c['id'], $completed)) {
                            $current = $c;
                            break;
                        }
                    }
                }
            }

            // Refresh XP display
            $user = $pdo->prepare("SELECT username, xp FROM users WHERE id = ?")->execute([$user_id]);
            $user = $pdo->prepare("SELECT username, xp FROM users WHERE id = ?");
            $user->execute([$user_id]);
            $user = $user->fetch();

        } catch (PDOException $e) {
            $result_error = "SQL Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Sandbox</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="stylemain.css">
</head>
<body>

<div class="topbar">
    <div class="brand">SQL SANDBOX</div>
    <div class="user-info">
        <span><?= htmlspecialchars($user['username']) ?></span>
        <span style="color:#5c6bc0; margin: 0 0.5rem;">|</span>
        <span>⚡ <?= $user['xp'] ?> XP</span>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Log out</a>
    </div>
</div>

<?php foreach ($new_achievements as $ach): ?>
    <div class="achievement-toast">
        <?= $ach['icon'] ?> Achievement unlocked: <strong><?= htmlspecialchars($ach['title']) ?></strong>
    </div>
<?php endforeach; ?>

<div class="main">

    <div class="sidebar">
        <h6>Challenges</h6>
        <?php foreach ($challenges as $c): ?>
            <?php
                $done   = in_array($c['id'], $completed);
                $active = $current && $c['id'] === $current['id'];
                $locked = !$done && !$active;
                $class  = $done ? 'done' : ($active ? 'active' : 'locked');
            ?>
            <div class="challenge-item <?= $class ?>">
                <?= htmlspecialchars($c['title']) ?>
                <?php if ($done): ?>
                    <span style="float:right; color:#4caf50; font-size:0.75rem;">+<?= $c['xp_reward'] ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Main content -->
    <div class="content">

        <?php if ($current): ?>

            <!-- Challenge prompt -->
            <div class="challenge-box">
                <h5>🎯 <?= htmlspecialchars($current['title']) ?></h5>
                <p><?= htmlspecialchars($current['prompt']) ?></p>
                <?php if ($current['hint']): ?>
                    <div class="hint">💡 Hint: <?= htmlspecialchars($current['hint']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Query input -->
            <form method="POST">
                <textarea name="query" placeholder="SELECT ..."><?= htmlspecialchars($_POST['query'] ?? '') ?></textarea>
                <br>
                <button type="submit" class="btn-run">▶ RUN QUERY</button>
            </form>

            <!-- Feedback -->
            <?php if ($success_msg): ?>
                <div class="alert-success" style="margin-top:1rem;"><?= $success_msg ?></div>
            <?php endif; ?>
            <?php if ($result_error): ?>
                <div class="alert-danger" style="margin-top:1rem;"><?= htmlspecialchars($result_error) ?></div>
            <?php endif; ?>

            <!-- Results table -->
            <?php if ($query_result !== null): ?>
                <div style="overflow-x:auto; margin-top:0.5rem;">
                    <?php if (count($query_result) === 0): ?>
                        <p style="color:#5c6bc0; margin-top:1rem;">Query ran but returned no rows.</p>
                    <?php else: ?>
                        <table class="result-table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($query_result[0]) as $col): ?>
                                        <th><?= htmlspecialchars($col) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($query_result as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $val): ?>
                                            <td><?= htmlspecialchars($val ?? 'NULL') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p style="color:#5c6bc0; font-size:0.8rem; margin-top:0.5rem;">
                            <?= count($query_result) ?> row(s) returned
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="all-done">
                <div class="trophy">🏆</div>
                <h4 style="color:#ffd600; margin: 1rem 0;">All challenges complete!</h4>
                <p>You've finished every challenge. Check your <a href="profile.php" style="color:#7986cb;">profile</a> to see your achievements.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php if ($new_achievements): ?>
<script>
    setTimeout(() => {
        document.querySelectorAll('.achievement-toast').forEach(t => t.remove());
    }, 4000);
</script>
<?php endif; ?>

</body>
</html>