<?php // training/tc_topic.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAccess();

$trainingData = require __DIR__ . '/tc_data.php';

$role = signedInRole();
$topicKey = (string) ($_GET['topic'] ?? '');

if (!isset($trainingData['topics'][$topicKey])) {
    showAccessDeniedPage('The requested training topic was not found.');
}

$topic = $trainingData['topics'][$topicKey];

if (!in_array($role, $topic['roles'], true)) {
    showAccessDeniedPage(
        'That training topic is for a different role. Choose a topic from the Training Center.'
    );
}

$movie = $trainingData['movies'][$topic['movie']];
$movieStart = (int) $topic['start'];
$movieAvailable = is_file(__DIR__ . '/movies/' . $movie['file']);

$movieStartText =
    floor($movieStart / 60)
    . ':'
    . str_pad((string) ($movieStart % 60), 2, '0', STR_PAD_LEFT);

$movieAddress =
    APPLICATION_URL
    . '/training/movies/'
    . rawurlencode($movie['file'])
    . ($movieStart > 0 ? '#t=' . $movieStart : '');

$pageTitle = $topic['title'];
$currentSection = 'training';
$currentPage = 'topic';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel training-panel training-topic-panel">

    <div class="page-intro training-compact-intro">
        <h1><?= escapeOutput($topic['title']) ?></h1>
        <p><?= escapeOutput($topic['assignment']) ?></p>
    </div>

    <div class="training-topic-meta">
        <div>
            <span>Who Uses This</span>
            <strong><?= escapeOutput($topic['audience']) ?></strong>
        </div>
        <div>
            <span>Training Movie</span>
            <strong><?= escapeOutput($movie['title']) ?></strong>
        </div>
    </div>

    <p class="training-topic-summary">
        <?= escapeOutput($topic['summary']) ?>
    </p>

    <section class="training-steps">
        <h2>Steps</h2>
        <ol>
            <?php foreach ($topic['steps'] as $step): ?>
                <li><?= escapeOutput($step) ?></li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="training-movie-card">
        <h2>Watch This Topic</h2>

        <?php if ($movieAvailable): ?>

            <video
                controls
                preload="metadata"
                class="training-video"
            >
                <source
                    src="<?= escapeOutput($movieAddress) ?>"
                    type="video/mp4"
                >
                Your browser does not support the video player.
            </video>

            <?php if ($movieStart > 0): ?>
                <p class="training-start-note">
                    This topic starts at
                    <?= escapeOutput($movieStartText) ?>
                    in the movie.
                </p>
            <?php endif; ?>

            <a
                href="<?= escapeOutput($movieAddress) ?>"
                class="button button-primary"
            >
                Open Training Movie
            </a>

        <?php else: ?>

            <div class="message message-warning">
                This training movie is not available yet.
            </div>

        <?php endif; ?>
    </section>

    <div class="page-main-actions training-topic-actions">
        <a
            href="<?= APPLICATION_URL ?>/training/tc_home.php"
            class="button button-secondary"
        >
            Training Center
        </a>
    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>