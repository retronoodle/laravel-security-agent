<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA Admin — Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f4f6f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-card { background: #fff; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,.12); width: 100%; max-width: 380px; }
        h1 { font-size: 1.3rem; margin-bottom: 1.5rem; color: #1a1a2e; }
        label { display: block; font-size: .875rem; font-weight: 500; margin-bottom: .35rem; color: #444; }
        input[type=password] { width: 100%; padding: .5rem .75rem; border: 1px solid #ced4da; border-radius: 5px; font-size: .9rem; margin-bottom: 1rem; }
        .btn { display: block; width: 100%; padding: .6rem; background: #4361ee; color: #fff; border: none; border-radius: 5px; font-size: .95rem; cursor: pointer; }
        .btn:hover { background: #3451d1; }
        .error { color: #dc3545; font-size: .85rem; margin-bottom: .75rem; }
    </style>
</head>
<body>
<div class="login-card">
    <h1>LSA Admin</h1>

    @if ($errors->has('password'))
        <p class="error">{{ $errors->first('password') }}</p>
    @endif

    <form method="POST" action="{{ url(config('lsa.admin.path', 'lsa-admin') . '/login') }}">
        @csrf
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autofocus>
        <button type="submit" class="btn">Sign in</button>
    </form>
</div>
</body>
</html>
