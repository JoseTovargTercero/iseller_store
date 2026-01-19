<?php
require_once 'includes/session.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendimiento Económico - iSeller Admin</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .card-stat-eco {
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
        }
        .card-stat-eco:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.1), 0 10px 10px rgba(0,0,0,0.08);
        }
        .icon-box {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold mb-0">Rendimiento Económico</h1>
            <button class="btn btn-primary btn-sm" onclick="loadStats()">
                <i class="bi bi-arrow-clockwise"></i> Actualizar
            </button>
        </div>

        <!-- Indicator Cards -->
        <div class="row g-3 mb-4">
            <!-- Daily -->
            <div class="col-md-3">
                <div class="card card-stat-eco border-0 shadow-sm p-4 h-100">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-calendar-event fs-4"></i>
                    </div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Hoy</p>
                    <h2 class="fw-bold mb-2" id="daily-income">$0.00</h2>
                    <p class="mb-0 text-success small">
                        <i class="bi bi-graph-up"></i> Ganancia: <strong id="daily-profit">$0.00</strong>
                    </p>
                    <p class="mb-0 text-muted extra-small" id="daily-sales">0 ventas</p>
                </div>
            </div>
            <!-- Weekly -->
            <div class="col-md-3">
                <div class="card card-stat-eco border-0 shadow-sm p-4 h-100">
                    <div class="icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-calendar-week fs-4"></i>
                    </div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Últimos 7 días</p>
                    <h2 class="fw-bold mb-2" id="weekly-income">$0.00</h2>
                    <p class="mb-0 text-success small">
                        <i class="bi bi-graph-up"></i> Ganancia: <strong id="weekly-profit">$0.00</strong>
                    </p>
                    <p class="mb-0 text-muted extra-small" id="weekly-sales">0 ventas</p>
                </div>
            </div>
            <!-- Monthly -->
            <div class="col-md-3">
                <div class="card card-stat-eco border-0 shadow-sm p-4 h-100">
                    <div class="icon-box bg-info bg-opacity-10 text-info">
                        <i class="bi bi-calendar-month fs-4"></i>
                    </div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Este Mes</p>
                    <h2 class="fw-bold mb-2" id="monthly-income">$0.00</h2>
                    <p class="mb-0 text-success small">
                        <i class="bi bi-graph-up"></i> Ganancia: <strong id="monthly-profit">$0.00</strong>
                    </p>
                    <p class="mb-0 text-muted extra-small" id="monthly-sales">0 ventas</p>
                </div>
            </div>
            <!-- Total -->
            <div class="col-md-3">
                <div class="card card-stat-eco border-0 shadow-sm p-4 h-100">
                    <div class="icon-box bg-dark bg-opacity-10 text-dark">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Total Histórico</p>
                    <h2 class="fw-bold mb-2" id="total-income">$0.00</h2>
                    <p class="mb-0 text-success small">
                        <i class="bi bi-graph-up"></i> Ganancia: <strong id="total-profit">$0.00</strong>
                    </p>
                    <p class="mb-0 text-muted extra-small" id="total-sales">0 ventas</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold mb-4">Ingresos y Ganancias (Últimos 15 días)</h5>
                    <div style="height: 400px;">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let performanceChart = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            
            document.getElementById('btnLogout').onclick = function() {
                fetch('api/auth.php?action=logout').then(() => window.location.reload());
            };
        });

        async function loadStats() {
            try {
                const res = await fetch('api/performance_stats.php');
                const data = await res.json();
                
                if (data.success) {
                    renderStats(data.stats);
                    renderChart(data.chart_data);
                }
            } catch (error) {
                console.error("Error loading stats:", error);
            }
        }

        function renderStats(stats) {
            const fmt = (num) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num);

            ['daily', 'weekly', 'monthly', 'total'].forEach(period => {
                document.getElementById(`${period}-income`).textContent = fmt(stats[period].income);
                document.getElementById(`${period}-profit`).textContent = fmt(stats[period].profit);
                document.getElementById(`${period}-sales`).textContent = `${stats[period].sales} ventas`;
            });
        }

        function renderChart(chartData) {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            
            if (performanceChart) performanceChart.destroy();

            performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Ingresos ($)',
                            data: chartData.income,
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Ganancias ($)',
                            data: chartData.profit,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '$' + value
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
