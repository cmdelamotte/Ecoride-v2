<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ecoride - Covoiturage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

    <header>
        <?php include __DIR__ . '/partials/navbar.php'; ?>
    </header>

    <main id="main-content">
        <?php echo $content; ?>
    </main>

    <footer class="mt-auto">
        <?php include __DIR__ . '/partials/footer.php'; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="/router/Router.js"></script>

</body>
</html>