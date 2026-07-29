<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="0;url={{ auth()->check() ? route('welcome') : route('login') }}">
    <title>Redirecting…</title>
    <script>
        window.location.replace(@json(auth()->check() ? route('welcome') : route('login')));
    </script>
</head>
<body></body>
</html>
