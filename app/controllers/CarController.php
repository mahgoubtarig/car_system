<?php
/**
 * متحكم السيارات
 */

class CarController
{
    private Car $carModel;
    
    public function __construct()
    {
        $this->carModel = new Car();
    }
    
    /**
     * قائمة السيارات
     */
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'status'      => trim($_GET['status'] ?? ''),
            'car_type_id' => (int) ($_GET['car_type_id'] ?? 0),
            'brand'       => trim($_GET['brand'] ?? ''),
        ];
        
        $page = max(1, (int) ($_GET['page'] ?? 1));
        
        $result = $this->carModel->all($filters, $page, 15);
        $types  = $this->carModel->getTypes();
        $brands = $this->carModel->getBrands();
        $stats  = $this->carModel->getStats();
        
        $cars        = $result['items'];
        $total       = $result['total'];
        $totalPages  = $result['total_pages'];
        $currentPage = $page;
        $user        = Auth::user();
        $pageTitle   = 'cars';
        
        require BASE_PATH . '/app/views/cars/index.php';
    }
    
    /**
     * نموذج إضافة سيارة
     */
    public function create(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $types = $this->carModel->getTypes();
        $user = Auth::user();
        $pageTitle = 'add_car';
        $car = null;
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) $errors = [];
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) $old = [];
        
        require BASE_PATH . '/app/views/cars/create.php';
    }
    
    /**
     * حفظ سيارة جديدة
     */
    public function store(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/cars'));
            exit;
        }
        
        // CSRF
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/cars/create'));
            exit;
        }
        
        $errors = $this->validate($_POST);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/cars/create'));
            exit;
        }
        
        $data = $this->prepareData($_POST);
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            // 1. إنشاء السيارة
            $carId = $this->carModel->create($data);
            
            // 2. رفع الصور
            $this->handleImageUploads($carId);
            
            // 3. تسجيل النشاط
            Auth::logActivityStatic(Auth::id(), 'create', 'cars', ['car_id' => $carId]);
            
            $db->commit();
            
            Auth::setFlash('success', 'تم إضافة السيارة بنجاح');
            header('Location: ' . url('/cars/' . $carId));
            exit;
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log('Car create error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ: ' . $e->getMessage()]);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/cars/create'));
            exit;
        }
    }
    
    /**
     * عرض تفاصيل سيارة
     */
    public function show(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $car = $this->carModel->find($id);
        
        if (!$car) {
            http_response_code(404);
            Auth::setFlash('error', 'السيارة غير موجودة');
            header('Location: ' . url('/cars'));
            exit;
        }
        
        // ✅ جلب الصور الإضافية
        $carImages = $this->carModel->getImages($id);
        
        $db = Database::getInstance();
        
        $rentals = $db->fetchAll(
            "SELECT r.*, cu.full_name as customer_name
             FROM rentals r
             JOIN customers cu ON cu.id = r.customer_id
             WHERE r.car_id = :id
             ORDER BY r.created_at DESC
             LIMIT 10",
            ['id' => $id]
        );
        
        $expenses = $db->fetchAll(
            "SELECT e.*, ec.name_ar as category_name
             FROM car_expenses e
             JOIN expense_categories ec ON ec.id = e.category_id
             WHERE e.car_id = :id
             ORDER BY e.expense_date DESC
             LIMIT 10",
            ['id' => $id]
        );
        
        $income = (float) ($db->fetchOne(
            "SELECT COALESCE(SUM(p.amount), 0) as total
             FROM payments p
             JOIN rentals r ON r.id = p.rental_id
             WHERE r.car_id = :id
             AND p.payment_type IN ('rent', 'penalty')",
            ['id' => $id]
        )['total'] ?? 0);
        
        $expensesTotal = (float) ($db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM car_expenses
             WHERE car_id = :id",
            ['id' => $id]
        )['total'] ?? 0);
        
        $profit = $income - $expensesTotal;
        
        $user = Auth::user();
        $pageTitle = 'car_details';
        
        require BASE_PATH . '/app/views/cars/show.php';
    }
    
    /**
     * نموذج تعديل سيارة
     */
    public function edit(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $car = $this->carModel->find($id);
        
        if (!$car) {
            Auth::setFlash('error', 'السيارة غير موجودة');
            header('Location: ' . url('/cars'));
            exit;
        }
        
        // ✅ جلب الصور
        $carImages = $this->carModel->getImages($id);
        
        $types = $this->carModel->getTypes();
        $user = Auth::user();
        $pageTitle = 'edit_car';
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) $errors = [];
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) $old = [];
        
        require BASE_PATH . '/app/views/cars/edit.php';
    }
    
    /**
     * تحديث سيارة
     */
    public function update(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/cars'));
            exit;
        }
        
        $car = $this->carModel->find($id);
        
        if (!$car) {
            Auth::setFlash('error', 'السيارة غير موجودة');
            header('Location: ' . url('/cars'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/cars/' . $id . '/edit'));
            exit;
        }
        
        $errors = $this->validate($_POST, $id);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/cars/' . $id . '/edit'));
            exit;
        }
        
        $data = $this->prepareData($_POST);
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            // 1. تحديث السيارة
            $this->carModel->update($id, $data);
            
            // 2. رفع الصور الجديدة
            $this->handleImageUploads($id);
            
            // 3. تسجيل النشاط
            Auth::logActivityStatic(Auth::id(), 'update', 'cars', ['car_id' => $id]);
            
            $db->commit();
            
            Auth::setFlash('success', 'تم تحديث السيارة بنجاح');
            header('Location: ' . url('/cars/' . $id));
            exit;
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log('Car update error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ: ' . $e->getMessage()]);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/cars/' . $id . '/edit'));
            exit;
        }
    }
    
    /**
     * حذف سيارة
     */
    public function delete(int $id): void
    {
        if (!Auth::check() || !Auth::can('delete_car')) {
            http_response_code(403);
            Auth::setFlash('error', 'غير مصرح');
            header('Location: ' . url('/cars'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/cars'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/cars'));
            exit;
        }
        
        $success = $this->carModel->delete($id);
        
        if ($success) {
            Auth::logActivityStatic(Auth::id(), 'delete', 'cars', ['car_id' => $id]);
            Auth::setFlash('success', 'تم حذف السيارة');
        } else {
            Auth::setFlash('error', 'لا يمكن حذف سيارة لها عقود');
        }
        
        header('Location: ' . url('/cars'));
        exit;
    }
    
    /**
     * تغيير حالة السيارة
     */
    public function changeStatus(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/cars/' . $id));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/cars/' . $id));
            exit;
        }
        
        $status = $_POST['status'] ?? '';
        $allowed = ['available', 'maintenance', 'out_of_service'];
        
        if (!in_array($status, $allowed, true)) {
            Auth::setFlash('error', 'حالة غير صحيحة');
            header('Location: ' . url('/cars/' . $id));
            exit;
        }
        
        $car = $this->carModel->find($id);
        if ($car && $car['status'] === 'rented') {
            Auth::setFlash('error', 'لا يمكن تغيير الحالة أثناء وجود السيارة مؤجرة');
            header('Location: ' . url('/cars/' . $id));
            exit;
        }
        
        try {
            $this->carModel->changeStatus($id, $status);
            Auth::logActivityStatic(Auth::id(), 'change_status', 'cars', [
                'car_id' => $id,
                'status' => $status,
            ]);
            
            $labels = [
                'available' => 'متاحة',
                'maintenance' => 'في الصيانة',
                'out_of_service' => 'خارج الخدمة',
            ];
            
            Auth::setFlash('success', 'تم تغيير الحالة إلى: ' . ($labels[$status] ?? $status));
        } catch (Exception $e) {
            Auth::setFlash('error', 'حدث خطأ');
        }
        
        header('Location: ' . url('/cars/' . $id));
        exit;
    }
    
    /**
     * ✅ حذف صورة
     */
    public function deleteImage(int $imageId): void
    {
        if (!Auth::check()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'غير مصرح']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'انتهت صلاحية الجلسة']);
            exit;
        }
        
        try {
            $success = $this->carModel->deleteImage($imageId);
            
            if ($success) {
                Auth::logActivityStatic(Auth::id(), 'delete_image', 'cars', ['image_id' => $imageId]);
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => $success], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    /**
     * ✅ حذف الصورة الرئيسية
     */
    public function deleteMainImage(int $carId): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/cars/' . $carId));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/cars/' . $carId));
            exit;
        }
        
        $this->carModel->deleteMainImage($carId);
        Auth::logActivityStatic(Auth::id(), 'delete_main_image', 'cars', ['car_id' => $carId]);
        
        Auth::setFlash('success', 'تم حذف الصورة الرئيسية');
        header('Location: ' . url('/cars/' . $carId));
        exit;
    }
    
    // ============================================================
    // دوال مساعدة
    // ============================================================
    
    /**
     * ✅ معالجة رفع الصور
     */
    private function handleImageUploads(int $carId): void
    {
        $uploadDir = STORAGE_PATH . '/uploads/cars';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        
        // 1. الصورة الرئيسية
        if (!empty($_FILES['main_image']['name']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = $this->uploadImage(
                $_FILES['main_image'],
                $uploadDir,
                'main_' . $carId
            );
            
            if ($uploaded) {
                $this->carModel->deleteMainImage($carId);
                $this->carModel->updateMainImage($carId, $uploaded);
            }
        }
        
        // 2. الصور الإضافية
        if (!empty($_FILES['additional_images']['name'][0])) {
            $fileCount = count($_FILES['additional_images']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name'     => $_FILES['additional_images']['name'][$i],
                        'type'     => $_FILES['additional_images']['type'][$i],
                        'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                        'error'    => $_FILES['additional_images']['error'][$i],
                        'size'     => $_FILES['additional_images']['size'][$i],
                    ];
                    
                    $uploaded = $this->uploadImage(
                        $file,
                        $uploadDir,
                        'img_' . $carId . '_' . ($i + 1)
                    );
                    
                    if ($uploaded) {
                        $this->carModel->addImage($carId, $uploaded, false);
                    }
                }
            }
        }
    }
    
    /**
     * ✅ رفع صورة واحدة
     */
    private function uploadImage(array $file, string $uploadDir, string $prefix): ?string
    {
        // التحقق من الحجم (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            error_log('Image too large: ' . $file['size']);
            return null;
        }
        
        // التحقق من النوع
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            error_log('Invalid image type: ' . $mimeType);
            return null;
        }
        
        // امتداد الملف
        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };
        
        // اسم فريد
        $fileName = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $filePath = $uploadDir . '/' . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return $fileName;
        }
        
        return null;
    }
    
    /**
     * التحقق من المدخلات
     */
    private function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        
        if (empty($data['plate_number'])) {
            $errors['plate_number'] = 'رقم اللوحة مطلوب';
        } elseif ($this->carModel->plateExists($data['plate_number'], $excludeId)) {
            $errors['plate_number'] = 'رقم اللوحة مستخدم بالفعل';
        }
        
        if (empty($data['chassis_number'])) {
            $errors['chassis_number'] = 'رقم الهيكل مطلوب';
        } elseif ($this->carModel->chassisExists($data['chassis_number'], $excludeId)) {
            $errors['chassis_number'] = 'رقم الهيكل مستخدم بالفعل';
        }
        
        if (empty($data['brand'])) $errors['brand'] = 'الماركة مطلوبة';
        if (empty($data['model'])) $errors['model'] = 'الموديل مطلوب';
        
        if (empty($data['year']) || $data['year'] < 1900 || $data['year'] > (int)date('Y') + 1) {
            $errors['year'] = 'سنة الصنع غير صحيحة';
        }
        
        if (empty($data['car_type_id'])) $errors['car_type_id'] = 'نوع السيارة مطلوب';
        
        if (!isset($data['daily_price']) || $data['daily_price'] < 0) {
            $errors['daily_price'] = 'السعر اليومي غير صحيح';
        }
        
        return $errors;
    }
    
    /**
     * تجهيز بيانات السيارة
     */
    private function prepareData(array $data): array
    {
        return [
            'plate_number'         => trim($data['plate_number'] ?? ''),
            'car_type_id'          => (int) ($data['car_type_id'] ?? 0),
            'brand'                => trim($data['brand'] ?? ''),
            'model'                => trim($data['model'] ?? ''),
            'year'                 => (int) ($data['year'] ?? date('Y')),
            'color'                => trim($data['color'] ?? ''),
            'chassis_number'       => trim($data['chassis_number'] ?? ''),
            'fuel_type'            => $data['fuel_type'] ?? 'petrol',
            'transmission'         => $data['transmission'] ?? 'automatic',
            'seats'                => (int) ($data['seats'] ?? 5),
            'odometer'             => (int) ($data['odometer'] ?? 0),
            'daily_price'          => (float) ($data['daily_price'] ?? 0),
            'weekly_price'         => (float) ($data['weekly_price'] ?? 0),
            'monthly_price'        => (float) ($data['monthly_price'] ?? 0),
            'registration_expiry'  => !empty($data['registration_expiry']) ? $data['registration_expiry'] : null,
            'insurance_expiry'     => !empty($data['insurance_expiry']) ? $data['insurance_expiry'] : null,
            'inspection_expiry'    => !empty($data['inspection_expiry']) ? $data['inspection_expiry'] : null,
            'purchase_price'       => !empty($data['purchase_price']) ? (float) $data['purchase_price'] : null,
            'status'               => $data['status'] ?? 'available',
            'notes'                => trim($data['notes'] ?? ''),
        ];
    }
}