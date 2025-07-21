<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Menu.php';

requireLogin();

$pageTitle = 'จัดการเมนู';
$activePage = 'menu';

$menu = new Menu($db);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'name' => $_POST['name'],
                    'price' => $_POST['price'],
                    'category' => $_POST['category'],
                    'description' => $_POST['description'],
                    'image_url' => $_POST['image_url'],
                    'available' => isset($_POST['available']) ? 1 : 0
                ];
                $menu->addItem($data);
                $message = "เพิ่มเมนูสำเร็จ";
                break;

            case 'edit':
                $data = [
                    'name' => $_POST['name'],
                    'price' => $_POST['price'],
                    'category' => $_POST['category'],
                    'description' => $_POST['description'],
                    'image_url' => $_POST['image_url'],
                    'available' => isset($_POST['available']) ? 1 : 0
                ];
                $menu->updateItem($_POST['id'], $data);
                $message = "แก้ไขเมนูสำเร็จ";
                break;

            case 'delete':
                $menu->deleteItem($_POST['id']);
                $message = "ลบเมนูสำเร็จ";
                break;
        }
    }
}

$menuItems = $menu->getAllItems();
$categories = $menu->getCategories();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-utensils"></i> จัดการเมนู</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuModal">
        <i class="fas fa-plus"></i> เพิ่มเมนูใหม่
    </button>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Menu Items Table -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">รายการเมนูทั้งหมด</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="menuTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>รูปภาพ</th>
                        <th>ชื่อเมนู</th>
                        <th>หมวดหมู่</th>
                        <th>ราคา</th>
                        <th>สถานะ</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($menuItems as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['image_url']): ?>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 0.5rem;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                        </td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($item['category']) ?></span></td>
                        <td class="fw-bold"><?= formatCurrency($item['price']) ?></td>
                        <td>
                            <span class="badge bg-<?= $item['available'] ? 'success' : 'danger' ?>">
                                <?= $item['available'] ? 'พร้อมขาย' : 'ไม่พร้อมขาย' ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editMenu(<?= htmlspecialchars(json_encode($item)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMenu(<?= $item['id'] ?>, '<?= htmlspecialchars($item['name']) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Menu Modal -->
<div class="modal fade" id="addMenuModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มเมนูใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">ชื่อเมนู</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">ราคา</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category" class="form-label">หมวดหมู่</label>
                                <input type="text" class="form-control" id="category" name="category" list="categoryList" required>
                                <datalist id="categoryList">
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['category']) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="image_url" class="form-label">URL รูปภาพ</label>
                                <input type="url" class="form-control" id="image_url" name="image_url">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">รายละเอียด</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="available" name="available" checked>
                        <label class="form-check-label" for="available">
                            พร้อมขาย
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Menu Modal -->
<div class="modal fade" id="editMenuModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขเมนู</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">ชื่อเมนู</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">ราคา</label>
                                <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_category" class="form-label">หมวดหมู่</label>
                                <input type="text" class="form-control" id="edit_category" name="category" list="categoryList" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_image_url" class="form-label">URL รูปภาพ</label>
                                <input type="url" class="form-control" id="edit_image_url" name="image_url">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">รายละเอียด</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_available" name="available">
                        <label class="form-check-label" for="edit_available">
                            พร้อมขาย
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#menuTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
        },
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 5] }
        ]
    });
});

function editMenu(item) {
    $('#edit_id').val(item.id);
    $('#edit_name').val(item.name);
    $('#edit_price').val(item.price);
    $('#edit_category').val(item.category);
    $('#edit_description').val(item.description);
    $('#edit_image_url').val(item.image_url);
    $('#edit_available').prop('checked', item.available == 1);
    
    $('#editMenuModal').modal('show');
}

function deleteMenu(id, name) {
    if (confirm(`คุณต้องการลบเมนู "${name}" หรือไม่?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>