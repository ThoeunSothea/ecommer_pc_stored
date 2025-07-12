<?php

require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/auth.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}


$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));
$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
}




$query = "SELECT 
            p.product_id,
            p.name, 
            SUM(oi.quantity) AS total_quantity, 
            SUM(oi.price * oi.quantity) AS total_sales,
            p.image
          FROM order_items oi
          JOIN products p ON oi.product_id = p.product_id 
          JOIN orders o ON oi.order_id = o.order_id
          WHERE o.status = 'completed' 
          AND o.created_at BETWEEN ? AND ?
          GROUP BY p.product_id, p.name, p.image
          ORDER BY total_sales DESC 
          LIMIT 7";


        $stmt = $db->getConnection()->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->getConnection()->error);
        }

        $stmt->bind_param('ss', $startDateTime, $endDateTime);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $topProducts = $stmt->get_result();
        if (!$topProducts) {
            throw new Exception("Get result failed: " . $stmt->error);
        }


        $query = "SELECT 
            SUM(total_amount) as total_sales,
            COUNT(DISTINCT order_id) as total_orders,
            AVG(total_amount) as avg_order_value,
            COUNT(DISTINCT user_id) as total_customers
          FROM orders 
          WHERE status = 'completed' 
          AND created_at BETWEEN ? AND ?";


        $stmt = $db->getConnection()->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->getConnection()->error);
        }

        $stmt->bind_param('ss', $startDateTime, $endDateTime);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Get result failed: " . $stmt->error);
        }

        $salesData = $result->fetch_assoc();
        $totalSales = $salesData['total_sales'] ?? 0;
        $totalOrders = $salesData['total_orders'] ?? 0;
        $avgOrderValue = $salesData['avg_order_value'] ?? 0;
        $totalCustomers = $salesData['total_customers'] ?? 0;


        $query = "SELECT 
            DATE(created_at) as date,
            COUNT(DISTINCT order_id) as order_count,
            SUM(total_amount) as total_sales
          FROM orders
          WHERE status = 'completed'
          AND created_at BETWEEN ? AND ?
          GROUP BY DATE(created_at)
          ORDER BY date ASC";

        

        $stmt = $db->getConnection()->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->getConnection()->error);
        }

        $stmt->bind_param('ss', $startDateTime, $endDateTime);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $salesReport = $stmt->get_result();
        if (!$salesReport) {
            throw new Exception("Get result failed: " . $stmt->error);
        }


?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>របាយការណ៍</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        
    </style>
