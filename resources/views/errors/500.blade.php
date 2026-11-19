{{-- Página autónoma: no depende del layout ni de Vite, para poder mostrarse aunque algo de eso falle. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error del servidor · VíaAliwén</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: #ffffff; color: #1f2933; }
        main { text-align: center; padding: 2rem; max-width: 34rem; }
        .marca { color: #0b2e4f; font-weight: 800; font-size: 1.4rem; }
        .codigo { color: #1565a6; font-size: 3rem; font-weight: 800; margin: 1rem 0 0; }
        a { display: inline-block; margin-top: 1.5rem; padding: 0.6rem 1.2rem; border-radius: 0.4rem;
            background: #2e7d4f; color: #ffffff; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <div class="marca">VíaAliwén</div>
        <p class="codigo">500</p>
        <h1>Tuvimos un problema</h1>
        <p>Algo falló de nuestro lado. Probá de nuevo en unos minutos.</p>
        <a href="/">Volver al inicio</a>
    </main>
</body>
</html>
