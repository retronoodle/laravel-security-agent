<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f4f6f8; color: #1a1a2e; }
        nav { background: #1a1a2e; padding: 0 1.5rem; display: flex; align-items: center; height: 52px; }
        nav a { color: #e0e0e0; text-decoration: none; font-size: .9rem; margin-right: 1.5rem; }
        nav a:hover { color: #fff; }
        nav .brand { color: #fff; font-weight: 600; margin-right: 2rem; }
        main { max-width: 960px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); margin-bottom: 1.5rem; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-error  { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .btn { display: inline-block; padding: .5rem 1.25rem; border-radius: 5px; border: none; cursor: pointer; font-size: .9rem; }
        .btn-primary { background: #4361ee; color: #fff; }
        .btn-primary:hover { background: #3451d1; }
        label { display: block; font-size: .875rem; font-weight: 500; margin-bottom: .35rem; }
        input[type=password], input[type=text], select { width: 100%; padding: .5rem .75rem; border: 1px solid #ced4da; border-radius: 5px; font-size: .9rem; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th { text-align: left; padding: .5rem .75rem; border-bottom: 2px solid #dee2e6; color: #6c757d; font-weight: 600; }
        td { padding: .5rem .75rem; border-bottom: 1px solid #f1f1f1; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-box { background: #fff; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); text-align: center; }
        .stat-box .value { font-size: 2rem; font-weight: 700; color: #4361ee; }
        .stat-box .label { font-size: .8rem; color: #6c757d; margin-top: .25rem; }
    </style>
</head>
<body>
<nav>
    <span class="brand">LSA Admin</span>
    @php $path = config('lsa.admin.path', 'lsa-admin'); @endphp
    <a href="/{{ $path }}">Dashboard</a>
    <a href="/{{ $path }}/settings">Settings</a>
</nav>
<main>
    @yield('content')
</main>
</body>
</html>
