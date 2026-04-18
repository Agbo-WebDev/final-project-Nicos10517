
<?php
// This is basically my index now! I titled it Sandbox since my big focus was making it possible to 
// do sql prompts and get them checked

//This just initiates the user session, and ensures that the databases and the unlocks for the items/achievements are connected
session_start();
require 'db.php';
require 'unlock_helpers.php';

// This ensures that the use is logged in!
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Gathering the information from Xampp about my challenges and the user data (which challenges each user has completed)

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

// This is gathering more user data, and setting up the variables for the sandbox (what the query is and should be, and possible errors)

$user = $pdo->prepare("SELECT username, xp FROM users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch();


$query_result  = null;
$result_error  = null;
$success_msg   = null;
$new_achievements = [];
$new_items = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $raw_query = trim($_POST['query']);

    // I want to make sure at the start that people are just doing SELECT * FROM the database, and nothing too complicated
    // These are handlers to ensure that the users are only tackling the challenge given
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

            // Try statement was all that could work here, since I was execting errors to return
            //Essentially it tries to run the query and the data, if it completes the challenge it moves on, if not it returns the error
            $already = $pdo->prepare("SELECT user_achievements.id FROM user_achievements 
                JOIN achievements ON achievements.id = user_achievements.achievement_id 
                WHERE user_id = ? AND achievements.id = 'first_query'");
            $already->execute([$user_id]);
            if (!$already->fetch()) {
                $ach = $pdo->query("SELECT id, title, icon FROM achievements WHERE id = 'first_query'")->fetch();
                $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)")
                    ->execute([$user_id, $ach['id']]);
                $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?")
                    ->execute([$ach['xp_reward'] ?? 25, $user_id]);
                $new_achievements[] = $ach;
            }

            // Check if this completes the current challenge
            if ($current && !in_array($current['id'], $completed)) {
                // Prefer the multi-keyword column; fall back to single keyword if empty
                $required_list = !empty($current['required_keywords'])
                    ? $current['required_keywords']
                    : $current['required_keyword'];

                $keywords = array_filter(array_map('trim', explode(',', strtoupper($required_list))));

                // Making sure that all the key words I wanted the user to implement are available
                $all_present = !empty($keywords);
                foreach ($keywords as $kw) {
                    if (!str_contains($upper, $kw)) {
                        $all_present = false;
                        break;
                    }
                }

                if (empty($keywords) || $all_present) {

                    // Updates user progress for when you log back in, and 
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
                    foreach ($keyword_achievements as $kw => $id) {
                        if (str_contains($upper, $kw)) {
                            $ach = $pdo->query("SELECT id, title, icon, xp_reward FROM achievements WHERE id = '$id'")->fetch();
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
                        $ach = $pdo->query("SELECT id, title, icon, xp_reward FROM achievements WHERE id = 'five_done'")->fetch();
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
                        $ach = $pdo->query("SELECT id, title, icon, xp_reward FROM achievements WHERE id = 'all_done'")->fetch();
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

                    // Grant any items tied to newly-earned achievements
                    $new_items = grantItemsForAchievements($pdo, $user_id);

                    // Advance to next challenge!! If I had mroe time I would make it so that you could revisit, but alas
                    $current = null;
                    foreach ($challenges as $c) {
                        if (!in_array($c['id'], $completed)) {
                            $current = $c;
                            break;
                        }
                    }
                } else {
                    $missing = array_filter($keywords, fn($kw) => !str_contains($upper, $kw));
                    $result_error = "Query worked, but to complete this challenge you still need: " 
                                . implode(', ', $missing);
                }
            }

            // Refresh XP display  for when the user levels up
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
    <a class="brand" href="sandbox.php" >SQL SANDBOX</a>
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

<?php foreach ($new_items as $item): ?>
    <div class="achievement-toast item-unlock-toast">
        <?= $item['icon'] ?> New item unlocked: <strong><?= htmlspecialchars($item['name']) ?></strong>
        <div style="font-size:0.75rem; margin-top:0.25rem;">Visit your profile to equip it!</div>
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

    
    <div class="content">

        <?php if ($current): ?>

            <!--This is the challenge prompt, where the different questions show up-->
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

        <?php else: ?>
            <!--if you have completed all the challenges, this makes sure you just are led to your profile-->

            <div class="all-done">
                <div class="trophy">🏆</div>
                <h4 style="color:#ffd600; margin: 1rem 0;">All challenges complete!</h4>
                <p>You've finished every challenge. Check your <a href="profile.php" style="color:#7986cb;">profile</a> to see your achievements.</p>
            </div>

        <?php endif; ?>

        <!--This is the feedback that shows if you got an error-->

        <?php if ($success_msg): ?>
            <div class="alert-success" style="margin-top:1rem;"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if ($result_error): ?>
            <div class="alert-danger" style="margin-top:1rem;"><?= htmlspecialchars($result_error) ?></div>
        <?php endif; ?>

        <!-- Results table (shown whether or not there's a current challenge) -->
        <?php if ($query_result !== null): ?>
            <div style="overflow-x:auto; margin-top:0.5rem;">
                <?php if (count($query_result) === 0): ?>
                    <p style="color:#5c6bc0; margin-top:1rem;">Query ran but returned no rows.</p>
                <?php else: ?>
                    <?php if (!$current): ?>
                        <h5 style="color:#5c6bc0; margin-bottom:0.5rem; margin-top:1.5rem;">Your winning query:</h5>
                    <?php endif; ?>
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

    </div>
</div>

<?php if ($new_achievements || $new_items): ?>
<script>
    // I had to play with this a little bit, this has an achievement pop up if you unlocked one, and then sets a timer so that 
    // later the item you unlocked will pop up, to prevent them from overlapping each other
    document.querySelectorAll('.item-unlock-toast').forEach(t => {
        t.style.display = 'none';
    });
    setTimeout(() => {
        document.querySelectorAll('.achievement-toast:not(.item-unlock-toast)').forEach(t => t.remove());
        document.querySelectorAll('.item-unlock-toast').forEach(t => {
            t.style.display = '';
        });
    }, 4000);
    setTimeout(() => {
        document.querySelectorAll('.item-unlock-toast').forEach(t => t.remove());
    }, 8000);
</script>
<?php endif; ?>

</body>
</html>