<?php
/**
 * متحكم المستأجرين
 */

class CustomerController
{
    private Customer $customerModel;
    
    public function __construct()
    {
        $this->customerModel = new Customer();
    }
    
    /**
     * قائمة المستأجرين
     */
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        // الفلاتر
        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'city'           => trim($_GET['city'] ?? ''),
            'id_type'        => trim($_GET['id_type'] ?? ''),
            'is_blacklisted' => $_GET['is_blacklisted'] ?? '',
        ];
        
        $page = max(1, (int) ($_GET['page'] ?? 1));
        
        // جلب البيانات
        $result = $this->customerModel->all($filters, $page, 15);
        $cities = $this->customerModel->getCities();
        $stats  = $this->customerModel->getStats();
        
        // متغيرات للعرض
        $customers       = $result['items'];
        $total           = $result['total'];
        $totalPages      = $result['total_pages'];
        $currentPage     = $page;
        $user            = Auth::user();
        $pageTitle       = 'customers';
        
        require BASE_PATH . '/app/views/customers/index.php';
    }
    
    /**
     * نموذج إضافة مستأجر
     */
    public function create(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $user = Auth::user();
        $pageTitle = 'add_customer';
        $customer = null;
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) {
            $errors = [];
        }
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) {
            $old = [];
        }
        
        require BASE_PATH . '/app/views/customers/create.php';
    }
    
    /**
     * حفظ مستأجر جديد
     */
    public function store(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/customers'));
            exit;
        }
        
        // CSRF
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/customers/create'));
            exit;
        }
        
        // التحقق
        $errors = $this->validate($_POST);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/customers/create'));
            exit;
        }
        
        // تجهيز البيانات
        $data = $this->prepareData($_POST);
        
        try {
            $customerId = $this->customerModel->create($data);
            
            Auth::logActivityStatic(Auth::id(), 'create', 'customers', ['customer_id' => $customerId]);
            
            Auth::setFlash('success', 'تم إضافة المستأجر بنجاح');
            header('Location: ' . url('/customers/' . $customerId));
            exit;
            
        } catch (Exception $e) {
            error_log('Customer create error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ أثناء الحفظ: ' . $e->getMessage()]);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/customers/create'));
            exit;
        }
    }
    
    /**
     * عرض تفاصيل مستأجر
     */
    public function show($id = null): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }

        // --- التعديل هنا: منع حلقة إعادة التوجيه اللانهائية ---
        if (empty($id)) {
            $this->index();
            return;
        }
        $id = (int) $id;
        // ------------------------------------------------
        
        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            Auth::setFlash('error', 'المستأجر غير موجود');
            header('Location: ' . url('/customers'));
            exit;
        }
        
        $db = Database::getInstance();
        
        // جلب العقود
        $rentals = $db->fetchAll(
            "SELECT r.*, c.plate_number, c.brand, c.model
             FROM rentals r
             JOIN cars c ON c.id = r.car_id
             WHERE r.customer_id = :id
             ORDER BY r.created_at DESC
             LIMIT 10",
            ['id' => $id]
        );
        
        // الإحصائيات المالية
        $totalPaid = (float) ($db->fetchOne(
            "SELECT COALESCE(SUM(p.amount), 0) as total
             FROM payments p
             JOIN rentals r ON r.id = p.rental_id
             WHERE r.customer_id = :id
             AND p.payment_type IN ('rent', 'penalty')",
            ['id' => $id]
        )['total'] ?? 0);
        
        $totalContracted = (float) ($db->fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as total
             FROM rentals
             WHERE customer_id = :id",
            ['id' => $id]
        )['total'] ?? 0);
        
        $remaining = $totalContracted - $totalPaid;
        
        $user = Auth::user();
        $pageTitle = 'customer_details';
        
        require BASE_PATH . '/app/views/customers/show.php';
    }
    
    /**
     * نموذج تعديل مستأجر
     */
    public function edit($id = null): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if (empty($id)) {
            header('Location: ' . url('/customers'));
            exit;
        }
        $id = (int) $id;
        
        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            Auth::setFlash('error', 'المستأجر غير موجود');
            header('Location: ' . url('/customers'));
            exit;
        }
        
        $user = Auth::user();
        $pageTitle = 'edit_customer';
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) {
            $errors = [];
        }
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) {
            $old = [];
        }
        
        require BASE_PATH . '/app/views/customers/edit.php';
    }
    
    /**
     * تحديث مستأجر
     */
    public function update($id = null): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if (empty($id) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/customers'));
            exit;
        }
        $id = (int) $id;
        
        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            Auth::setFlash('error', 'المستأجر غير موجود');
            header('Location: ' . url('/customers'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/customers/' . $id . '/edit'));
            exit;
        }
        
        $errors = $this->validate($_POST, $id);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/customers/' . $id . '/edit'));
            exit;
        }
        
        $data = $this->prepareData($_POST);
        
        try {
            $this->customerModel->update($id, $data);
            Auth::logActivityStatic(Auth::id(), 'update', 'customers', ['customer_id' => $id]);
            Auth::setFlash('success', 'تم تحديث المستأجر بنجاح');
            header('Location: ' . url('/customers/' . $id));
            exit;
        } catch (Exception $e) {
            error_log('Customer update error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ أثناء التحديث']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/customers/' . $id . '/edit'));
            exit;
        }
    }
    
    /**
     * حذف مستأجر
     */
    public function delete($id = null): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح');
            header('Location: ' . url('/customers'));
            exit;
        }
        
        if (empty($id) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/customers'));
            exit;
        }
        $id = (int) $id;
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/customers'));
            exit;
        }
        
        $success = $this->customerModel->delete($id);
        
        if ($success) {
            Auth::logActivityStatic(Auth::id(), 'delete', 'customers', ['customer_id' => $id]);
            Auth::setFlash('success', 'تم حذف المستأجر');
        } else {
            Auth::setFlash('error', 'لا يمكن حذف مستأجر له عقود');
        }
        
        header('Location: ' . url('/customers'));
        exit;
    }
    
    /**
     * تبديل القائمة السوداء
     */
    public function toggleBlacklist($id = null): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح');
            $redirectUrl = !empty($id) ? url('/customers/' . (int)$id) : url('/customers');
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        if (empty($id) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $redirectUrl = !empty($id) ? url('/customers/' . (int)$id) : url('/customers');
            header('Location: ' . $redirectUrl);
            exit;
        }
        $id = (int) $id;
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/customers/' . $id));
            exit;
        }
        
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            Auth::setFlash('error', 'المستأجر غير موجود');
            header('Location: ' . url('/customers'));
            exit;
        }
        
        $newStatus = empty($customer['is_blacklisted']);
        $reason = trim($_POST['reason'] ?? '');
        
        $this->customerModel->setBlacklist($id, $newStatus, $reason);
        Auth::logActivityStatic(Auth::id(), 'blacklist_toggle', 'customers', [
            'customer_id' => $id,
            'blacklisted' => $newStatus,
        ]);
        
        Auth::setFlash('success', $newStatus 
            ? 'تم إضافة المستأجر إلى القائمة السوداء' 
            : 'تم إزالة المستأجر من القائمة السوداء');
        
        header('Location: ' . url('/customers/' . $id));
        exit;
    }
    
    /**
     * التحقق من المدخلات
     */
    private function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        
        // الاسم
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'الاسم الكامل مطلوب';
        }
        
        // رقم الهوية
        if (empty($data['id_number'])) {
            $errors['id_number'] = 'رقم الهوية / الإقامة مطلوب';
        } elseif ($this->customerModel->idNumberExists($data['id_number'], $excludeId)) {
            $errors['id_number'] = 'رقم الهوية مستخدم بالفعل';
        }
        
        // نوع الإثبات
        if (empty($data['id_type'])) {
            $errors['id_type'] = 'نوع الإثبات مطلوب';
        }
        
        // الجوال
        if (empty($data['phone'])) {
            $errors['phone'] = 'رقم الجوال مطلوب';
        }
        
        return $errors;
    }
    
    /**
     * تجهيز بيانات المستأجر
     */
    private function prepareData(array $data): array
    {
        return [
            'full_name'           => trim($data['full_name'] ?? ''),
            'nationality'         => trim($data['nationality'] ?? ''),
            'id_type'             => $data['id_type'] ?? 'national_id',
            'id_number'           => trim($data['id_number'] ?? ''),
            'id_expiry'           => !empty($data['id_expiry']) ? $data['id_expiry'] : null,
            'birth_date'          => !empty($data['birth_date']) ? $data['birth_date'] : null,
            'phone'               => trim($data['phone'] ?? ''),
            'phone2'              => trim($data['phone2'] ?? ''),
            'email'               => trim($data['email'] ?? ''),
            'city'                => trim($data['city'] ?? ''),
            'address'             => trim($data['address'] ?? ''),
            'license_number'      => trim($data['license_number'] ?? ''),
            'license_expiry'      => !empty($data['license_expiry']) ? $data['license_expiry'] : null,
            'sponsor_or_employer' => trim($data['sponsor_or_employer'] ?? ''),
            'emergency_contact'   => trim($data['emergency_contact'] ?? ''),
            'id_image'            => null,
            'license_image'       => null,
            'is_blacklisted'      => isset($data['is_blacklisted']) ? 1 : 0,
            'blacklist_reason'    => trim($data['blacklist_reason'] ?? ''),
            'notes'               => trim($data['notes'] ?? ''),
        ];
    }
}