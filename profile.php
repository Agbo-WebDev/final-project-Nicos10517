<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// This is fetching the user info
$stmt = $pdo->prepare("SELECT username, xp FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// "Fetching the date the user joined
$stmt = $pdo->prepare("
    SELECT MIN(completed_at) AS started_at
    FROM user_progress
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$started_row = $stmt->fetch();

$start_date = !empty($started_row['started_at'])
    ? date('M j, Y', strtotime($started_row['started_at']))
    : null;

// This calculates the total XP available from all the challenges and divies up that xp between 10 levels so you're
//not weirdly stuck at a strange level at the end
$total_xp_row = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(xp_reward), 0) FROM challenges) +
        (SELECT COALESCE(SUM(xp_reward), 0) FROM achievements) AS total_xp
")->fetch();
$total_xp_available = (int) $total_xp_row['total_xp'];

$MAX_LEVEL = 10;
$XP_PER_LEVEL = max(1, (int) ceil($total_xp_available / $MAX_LEVEL));

$effective_xp = min($user['xp'], $total_xp_available);

$level = (int) floor($effective_xp / $XP_PER_LEVEL) + 1;
if ($level > $MAX_LEVEL) $level = $MAX_LEVEL;

$xp_into_level = $effective_xp % $XP_PER_LEVEL;
$xp_to_next    = $XP_PER_LEVEL - $xp_into_level;
$progress_pct  = ($xp_into_level / $XP_PER_LEVEL) * 100;

$is_max_level = ($effective_xp >= $total_xp_available);

// These are the fun titles you unlock on your profile as you join!
$titles = [
    1  => 'Newcomer',
    2  => 'Query Apprentice',
    3  => 'SELECT Scholar',
    4  => 'WHERE Wizard',
    5  => 'Join Journeyman',
    6  => 'Order Operator',
    7  => 'Group Guru',
    8  => 'Schema Sage',
    9  => 'Query Virtuoso',
    10 => 'Database Deity',
];
$title = $titles[$level] ?? 'Newcomer';

//This displays your most recent achievement and status!
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.icon, ua.earned_at
    FROM user_achievements ua
    JOIN achievements a ON a.id = ua.achievement_id
    WHERE ua.user_id = ?
    ORDER BY ua.earned_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$latest_achievement = $stmt->fetch();

// This grabs the items that the user has access to
$stmt = $pdo->prepare("
    SELECT 
        i.id, 
        i.name, 
        i.icon, 
        i.image_path, 
        i.z_index,
        COALESCE(ui.equipped, 0) AS equipped,
        ui.id IS NOT NULL AS unlocked,
        a.title AS unlock_hint
    FROM items i
    LEFT JOIN user_items ui 
      ON ui.item_id = i.id AND ui.user_id = ?
    LEFT JOIN achievements a 
      ON a.id = i.unlock_achievement_id
    ORDER BY i.z_index ASC
");
$stmt->execute([$user_id]);
$user_items = $stmt->fetchAll();
//The start of this html is just copy pasted from sandbox, just establishing the task bar like before
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="stylemain.css">
</head>
<body>

<div class="topbar">
    <a class="brand" href="sandbox.php">SQL SANDBOX</a>
    <div class="user-info">
        <span><?= htmlspecialchars($user['username']) ?></span>
        <span style="color:#5c6bc0; margin: 0 0.5rem;">|</span>
        <span>⚡ <?= $user['xp'] ?> XP</span>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Log out</a>
    </div>
</div>

<div class="profile-page">

    <!--This loads in our favorite Beepo!-->
    <div class="profile-top">
        <div class="item-rack">
            <?php foreach ($user_items as $item): ?>
                <?php if ($item['unlocked']): ?>
                    <div class="item-slot unlocked <?= $item['equipped'] ? 'equipped' : '' ?>"
                        data-item-id="<?= htmlspecialchars($item['id']) ?>"
                        title="<?= htmlspecialchars($item['name']) ?>">
                        <?= $item['icon'] ?>
                    </div>
                <?php else: ?>
                    <div class="item-slot locked"
                        title="Locked — earn '<?= htmlspecialchars($item['unlock_hint'] ?? '???') ?>' to unlock">
                        🔒
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="beepo-stage">
            <div class="beepo-wrap">
                <img id="profimg" src="Beepo.png" alt="Beepo">
                <?php foreach ($user_items as $item): ?>
                    <?php if ($item['unlocked']): ?>
                        <img class="clothing-layer"
                            data-item-id="<?= htmlspecialchars($item['id']) ?>"
                            src="<?= htmlspecialchars($item['image_path']) ?>"
                            style="z-index: <?= (int) $item['z_index'] ?>;
                                    display: <?= $item['equipped'] ? 'block' : 'none' ?>;"
                            alt="">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!--These arethe little profile cards underneath beepo, which werekind of a pain to get aligned-->
    <div class="profile-cards">

        <div class="profile-card">
            <div class="card-label">Current Level</div>
            <?php if ($is_max_level): ?>
                <div class="card-value">Level <?= $MAX_LEVEL ?> — MAX</div>
                <div class="xp-bar-wrap">
                    <div class="xp-bar" style="width: 100%;"></div>
                </div>
                <div class="card-sub">You've earned all <?= $total_xp_available ?> XP!</div>
            <?php else: ?>
                <div class="card-value">Level <?= $level ?></div>
                <div class="xp-bar-wrap">
                    <div class="xp-bar" style="width: <?= $progress_pct ?>%;"></div>
                </div>
                <div class="card-sub"><?= $xp_to_next ?> XP to Level <?= $level + 1 ?></div>
            <?php endif; ?>
        </div>

        <div class="profile-card">
            <div class="card-label">Player</div>
            <div class="card-value"><?= htmlspecialchars($user['username']) ?></div>
            <?php if ($start_date): ?>
                <div class="card-sub">Started <?= htmlspecialchars($start_date) ?></div>
            <?php else: ?>
                <div class="card-sub">No challenges completed yet</div>
            <?php endif; ?>
            <div class="card-title-badge"><?= htmlspecialchars($title) ?></div>
        </div>

        <div class="profile-card">
            <div class="card-label">Latest Achievement</div>
            <?php if ($latest_achievement): ?>
                <div class="card-value">
                    <?= $latest_achievement['icon'] ?>
                    <?= htmlspecialchars($latest_achievement['title']) ?>
                </div>
                <div class="card-sub">
                    Earned <?= date('M j, Y', strtotime($latest_achievement['earned_at'])) ?>
                </div>
            <?php else: ?>
                <div class="card-value" style="opacity:0.6;">None yet</div>
                <div class="card-sub">Run some queries to earn your first!</div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!--//Event listener to ensure nothing breaks down when you try to equip items-->
<script>
document.querySelectorAll('.item-slot.unlocked').forEach(slot => {
    slot.addEventListener('click', async () => {
        const itemId = slot.dataset.itemId;
        if (slot.classList.contains('pending')) return;
        slot.classList.add('pending');

        try {
            const formData = new FormData();
            formData.append('item_id', itemId);

            const response = await fetch('equip_item.php', {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();

            if (data.success) {
                slot.classList.toggle('equipped', data.equipped);
                const layer = document.querySelector(
                    `.clothing-layer[data-item-id="${itemId}"]`
                );
                if (layer) {
                    layer.style.display = data.equipped ? 'block' : 'none';
                }
            } else {
                console.error('Equip failed:', data.error);
            }
        } catch (err) {
            console.error('Request failed:', err);
        } finally {
            slot.classList.remove('pending');
        }
    });
});
</script>

</body>
</html>