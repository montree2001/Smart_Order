
<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/tcpdf/tcpdf.php';

requireLogin();

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get report data
$salesSummary = $db->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as avg_order_value,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$startDate, $endDate]);

$popularItems = $db->fetchAll("
    SELECT 
        mi.name,
        mi.category,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total_price) as total_revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'completed'
    GROUP BY mi.id, mi.name, mi.category
    ORDER BY total_sold DESC
    LIMIT 10
", [$startDate, $endDate]);

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Smart Order System');
$pdf->SetTitle('รายงานยอดขาย');

$pdf->SetMargins(15, 27, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 25);

$pdf->AddPage();

// Set font
$pdf->SetFont('thsarabunnew', '', 16);

// Title
$pdf->Cell(0, 10, 'รายงานยอดขาย', 0, 1, 'C');
$pdf->Cell(0, 8, 'ระหว่างวันที่ ' . date('d/m/Y', strtotime($startDate)) . ' ถึง ' . date('d/m/Y', strtotime($endDate)), 0, 1, 'C');
$pdf->Ln(10);

// Summary
$pdf->SetFont('thsarabunnew', 'B', 14);
$pdf->Cell(0, 8, 'สรุปภาพรวม', 0, 1, 'L');
$pdf->SetFont('thsarabunnew', '', 12);

$html = '
<table border="1" cellpadding="5">
    <tr>
        <td width="50%"><b>จำนวนออเดอร์ทั้งหมด</b></td>
        <td width="50%">' . number_format($salesSummary['total_orders']) . ' ออเดอร์</td>
    </tr>
    <tr>
        <td><b>ยอดขายรวม</b></td>
        <td>' . number_format($salesSummary['total_sales'], 2) . ' บาท</td>
    </tr>
    <tr>
        <td><b>ค่าเฉลี่ยต่อออเดอร์</b></td>
        <td>' . number_format($salesSummary['avg_order_value'], 2) . ' บาท</td>
    </tr>
    <tr>
        <td><b>ออเดอร์ที่เสร็จสิ้น</b></td>
        <td>' . number_format($salesSummary['completed_orders']) . ' ออเดอร์</td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->Ln(10);

// Popular items
$pdf->SetFont('thsarabunnew', 'B', 14);
$pdf->Cell(0, 8, 'เมนูขายดี Top 10', 0, 1, 'L');

$html = '<table border="1" cellpadding="5">
    <tr style="background-color: #f0f0f0;">
        <td width="10%"><b>อันดับ</b></td>
        <td width="40%"><b>ชื่อเมนู</b></td>
        <td width="20%"><b>หมวดหมู่</b></td>
        <td width="15%"><b>จำนวนที่ขาย</b></td>
        <td width="15%"><b>ยอดขาย</b></td>
    </tr>';

foreach ($popularItems as $index => $item) {
    $html .= '<tr>
        <td>' . ($index + 1) . '</td>
        <td>' . htmlspecialchars($item['name']) . '</td>
        <td>' . htmlspecialchars($item['category']) . '</td>
        <td>' . number_format($item['total_sold']) . '</td>
        <td>' . number_format($item['total_revenue'], 2) . '</td>
    </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, false, false, '');

// Output PDF
$pdf->Output('sales_report_' . date('Y-m-d') . '.pdf', 'D');

// assets/js/custom.js - Main JavaScript file
// Auto-refresh functionality
function enableAutoRefresh(interval = 30000) {
    setInterval(function() {
        if (document.hidden) return; // Don't refresh if tab is not active
        
        const currentTime = new Date().getTime();
        const pageLoadTime = window.performance.timing.navigationStart;
        const timeSinceLoad = currentTime - pageLoadTime;
        
        // Only auto-refresh if page has been loaded for more than 5 seconds
        if (timeSinceLoad > 5000) {
            location.reload();
        }
    }, interval);
}

// Initialize DataTables with Thai language
function initDataTable(selector, options = {}) {
    const defaultOptions = {
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
        },
        responsive: true,
        pageLength: 25,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        ...options
    };
    
    return $(selector).DataTable(defaultOptions);
}

// Status color helpers
function getStatusBadge(status) {
    const statusConfig = {
        'pending': { class: 'warning', text: 'รอยืนยัน' },
        'confirmed': { class: 'info', text: 'ยืนยันแล้ว' },
        'preparing': { class: 'warning', text: 'กำลังทำ' },
        'ready': { class: 'success', text: 'พร้อมเสิร์ฟ' },
        'completed': { class: 'success', text: 'เสร็จสิ้น' },
        'cancelled': { class: 'danger', text: 'ยกเลิก' }
    };
    
    const config = statusConfig[status] || { class: 'secondary', text: status };
    return `<span class="badge bg-${config.class}">${config.text}</span>`;
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB'
    }).format(amount);
}

// Format datetime
function formatDateTime(datetime) {
    return new Date(datetime).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Show notification
function showNotification(message, type = 'success', duration = 5000) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    `;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, duration);
}

// Initialize tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Page load initialization
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    
    // Initialize auto-refresh for specific pages
    if (window.location.pathname.includes('queue_management') || 
        window.location.pathname.includes('index.php')) {
        enableAutoRefresh(15000); // 15 seconds for queue and dashboard
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});