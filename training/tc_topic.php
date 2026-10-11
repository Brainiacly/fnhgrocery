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

// Follow the same section and lesson order used by Training Center cards.
// Only include lessons available to this employee's role.
$visibleTopicKeys = [];
foreach ($trainingData['sections'] as $sectionKey => $section) {
    foreach ($trainingData['topics'] as $candidateKey => $candidate) {
        if (
            $candidate['section'] === $sectionKey
            && in_array($role, $candidate['roles'], true)
        ) {
            $visibleTopicKeys[] = $candidateKey;
        }
    }
}

$lessonIndex = array_search($topicKey, $visibleTopicKeys, true);
$lessonCount = count($visibleTopicKeys);
$previousTopicKey = $visibleTopicKeys[($lessonIndex - 1 + $lessonCount) % $lessonCount];
$nextTopicKey = $visibleTopicKeys[($lessonIndex + 1) % $lessonCount];
$previousTopicAddress = APPLICATION_URL . '/training/tc_topic.php?topic=' . rawurlencode($previousTopicKey);
$nextTopicAddress = APPLICATION_URL . '/training/tc_topic.php?topic=' . rawurlencode($nextTopicKey);

$section = $trainingData['sections'][$topic['section']];
$videoFile = $topicKey . '.mp4';
$videoAvailable = is_file(__DIR__ . '/movies/' . $videoFile);
$videoAddress = APPLICATION_URL
    . '/training/movies/' . rawurlencode($videoFile);

$pageTitle = $topic['title'];
$currentSection = 'training';
$currentPage = 'topic';

require __DIR__ . '/../includes/header.php';
?>

<section class="content-panel training-panel training-topic-panel">
    <div class="page-intro training-compact-intro">
        <p class="training-lesson-number">Lesson <?= $lessonIndex + 1 ?> of <?= $lessonCount ?></p>
        <h1><?= escapeOutput($topic['title']) ?></h1>
        <p><?= escapeOutput($topic['summary']) ?></p>
    </div>

    <div class="training-topic-meta">
        <div>
            <span>Who Uses This</span>
            <strong><?= escapeOutput($topic['audience']) ?></strong>
        </div>
        <div>
            <span>Training Area</span>
            <strong><?= escapeOutput($section['title']) ?></strong>
        </div>
    </div>

    <figure class="training-topic-figure">
        <?php if ($videoAvailable): ?>
            <video controls muted playsinline preload="metadata" class="training-video"
                aria-label="<?= escapeOutput($topic['title']) ?> demonstration">
                <source src="<?= escapeOutput($videoAddress) ?>" type="video/mp4">
                Your browser does not support video playback.
            </video>
        <?php else: ?>
            <div class="training-video-pending training-topic-pending" role="status">
                This lesson's video is not available yet. You can still follow the directions below.
            </div>
        <?php endif; ?>

        <figcaption class="training-clip-caption training-topic-directions">
            <h2>Directions</h2>
            <ol>
                <?php foreach ($topic['steps'] as $step): ?>
                    <li><?= escapeOutput($step) ?></li>
                <?php endforeach; ?>
            </ol>
        </figcaption>
    </figure>

    <nav class="training-lesson-navigation" aria-label="Training lesson navigation">
        <a href="<?= escapeOutput($previousTopicAddress) ?>" class="button button-secondary"
            aria-label="Previous lesson: <?= escapeOutput($trainingData['topics'][$previousTopicKey]['title']) ?>">
            &#8592; Previous
        </a>
        <a href="<?= APPLICATION_URL ?>/training/tc_home.php" class="button button-secondary">
            All Lessons
        </a>
        <a href="<?= escapeOutput($nextTopicAddress) ?>" class="button button-secondary"
            aria-label="Next lesson: <?= escapeOutput($trainingData['topics'][$nextTopicKey]['title']) ?>">
            Next &#8594;
        </a>
    </nav>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>