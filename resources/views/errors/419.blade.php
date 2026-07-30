<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="0;url={{ route('login') }}">
    <title>Redirecting…</title>
    <script>
        window.location.replace(@json(route('login')));
    </script>
</head>
<body></body>
</html>
