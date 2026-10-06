<?php
/**
 * متحكم المصروفات
 */

class ExpenseController
{
    private Expense $expenseModel;
    private Car $carModel;
    
    public function __construct()
    {
        $this->expenseModel = new Expense();
        $this->carModel = new Car();
    }
    
    /**
     * قائمة المصروفات
     */
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        // الفلاتر
        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'car_id'      => (int) ($_GET['car_id'] ?? 0),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'date_from'   => trim($_GET['date_from'] ?? ''),
            'date_to'     => trim($_GET['date_to'] ?? ''),
        ];
        
        $page = max(1, (int) ($_GET['page'] ?? 1));
        
        // البيانات
        $result = $this->expenseModel->all($filters, $page, 20);
        $categories = $this->expenseModel->getCategories();
        $cars = $this->expenseModel->getCars();
        $stats = $this->expenseModel->getStats();
        
        $expenses = $result['items'];
        $total = $result['total'];
        $totalAmount = $result['total_amount'];
        $totalPages = $result['total_pages'];
        $currentPage = $page;
        $user = Auth::user();
        $pageTitle = 'expenses';
        
        require BASE_PATH . '/app/views/expenses/index.php';
    }
    
    /**
     * نموذج إضافة مصروف
     */
    public function create(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $categories = $this->expenseModel->getCategories();
        $cars = $this->expenseModel->getCars();
        
        $user = Auth::user();
        $pageTitle = 'add_expense';
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) $errors = [];
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) $old = [];
        
        // سيارة محددة مسبقاً
        $preSelectedCarId = (int) ($_GET['car_id'] ?? 0);
        if ($preSelectedCarId > 0 && empty($old['car_id'])) {
            $old['car_id'] = $preSelectedCarId;
        }
        
        require BASE_PATH . '/app/views/expenses/create.php';
    }
    
    /**
     * حفظ مصروف
     */
    public function store(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        // CSRF
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/expenses/create'));
            exit;
        }
        
        // التحقق
        $errors = $this->validate($_POST);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/expenses/create'));
            exit;
        }
        
        // تجهيز البيانات
        $data = $this->prepareData($_POST);
        $data['created_by'] = Auth::id();
        
        try {
            $expenseId = $this->expenseModel->create($data);
            
            // تسجيل النشاط
            Auth::logActivityStatic(Auth::id(), 'create', 'car_expenses', [
                'expense_id' => $expenseId,
                'car_id'     => $data['car_id'],
                'amount'     => $data['amount'],
            ]);
            
            Auth::setFlash('success', 'تم إضافة المصروف بنجاح');
            header('Location: ' . url('/expenses/' . $expenseId));
            exit;
            
        } catch (Exception $e) {
            error_log('Expense create error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ: ' . $e->getMessage()]);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/expenses/create'));
            exit;
        }
    }
    
    /**
     * عرض تفاصيل مصروف
     */
    public function show(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $expense = $this->expenseModel->find($id);
        
        if (!$expense) {
            Auth::setFlash('error', 'المصروف غير موجود');
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        // معلومات السيارة
        $car = $this->carModel->find((int) $expense['car_id']);
        
        // إجمالي مصروفات السيارة
        $carTotalExpenses = $this->expenseModel->getCarTotal((int) $expense['car_id']);
        
        // مصروفات السيارة الأخرى
        $carExpenses = $this->expenseModel->getByCar((int) $expense['car_id'], 10);
        
        $user = Auth::user();
        $pageTitle = 'expense_details';
        
        require BASE_PATH . '/app/views/expenses/show.php';
    }
    
    /**
     * نموذج تعديل مصروف
     */
    public function edit(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $expense = $this->expenseModel->find($id);
        
        if (!$expense) {
            Auth::setFlash('error', 'المصروف غير موجود');
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        $categories = $this->expenseModel->getCategories();
        $cars = $this->expenseModel->getCars();
        
        $user = Auth::user();
        $pageTitle = 'edit_expense';
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) $errors = [];
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) $old = [];
        
        require BASE_PATH . '/app/views/expenses/edit.php';
    }
    
    /**
     * تحديث مصروف
     */
    public function update(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        $expense = $this->expenseModel->find($id);
        
        if (!$expense) {
            Auth::setFlash('error', 'المصروف غير موجود');
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        // CSRF
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/expenses/' . $id . '/edit'));
            exit;
        }
        
        // التحقق
        $errors = $this->validate($_POST);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/expenses/' . $id . '/edit'));
            exit;
        }
        
        $data = $this->prepareData($_POST);
        
        try {
            $this->expenseModel->update($id, $data);
            Auth::logActivityStatic(Auth::id(), 'update', 'car_expenses', ['expense_id' => $id]);
            Auth::setFlash('success', 'تم تحديث المصروف بنجاح');
            header('Location: ' . url('/expenses/' . $id));
            exit;
        } catch (Exception $e) {
            error_log('Expense update error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ: ' . $e->getMessage()]);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/expenses/' . $id . '/edit'));
            exit;
        }
    }
    
    /**
     * حذف مصروف
     */
    public function delete(int $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح');
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        $expense = $this->expenseModel->find($id);
        if (!$expense) {
            Auth::setFlash('error', 'المصروف غير موجود');
            header('Location: ' . url('/expenses'));
            exit;
        }
        
        try {
            $this->expenseModel->delete($id);
            Auth::logActivityStatic(Auth::id(), 'delete', 'car_expenses', ['expense_id' => $id]);
            Auth::setFlash('success', 'تم حذف المصروف');
        } catch (Exception $e) {
            error_log('Expense delete error: ' . $e->getMessage());
            Auth::setFlash('error', 'حدث خطأ أثناء الحذف');
        }
        
        header('Location: ' . url('/expenses'));
        exit;
    }
    
    /**
     * التحقق
     */
    private function validate(array $data): array
    {
        $errors = [];
        
        if (empty($data['car_id'])) {
            $errors['car_id'] = 'السيارة مطلوبة';
        }
        
        if (empty($data['category_id'])) {
            $errors['category_id'] = 'التصنيف مطلوب';
        }
        
        if (empty($data['description'])) {
            $errors['description'] = 'الوصف مطلوب';
        }
        
        if (empty($data['amount']) || (float) $data['amount'] <= 0) {
            $errors['amount'] = 'المبلغ يجب أن يكون أكبر من صفر';
        }
        
        if (empty($data['expense_date'])) {
            $errors['expense_date'] = 'تاريخ المصروف مطلوب';
        }
        
        return $errors;
    }
    
    /**
     * تجهيز البيانات
     */
    private function prepareData(array $data): array
    {
        return [
            'car_id'            => (int) $data['car_id'],
            'category_id'       => (int) $data['category_id'],
            'description'       => trim($data['description'] ?? ''),
            'amount'            => (float) $data['amount'],
            'expense_date'      => $data['expense_date'],
            'vendor'            => trim($data['vendor'] ?? '') ?: null,
            'invoice_no'        => trim($data['invoice_no'] ?? '') ?: null,
            'invoice_image'     => null,
            'odometer_at'       => !empty($data['odometer_at']) ? (int) $data['odometer_at'] : null,
            'next_service_date' => !empty($data['next_service_date']) ? $data['next_service_date'] : null,
            'next_service_km'   => !empty($data['next_service_km']) ? (int) $data['next_service_km'] : null,
        ];
    }
}