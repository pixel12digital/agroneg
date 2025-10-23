<?php
session_name('agroneg_admin');
session_set_cookie_params(['path' => '/']);
session_start();

// Incluir configuração de banco de dados
require_once(__DIR__ . '/../../config/db.php');

// Verificar se o usuário está logado
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true) {
    header("Location: ../login.php");
    exit();
}

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Verificar se é admin para mostrar opções restritas
$is_admin = isset($_SESSION['nivel']) && $_SESSION['nivel'] === 'admin';

// Detectar página atual
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="description" content="Painel Administrativo AgroNeg">
    <meta name="theme-color" content="#006837">
    <title>Painel Administrativo - AgroNeg</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #006837;
            --primary-dark: #004d27;
            --secondary: #F7941D;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            padding-top: 56px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 0;
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.05);
            width: 250px;
            background-color: #fff;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: #333;
            padding: 0.8rem 1.5rem;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover {
            background-color: var(--light-bg);
        }
        
        .sidebar .nav-link.active {
            color: var(--primary);
            border-left-color: var(--primary);
            background-color: var(--light-bg);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            color: var(--primary);
            width: 25px;
            text-align: center;
        }
        
        /* Main content */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        /* Navbar */
        .navbar {
            background-color: var(--primary) !important;
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            color: white !important;
        }
        
        .navbar .navbar-toggler {
            border: none;
        }
        
        .navbar .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .navbar .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        .navbar .nav-link {
            color: white !important;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        /* Forms for better mobile experience */
        .form-control, .form-select {
            padding: 0.6rem 0.75rem;
            min-height: 42px;
        }
        
        /* Responsive adjustments for tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Increase touch targets for mobile */
        @media (max-width: 768px) {
            .btn {
                padding: 0.5rem 0.75rem;
                min-height: 44px;  /* Mínimo recomendado para áreas de toque */
            }
            
            .form-label {
                font-size: 1rem;
                margin-bottom: 0.3rem;
            }
            
            .dropdown-item {
                padding: 0.5rem 1rem;
                min-height: 44px;
            }
            
            /* Melhorar navegação em tabelas */
            table.dataTable > thead .sorting:after,
            table.dataTable > thead .sorting_asc:after,
            table.dataTable > thead .sorting_desc:after {
                font-size: 1.2rem;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .main-content.active {
                margin-left: 250px;
            }
            
            h1, .h1 {
                font-size: 1.8rem;
            }
            
            h3, .h3 {
                font-size: 1.3rem;
            }
        }
        
        /* Extra small devices */
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .btn-group-sm > .btn, .btn-sm {
                padding: 0.375rem 0.5rem;
                font-size: 0.875rem;
            }
            
            /* Ajustar cabeçalhos e botões de seção em telas pequenas */
            .d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
            
            .d-flex.justify-content-between.align-items-center > div {
                width: 100%;
            }
            
            .d-flex.justify-content-between.align-items-center .btn {
                width: 100%;
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-leaf me-2"></i>AgroNeg Admin
            </a>
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION["nome_usuario"] ?? "Usuário"; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-edit me-2"></i>Meu Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="pt-2">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Painel de Controle
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'parceiros.php') ? 'active' : ''; ?>" href="parceiros.php">
                        <i class="fas fa-handshake"></i>Parceiros
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'municipios.php') ? 'active' : ''; ?>" href="municipios.php">
                        <i class="fas fa-map-marker-alt"></i>Municípios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'categorias.php') ? 'active' : ''; ?>" href="categorias.php">
                        <i class="fas fa-tags"></i>Categorias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'eventos.php') ? 'active' : ''; ?>" href="eventos.php">
                        <i class="fas fa-calendar-alt"></i>Eventos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'mensagens.php') ? 'active' : ''; ?>" href="mensagens.php">
                        <i class="fas fa-envelope"></i>Mensagens
                        <?php
                        // Contar mensagens não lidas (COM CACHE - evita conexão desnecessária)
                        $cache_file = __DIR__ . '/../../cache/mensagens_count.cache';
                        $cache_time = 300; // 5 minutos
                        
                        $count = 0;
                        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
                            $count = (int)file_get_contents($cache_file);
                        } else if ($conn) {
                            $query = "SELECT COUNT(*) as total FROM mensagens_contato WHERE status = 'novo'";
                            $result = $conn->query($query);
                            if ($result) {
                                $row = $result->fetch_assoc();
                                $count = $row ? (int)$row['total'] : 0;
                                file_put_contents($cache_file, $count);
                            }
                        }
                        
                        if ($count > 0) {
                            echo '<span class="badge bg-danger ms-1">' . $count . '</span>';
                        }
                        ?>
                    </a>
                </li>
                <?php if ($is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'usuarios.php') ? 'active' : ''; ?>" href="usuarios.php">
                        <i class="fas fa-users"></i>Usuários
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'configuracoes.php') ? 'active' : ''; ?>" href="configuracoes.php">
                        <i class="fas fa-cogs"></i>Configurações
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link" href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i>Ver Site
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid"> 