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
            Choose a lesson box to open its short, silent video and step-by-step directions.
            Videos play only inside lessons. Use Previous and Next to move between lessons.
        </p>
    </div>

    <?php $lessonNumber = 0; ?>
    <?php foreach ($trainingData['sections'] as $sectionKey => $section): ?>
        <?php
        $sectionTopics = [];

        foreach ($visibleTopics as $topicKey => $topic) {
            if ($topic['section'] === $sectionKey) {
                $sectionTopics[$topicKey] = $topic;
            }
        }
        ?>

        <?php if ($sectionTopics): ?>
            <section class="training-section" aria-labelledby="section-<?= escapeOutput($sectionKey) ?>">
                <h2 id="section-<?= escapeOutput($sectionKey) ?>" class="training-section-heading">
                    <?= escapeOutput($section['title']) ?>
                </h2>
                <p class="training-topic-summary">
                    <?= escapeOutput($section['summary']) ?>
                </p>

                <div class="training-lesson-grid">
                    <?php foreach ($sectionTopics as $topicKey => $topic): ?>
                        <?php
                        $lessonNumber++;
                        $videoAvailable = is_file(__DIR__ . '/movies/' . $topicKey . '.mp4');
                        $topicAddress = APPLICATION_URL
                            . '/training/tc_topic.php?topic='
                            . rawurlencode($topicKey);
                        ?>
                        <a href="<?= escapeOutput($topicAddress) ?>" class="training-lesson-card"
                            aria-label="Open lesson <?= $lessonNumber ?>: <?= escapeOutput($topic['title']) ?>">
                            <span class="training-lesson-number">Lesson <?= $lessonNumber ?></span>
                            <strong class="training-lesson-title"><?= escapeOutput($topic['title']) ?></strong>
                            <span class="training-lesson-summary"><?= escapeOutput($topic['summary']) ?></span>
                            <span class="training-lesson-bottom">
                                <span class="training-lesson-status">
                                    <?= $videoAvailable ? (!empty($topic['needs_update']) ? 'Video needs update' : 'Video ready') : 'Video coming soon' ?>
                                </span>
                                <span class="training-lesson-open" aria-hidden="true">Open Lesson &#8594;</span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>