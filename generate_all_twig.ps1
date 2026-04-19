# ============================================================
# SCRIPT POWERSHELL - CREATION DE TOUS LES FICHIERS TWIG
# Horizia - Agence de Location de Vehicules
# ============================================================
# Executer depuis la racine du projet Symfony
# ============================================================

$utf8NoBOM = New-Object System.Text.UTF8Encoding $false

function Write-Twig($path, $content) {
    $dir = Split-Path $path
    if (!(Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    [System.IO.File]::WriteAllText((Resolve-Path ".").Path + "\" + $path.Replace("/","\"), $content, $utf8NoBOM)
    $b = [System.IO.File]::ReadAllBytes((Resolve-Path ".").Path + "\" + $path.Replace("/","\"))
    if ($b[0] -eq 123 -or $b[0] -eq 60) { Write-Host "OK: $path" -ForegroundColor Green }
    else { Write-Host "ERREUR: $path (bytes: $($b[0]) $($b[1]))" -ForegroundColor Red }
}

# ============================================================
# 1. base.html.twig
# ============================================================
Write-Twig "templates/base.html.twig" @'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>{% block title %}Horizia{% endblock %}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {% block stylesheets %}
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            :root {
                --color-primary: #23779C;
                --color-secondary: #3D94CA;
                --color-accent: #81AE8D;
                --color-warning: #E8B156;
                --color-neutral: #DACEB6;
                --color-dark: #103749;
                --color-sidebar: #1f2d3d;
                --color-light-bg: #f8f6f1;
                --color-white: #ffffff;
                --color-border: #e9edf3;
                --color-muted: #6c757d;
            }
            body { background-color: var(--color-light-bg); margin: 0; padding: 0; }
            .admin-layout { display: flex; min-height: 100vh; }
            .admin-sidebar {
                position: fixed; top: 0; left: 0; width: 280px; height: 100vh;
                background: var(--color-sidebar); color: #fff; padding: 1.5rem 0;
                overflow-y: auto; z-index: 1030; transition: transform 0.3s ease;
            }
            .sidebar-brand-center {
                display: flex; flex-direction: column; align-items: center;
                justify-content: center; padding: 0 24px 24px; text-align: center;
            }
            .sidebar-logo-large { width: 120px; height: 120px; object-fit: contain; display: block; margin-bottom: 10px; }
            .sidebar-menu { list-style: none; padding: 0; margin: 0; }
            .sidebar-menu li { margin-bottom: 4px; }
            .sidebar-link {
                display: flex; align-items: center; gap: 12px; padding: 12px 24px;
                color: rgba(255,255,255,.85); text-decoration: none; transition: .2s ease;
            }
            .sidebar-link:hover, .sidebar-link.active { background: rgba(61,148,202,.18); color: #fff; }
            .admin-main {
                margin-left: 280px; flex-grow: 1; display: flex; flex-direction: column;
                background: #f5f7fb; min-width: 0; min-height: 100vh; transition: margin-left 0.3s ease;
            }
            body.sidebar-collapsed .admin-sidebar { transform: translateX(-100%); }
            body.sidebar-collapsed .admin-main { margin-left: 0; }
            .admin-topbar {
                position: sticky; top: 0; z-index: 1020; height: 72px; background: #fff;
                border-bottom: 1px solid var(--color-border); display: flex;
                align-items: center; justify-content: space-between; padding: 0 24px;
            }
            .sidebar-toggle-btn { background: transparent; border: none; color: #495057; font-size: 1.8rem; line-height: 1; padding: 0; cursor: pointer; }
            .sidebar-toggle-btn:hover { color: var(--color-primary); }
            .topbar-icon-btn { background: transparent; border: none; color: #495057; position: relative; }
            .notification-badge {
                position: absolute; top: -4px; right: -6px; width: 20px; height: 20px;
                border-radius: 50%; background: #3D94CA; color: #fff; font-size: 12px;
                display: flex; align-items: center; justify-content: center;
            }
            .admin-content { padding: 32px; }
            .dashboard-card { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 0 0 1px rgba(15,23,42,.03), 0 10px 30px rgba(15,23,42,.04); }
            .stat-label { font-size: .95rem; color: #7c8793; margin-bottom: 10px; font-weight: 600; }
            .stat-value { font-size: 2.2rem; font-weight: 700; line-height: 1; margin-bottom: 12px; }
            .stat-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
            .soft-blue { background: rgba(61,148,202,.16); color: #3D94CA; }
            .soft-green { background: rgba(129,174,141,.18); color: #81AE8D; }
            .soft-yellow { background: rgba(232,177,86,.18); color: #E8B156; }
            .soft-neutral { background: rgba(218,206,182,.45); color: #23779C; }
            .soft-red { background: rgba(220,53,69,.15); color: #dc3545; }
            .card-horozia { border: none; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); border-radius: 14px; background-color: #fff; }
            .btn-horozia-primary { background-color: var(--color-primary); border-color: var(--color-primary); color: white; border-radius: 10px; font-weight: 600; transition: .2s ease; }
            .btn-horozia-primary:hover { background-color: #1d6482; border-color: #1d6482; color: white; }
            .btn-horozia-secondary { background-color: var(--color-secondary); border-color: var(--color-secondary); color: white; border-radius: 10px; font-weight: 600; }
            .btn-horozia-secondary:hover { background-color: #337fad; color: white; }
            .btn-horozia-accent { background-color: var(--color-accent); border-color: var(--color-accent); color: white; border-radius: 10px; font-weight: 600; }
            .btn-horozia-warning { background-color: var(--color-warning); border-color: var(--color-warning); color: white; border-radius: 10px; font-weight: 600; }
            .btn-horozia-danger { background-color: #dc3545; border-color: #dc3545; color: white; border-radius: 10px; font-weight: 600; }
            .btn-horozia-danger:hover { background-color: #bb2d3b; color: white; }
            .btn-horozia-outline { background: transparent; border: 2px solid var(--color-primary); color: var(--color-primary); border-radius: 10px; font-weight: 600; transition: .2s; }
            .btn-horozia-outline:hover { background: var(--color-primary); color: #fff; }
            .alert-horozia { border: none; border-radius: 14px; padding: 14px 18px; font-weight: 500; box-shadow: 0 8px 18px rgba(0,0,0,.06); }
            .alert-success.alert-horozia { background-color: rgba(129,174,141,.18); color: #2f5d3a; }
            .alert-danger.alert-horozia { background-color: rgba(220,53,69,.12); color: #842029; }
            .alert-warning.alert-horozia { background-color: rgba(232,177,86,.22); color: #7a5714; }
            .alert-info.alert-horozia { background-color: rgba(61,148,202,.16); color: #174f6a; }
            .table-horozia th { background-color: var(--color-primary) !important; color: white !important; }
            .dashboard-table thead th { color: #6f7c89; font-weight: 700; border-bottom: 1px solid #e9edf3; }
            .dashboard-table td, .dashboard-table th { padding: 14px 12px; }
            .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
            .page-header-left { display: flex; align-items: center; gap: 16px; }
            .page-header-icon {
                width: 56px; height: 56px; border-radius: 14px;
                background: linear-gradient(135deg, rgba(61,148,202,.15), rgba(35,119,156,.20));
                display: flex; align-items: center; justify-content: center;
                color: var(--color-primary); font-size: 1.4rem;
            }
            .page-header h2 { font-size: 1.7rem; font-weight: 800; color: #1f2d3d; margin: 0; }
            .badge-count { display: inline-flex; align-items: center; justify-content: center; background: var(--color-secondary); color: #fff; border-radius: 20px; padding: 2px 12px; font-size: .85rem; font-weight: 700; margin-left: 8px; }
            .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; transition: .2s; text-decoration: none; }
            .action-btn-view { background: rgba(61,148,202,.12); color: #3D94CA; }
            .action-btn-view:hover { background: #3D94CA; color: #fff; }
            .action-btn-edit { background: rgba(232,177,86,.15); color: #E8B156; }
            .action-btn-edit:hover { background: #E8B156; color: #fff; }
            .action-btn-delete { background: rgba(220,53,69,.12); color: #dc3545; }
            .action-btn-delete:hover { background: #dc3545; color: #fff; }
            .badge-reservee { background: rgba(61,148,202,.15); color: #3D94CA; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 700; }
            .badge-en-cours { background: rgba(129,174,141,.2); color: #2f5d3a; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 700; }
            .badge-terminee { background: rgba(108,117,125,.15); color: #495057; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 700; }
            .badge-annulee { background: rgba(220,53,69,.12); color: #842029; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 700; }
            .badge-noshow { background: rgba(232,177,86,.2); color: #7a5714; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 700; }
            .badge-disponible { background: rgba(129,174,141,.2); color: #2f5d3a; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 700; }
            .badge-louee { background: rgba(61,148,202,.15); color: #174f6a; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 700; }
            .badge-maintenance { background: rgba(232,177,86,.2); color: #7a5714; padding: 4px 12px; border-radius: 20px; font-size: .82rem; font-weight: 700; }
            .form-section { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); margin-bottom: 20px; }
            .form-section-title { font-size: 1rem; font-weight: 700; color: #1f2d3d; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #eef2f7; display: flex; align-items: center; gap: 8px; }
            .form-label { font-weight: 600; color: #4d5965; font-size: .92rem; }
            .form-control, .form-select { border-radius: 10px; border: 1px solid #dee2e6; padding: 10px 14px; font-size: .95rem; }
            .form-control:focus, .form-select:focus { border-color: var(--color-secondary); box-shadow: 0 0 0 3px rgba(61,148,202,.15); }
            .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
            .calendar-cell {
                min-height: 90px; background: #fff; border-radius: 10px; padding: 8px;
                border: 1px solid #eef2f7; transition: .2s;
            }
            .calendar-cell:hover { border-color: var(--color-secondary); }
            .calendar-cell.today { border-color: var(--color-primary); background: rgba(61,148,202,.04); }
            .calendar-cell.empty { background: #f5f7fb; border: 1px solid #eef2f7; }
            .calendar-day-num { font-size: .85rem; font-weight: 700; color: #4d5965; margin-bottom: 4px; }
            .calendar-event { font-size: .72rem; padding: 2px 6px; border-radius: 6px; margin-bottom: 2px; background: rgba(61,148,202,.15); color: #174f6a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
        </style>
    {% endblock %}
</head>
<body>
    {% block body %}{% endblock %}

    {% block javascripts %}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const flashMessages = document.querySelectorAll('.swal-flash-message');
                flashMessages.forEach(item => {
                    const type = item.dataset.type;
                    const message = item.dataset.message;
                    let icon = 'info', title = 'Information';
                    if (type === 'success') { icon = 'success'; title = 'Succes'; }
                    else if (type === 'error' || type === 'danger') { icon = 'error'; title = 'Erreur'; }
                    else if (type === 'warning') { icon = 'warning'; title = 'Attention'; }
                    Swal.fire({ icon, title, text: message, timer: 2500, showConfirmButton: false });
                });

                const toggleButton = document.getElementById('sidebarToggle');
                if (localStorage.getItem('sidebar-collapsed') === 'true') {
                    document.body.classList.add('sidebar-collapsed');
                }
                if (toggleButton) {
                    toggleButton.addEventListener('click', function () {
                        document.body.classList.toggle('sidebar-collapsed');
                        localStorage.setItem('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
                    });
                }
            });
        </script>
    {% endblock %}
</body>
</html>
'@

# ============================================================
# 2. admin/layout.html.twig
# ============================================================
Write-Twig "templates/admin/layout.html.twig" @'
{% extends 'base.html.twig' %}

{% block body %}
<div class="admin-layout">
    {% include 'admin/sidebar.html.twig' %}
    <main class="admin-main">
        {% include 'admin/topbar.html.twig' %}
        <div class="admin-content">
            {% for type, messages in app.flashes %}
                {% for message in messages %}
                    <div class="swal-flash-message d-none" data-type="{{ type }}" data-message="{{ message|e('html_attr') }}"></div>
                {% endfor %}
            {% endfor %}
            {% block admin_content %}{% endblock %}
        </div>
        <footer class="admin-footer text-center py-3">
            <small>© 2025 Horizia - Agence de Location de Vehicules</small>
        </footer>
    </main>
</div>
{% endblock %}
'@

# ============================================================
# 3. admin/sidebar.html.twig
# ============================================================
Write-Twig "templates/admin/sidebar.html.twig" @'
{% set route = app.request.attributes.get('_route') %}
<div class="admin-sidebar">
    <div class="sidebar-brand-center">
        <img src="{{ asset('images/logo.png') }}" alt="Horizia" class="sidebar-logo-large">
        <span style="color:#fff;font-weight:800;font-size:1.1rem;letter-spacing:1px;">HORIZIA</span>
        <span style="color:rgba(255,255,255,.5);font-size:.75rem;">Location de Vehicules</span>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a class="sidebar-link {{ route starts with 'admin_dashboard' ? 'active' : '' }}" href="{{ path('admin_dashboard') }}">
                <i class="bi bi-grid-fill"></i><span>Dashboard</span>
            </a>
        </li>
        <li>
            <a class="sidebar-link {{ route starts with 'admin_vehicule' ? 'active' : '' }}" href="{{ path('admin_vehicule_index') }}">
                <i class="bi bi-truck-front-fill"></i><span>Vehicules</span>
            </a>
        </li>
        <li>
            <a class="sidebar-link {{ route starts with 'admin_location' ? 'active' : '' }}" href="{{ path('admin_location_index') }}">
                <i class="bi bi-calendar-check-fill"></i><span>Locations</span>
            </a>
        </li>
        <li>
            <a class="sidebar-link {{ route starts with 'admin_planning' ? 'active' : '' }}" href="{{ path('admin_planning_index') }}">
                <i class="bi bi-calendar3"></i><span>Planning</span>
            </a>
        </li>
        <li>
            <a class="sidebar-link {{ route starts with 'admin_documents' ? 'active' : '' }}" href="{{ path('admin_documents_index') }}">
                <i class="bi bi-file-earmark-text-fill"></i><span>Documents</span>
            </a>
        </li>
        <li style="border-top:1px solid rgba(255,255,255,.08);margin-top:8px;padding-top:8px;">
            <a class="sidebar-link {{ route starts with 'admin_marque' ? 'active' : '' }}" href="{{ path('admin_marque_index') }}">
                <i class="bi bi-tags-fill"></i><span>Marques</span>
            </a>
        </li>
        <li>
            <a class="sidebar-link {{ route starts with 'admin_modele' ? 'active' : '' }}" href="{{ path('admin_modele_index') }}">
                <i class="bi bi-list-ul"></i><span>Modeles</span>
            </a>
        </li>
    </ul>
</div>
'@

# ============================================================
# 4. admin/topbar.html.twig
# ============================================================
Write-Twig "templates/admin/topbar.html.twig" @'
<nav class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
        <button type="button" id="sidebarToggle" class="sidebar-toggle-btn">
            <i class="bi bi-list"></i>
        </button>
        <span style="font-weight:700;color:#1f2d3d;font-size:1.05rem;">{% block topbar_title %}Admin{% endblock %}</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <button class="topbar-icon-btn position-relative" type="button">
            <i class="bi bi-bell fs-5"></i>
        </button>
        <div class="dropdown">
            <button class="btn border-0 bg-transparent d-flex align-items-center gap-2 p-0" type="button" data-bs-toggle="dropdown">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#23779C,#3D94CA);display:flex;align-items:center;justify-content:center;color:#fff;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <span class="fw-semibold text-dark d-none d-md-inline">Administrateur</span>
                <i class="bi bi-chevron-down small text-muted"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-2" style="min-width:200px;">
                <li><a class="dropdown-item rounded-2 py-2" href="{{ path('admin_dashboard') }}"><i class="bi bi-grid me-2"></i>Dashboard</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item rounded-2 py-2 text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i>Deconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>
'@

# ============================================================
# 5. dashboard/index.html.twig
# ============================================================
Write-Twig "templates/admin/dashboard/index.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Dashboard – Horizia{% endblock %}
{% block topbar_title %}Dashboard{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-grid-fill"></i></div>
        <div>
            <h2>Dashboard</h2>
            <p class="text-muted mb-0" style="font-size:.92rem;">Vue d'ensemble – {{ moisNom }}</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="dashboard-card d-flex align-items-center gap-3">
            <div class="stat-icon soft-blue"><i class="bi bi-truck-front-fill fs-5"></i></div>
            <div>
                <div class="stat-label mb-0">Vehicules dispo</div>
                <div class="stat-value" style="font-size:1.8rem;">{{ vehiculesDisponibles }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dashboard-card d-flex align-items-center gap-3">
            <div class="stat-icon soft-yellow"><i class="bi bi-key-fill fs-5"></i></div>
            <div>
                <div class="stat-label mb-0">Vehicules loues</div>
                <div class="stat-value" style="font-size:1.8rem;">{{ vehiculesLoues }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dashboard-card d-flex align-items-center gap-3">
            <div class="stat-icon soft-green"><i class="bi bi-calendar-check-fill fs-5"></i></div>
            <div>
                <div class="stat-label mb-0">Locations actives</div>
                <div class="stat-value" style="font-size:1.8rem;">{{ locationsActives }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dashboard-card d-flex align-items-center gap-3">
            <div class="stat-icon soft-neutral"><i class="bi bi-cash-coin fs-5"></i></div>
            <div>
                <div class="stat-label mb-0">CA du mois</div>
                <div class="stat-value" style="font-size:1.5rem;">{{ caMois|number_format(0, ',', ' ') }} TND</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-title-custom mb-3"><i class="bi bi-pie-chart-fill me-2" style="color:var(--color-primary);"></i>Repartition statuts</div>
            <div style="width:220px;height:220px;margin:0 auto;position:relative;">
                <canvas id="chartStatuts"></canvas>
            </div>
            <div class="mt-3">
                {% for statut, nb in statuts %}
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.88rem;">
                    <span>{{ statut }}</span>
                    <span class="fw-700">{{ nb }}</span>
                </div>
                {% endfor %}
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-title-custom mb-3"><i class="bi bi-trophy-fill me-2" style="color:var(--color-warning);"></i>Top modeles loues</div>
            {% if topModele.total > 0 %}
            <div class="text-center mb-3" style="padding:16px;background:rgba(61,148,202,.07);border-radius:12px;">
                <div style="font-size:2rem;font-weight:800;color:var(--color-primary);">{{ topModele.nomMarque }} {{ topModele.nomModele }}</div>
                <div class="text-muted" style="font-size:.85rem;">{{ topModele.total }} location(s)</div>
            </div>
            {% endif %}
            {% for nom, nb in top5Modeles %}
            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:.9rem;">
                <span><i class="bi bi-car-front me-2" style="color:var(--color-secondary);"></i>{{ nom }}</span>
                <span class="badge-count" style="background:rgba(61,148,202,.15);color:var(--color-primary);padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:700;">{{ nb }}</span>
            </div>
            {% endfor %}
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-title-custom mb-3"><i class="bi bi-speedometer2 me-2" style="color:var(--color-accent);"></i>Taux d'occupation</div>
            <div class="text-center py-3">
                <div style="font-size:3rem;font-weight:800;color:var(--color-primary);">{{ tauxOccupation }}%</div>
                <div class="text-muted" style="font-size:.88rem;">{{ moisNom }}</div>
            </div>
            <div style="background:#eef2f7;border-radius:99px;height:12px;overflow:hidden;">
                <div style="height:100%;width:{{ tauxOccupation }}%;background:linear-gradient(90deg,#23779C,#3D94CA);border-radius:99px;transition:.6s;"></div>
            </div>
        </div>
    </div>
</div>

{% if alertesRetour|length > 0 %}
<div class="dashboard-card mb-3">
    <div class="card-title-custom mb-3" style="color:#dc3545;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Retours prevus dans 3 jours</div>
    <div class="table-responsive">
        <table class="table dashboard-table">
            <thead><tr><th>#</th><th>Client</th><th>Vehicule</th><th>Fin prevue</th><th>Action</th></tr></thead>
            <tbody>
                {% for loc in alertesRetour %}
                <tr>
                    <td>{{ loc.idLocation }}</td>
                    <td>{{ loc.clientNomComplet }}</td>
                    <td>{{ loc.vehicule.immatriculation }}</td>
                    <td style="color:#dc3545;font-weight:700;">{{ loc.dateFinPrevue|date('d/m/Y') }}</td>
                    <td><a href="{{ path('admin_location_show', {id: loc.idLocation}) }}" class="action-btn action-btn-view"><i class="bi bi-eye"></i></a></td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
</div>
{% endif %}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartStatuts');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [{% for s, n in statuts %}'{{ s }}'{% if not loop.last %},{% endif %}{% endfor %}],
                datasets: [{
                    data: [{% for s, n in statuts %}{{ n }}{% if not loop.last %},{% endif %}{% endfor %}],
                    backgroundColor: ['#3D94CA','#81AE8D','#6c757d','#dc3545','#E8B156'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, cutout: '65%' }
        });
    }
});
</script>
{% endblock %}
'@

# ============================================================
# 6. location/index.html.twig
# ============================================================
Write-Twig "templates/admin/location/index.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Locations – Horizia{% endblock %}
{% block topbar_title %}Locations{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-calendar-check-fill"></i></div>
        <div>
            <h2>Locations <span class="badge-count">{{ locations|length }}</span></h2>
            <p class="text-muted mb-0" style="font-size:.92rem;">Gestion des locations de vehicules</p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="post" action="{{ path('admin_location_update_statuts') }}" style="display:inline;">
            <input type="hidden" name="_token" value="{{ csrf_token('update_statuts') }}">
            <button type="submit" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Maj statuts</button>
        </form>
        <a href="{{ path('admin_location_new') }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvelle location</a>
    </div>
</div>

<div class="card-horozia p-3 mb-3">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label mb-1">Rechercher</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Nom client, telephone..." value="{{ recherche }}" style="min-width:220px;">
        </div>
        <button type="submit" class="btn btn-horozia-primary btn-sm"><i class="bi bi-search me-1"></i>Rechercher</button>
        {% if recherche %}
        <a href="{{ path('admin_location_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-x-lg me-1"></i>Effacer</a>
        {% endif %}
    </form>
</div>

<div class="card-horozia">
    {% if locations|length > 0 %}
    <div class="table-responsive">
        <table class="table dashboard-table mb-0">
            <thead><tr class="table-horozia">
                <th>#</th><th>Client</th><th>Vehicule</th><th>Debut</th><th>Fin prevue</th><th>Montant</th><th>Statut</th><th>Actions</th>
            </tr></thead>
            <tbody>
                {% for location in locations %}
                <tr>
                    <td><span style="font-weight:700;color:var(--color-primary);">#{{ location.idLocation }}</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#23779C,#3D94CA);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;flex-shrink:0;"><i class="bi bi-person-fill"></i></div>
                            <div>
                                <strong style="font-size:.92rem;">{{ location.clientNomComplet }}</strong>
                                <div style="font-size:.82rem;color:#6c757d;">{{ location.clientTelephone }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span style="background:rgba(61,148,202,.12);color:#174f6a;padding:4px 10px;border-radius:20px;font-size:.82rem;font-weight:700;"><i class="bi bi-truck me-1"></i>{{ location.vehicule.immatriculation }}</span></td>
                    <td style="font-size:.9rem;">{{ location.dateDebut|date('d/m/Y') }}</td>
                    <td style="font-size:.9rem;">{{ location.dateFinPrevue|date('d/m/Y') }}</td>
                    <td style="font-weight:700;color:var(--color-primary);">{% if location.montantTotal %}{{ location.montantTotal|number_format(3, ',', ' ') }} TND{% endif %}</td>
                    <td>
                        {% if location.statut == 'reservee' or location.statut == 'reservee' %}<span class="badge-reservee">Reservee</span>
                        {% elseif location.statut == 'en_cours' %}<span class="badge-en-cours">En cours</span>
                        {% elseif location.statut == 'terminee' or location.statut == 'terminee' %}<span class="badge-terminee">Terminee</span>
                        {% elseif location.statut == 'annulee' or location.statut == 'annulee' %}<span class="badge-annulee">Annulee</span>
                        {% elseif location.statut == 'no_show' %}<span class="badge-noshow">No show</span>
                        {% else %}<span class="badge-terminee">{{ location.statut }}</span>{% endif %}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ path('admin_location_show', {id: location.idLocation}) }}" class="action-btn action-btn-view" title="Voir"><i class="bi bi-eye"></i></a>
                            <a href="{{ path('admin_location_edit', {id: location.idLocation}) }}" class="action-btn action-btn-edit" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="{{ path('admin_location_delete', {id: location.idLocation}) }}" onsubmit="return confirm('Supprimer cette location ?')">
                                <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ location.idLocation) }}">
                                <button type="submit" class="action-btn action-btn-delete" title="Supprimer"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
    {% else %}
    <div class="text-center py-5" style="color:#6c757d;">
        <i class="bi bi-calendar-x" style="font-size:3rem;opacity:.3;display:block;margin-bottom:12px;"></i>
        <p class="mb-3">Aucune location trouvee.</p>
        <a href="{{ path('admin_location_new') }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Creer une location</a>
    </div>
    {% endif %}
</div>
{% endblock %}
'@

# ============================================================
# 7. location/new.html.twig
# ============================================================
Write-Twig "templates/admin/location/new.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Nouvelle location – Horizia{% endblock %}
{% block topbar_title %}Nouvelle location{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-plus-circle-fill"></i></div>
        <div>
            <h2>Nouvelle location</h2>
            <p class="text-muted mb-0" style="font-size:.92rem;">Creer une nouvelle reservation de vehicule</p>
        </div>
    </div>
    <a href="{{ path('admin_location_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

{{ form_start(form, {attr: {class: 'needs-validation', novalidate: 'novalidate'}}) }}
<div class="row g-3">
    <div class="col-lg-8">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-person-fill" style="color:var(--color-primary);"></i>Informations client</div>
            <div class="row g-3">
                <div class="col-md-6">
                    {{ form_label(form.clientNomComplet) }}
                    {{ form_widget(form.clientNomComplet) }}
                    {{ form_errors(form.clientNomComplet) }}
                </div>
                <div class="col-md-6">
                    {{ form_label(form.clientTelephone) }}
                    {{ form_widget(form.clientTelephone) }}
                    {{ form_errors(form.clientTelephone) }}
                </div>
                <div class="col-md-6">
                    {{ form_label(form.clientCin) }}
                    {{ form_widget(form.clientCin) }}
                    {{ form_errors(form.clientCin) }}
                </div>
                <div class="col-md-6">
                    {{ form_label(form.clientVille) }}
                    {{ form_widget(form.clientVille) }}
                    {{ form_errors(form.clientVille) }}
                </div>
                <div class="col-12">
                    {{ form_label(form.clientAdresse) }}
                    {{ form_widget(form.clientAdresse) }}
                    {{ form_errors(form.clientAdresse) }}
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-truck-front-fill" style="color:var(--color-primary);"></i>Vehicule et periode</div>
            <div class="row g-3">
                <div class="col-12">
                    {{ form_label(form.vehicule) }}
                    {{ form_widget(form.vehicule) }}
                    {{ form_errors(form.vehicule) }}
                </div>
                <div class="col-md-6">
                    {{ form_label(form.dateDebut) }}
                    {{ form_widget(form.dateDebut) }}
                    {{ form_errors(form.dateDebut) }}
                </div>
                <div class="col-md-6">
                    {{ form_label(form.dateFinPrevue) }}
                    {{ form_widget(form.dateFinPrevue) }}
                    {{ form_errors(form.dateFinPrevue) }}
                </div>
                <div class="col-md-6">
                    {{ form_label(form.kilometrageDebut) }}
                    {{ form_widget(form.kilometrageDebut) }}
                    {{ form_errors(form.kilometrageDebut) }}
                </div>
                <div class="col-md-6">
                    {{ form_label(form.statut) }}
                    {{ form_widget(form.statut) }}
                    {{ form_errors(form.statut) }}
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-receipt" style="color:var(--color-primary);"></i>Facturation</div>
            <div class="row g-3">
                <div class="col-md-4">
                    {{ form_label(form.prixParJour) }}
                    {{ form_widget(form.prixParJour) }}
                    {{ form_errors(form.prixParJour) }}
                </div>
                <div class="col-md-4">
                    {{ form_label(form.avance) }}
                    {{ form_widget(form.avance) }}
                    {{ form_errors(form.avance) }}
                </div>
                <div class="col-md-4">
                    {{ form_label(form.montantTotal) }}
                    {{ form_widget(form.montantTotal) }}
                    {{ form_errors(form.montantTotal) }}
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-chat-text" style="color:var(--color-primary);"></i>Notes</div>
            {{ form_widget(form.notes) }}
            {{ form_errors(form.notes) }}
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-envelope-fill" style="color:var(--color-primary);"></i>Email confirmation</div>
            <label class="form-label">Email client (optionnel)</label>
            <input type="email" name="email_client" class="form-control" placeholder="client@email.com">
            <div class="mt-2" style="font-size:.82rem;color:#6c757d;">Un email de confirmation sera envoye automatiquement.</div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-info-circle" style="color:var(--color-primary);"></i>Informations</div>
            <div style="font-size:.85rem;color:#6c757d;line-height:1.7;">
                <div class="mb-1"><i class="bi bi-check2 me-1" style="color:var(--color-accent);"></i>Le montant sera calcule automatiquement</div>
                <div class="mb-1"><i class="bi bi-check2 me-1" style="color:var(--color-accent);"></i>Le statut du vehicule sera mis a jour</div>
                <div><i class="bi bi-check2 me-1" style="color:var(--color-accent);"></i>Un QR code sera genere</div>
            </div>
        </div>
        <button type="submit" class="btn btn-horozia-primary w-100"><i class="bi bi-check-lg me-2"></i>Enregistrer la location</button>
        <a href="{{ path('admin_location_index') }}" class="btn btn-horozia-outline w-100 mt-2">Annuler</a>
    </div>
</div>
{{ form_end(form) }}
{% endblock %}
'@

# ============================================================
# 8. location/edit.html.twig
# ============================================================
Write-Twig "templates/admin/location/edit.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Modifier location #{{ location.idLocation }} – Horizia{% endblock %}
{% block topbar_title %}Modifier location{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-pencil-fill"></i></div>
        <div>
            <h2>Modifier location <span class="badge-count">#{{ location.idLocation }}</span></h2>
            <p class="text-muted mb-0" style="font-size:.92rem;">{{ location.clientNomComplet }}</p>
        </div>
    </div>
    <a href="{{ path('admin_location_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

{{ form_start(form) }}
<div class="row g-3">
    <div class="col-lg-8">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-person-fill" style="color:var(--color-primary);"></i>Informations client</div>
            <div class="row g-3">
                <div class="col-md-6">{{ form_label(form.clientNomComplet) }}{{ form_widget(form.clientNomComplet) }}{{ form_errors(form.clientNomComplet) }}</div>
                <div class="col-md-6">{{ form_label(form.clientTelephone) }}{{ form_widget(form.clientTelephone) }}{{ form_errors(form.clientTelephone) }}</div>
                <div class="col-md-6">{{ form_label(form.clientCin) }}{{ form_widget(form.clientCin) }}{{ form_errors(form.clientCin) }}</div>
                <div class="col-md-6">{{ form_label(form.clientVille) }}{{ form_widget(form.clientVille) }}{{ form_errors(form.clientVille) }}</div>
                <div class="col-12">{{ form_label(form.clientAdresse) }}{{ form_widget(form.clientAdresse) }}{{ form_errors(form.clientAdresse) }}</div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-truck-front-fill" style="color:var(--color-primary);"></i>Vehicule et periode</div>
            <div class="row g-3">
                <div class="col-12">{{ form_label(form.vehicule) }}{{ form_widget(form.vehicule) }}{{ form_errors(form.vehicule) }}</div>
                <div class="col-md-6">{{ form_label(form.dateDebut) }}{{ form_widget(form.dateDebut) }}{{ form_errors(form.dateDebut) }}</div>
                <div class="col-md-6">{{ form_label(form.dateFinPrevue) }}{{ form_widget(form.dateFinPrevue) }}{{ form_errors(form.dateFinPrevue) }}</div>
                <div class="col-md-6">{{ form_label(form.dateFinReelle) }}{{ form_widget(form.dateFinReelle) }}{{ form_errors(form.dateFinReelle) }}</div>
                <div class="col-md-6">{{ form_label(form.statut) }}{{ form_widget(form.statut) }}{{ form_errors(form.statut) }}</div>
                <div class="col-md-6">{{ form_label(form.kilometrageDebut) }}{{ form_widget(form.kilometrageDebut) }}{{ form_errors(form.kilometrageDebut) }}</div>
                <div class="col-md-6">{{ form_label(form.kilometrageRetour) }}{{ form_widget(form.kilometrageRetour) }}{{ form_errors(form.kilometrageRetour) }}</div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-receipt" style="color:var(--color-primary);"></i>Facturation</div>
            <div class="row g-3">
                <div class="col-md-4">{{ form_label(form.prixParJour) }}{{ form_widget(form.prixParJour) }}{{ form_errors(form.prixParJour) }}</div>
                <div class="col-md-4">{{ form_label(form.avance) }}{{ form_widget(form.avance) }}{{ form_errors(form.avance) }}</div>
                <div class="col-md-4">{{ form_label(form.montantTotal) }}{{ form_widget(form.montantTotal) }}{{ form_errors(form.montantTotal) }}</div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-chat-text" style="color:var(--color-primary);"></i>Notes</div>
            {{ form_widget(form.notes) }}{{ form_errors(form.notes) }}
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-section">
            <div class="form-section-title">Actions</div>
            <button type="submit" class="btn btn-horozia-primary w-100 mb-2"><i class="bi bi-check-lg me-2"></i>Enregistrer</button>
            <a href="{{ path('admin_location_show', {id: location.idLocation}) }}" class="btn btn-horozia-outline w-100 mb-2">Voir la location</a>
            <a href="{{ path('admin_location_index') }}" class="btn btn-horozia-outline w-100">Annuler</a>
        </div>
        <div class="form-section">
            <div class="form-section-title" style="color:#dc3545;"><i class="bi bi-trash me-1"></i>Zone dangereuse</div>
            <form method="post" action="{{ path('admin_location_delete', {id: location.idLocation}) }}" onsubmit="return confirm('Supprimer definitivement cette location ?')">
                <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ location.idLocation) }}">
                <button type="submit" class="btn btn-horozia-danger w-100 btn-sm"><i class="bi bi-trash me-1"></i>Supprimer</button>
            </form>
        </div>
    </div>
</div>
{{ form_end(form) }}
{% endblock %}
'@

# ============================================================
# 9. location/show.html.twig
# ============================================================
Write-Twig "templates/admin/location/show.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Location #{{ location.idLocation }} – Horizia{% endblock %}
{% block topbar_title %}Detail location{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-calendar-check-fill"></i></div>
        <div>
            <h2>Location <span class="badge-count">#{{ location.idLocation }}</span></h2>
            <p class="text-muted mb-0" style="font-size:.92rem;">{{ location.clientNomComplet }}</p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ path('admin_location_edit', {id: location.idLocation}) }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
        <a href="{{ path('admin_location_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="dashboard-card text-center mb-3">
            <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#23779C,#3D94CA);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;margin:0 auto 14px;"><i class="bi bi-person-fill"></i></div>
            <h4 style="font-weight:800;margin-bottom:4px;">{{ location.clientNomComplet }}</h4>
            {% if location.clientTelephone %}
            <p style="color:#6c757d;font-size:.9rem;margin-bottom:4px;"><i class="bi bi-telephone me-1"></i>{{ location.clientTelephone }}</p>
            {% endif %}
            {% if location.clientCin %}
            <p style="color:#6c757d;font-size:.9rem;margin-bottom:12px;"><i class="bi bi-card-text me-1"></i>CIN: {{ location.clientCin }}</p>
            {% endif %}
            {% if location.statut == 'reservee' or location.statut == 'reservee' %}<span class="badge-reservee">Reservee</span>
            {% elseif location.statut == 'en_cours' %}<span class="badge-en-cours">En cours</span>
            {% elseif location.statut == 'terminee' or location.statut == 'terminee' %}<span class="badge-terminee">Terminee</span>
            {% elseif location.statut == 'annulee' or location.statut == 'annulee' %}<span class="badge-annulee">Annulee</span>
            {% elseif location.statut == 'no_show' %}<span class="badge-noshow">No show</span>
            {% else %}<span class="badge-terminee">{{ location.statut }}</span>{% endif %}
            {% if location.montantTotal %}
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #eef2f7;">
                <div style="font-size:.85rem;color:#6c757d;font-weight:600;">Montant total</div>
                <div style="font-size:2rem;font-weight:800;color:var(--color-primary);">{{ location.montantTotal|number_format(3, ',', ' ') }} TND</div>
            </div>
            {% endif %}
        </div>

        <div class="dashboard-card mb-3">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:12px;"><i class="bi bi-truck me-2" style="color:var(--color-primary);"></i>Vehicule</div>
            {% if location.vehicule.photo %}
            <img src="{{ location.vehicule.photo }}" alt="{{ location.vehicule.immatriculation }}" style="width:100%;height:120px;object-fit:cover;border-radius:10px;margin-bottom:12px;">
            {% endif %}
            <div class="d-flex justify-content-between mb-1" style="font-size:.9rem;"><span style="color:#6c757d;">Immatriculation</span><span style="font-weight:700;">{{ location.vehicule.immatriculation }}</span></div>
            {% if location.vehicule.modele is defined %}
            <div class="d-flex justify-content-between mb-1" style="font-size:.9rem;"><span style="color:#6c757d;">Modele</span><span>{{ location.vehicule.modele.marque.nomMarque ?? '' }} {{ location.vehicule.modele.nomModele ?? '' }}</span></div>
            {% endif %}
            <div class="d-flex justify-content-between" style="font-size:.9rem;"><span style="color:#6c757d;">Prix/jour</span><span style="font-weight:700;color:var(--color-primary);">{{ location.vehicule.prixParJour|number_format(3, ',', ' ') }} TND</span></div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="dashboard-card mb-3">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:16px;"><i class="bi bi-calendar-range me-2" style="color:var(--color-primary);"></i>Periode</div>
            <div class="row g-3 text-center">
                <div class="col-md-4">
                    <div style="background:#f5f7fb;border-radius:10px;padding:16px;">
                        <div style="font-size:.82rem;color:#6c757d;margin-bottom:4px;"><i class="bi bi-calendar-event me-1"></i>Debut</div>
                        <div style="font-weight:700;">{{ location.dateDebut|date('d/m/Y') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background:#f5f7fb;border-radius:10px;padding:16px;">
                        <div style="font-size:.82rem;color:#6c757d;margin-bottom:4px;"><i class="bi bi-hourglass me-1"></i>Duree</div>
                        {% set jours = ((location.dateFinPrevue|date('U') - location.dateDebut|date('U')) / 86400)|round %}
                        <div style="font-weight:800;color:var(--color-primary);">{{ jours }} jour(s)</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background:#f5f7fb;border-radius:10px;padding:16px;">
                        <div style="font-size:.82rem;color:#6c757d;margin-bottom:4px;"><i class="bi bi-calendar-check me-1"></i>Fin prevue</div>
                        <div style="font-weight:700;">{{ location.dateFinPrevue|date('d/m/Y') }}</div>
                    </div>
                </div>
            </div>
            {% if location.dateFinReelle %}
            <div style="background:rgba(129,174,141,.12);border:1px solid rgba(129,174,141,.3);border-radius:10px;padding:12px 16px;margin-top:12px;font-size:.9rem;">
                <i class="bi bi-calendar2-check me-2" style="color:var(--color-accent);"></i>Date de fin reelle : <strong>{{ location.dateFinReelle|date('d/m/Y') }}</strong>
            </div>
            {% endif %}
        </div>

        <div class="dashboard-card mb-3">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:16px;"><i class="bi bi-receipt me-2" style="color:var(--color-primary);"></i>Details financiers</div>
            {% if location.prixParJour %}
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;font-size:.92rem;"><span style="color:#6c757d;">Prix / jour</span><span style="font-weight:700;color:var(--color-primary);">{{ location.prixParJour|number_format(3, ',', ' ') }} TND</span></div>
            {% endif %}
            {% if location.montantTotal %}
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;font-size:.92rem;"><span style="color:#6c757d;">Montant total</span><span style="font-weight:800;color:var(--color-primary);">{{ location.montantTotal|number_format(3, ',', ' ') }} TND</span></div>
            {% endif %}
            {% if location.avance %}
            <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;font-size:.92rem;"><span style="color:#6c757d;">Avance versee</span><span style="font-weight:700;color:var(--color-accent);">{{ location.avance|number_format(3, ',', ' ') }} TND</span></div>
            {% endif %}
            {% if location.kilometrageDebut %}
            <div class="d-flex justify-content-between py-2" style="font-size:.92rem;"><span style="color:#6c757d;">Km depart</span><span>{{ location.kilometrageDebut }} km</span></div>
            {% endif %}
        </div>

        {% if location.notes %}
        <div class="dashboard-card mb-3">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:12px;"><i class="bi bi-chat-text me-2" style="color:var(--color-primary);"></i>Notes</div>
            <p style="font-size:.92rem;white-space:pre-wrap;margin:0;">{{ location.notes }}</p>
        </div>
        {% endif %}

        <div class="dashboard-card">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:14px;">Actions</div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ path('admin_location_edit', {id: location.idLocation}) }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                <a href="{{ path('admin_documents_qrcode', {id: location.idLocation}) }}" class="btn btn-horozia-secondary btn-sm"><i class="bi bi-qr-code me-1"></i>QR Code</a>
                <a href="{{ path('admin_documents_contrat', {id: location.idLocation}) }}" class="btn btn-horozia-accent btn-sm"><i class="bi bi-file-earmark-text me-1"></i>Contrat</a>
                <form method="post" action="{{ path('admin_location_delete', {id: location.idLocation}) }}" onsubmit="return confirm('Supprimer ?')">
                    <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ location.idLocation) }}">
                    <button type="submit" class="btn btn-horozia-danger btn-sm"><i class="bi bi-trash me-1"></i>Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 10. planning/index.html.twig
# ============================================================
Write-Twig "templates/admin/planning/index.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Planning {{ moisNom }} – Horizia{% endblock %}
{% block topbar_title %}Planning{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-calendar3"></i></div>
        <div>
            <h2>{{ moisNom }}</h2>
            <p class="text-muted mb-0" style="font-size:.92rem;">{{ locationsduMois|length }} location(s) ce mois</p>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        {% set prevMois = mois - 1 %}{% set prevAnnee = annee %}
        {% if prevMois < 1 %}{% set prevMois = 12 %}{% set prevAnnee = annee - 1 %}{% endif %}
        {% set nextMois = mois + 1 %}{% set nextAnnee = annee %}
        {% if nextMois > 12 %}{% set nextMois = 1 %}{% set nextAnnee = annee + 1 %}{% endif %}
        <a href="{{ path('admin_planning_mois', {annee: prevAnnee, mois: prevMois}) }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-chevron-left"></i></a>
        <a href="{{ path('admin_planning_index') }}" class="btn btn-horozia-outline btn-sm">Aujourd'hui</a>
        <a href="{{ path('admin_planning_mois', {annee: nextAnnee, mois: nextMois}) }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-chevron-right"></i></a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="dashboard-card text-center"><div style="font-size:1.8rem;font-weight:800;color:var(--color-primary);">{{ tauxOccupation }}%</div><div style="font-size:.85rem;color:#6c757d;">Taux d'occupation</div></div></div>
    <div class="col-6 col-lg-3"><div class="dashboard-card text-center"><div style="font-size:1.5rem;font-weight:800;color:var(--color-primary);">{{ caMois|number_format(0) }} TND</div><div style="font-size:.85rem;color:#6c757d;">CA du mois</div></div></div>
    <div class="col-6 col-lg-3"><div class="dashboard-card text-center"><div style="font-size:1.8rem;font-weight:800;color:var(--color-accent);">{{ statuts['en_cours'] ?? 0 }}</div><div style="font-size:.85rem;color:#6c757d;">En cours</div></div></div>
    <div class="col-6 col-lg-3"><div class="dashboard-card text-center"><div style="font-size:1.8rem;font-weight:800;color:var(--color-secondary);">{{ statuts['reservee'] ?? 0 }}</div><div style="font-size:.85rem;color:#6c757d;">Reservees</div></div></div>
</div>

<div class="dashboard-card mb-3">
    <div class="row mb-2">
        {% for jour in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] %}
        <div class="col" style="text-align:center;font-weight:700;font-size:.82rem;color:#6c757d;padding-bottom:8px;">{{ jour }}</div>
        {% endfor %}
    </div>
    {% set today = "now"|date('Y-m-d') %}
    <div class="calendar-grid">
        {% for i in 1..premierJourSemaine %}
            <div class="calendar-cell empty"></div>
        {% endfor %}
        {% for jour in 1..nbJours %}
            {% set dateStr = annee ~ '-' ~ mois|format('%02d') ~ '-' ~ jour|format('%02d') %}
            {% set locsJour = calendrier[dateStr] is defined ? calendrier[dateStr] : [] %}
            {% set isToday = dateStr == today %}
            <div class="calendar-cell {{ isToday ? 'today' : '' }}" onclick="window.location='{{ path('admin_planning_jour', {date: dateStr}) }}'" style="cursor:pointer;">
                <div class="calendar-day-num {{ isToday ? 'text-primary fw-800' : '' }}">{{ jour }}</div>
                {% for loc in locsJour|slice(0, 2) %}
                    <div class="calendar-event" title="{{ loc.vehicule.immatriculation }}">{{ loc.vehicule.immatriculation }}</div>
                {% endfor %}
                {% if locsJour|length > 2 %}
                    <div style="font-size:.7rem;color:#6c757d;">+{{ locsJour|length - 2 }} de plus</div>
                {% endif %}
            </div>
        {% endfor %}
    </div>
</div>

<div class="dashboard-card">
    <div style="font-weight:700;color:#1f2d3d;margin-bottom:14px;">Locations de {{ moisNom }} ({{ locationsduMois|length }})</div>
    {% if locationsduMois|length > 0 %}
    <div class="table-responsive">
        <table class="table dashboard-table mb-0">
            <thead><tr class="table-horozia"><th>#</th><th>Client</th><th>Vehicule</th><th>Debut</th><th>Fin prevue</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
                {% for loc in locationsduMois %}
                <tr>
                    <td style="font-weight:700;color:var(--color-primary);">#{{ loc.idLocation }}</td>
                    <td>{{ loc.clientNomComplet }}</td>
                    <td><span style="background:rgba(61,148,202,.12);color:#174f6a;padding:3px 10px;border-radius:20px;font-size:.82rem;font-weight:700;">{{ loc.vehicule.immatriculation }}</span></td>
                    <td>{{ loc.dateDebut|date('d/m/Y') }}</td>
                    <td>{{ loc.dateFinPrevue|date('d/m/Y') }}</td>
                    <td>
                        {% if loc.statut == 'en_cours' %}<span class="badge-en-cours">En cours</span>
                        {% elseif loc.statut == 'reservee' or loc.statut == 'reservee' %}<span class="badge-reservee">Reservee</span>
                        {% elseif loc.statut == 'terminee' or loc.statut == 'terminee' %}<span class="badge-terminee">Terminee</span>
                        {% else %}<span class="badge-terminee">{{ loc.statut }}</span>{% endif %}
                    </td>
                    <td><a href="{{ path('admin_location_show', {id: loc.idLocation}) }}" class="action-btn action-btn-view"><i class="bi bi-eye"></i></a></td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
    {% else %}
    <div class="text-center py-4" style="color:#6c757d;"><i class="bi bi-calendar-x" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px;"></i>Aucune location ce mois.</div>
    {% endif %}
</div>
{% endblock %}
'@

# ============================================================
# 11. planning/jour.html.twig
# ============================================================
Write-Twig "templates/admin/planning/jour.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Planning du {{ date|date('d/m/Y') }} – Horizia{% endblock %}
{% block topbar_title %}Planning du jour{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-calendar-day-fill"></i></div>
        <div>
            <h2>{{ date|date('d/m/Y') }}</h2>
            <p class="text-muted mb-0" style="font-size:.92rem;">{{ locations|length }} location(s) ce jour</p>
        </div>
    </div>
    <div class="d-flex gap-2">
        {% set prevDate = date|date_modify('-1 day')|date('Y-m-d') %}
        {% set nextDate = date|date_modify('+1 day')|date('Y-m-d') %}
        <a href="{{ path('admin_planning_jour', {date: prevDate}) }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-chevron-left"></i></a>
        <a href="{{ path('admin_planning_mois', {annee: date|date('Y'), mois: date|date('n')}) }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-calendar3 me-1"></i>Mois</a>
        <a href="{{ path('admin_planning_jour', {date: nextDate}) }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-chevron-right"></i></a>
    </div>
</div>

{% if locations|length > 0 %}
<div class="row g-3">
    {% for location in locations %}
    <div class="col-md-6 col-lg-4">
        <div class="dashboard-card">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#23779C,#3D94CA);display:flex;align-items:center;justify-content:center;color:#fff;"><i class="bi bi-person-fill"></i></div>
                <div>
                    <strong style="font-size:.95rem;">{{ location.clientNomComplet }}</strong>
                    <div style="font-size:.82rem;color:#6c757d;">{{ location.clientTelephone }}</div>
                </div>
            </div>
            <div class="d-flex justify-content-between mb-2" style="font-size:.88rem;">
                <span style="color:#6c757d;"><i class="bi bi-truck me-1"></i>Vehicule</span>
                <span style="font-weight:700;">{{ location.vehicule.immatriculation }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2" style="font-size:.88rem;">
                <span style="color:#6c757d;"><i class="bi bi-calendar-range me-1"></i>Periode</span>
                <span>{{ location.dateDebut|date('d/m') }} → {{ location.dateFinPrevue|date('d/m/Y') }}</span>
            </div>
            <div class="d-flex justify-content-between mb-3" style="font-size:.88rem;">
                <span style="color:#6c757d;">Statut</span>
                {% if location.statut == 'en_cours' %}<span class="badge-en-cours">En cours</span>
                {% elseif location.statut == 'reservee' or location.statut == 'reservee' %}<span class="badge-reservee">Reservee</span>
                {% elseif location.statut == 'terminee' or location.statut == 'terminee' %}<span class="badge-terminee">Terminee</span>
                {% else %}<span class="badge-terminee">{{ location.statut }}</span>{% endif %}
            </div>
            <a href="{{ path('admin_location_show', {id: location.idLocation}) }}" class="btn btn-horozia-primary btn-sm w-100"><i class="bi bi-eye me-1"></i>Voir la location</a>
        </div>
    </div>
    {% endfor %}
</div>
{% else %}
<div class="dashboard-card text-center py-5">
    <i class="bi bi-calendar-x" style="font-size:3rem;opacity:.3;display:block;margin-bottom:12px;color:#6c757d;"></i>
    <p style="color:#6c757d;margin-bottom:16px;">Aucune location pour ce jour.</p>
    <a href="{{ path('admin_location_new') }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Creer une location</a>
</div>
{% endif %}
{% endblock %}
'@

# ============================================================
# 12. marque/index.html.twig
# ============================================================
Write-Twig "templates/admin/marque/index.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Marques – Horizia{% endblock %}
{% block topbar_title %}Marques{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-tags-fill"></i></div>
        <div>
            <h2>Marques <span class="badge-count">{{ marques|length }}</span></h2>
            <p class="text-muted mb-0" style="font-size:.92rem;">Marques de vehicules disponibles</p>
        </div>
    </div>
    <a href="{{ path('admin_marque_new') }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouvelle marque</a>
</div>
<div class="row g-3">
    {% for marque in marques %}
    <div class="col-6 col-md-4 col-lg-3">
        <div class="dashboard-card text-center" style="transition:.2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
            {% if marque.logo %}
            <img src="{{ marque.logo }}" alt="{{ marque.nomMarque }}" style="width:70px;height:70px;object-fit:contain;margin-bottom:12px;">
            {% else %}
            <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,rgba(61,148,202,.15),rgba(35,119,156,.2));display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.8rem;color:var(--color-primary);"><i class="bi bi-truck"></i></div>
            {% endif %}
            <h6 style="font-weight:800;color:#1f2d3d;margin-bottom:4px;">{{ marque.nomMarque }}</h6>
            <div style="font-size:.8rem;color:#6c757d;margin-bottom:14px;">{{ marque.modeles|length }} modele(s)</div>
            <div class="d-flex gap-2 justify-content-center">
                <a href="{{ path('admin_marque_show', {id: marque.idMarque}) }}" class="action-btn action-btn-view"><i class="bi bi-eye"></i></a>
                <a href="{{ path('admin_marque_new') }}" class="action-btn action-btn-edit" title="Ajouter modele"><i class="bi bi-plus"></i></a>
            </div>
        </div>
    </div>
    {% else %}
    <div class="col-12">
        <div class="dashboard-card text-center py-5" style="color:#6c757d;">
            <i class="bi bi-tags" style="font-size:3rem;opacity:.3;display:block;margin-bottom:12px;"></i>
            <p class="mb-3">Aucune marque trouvee.</p>
            <a href="{{ path('admin_marque_new') }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Ajouter une marque</a>
        </div>
    </div>
    {% endfor %}
</div>
{% endblock %}
'@

# ============================================================
# 13. marque/new.html.twig
# ============================================================
Write-Twig "templates/admin/marque/new.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Nouvelle marque – Horizia{% endblock %}
{% block topbar_title %}Nouvelle marque{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-plus-circle-fill"></i></div>
        <div><h2>Nouvelle marque</h2></div>
    </div>
    <a href="{{ path('admin_marque_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-tags-fill" style="color:var(--color-primary);"></i>Informations marque</div>
            {{ form_start(form) }}
            <div class="mb-3">{{ form_label(form.nomMarque) }}{{ form_widget(form.nomMarque) }}{{ form_errors(form.nomMarque) }}</div>
            <div class="mb-3">{{ form_label(form.logo) }}{{ form_widget(form.logo) }}{{ form_errors(form.logo) }}<div style="font-size:.8rem;color:#6c757d;margin-top:4px;">URL de l'image du logo</div></div>
            <button type="submit" class="btn btn-horozia-primary w-100"><i class="bi bi-check-lg me-2"></i>Enregistrer</button>
            {{ form_end(form) }}
        </div>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 14. marque/show.html.twig
# ============================================================
Write-Twig "templates/admin/marque/show.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}{{ marque.nomMarque }} – Horizia{% endblock %}
{% block topbar_title %}Detail marque{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-tags-fill"></i></div>
        <div><h2>{{ marque.nomMarque }}</h2><p class="text-muted mb-0" style="font-size:.92rem;">{{ marque.modeles|length }} modele(s)</p></div>
    </div>
    <a href="{{ path('admin_marque_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="dashboard-card text-center">
            {% if marque.logo %}
            <img src="{{ marque.logo }}" alt="{{ marque.nomMarque }}" style="width:120px;height:120px;object-fit:contain;margin-bottom:16px;">
            {% else %}
            <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,rgba(61,148,202,.15),rgba(35,119,156,.2));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:2.5rem;color:var(--color-primary);"><i class="bi bi-truck"></i></div>
            {% endif %}
            <h3 style="font-weight:800;">{{ marque.nomMarque }}</h3>
            <div style="font-size:.9rem;color:#6c757d;">{{ marque.modeles|length }} modele(s) enregistre(s)</div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:14px;"><i class="bi bi-list-ul me-2" style="color:var(--color-primary);"></i>Modeles de cette marque</div>
            {% if marque.modeles|length > 0 %}
            <div class="row g-2">
                {% for modele in marque.modeles %}
                <div class="col-md-6">
                    <div style="background:#f5f7fb;border-radius:10px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:600;">{{ modele.nomModele }}</span>
                        <a href="{{ path('admin_modele_show', {id: modele.idModele}) }}" class="action-btn action-btn-view" style="width:28px;height:28px;"><i class="bi bi-eye"></i></a>
                    </div>
                </div>
                {% endfor %}
            </div>
            {% else %}
            <div style="text-align:center;color:#6c757d;padding:20px;font-size:.9rem;">Aucun modele pour cette marque.</div>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 15. marque/edit.html.twig  (view only - marques are not editable)
# ============================================================
Write-Twig "templates/admin/marque/edit.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}{{ marque.nomMarque }} – Horizia{% endblock %}
{% block topbar_title %}Marque{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-tags-fill"></i></div>
        <div><h2>{{ marque.nomMarque }}</h2></div>
    </div>
    <a href="{{ path('admin_marque_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="form-section">
            {{ form_start(form) }}
            <div class="mb-3">{{ form_label(form.nomMarque) }}{{ form_widget(form.nomMarque) }}{{ form_errors(form.nomMarque) }}</div>
            <div class="mb-3">{{ form_label(form.logo) }}{{ form_widget(form.logo) }}{{ form_errors(form.logo) }}</div>
            <button type="submit" class="btn btn-horozia-primary w-100"><i class="bi bi-check-lg me-2"></i>Enregistrer</button>
            {{ form_end(form) }}
        </div>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 16. modele/index.html.twig
# ============================================================
Write-Twig "templates/admin/modele/index.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Modeles – Horizia{% endblock %}
{% block topbar_title %}Modeles{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-list-ul"></i></div>
        <div><h2>Modeles <span class="badge-count">{{ modeles|length }}</span></h2><p class="text-muted mb-0" style="font-size:.92rem;">Modeles de vehicules</p></div>
    </div>
    <a href="{{ path('admin_modele_new') }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau modele</a>
</div>
<div class="card-horozia">
    <div class="table-responsive">
        <table class="table dashboard-table mb-0">
            <thead><tr class="table-horozia"><th>#</th><th>Marque</th><th>Modele</th><th>Vehicules</th><th>Actions</th></tr></thead>
            <tbody>
                {% for modele in modeles %}
                <tr>
                    <td style="font-weight:700;color:var(--color-primary);">{{ modele.idModele }}</td>
                    <td><span style="background:rgba(61,148,202,.12);color:#174f6a;padding:4px 10px;border-radius:20px;font-size:.82rem;font-weight:700;">{{ modele.marque.nomMarque }}</span></td>
                    <td style="font-weight:600;">{{ modele.nomModele }}</td>
                    <td>{{ modele.vehicules|length }} vehicule(s)</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ path('admin_modele_show', {id: modele.idModele}) }}" class="action-btn action-btn-view"><i class="bi bi-eye"></i></a>
                        </div>
                    </td>
                </tr>
                {% else %}
                <tr><td colspan="5" class="text-center py-4" style="color:#6c757d;">Aucun modele trouve.</td></tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 17. modele/new.html.twig
# ============================================================
Write-Twig "templates/admin/modele/new.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Nouveau modele – Horizia{% endblock %}
{% block topbar_title %}Nouveau modele{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-plus-circle-fill"></i></div>
        <div><h2>Nouveau modele</h2></div>
    </div>
    <a href="{{ path('admin_modele_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-list-ul" style="color:var(--color-primary);"></i>Informations modele</div>
            {{ form_start(form) }}
            <div class="mb-3">{{ form_label(form.marque) }}{{ form_widget(form.marque) }}{{ form_errors(form.marque) }}</div>
            <div class="mb-3">{{ form_label(form.nomModele) }}{{ form_widget(form.nomModele) }}{{ form_errors(form.nomModele) }}</div>
            <div class="mb-3">{{ form_label(form.image) }}{{ form_widget(form.image) }}{{ form_errors(form.image) }}<div style="font-size:.8rem;color:#6c757d;margin-top:4px;">URL de l'image du modele (optionnel)</div></div>
            <button type="submit" class="btn btn-horozia-primary w-100"><i class="bi bi-check-lg me-2"></i>Enregistrer</button>
            {{ form_end(form) }}
        </div>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 18. modele/show.html.twig
# ============================================================
Write-Twig "templates/admin/modele/show.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}{{ modele.nomModele }} – Horizia{% endblock %}
{% block topbar_title %}Detail modele{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-list-ul"></i></div>
        <div><h2>{{ modele.nomModele }}</h2><p class="text-muted mb-0" style="font-size:.92rem;">{{ modele.marque.nomMarque }}</p></div>
    </div>
    <a href="{{ path('admin_modele_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="dashboard-card text-center">
            {% if modele.image %}
            <img src="{{ modele.image }}" alt="{{ modele.nomModele }}" style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:16px;">
            {% else %}
            <div style="width:100%;height:120px;border-radius:10px;background:linear-gradient(135deg,rgba(61,148,202,.1),rgba(35,119,156,.15));display:flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:3rem;color:var(--color-primary);"><i class="bi bi-car-front"></i></div>
            {% endif %}
            <h4 style="font-weight:800;">{{ modele.nomModele }}</h4>
            <span style="background:rgba(61,148,202,.12);color:#174f6a;padding:4px 14px;border-radius:20px;font-size:.85rem;font-weight:700;">{{ modele.marque.nomMarque }}</span>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:14px;"><i class="bi bi-truck me-2" style="color:var(--color-primary);"></i>Vehicules de ce modele</div>
            {% if modele.vehicules|length > 0 %}
            <div class="table-responsive">
                <table class="table dashboard-table mb-0">
                    <thead><tr class="table-horozia"><th>Immat.</th><th>Annee</th><th>Etat</th><th>Prix/jour</th><th>Action</th></tr></thead>
                    <tbody>
                        {% for vehicule in modele.vehicules %}
                        <tr>
                            <td style="font-weight:700;">{{ vehicule.immatriculation }}</td>
                            <td>{{ vehicule.annee }}</td>
                            <td>
                                {% if vehicule.etat == 'disponible' %}<span class="badge-disponible">Disponible</span>
                                {% elseif vehicule.etat == 'louee' %}<span class="badge-louee">Loue</span>
                                {% else %}<span class="badge-maintenance">{{ vehicule.etat }}</span>{% endif %}
                            </td>
                            <td style="color:var(--color-primary);font-weight:700;">{{ vehicule.prixParJour|number_format(3, ',', ' ') }} TND</td>
                            <td><a href="{{ path('admin_vehicule_show', {id: vehicule.idVehicule}) }}" class="action-btn action-btn-view"><i class="bi bi-eye"></i></a></td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <div style="text-align:center;color:#6c757d;padding:20px;">Aucun vehicule pour ce modele.</div>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 19. modele/edit.html.twig
# ============================================================
Write-Twig "templates/admin/modele/edit.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}{{ modele.nomModele }} – Horizia{% endblock %}
{% block topbar_title %}Modele{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-list-ul"></i></div>
        <div><h2>{{ modele.nomModele }}</h2></div>
    </div>
    <a href="{{ path('admin_modele_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="form-section">
            {{ form_start(form) }}
            <div class="mb-3">{{ form_label(form.marque) }}{{ form_widget(form.marque) }}{{ form_errors(form.marque) }}</div>
            <div class="mb-3">{{ form_label(form.nomModele) }}{{ form_widget(form.nomModele) }}{{ form_errors(form.nomModele) }}</div>
            <div class="mb-3">{{ form_label(form.image) }}{{ form_widget(form.image) }}{{ form_errors(form.image) }}</div>
            <button type="submit" class="btn btn-horozia-primary w-100"><i class="bi bi-check-lg me-2"></i>Enregistrer</button>
            {{ form_end(form) }}
        </div>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 20. vehicule/index.html.twig
# ============================================================
Write-Twig "templates/admin/vehicule/index.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Vehicules – Horizia{% endblock %}
{% block topbar_title %}Vehicules{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-truck-front-fill"></i></div>
        <div><h2>Vehicules <span class="badge-count">{{ vehicules|length }}</span></h2><p class="text-muted mb-0" style="font-size:.92rem;">Flotte de vehicules</p></div>
    </div>
    <a href="{{ path('admin_vehicule_new') }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau vehicule</a>
</div>

<div class="card-horozia p-3 mb-3">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label mb-1">Immatriculation</label><input type="text" name="immat" class="form-control form-control-sm" value="{{ rechercheImmat }}" placeholder="Ex: TU 123..."></div>
        <div><label class="form-label mb-1">Etat</label><select name="etat" class="form-control form-control-sm"><option value="">Tous</option><option value="disponible" {{ filtreEtat == 'disponible' ? 'selected' : '' }}>Disponible</option><option value="louee" {{ filtreEtat == 'louee' ? 'selected' : '' }}>Loue</option><option value="en_maintenance" {{ filtreEtat == 'en_maintenance' ? 'selected' : '' }}>Maintenance</option></select></div>
        <div><label class="form-label mb-1">Carburant</label><select name="carburant" class="form-control form-control-sm"><option value="">Tous</option><option value="Essence" {{ filtreCarburant == 'Essence' ? 'selected' : '' }}>Essence</option><option value="Diesel" {{ filtreCarburant == 'Diesel' ? 'selected' : '' }}>Diesel</option><option value="Hybride" {{ filtreCarburant == 'Hybride' ? 'selected' : '' }}>Hybride</option><option value="Electrique" {{ filtreCarburant == 'Electrique' ? 'selected' : '' }}>Electrique</option></select></div>
        <div><label class="form-label mb-1">Marque</label><select name="marque" class="form-control form-control-sm"><option value="">Toutes</option>{% for marque in marques %}<option value="{{ marque.idMarque }}" {{ filtreMarque == marque.idMarque|string ? 'selected' : '' }}>{{ marque.nomMarque }}</option>{% endfor %}</select></div>
        <button type="submit" class="btn btn-horozia-primary btn-sm"><i class="bi bi-search me-1"></i>Filtrer</button>
        <a href="{{ path('admin_vehicule_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-x-lg me-1"></i>Reset</a>
    </form>
</div>

<div class="card-horozia">
    <div class="table-responsive">
        <table class="table dashboard-table mb-0">
            <thead><tr class="table-horozia"><th>Immat.</th><th>Modele</th><th>Annee</th><th>Carburant</th><th>Km</th><th>Prix/jour</th><th>Etat</th><th>Actions</th></tr></thead>
            <tbody>
                {% for vehicule in vehicules %}
                <tr>
                    <td style="font-weight:700;color:var(--color-primary);">{{ vehicule.immatriculation }}</td>
                    <td>{% if vehicule.modele is defined and vehicule.modele %}{{ vehicule.modele.marque.nomMarque }} {{ vehicule.modele.nomModele }}{% endif %}</td>
                    <td>{{ vehicule.annee }}</td>
                    <td><span style="background:rgba(232,177,86,.15);color:#7a5714;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:700;">{{ vehicule.carburant }}</span></td>
                    <td style="font-size:.9rem;">{{ vehicule.kilometrage|number_format(0, ',', ' ') }} km</td>
                    <td style="font-weight:700;color:var(--color-primary);">{{ vehicule.prixParJour|number_format(3, ',', ' ') }} TND</td>
                    <td>
                        {% if vehicule.etat == 'disponible' %}<span class="badge-disponible">Disponible</span>
                        {% elseif vehicule.etat == 'louee' %}<span class="badge-louee">Loue</span>
                        {% elseif vehicule.etat == 'en_maintenance' %}<span class="badge-maintenance">Maintenance</span>
                        {% else %}<span class="badge-maintenance">{{ vehicule.etat }}</span>{% endif %}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ path('admin_vehicule_show', {id: vehicule.idVehicule}) }}" class="action-btn action-btn-view"><i class="bi bi-eye"></i></a>
                            <a href="{{ path('admin_vehicule_edit', {id: vehicule.idVehicule}) }}" class="action-btn action-btn-edit"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="{{ path('admin_vehicule_delete', {id: vehicule.idVehicule}) }}" onsubmit="return confirm('Supprimer ce vehicule ?')">
                                <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ vehicule.idVehicule) }}">
                                <button type="submit" class="action-btn action-btn-delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                {% else %}
                <tr><td colspan="8" class="text-center py-4" style="color:#6c757d;">Aucun vehicule trouve.</td></tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 21. vehicule/new.html.twig
# ============================================================
Write-Twig "templates/admin/vehicule/new.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Nouveau vehicule – Horizia{% endblock %}
{% block topbar_title %}Nouveau vehicule{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-plus-circle-fill"></i></div>
        <div><h2>Nouveau vehicule</h2></div>
    </div>
    <a href="{{ path('admin_vehicule_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
{{ form_start(form) }}
<div class="row g-3">
    <div class="col-lg-8">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-truck-front-fill" style="color:var(--color-primary);"></i>Informations vehicule</div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Immatriculation</label>{{ form_widget(form.immatriculation) }}{{ form_errors(form.immatriculation) }}</div>
                <div class="col-md-6"><label class="form-label">Modele</label>{{ form_widget(form.modele) }}{{ form_errors(form.modele) }}</div>
                <div class="col-md-4"><label class="form-label">Annee</label>{{ form_widget(form.annee) }}{{ form_errors(form.annee) }}</div>
                <div class="col-md-4"><label class="form-label">Carburant</label>{{ form_widget(form.carburant) }}{{ form_errors(form.carburant) }}</div>
                <div class="col-md-4"><label class="form-label">Couleur</label>{{ form_widget(form.couleur) }}{{ form_errors(form.couleur) }}</div>
                <div class="col-md-6"><label class="form-label">Kilometrage</label>{{ form_widget(form.kilometrage) }}{{ form_errors(form.kilometrage) }}</div>
                <div class="col-md-6"><label class="form-label">Etat</label>{{ form_widget(form.etat) }}{{ form_errors(form.etat) }}</div>
                <div class="col-md-6"><label class="form-label">Prix par jour (TND)</label>{{ form_widget(form.prixParJour) }}{{ form_errors(form.prixParJour) }}</div>
                <div class="col-md-6"><label class="form-label">Photo (URL)</label>{{ form_widget(form.photo) }}{{ form_errors(form.photo) }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-section">
            <button type="submit" class="btn btn-horozia-primary w-100 mb-2"><i class="bi bi-check-lg me-2"></i>Enregistrer</button>
            <a href="{{ path('admin_vehicule_index') }}" class="btn btn-horozia-outline w-100">Annuler</a>
        </div>
    </div>
</div>
{{ form_end(form) }}
{% endblock %}
'@

# ============================================================
# 22. vehicule/edit.html.twig
# ============================================================
Write-Twig "templates/admin/vehicule/edit.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Modifier {{ vehicule.immatriculation }} – Horizia{% endblock %}
{% block topbar_title %}Modifier vehicule{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-pencil-fill"></i></div>
        <div><h2>{{ vehicule.immatriculation }}</h2></div>
    </div>
    <a href="{{ path('admin_vehicule_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
{{ form_start(form) }}
<div class="row g-3">
    <div class="col-lg-8">
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-truck-front-fill" style="color:var(--color-primary);"></i>Informations vehicule</div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Immatriculation</label>{{ form_widget(form.immatriculation) }}{{ form_errors(form.immatriculation) }}</div>
                <div class="col-md-6"><label class="form-label">Modele</label>{{ form_widget(form.modele) }}{{ form_errors(form.modele) }}</div>
                <div class="col-md-4"><label class="form-label">Annee</label>{{ form_widget(form.annee) }}{{ form_errors(form.annee) }}</div>
                <div class="col-md-4"><label class="form-label">Carburant</label>{{ form_widget(form.carburant) }}{{ form_errors(form.carburant) }}</div>
                <div class="col-md-4"><label class="form-label">Couleur</label>{{ form_widget(form.couleur) }}{{ form_errors(form.couleur) }}</div>
                <div class="col-md-6"><label class="form-label">Kilometrage</label>{{ form_widget(form.kilometrage) }}{{ form_errors(form.kilometrage) }}</div>
                <div class="col-md-6"><label class="form-label">Etat</label>{{ form_widget(form.etat) }}{{ form_errors(form.etat) }}</div>
                <div class="col-md-6"><label class="form-label">Prix par jour (TND)</label>{{ form_widget(form.prixParJour) }}{{ form_errors(form.prixParJour) }}</div>
                <div class="col-md-6"><label class="form-label">Photo (URL)</label>{{ form_widget(form.photo) }}{{ form_errors(form.photo) }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-section">
            <button type="submit" class="btn btn-horozia-primary w-100 mb-2"><i class="bi bi-check-lg me-2"></i>Enregistrer</button>
            <a href="{{ path('admin_vehicule_show', {id: vehicule.idVehicule}) }}" class="btn btn-horozia-outline w-100 mb-2">Voir</a>
            <a href="{{ path('admin_vehicule_index') }}" class="btn btn-horozia-outline w-100 mb-3">Annuler</a>
            <form method="post" action="{{ path('admin_vehicule_delete', {id: vehicule.idVehicule}) }}" onsubmit="return confirm('Supprimer ?')">
                <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ vehicule.idVehicule) }}">
                <button type="submit" class="btn btn-horozia-danger w-100 btn-sm"><i class="bi bi-trash me-1"></i>Supprimer</button>
            </form>
        </div>
    </div>
</div>
{{ form_end(form) }}
{% endblock %}
'@

# ============================================================
# 23. vehicule/show.html.twig
# ============================================================
Write-Twig "templates/admin/vehicule/show.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}{{ vehicule.immatriculation }} – Horizia{% endblock %}
{% block topbar_title %}Detail vehicule{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-truck-front-fill"></i></div>
        <div><h2>{{ vehicule.immatriculation }}</h2><p class="text-muted mb-0" style="font-size:.92rem;">{% if vehicule.modele is defined and vehicule.modele %}{{ vehicule.modele.marque.nomMarque }} {{ vehicule.modele.nomModele }}{% endif %}</p></div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ path('admin_vehicule_edit', {id: vehicule.idVehicule}) }}" class="btn btn-horozia-primary btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
        <a href="{{ path('admin_vehicule_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="dashboard-card mb-3">
            {% if vehicule.photo %}
            <img src="{{ vehicule.photo }}" alt="{{ vehicule.immatriculation }}" style="width:100%;height:200px;object-fit:cover;border-radius:10px;margin-bottom:16px;">
            {% else %}
            <div style="width:100%;height:160px;border-radius:10px;background:linear-gradient(135deg,rgba(61,148,202,.1),rgba(35,119,156,.15));display:flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:4rem;color:var(--color-primary);"><i class="bi bi-truck-front"></i></div>
            {% endif %}
            <div class="text-center">
                <h4 style="font-weight:800;color:var(--color-primary);">{{ vehicule.immatriculation }}</h4>
                {% if vehicule.etat == 'disponible' %}<span class="badge-disponible">Disponible</span>
                {% elseif vehicule.etat == 'louee' %}<span class="badge-louee">Loue</span>
                {% else %}<span class="badge-maintenance">{{ vehicule.etat }}</span>{% endif %}
                <div style="font-size:2rem;font-weight:800;color:var(--color-primary);margin-top:12px;">{{ vehicule.prixParJour|number_format(3, ',', ' ') }} TND<span style="font-size:.9rem;font-weight:400;color:#6c757d;"> / jour</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="dashboard-card mb-3">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:16px;"><i class="bi bi-info-circle me-2" style="color:var(--color-primary);"></i>Caracteristiques</div>
            <div class="row g-2">
                <div class="col-6"><div style="background:#f5f7fb;border-radius:10px;padding:12px;text-align:center;"><div style="font-size:.78rem;color:#6c757d;">Modele</div><div style="font-weight:700;font-size:.9rem;">{% if vehicule.modele is defined and vehicule.modele %}{{ vehicule.modele.nomModele }}{% endif %}</div></div></div>
                <div class="col-6"><div style="background:#f5f7fb;border-radius:10px;padding:12px;text-align:center;"><div style="font-size:.78rem;color:#6c757d;">Marque</div><div style="font-weight:700;font-size:.9rem;">{% if vehicule.modele is defined and vehicule.modele %}{{ vehicule.modele.marque.nomMarque }}{% endif %}</div></div></div>
                <div class="col-6"><div style="background:#f5f7fb;border-radius:10px;padding:12px;text-align:center;"><div style="font-size:.78rem;color:#6c757d;">Annee</div><div style="font-weight:700;">{{ vehicule.annee }}</div></div></div>
                <div class="col-6"><div style="background:#f5f7fb;border-radius:10px;padding:12px;text-align:center;"><div style="font-size:.78rem;color:#6c757d;">Carburant</div><div style="font-weight:700;">{{ vehicule.carburant }}</div></div></div>
                {% if vehicule.couleur %}<div class="col-6"><div style="background:#f5f7fb;border-radius:10px;padding:12px;text-align:center;"><div style="font-size:.78rem;color:#6c757d;">Couleur</div><div style="font-weight:700;">{{ vehicule.couleur }}</div></div></div>{% endif %}
                <div class="col-6"><div style="background:#f5f7fb;border-radius:10px;padding:12px;text-align:center;"><div style="font-size:.78rem;color:#6c757d;">Kilometrage</div><div style="font-weight:700;">{{ vehicule.kilometrage|number_format(0, ',', ' ') }} km</div></div></div>
            </div>
        </div>
        <div class="dashboard-card">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:14px;"><i class="bi bi-calendar-check me-2" style="color:var(--color-primary);"></i>Locations ({{ vehicule.locations|length }})</div>
            {% if vehicule.locations|length > 0 %}
            <div style="max-height:200px;overflow-y:auto;">
                {% for loc in vehicule.locations|slice(0, 5) %}
                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #eef2f7;font-size:.88rem;">
                    <span>{{ loc.clientNomComplet }}</span>
                    <span style="color:#6c757d;">{{ loc.dateDebut|date('d/m/Y') }}</span>
                    <a href="{{ path('admin_location_show', {id: loc.idLocation}) }}" class="action-btn action-btn-view" style="width:26px;height:26px;"><i class="bi bi-eye"></i></a>
                </div>
                {% endfor %}
            </div>
            {% else %}
            <div style="text-align:center;color:#6c757d;font-size:.88rem;">Aucune location pour ce vehicule.</div>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}
'@

# ============================================================
# 24. documents/index.html.twig
# ============================================================
Write-Twig "templates/admin/documents/index.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}Documents – Horizia{% endblock %}
{% block topbar_title %}Documents{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div><h2>Documents <span class="badge-count">{{ locations|length }}</span></h2><p class="text-muted mb-0" style="font-size:.92rem;">Contrats, factures et QR codes</p></div>
    </div>
</div>

<div class="card-horozia p-3 mb-3">
    <form method="get" class="d-flex gap-2 align-items-end">
        <div><label class="form-label mb-1">Rechercher</label><input type="text" name="q" class="form-control form-control-sm" placeholder="Client, vehicule..." value="{{ app.request.query.get('q') }}" style="min-width:220px;"></div>
        <button type="submit" class="btn btn-horozia-primary btn-sm"><i class="bi bi-search me-1"></i>Rechercher</button>
        {% if app.request.query.get('q') %}<a href="{{ path('admin_documents_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-x-lg me-1"></i>Effacer</a>{% endif %}
    </form>
</div>

<div class="card-horozia">
    {% if locations|length > 0 %}
    <div class="table-responsive">
        <table class="table dashboard-table mb-0">
            <thead><tr class="table-horozia"><th>#</th><th>Client</th><th>Vehicule</th><th>Periode</th><th>Statut</th><th>Documents</th></tr></thead>
            <tbody>
                {% for location in locations %}
                <tr>
                    <td style="font-weight:700;color:var(--color-primary);">#{{ location.idLocation }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#23779C,#3D94CA);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0;"><i class="bi bi-person-fill"></i></div>
                            <strong style="font-size:.9rem;">{{ location.clientNomComplet }}</strong>
                        </div>
                    </td>
                    <td><span style="background:rgba(61,148,202,.12);color:#174f6a;padding:3px 10px;border-radius:20px;font-size:.82rem;font-weight:700;"><i class="bi bi-truck me-1"></i>{{ location.vehicule.immatriculation }}</span></td>
                    <td style="font-size:.88rem;">{{ location.dateDebut|date('d/m/Y') }} → {{ location.dateFinPrevue|date('d/m/Y') }}</td>
                    <td>
                        {% if location.statut == 'en_cours' %}<span class="badge-en-cours">En cours</span>
                        {% elseif location.statut == 'reservee' or location.statut == 'reservee' %}<span class="badge-reservee">Reservee</span>
                        {% elseif location.statut == 'terminee' or location.statut == 'terminee' %}<span class="badge-terminee">Terminee</span>
                        {% elseif location.statut == 'annulee' or location.statut == 'annulee' %}<span class="badge-annulee">Annulee</span>
                        {% else %}<span class="badge-terminee">{{ location.statut }}</span>{% endif %}
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="{{ path('admin_documents_contrat', {id: location.idLocation}) }}" class="btn btn-horozia-accent btn-sm" title="Contrat" style="padding:4px 10px;font-size:.78rem;"><i class="bi bi-file-earmark-text me-1"></i>Contrat</a>
                            <a href="{{ path('admin_documents_qrcode', {id: location.idLocation}) }}" class="btn btn-horozia-secondary btn-sm" title="QR Code" style="padding:4px 10px;font-size:.78rem;"><i class="bi bi-qr-code me-1"></i>QR</a>
                            <a href="{{ path('admin_location_show', {id: location.idLocation}) }}" class="action-btn action-btn-view"><i class="bi bi-eye"></i></a>
                        </div>
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
    {% else %}
    <div class="text-center py-5" style="color:#6c757d;"><i class="bi bi-file-earmark-x" style="font-size:3rem;opacity:.3;display:block;margin-bottom:12px;"></i><p>Aucun document disponible.</p></div>
    {% endif %}
</div>
{% endblock %}
'@

# ============================================================
# 25. documents/qrcode.html.twig
# ============================================================
Write-Twig "templates/admin/documents/qrcode.html.twig" @'
{% extends 'admin/layout.html.twig' %}
{% block title %}QR Code – Location #{{ location.idLocation }} – Horizia{% endblock %}
{% block topbar_title %}QR Code{% endblock %}
{% block admin_content %}
<div class="page-header">
    <div class="page-header-left">
        <div class="page-header-icon"><i class="bi bi-qr-code"></i></div>
        <div><h2>QR Code <span class="badge-count">#{{ location.idLocation }}</span></h2><p class="text-muted mb-0" style="font-size:.92rem;">{{ location.clientNomComplet }}</p></div>
    </div>
    <a href="{{ path('admin_documents_index') }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="dashboard-card text-center mb-3">
            <div style="font-weight:700;color:#1f2d3d;margin-bottom:20px;font-size:1.05rem;"><i class="bi bi-qr-code-scan me-2" style="color:var(--color-primary);"></i>QR Code de la location</div>
            <div style="display:inline-block;padding:20px;background:#fff;border:2px solid var(--color-border);border-radius:16px;box-shadow:0 4px 20px rgba(15,23,42,.08);margin-bottom:20px;">
                <img src="{{ qrCode }}" alt="QR Code Location #{{ location.idLocation }}" style="width:220px;height:220px;display:block;">
            </div>
            <div style="background:#f5f7fb;border-radius:12px;padding:16px;margin-bottom:20px;text-align:left;">
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;font-size:.9rem;"><span style="color:#6c757d;"><i class="bi bi-person me-2"></i>Client</span><strong>{{ location.clientNomComplet }}</strong></div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #eef2f7;font-size:.9rem;"><span style="color:#6c757d;"><i class="bi bi-truck me-2"></i>Vehicule</span><span style="font-weight:700;">{{ location.vehicule.immatriculation }}</span></div>
                <div class="d-flex justify-content-between py-2" style="font-size:.9rem;"><span style="color:#6c757d;"><i class="bi bi-calendar-range me-2"></i>Periode</span><span>{{ location.dateDebut|date('d/m/Y') }} → {{ location.dateFinPrevue|date('d/m/Y') }}</span></div>
            </div>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="{{ qrCode }}" download="qrcode_location_{{ location.idLocation }}.png" class="btn btn-horozia-primary btn-sm"><i class="bi bi-download me-1"></i>Telecharger</a>
                <a href="{{ path('admin_location_show', {id: location.idLocation}) }}" class="btn btn-horozia-outline btn-sm"><i class="bi bi-eye me-1"></i>Voir la location</a>
            </div>
        </div>
        <div style="background:rgba(61,148,202,.08);border:1px solid rgba(61,148,202,.2);border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:10px;">
            <i class="bi bi-info-circle-fill mt-1" style="color:var(--color-primary);flex-shrink:0;"></i>
            <p style="font-size:.85rem;margin:0;color:#4d5965;">Ce QR code peut etre imprime ou partage avec le client pour un acces rapide aux informations de la location.</p>
        </div>
    </div>
</div>
{% endblock %}
'@

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "TOUS LES FICHIERS ONT ETE CREES !" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Lancez maintenant: php bin/console cache:clear" -ForegroundColor Yellow
