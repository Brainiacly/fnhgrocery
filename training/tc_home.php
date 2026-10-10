<?php // training/tc_home.php

/**
 * Brian Phillips
 * CSC 680
 */

require_once __DIR__ . '/../includes/access_control.php';

requireAccess();

$trainingData = require __DIR__ . '/tc_data.php';

$role = signedInRole();

$visibleTopics = [];

foreach ($trainingData['topics'] as $topicKey => $topic) {
    if (in_array($role, $topic['roles'], true)) {
        $visibleTopics[$topicKey] = $topic;
    }
}

$pageTitle = 'Training Center';
$currentSection = 'training';
$currentPage = 'index';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel training-panel">

    <div class="page-intro training-compact-intro">
        <h1>Training Center</h1>
        <p>
            Watch a training movie, or open a single topic to read the steps
            and jump to the part of the movie that shows it.
        </p>
    </div>

    <h2 class="training-section-heading">Training Movies</h2>

    <div class="training-grid">

        <?php foreach ($trainingData['movies'] as $movieKey => $movie): ?>

            <?php
            $movieTopics = [];

            foreach ($visibleTopics as $topicKey => $topic) {
                if ($topic['movie'] === $movieKey) {
                    $movieTopics[$topicKey] = $topic;
                }
            }

            $movieAvailable = is_file(
                __DIR__ . '/movies/' . $movie['file']
            );

            $movieAddress =
                APPLICATION_URL
                . '/training/movies/'
                . rawurlencode($movie['file']);
            ?>

            <?php if ($movieTopics): ?>

                <div class="training-movie-tile">

                    <strong><?= escapeOutput($movie['title']) ?></strong>

                    <span><?= escapeOutput($movie['summary']) ?></span>

                    <?php if ($movieAvailable): ?>

                        <a
                            href="<?= escapeOutput($movieAddress) ?>"
                            class="button button-primary"
                        >
                            Watch Movie
                        </a>

                    <?php else: ?>

                        <span class="training-movie-pending">
                            This movie is not available yet.
                        </span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        <?php endforeach; ?>

    </div>

    <h2 class="training-section-heading">Training Topics</h2>

    <div class="training-grid">

        <?php foreach ($visibleTopics as $topicKey => $topic): ?>

            <?php
            $topicAddress =
                APPLICATION_URL
                . '/training/tc_topic.php?topic='
                . rawurlencode($topicKey);
            ?>

            <a
                href="<?= escapeOutput($topicAddress) ?>"
                class="training-card"
            >
                <span class="training-assignment-label">
                    <?= escapeOutput($topic['assignment']) ?>
                </span>
                <strong><?= escapeOutput($topic['title']) ?></strong>
                <span><?= escapeOutput($topic['summary']) ?></span>
            </a>

        <?php endforeach; ?>

    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>