</head>
<body>
    
    
    <div class="main-content">
        <header class="admin-header">
            <h1><i class="fas fa-chart-bar"></i> របាយការណ៍</h1>
            <div class="user-info">
                <span><?= $_SESSION['username'] ?></span>
                <a href="../../auth/logout.php" class="btn-logout">ចាកចេញ</a>
            </div>
        </header>
        
        <div class="report-filters">
            <form action="reports.php" method="POST">
                <div class="form-rows">
                    <div class="form-group">
                        <label for="start_date">ពីថ្ងៃ៖</label>
                        <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">ដល់ថ្ងៃ៖</label>
                        <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>" required>
                    </div>
                    
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> ត្រង
                    </button>
                </div>
            </form>
        </div>

        <div class="report-summary">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="summary-content">
                    <h3>ចន្លោះពេល</h3>
                    <p><?= date('d/m/Y', strtotime($startDate)) ?> - <?= date('d/m/Y', strtotime($endDate)) ?></p>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="summary-content">
                    <h3>ការកម្មង់សរុប</h3>
                    <p><?= $totalOrders ?? 0 ?></p>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-content">
                    <h3>ចំណូលសរុប</h3>
                    <p>$<?= number_format($totalSales ?? 0, 2) ?></p>
                </div>
            </div>

        </div>
        
        <div class="report-charts">
            <div class="chart-container">
                <h2><i class="fas fa-chart-line"></i> លក់ដុំប្រចាំថ្ងៃ</h2>
                <canvas id="salesChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h2><i class="fas fa-star"></i> ផលិតផលលក់ដាច់បំផុត</h2>
                <canvas id="productsChart"></canvas>
            </div>
        </div>
        
        <div class="report-tables">
    <div class="table-container">
        <h2><i class="fas fa-chart-line"></i> ទិន្នន័យលក់ដុំប្រចាំថ្ងៃ</h2>
        <div class="table-actions">
            <button class="btn-export" onclick="exportToExcel('sales-data')">
                <i class="fas fa-file-excel"></i> នាំចេញ
            </button>
        </div>
        <div class="table-responsive">
            <table id="sales-data">
                <thead>
                    <tr>
                        <th>ថ្ងៃ</th>
                        <th>ចំនួនកម្មង់</th>
                        <th>សរុបលក់ដុំ</th>
                        <th>តម្លៃមធ្យមកម្មង់</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($salesReport->num_rows > 0): ?>
                        <?php 
                        $grandTotalOrders = 0;
                        $grandTotalSales = 0;
                        while ($row = $salesReport->fetch_assoc()): 
                            $grandTotalOrders += $row['order_count'];
                            $grandTotalSales += $row['total_sales'];
                            $avgOrderValue = $row['order_count'] > 0 ? $row['total_sales'] / $row['order_count'] : 0;
                        ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                                <td><?= number_format($row['order_count']) ?></td>
                                <td>$<?= number_format($row['total_sales'], 2) ?></td>
                                <td>$<?= number_format($avgOrderValue, 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <tr class="summary-row">
                            <td><strong>សរុប</strong></td>
                            <td><strong><?= number_format($grandTotalOrders) ?></strong></td>
                            <td><strong>$<?= number_format($grandTotalSales, 2) ?></strong></td>
                            <td><strong>$<?= number_format($grandTotalSales / max(1, $grandTotalOrders), 2) ?></strong></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">មិនមានទិន្នន័យសម្រាប់ចន្លោះពេលនេះទេ។</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="table-container">
        <h2><i class="fas fa-star"></i> ផលិតផលលក់ដាច់បំផុត ៥</h2>
        <div class="table-actions">
            <button class="btn-export" onclick="exportToExcel('top-products')">
                <i class="fas fa-file-excel"></i> នាំចេញ
            </button>
        </div>
        
        <div class="table-responsive">
            <table id="top-products">
                <thead>
                    <tr>
                        <th>ល.រ</th>
                        <th>ផលិតផល</th>
                        <th>ឈ្មោះផលិតផល</th>
                        <th>ចំនួនដែលបានលក់</th>
                        <th>សរុបលក់ដុំ</th>
                        <th>ភាគរយ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($topProducts->num_rows > 0): ?>
                        <?php 
                        $counter = 1;
                        $totalAllProducts = 0;
                        // Calculate total first
                        $topProducts->data_seek(0);
                        while ($row = $topProducts->fetch_assoc()) {
                            $totalAllProducts += $row['total_sales'];
                        }
                        $topProducts->data_seek(0);
                        while ($row = $topProducts->fetch_assoc()): 
                            $percentage = $totalAllProducts > 0 ? ($row['total_sales'] / $totalAllProducts) * 100 : 0;
                        ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td>
                                    <div class="product-info">
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="../../assets/images/products/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" width="50" alt="Product Image" class="product-thumbnail" >
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= number_format($row['total_quantity']) ?></td>
                                <td>$<?= number_format($row['total_sales'], 2) ?></td>
                                <td>
                                    <div class="percentage-bar">
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                        <span><?= number_format($percentage, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">មិនមានទិន្នន័យសម្រាប់ចន្លោះពេលនេះទេ។</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>

    <script>
        function exportToExcel(tableId) {
            const table = document.getElementById(tableId);
            const html = table.outerHTML;
            const blob = new Blob([html], {type: 'application/vnd.ms-excel'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = tableId + '_<?= date('Y-m-d') ?>.xls';
            a.click();
            URL.revokeObjectURL(url);
        }
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        
        <?php
        $salesReport->data_seek(0);
        $dates = [];
        $salesData = [];
        $orderCounts = [];
        
        while ($row = $salesReport->fetch_assoc()) {
            $dates[] = date('d/m', strtotime($row['date']));
            $salesData[] = $row['total_sales'];
            $orderCounts[] = $row['order_count'];
        }
        ?>
        
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [
                    {
                        label: 'សរុបលក់ដុំ',
                        data: <?= json_encode($salesData) ?>,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'ចំនួនកម្មង់',
                        data: <?= json_encode($orderCounts) ?>,
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        labels: {
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        bodyFont: {
                            family: "'Khmer OS', 'Arial', sans-serif"
                        },
                        titleFont: {
                            family: "'Khmer OS', 'Arial', sans-serif"
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'សរុបលក់ដុំ ($)',
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif"
                            }
                        },
                        ticks: {
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif"
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'ចំនួនកម្មង់',
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif"
                            }
                        },
                        ticks: {
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif"
                            }
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif"
                            }
                        }
                    }
                }
            }
        });
        // Products Chart
        const productsCtx = document.getElementById('productsChart').getContext('2d');
        
        <?php
        $topProducts->data_seek(0);
        $productNames = [];
        $productSales = [];

        while ($row = $topProducts->fetch_assoc()) {
            $productNames[] = $row['name'];
            $productSales[] = $row['total_sales'];
        }
        ?>
                
        const productsChart = new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($productNames) ?>,
                datasets: [{
                    label: 'សរុបលក់ដុំ',
                    data: <?= json_encode($productSales) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(199, 199, 199, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif",
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        bodyFont: {
                            family: "'Khmer OS', 'Arial', sans-serif",
                            size: 14
                        },
                        titleFont: {
                            family: "'Khmer OS', 'Arial', sans-serif",
                            size: 16
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'សរុបលក់ដុំ ($)',
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif",
                                size: 14
                            }
                        },
                        ticks: {
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif",
                                size: 12
                            },
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: "'Khmer OS', 'Arial', sans-serif",
                                size: 12
                            }
                        }
                    }
                },
                // Rotate labels if they're too long
                indexAxis: 'x',
                barPercentage: 0.8,
                categoryPercentage: 0.9
            }
        });
        
    </script>
</body>
</html>