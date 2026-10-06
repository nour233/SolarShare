<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SolarShare - Back office</title>
    <link href="/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
        /* Same colors and fonts as the front (public/front/css/style.css). */
        :root { --primary: #32C36C; --light: #F6F7F8; --dark: #1A2A36; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; background: var(--light); color: #6c757d; font-family: 'Open Sans', sans-serif; }
        .back-top { background: var(--dark); padding: 22px 32px; }
        .back-top span { color: var(--primary); font-family: 'Roboto', sans-serif; font-weight: 700; font-size: 26px; }
        .back-main { flex: 1; display: grid; place-items: center; padding: 32px 16px; }
        .back-card { width: 100%; max-width: 540px; background: #fff; border: 1px solid #edf0ed; border-radius: 12px; padding: 40px 32px; text-align: center; box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075); }
        .back-card h1 { margin: 0 0 12px; color: var(--dark); font-family: 'Roboto', sans-serif; font-weight: 700; font-size: 28px; }
        .back-card p { margin: 0 0 28px; font-size: 15px; }
        .back-card button { border: 0; border-radius: 50px; background: var(--primary); color: #fff; padding: 13px 40px; font-family: 'Open Sans', sans-serif; font-weight: 600; font-size: 14px; cursor: pointer; }
        .back-card button:hover { filter: brightness(.95); }
    </style>
</head>
<body>
    <header class="back-top"><span>SolarShare</span></header>
    <main class="back-main">
        <div class="back-card">
            <h1>SolarShare - Back office</h1>
            <p>Bienvenue, {{ auth()->user()->name }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Se déconnecter</button>
            </form>
        </div>
    </main>
</body>
</html>
