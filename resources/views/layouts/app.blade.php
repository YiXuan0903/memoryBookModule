<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Memory Book')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .memory-img { object-fit: cover; width: 100%; height: 200px; max-height: 200px; border-radius: 0.5rem; }
        body { overflow-x: hidden; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; padding-top: 70px; transition: background 0.3s, color 0.3s; }
        .sidebar { min-height: 100vh; width: 240px; background: #ffffff; border-right: 1px solid #e5e5e5; box-shadow: 2px 0 6px rgba(0,0,0,0.05); position: fixed; top: 70px; left: 0; transition: all 0.3s; z-index: 1030; }
        .sidebar.collapsed { width: 70px; }
        .sidebar .nav-link { color: #444; margin: 4px 0; border-radius: 12px; padding: 10px 14px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .sidebar .nav-link.active { background: #0d6efd; color: #fff !important; }
        .sidebar.collapsed .nav-link span { display: none; }
        .sidebar.collapsed .nav-link { justify-content: center; }
        @media (max-width: 992px) { .sidebar { left: -240px; } .sidebar.show { left: 0; } .main-content { margin-left: 0 !important; } }
        .main-content { margin-left: 240px; padding: 2rem; transition: all 0.3s; }
        .sidebar.collapsed ~ .main-content { margin-left: 70px; }
        .content-wrapper { background: #fff; border-radius: 20px; padding: 2rem; box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
        .navbar-custom { background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.08); z-index: 1040; }
        body.light-theme { background: #f4f6f9; color: #212529; }
        body.light-theme .content-wrapper { background: #fff; }
        body.dark-theme { background: #1e1e2f; color: #f1f1f1; }
        body.dark-theme .sidebar { background: #2a2a3d; border-right: 1px solid #3a3a52; }
        body.dark-theme .sidebar .nav-link { color: #ccc; }
        body.dark-theme .sidebar .nav-link:hover { background: #3a3a52; color: #fff; }
        body.dark-theme .sidebar .nav-link.active { background: #0d6efd; color: #fff !important; }
        body.dark-theme .content-wrapper { background: #2a2a3d; }
        body.pastel-theme { background: #fdf6f0; color: #333; }
        body.pastel-theme .sidebar { background: #ffe9e3; border-right: 1px solid #f4d4ca; }
        body.pastel-theme .sidebar .nav-link { color: #5a3e36; }
        body.pastel-theme .sidebar .nav-link.active { background: #f78da7; color: #fff !important; }
        body.pastel-theme .content-wrapper { background: #fff7f3; }
        .template-daily { border-left: 4px solid #0d6efd; padding-left: 1rem; }
        .template-travel { background: #f0f8ff; padding: 1rem; border-radius: 10px; }
        .template-gratitude ul { list-style: "💡 "; }
        .template-study pre { font-family: monospace; white-space: pre-wrap; }
    </style>
    @yield('additional_css')
</head>
<body class="{{ Auth::check() ? (Auth::user()->theme ?? session('theme', 'light')) . '-theme' : session('theme', 'light') . '-theme' }}">
@if (!request()->is('dashboard'))
<nav id="topNavbar" class="navbar navbar-expand-lg navbar-custom fixed-top px-3">
    <div class="d-flex align-items-center">
        <button class="btn btn-sm btn-outline-secondary me-3" id="toggleSidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <a class="navbar-brand fw-bold" href="#">📖 Memory Book</a>
    </div>
    <div class="dropdown ms-auto">
        @if(Auth::check())
            <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle fs-4"></i>
                <span class="ms-2">Hi, {{ Auth::user()->name }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="/profile">Profile</a></li>
                <li><a class="dropdown-item" href="/settings">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="/logout">
                        @csrf
                        <button class="dropdown-item text-danger">Logout</button>
                    </form>
                </li>
            </ul>
        @else
            <a href="/login" class="btn btn-primary">Login</a>
        @endif
    </div>
</nav>
@endif

<div class="d-flex">
    @if (!request()->is('dashboard'))
        <div id="sidebar" class="sidebar p-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-house"></i> <span>Main Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/memories" class="nav-link {{ request()->is('memories') && !request()->has('tab') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i> <span>Memories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/memories/create" class="nav-link {{ request()->is('memories/create') ? 'active' : '' }}">
                        <i class="bi bi-plus-square"></i> <span>Add Memory</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/memories?tab=friends" class="nav-link {{ request('tab') === 'friends' ? 'active' : '' }}">
                        <i class="bi bi-people"></i> <span>Friends</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/memories?tab=shared" class="nav-link {{ request('tab') === 'shared' ? 'active' : '' }}">
                        <i class="bi bi-share"></i> <span>Shared With Me</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/memories?tab=settings" class="nav-link {{ request('tab') === 'settings' ? 'active' : '' }}">
                        <i class="bi bi-gear"></i> <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <label class="form-label small">🎨 Theme</label>
                    <select id="themeSwitcher" class="form-select form-select-sm">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                        <option value="pastel">Pastel</option>
                    </select>
                </li>
            </ul>
        </div>
    @endif

    <div class="main-content flex-grow-1">
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 2000">
  <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body d-flex align-items-center gap-2" id="appToastBody">
        Toast message here
      </div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<button id="backToTopBtn" class="btn btn-primary rounded-circle shadow" style="position: fixed; bottom: 30px; right: 30px; display:none; z-index: 1050;">↑</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const mainContent = document.querySelector('.main-content');

    toggleBtn.addEventListener('click', function () {
        if (window.innerWidth < 992) sidebar.classList.toggle('show');
        else { sidebar.classList.toggle('collapsed'); mainContent.classList.toggle('collapsed'); }
    });

    function handleResize() {
        if (window.innerWidth < 992) { sidebar.classList.remove('collapsed'); mainContent.classList.remove('collapsed'); sidebar.classList.remove('show'); }
        else sidebar.classList.remove('show');
    }
    window.addEventListener('resize', handleResize);
    window.addEventListener('load', handleResize);

    const sidebarThemeSwitcher = document.getElementById('themeSwitcher');
    const settingsThemeSelector = document.getElementById('themeSelector');
    const body = document.body;

    window.addEventListener('load', () => {
        const savedTheme = localStorage.getItem('theme') || '{{ session("template","light") }}-theme';
        body.classList.add(savedTheme);
        const themeValue = savedTheme.replace('-theme','');
        if(sidebarThemeSwitcher) sidebarThemeSwitcher.value = themeValue;
        if(settingsThemeSelector) settingsThemeSelector.value = themeValue;
    });

    function applyTheme(value) {
        const newTheme = value + '-theme';
        body.classList.remove('light-theme','dark-theme','pastel-theme');
        body.classList.add(newTheme);
        localStorage.setItem('theme', newTheme);

        fetch("/memories/switch-theme", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' },
            body: JSON.stringify({ theme: value })
        })
        .then(res => res.json())
        .then(data => console.log('Theme switched:', data.theme))
        .catch(err => console.error(err));

        if(sidebarThemeSwitcher) sidebarThemeSwitcher.value = value;
        if(settingsThemeSelector) settingsThemeSelector.value = value;
    }

    if(sidebarThemeSwitcher) sidebarThemeSwitcher.addEventListener('change', e => applyTheme(e.target.value));
    if(settingsThemeSelector) settingsThemeSelector.addEventListener('change', e => applyTheme(e.target.value));

    function showToast(message, type = "info", delay = 3000) {
        let toastEl = document.getElementById("appToast");
        let body = document.getElementById("appToastBody");
        let icon = "";
        if (type === "success") icon = "<i class='bi bi-check-circle-fill text-success'></i>";
        if (type === "danger") icon = "<i class='bi bi-x-circle-fill text-danger'></i>";
        if (type === "warning") icon = "<i class='bi bi-exclamation-triangle-fill text-warning'></i>";
        if (type === "info") icon = "<i class='bi bi-info-circle-fill text-info'></i>";
        body.innerHTML = icon + message;
        toastEl.className = `toast align-items-center text-bg-${type} border-0`;
        let toast = new bootstrap.Toast(toastEl, { delay: delay });
        toast.show();
    }

    document.addEventListener("DOMContentLoaded", function () {
        @if(session('success')) showToast("{{ session('success') }}","success"); @endif
        @if(session('error')) showToast("{{ session('error') }}","danger"); @endif
        @if(session('warning')) showToast("{{ session('warning') }}","warning"); @endif
        @if(session('info')) showToast("{{ session('info') }}","info"); @endif

        @if(session('undo_id'))
            let msg = "Memory deleted. " +
                `<form method="POST" action="/memories/{{ session('undo_id') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="action" value="undo">
                    <button type="submit" class="btn btn-sm btn-light">Undo</button>
                </form>`;
            showToast(msg,"warning",5000);
        @endif
    });

    window.onscroll = function() {
        let btn = document.getElementById("backToTopBtn");
        if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) btn.style.display = "block";
        else btn.style.display = "none";
    };
    document.getElementById("backToTopBtn").onclick = function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
</script>
</body>
</html>
