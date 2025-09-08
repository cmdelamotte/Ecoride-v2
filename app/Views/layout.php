<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ecoride</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(\App\Helpers\CsrfHelper::getToken()) ?>">
    <?php $authStatus = isset($_SESSION['user_id']) ? 'authenticated' : 'guest'; ?>
    <meta name="auth-status" content="<?= htmlspecialchars($authStatus) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Poppins & Lato -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Poppins:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href= "https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Poppins:wght@400;500;600;700;900&display=swap" rel="stylesheet">
</head>
<body>

    <header>
        <?php include __DIR__ . '/partials/navbar.php'; ?>
    </header>

    <main id="main-content">
        <div id="dynamic-alerts-container" class="container mt-3" style="position: fixed; top: 0; left: 50%; transform: translateX(-50%); z-index: 1050; width: 100%; max-width: 700px;"></div>
        <?php echo $content; ?>
    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <!-- Scripts JavaScript globaux -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="/js/utils/displayFlashMessage.js"></script>
    <!-- Section pour les scripts spécifiques à la page -->
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script type="module" src="<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>