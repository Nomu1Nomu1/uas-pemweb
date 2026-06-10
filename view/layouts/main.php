<body>
<div class="app-layout">

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="content-wrapper">

        <?php require_once __DIR__ . '/header.php'; ?>

        <main class="main-content">

            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="alert alert-info">
                    <?= $_SESSION['flash'] ?>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <?= $content ?? '' ?>

        </main>

        <?php require_once __DIR__ . '/footer.php'; ?>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>