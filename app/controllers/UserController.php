<?php
/**
 * متحكم المستخدمين
 */

class UserController
{
    private User $userModel;
    
    public function __construct()
    {
        $this->userModel = new User();
    }
    
    /**
     * قائمة المستخدمين
     */
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        // المدير فقط
        if (!Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح — الإدارة للمدير فقط');
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $filters = [
            'search'    => trim($_GET['search'] ?? ''),
            'role'      => trim($_GET['role'] ?? ''),
            'is_active' => $_GET['is_active'] ?? '',
        ];
        
        $users = $this->userModel->all($filters);
        $stats = $this->userModel->getStats();
        $roles = $this->userModel->getRoles();
        
        $user = Auth::user();
        $pageTitle = 'users';
        
        require BASE_PATH . '/app/views/users/index.php';
    }
    
    /**
     * نموذج إضافة مستخدم
     */
    public function create(): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $roles = $this->userModel->getRoles();
        $user = Auth::user();
        $pageTitle = 'add_user';
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) $errors = [];
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) $old = [];
        
        require BASE_PATH . '/app/views/users/create.php';
    }
    
    /**
     * حفظ مستخدم جديد
     */
    public function store(): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/users'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/users/create'));
            exit;
        }
        
        $errors = $this->validate($_POST, null, true);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/users/create'));
            exit;
        }
        
        try {
            $userId = $this->userModel->create($_POST);
            
            Auth::logActivityStatic(Auth::id(), 'create', 'users', ['user_id' => $userId]);
            Auth::setFlash('success', 'تم إضافة المستخدم بنجاح');
            header('Location: ' . url('/users/' . $userId));
            exit;
            
        } catch (Exception $e) {
            error_log('User create error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ: ' . $e->getMessage()]);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/users/create'));
            exit;
        }
    }
    
    /**
     * عرض تفاصيل مستخدم
     */
    public function show(int $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $userData = $this->userModel->find($id);
        
        if (!$userData) {
            Auth::setFlash('error', 'المستخدم غير موجود');
            header('Location: ' . url('/users'));
            exit;
        }
        
        $permissions = $this->userModel->getRolePermissions($userData['role']);
        $recentActivity = $this->userModel->getRecentActivity($id, 20);
        $roles = $this->userModel->getRoles();
        
        $user = Auth::user();
        $pageTitle = 'user_details';
        
        require BASE_PATH . '/app/views/users/show.php';
    }
    
    /**
     * نموذج تعديل مستخدم
     */
    public function edit(int $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $userData = $this->userModel->find($id);
        
        if (!$userData) {
            Auth::setFlash('error', 'المستخدم غير موجود');
            header('Location: ' . url('/users'));
            exit;
        }
        
        $roles = $this->userModel->getRoles();
        $user = Auth::user();
        $pageTitle = 'edit_user';
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) $errors = [];
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) $old = [];
        
        require BASE_PATH . '/app/views/users/edit.php';
    }
    
    /**
     * تحديث مستخدم
     */
    public function update(int $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/users'));
            exit;
        }
        
        $userData = $this->userModel->find($id);
        
        if (!$userData) {
            Auth::setFlash('error', 'المستخدم غير موجود');
            header('Location: ' . url('/users'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/users/' . $id . '/edit'));
            exit;
        }
        
        $errors = $this->validate($_POST, $id, false);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/users/' . $id . '/edit'));
            exit;
        }
        
        try {
            $this->userModel->update($id, $_POST);
            
            Auth::logActivityStatic(Auth::id(), 'update', 'users', ['user_id' => $id]);
            Auth::setFlash('success', 'تم تحديث المستخدم بنجاح');
            header('Location: ' . url('/users/' . $id));
            exit;
            
        } catch (Exception $e) {
            error_log('User update error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ: ' . $e->getMessage()]);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/users/' . $id . '/edit'));
            exit;
        }
    }
    
    /**
     * حذف مستخدم
     */
    public function delete(int $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح');
            header('Location: ' . url('/users'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/users'));
            exit;
        }
        
        // منع حذف المستخدم نفسه
        if ($id === Auth::id()) {
            Auth::setFlash('error', 'لا يمكنك حذف حسابك الحالي');
            header('Location: ' . url('/users/' . $id));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/users'));
            exit;
        }
        
        $success = $this->userModel->delete($id);
        
        if ($success) {
            Auth::logActivityStatic(Auth::id(), 'delete', 'users', ['user_id' => $id]);
            Auth::setFlash('success', 'تم حذف المستخدم');
            header('Location: ' . url('/users'));
        } else {
            Auth::setFlash('error', 'لا يمكن حذف آخر مدير في النظام');
            header('Location: ' . url('/users/' . $id));
        }
        exit;
    }
    
    /**
     * تفعيل/تعطيل مستخدم
     */
    public function toggleActive(int $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح');
            header('Location: ' . url('/users'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/users'));
            exit;
        }
        
        // منع تعطيل المستخدم نفسه
        if ($id === Auth::id()) {
            Auth::setFlash('error', 'لا يمكنك تعطيل حسابك الحالي');
            header('Location: ' . url('/users/' . $id));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/users'));
            exit;
        }
        
        $success = $this->userModel->toggleActive($id);
        
        if ($success) {
            Auth::logActivityStatic(Auth::id(), 'toggle_active', 'users', ['user_id' => $id]);
            Auth::setFlash('success', 'تم تغيير حالة المستخدم');
        } else {
            Auth::setFlash('error', 'لا يمكن تعطيل آخر مدير نشط');
        }
        
        header('Location: ' . url('/users/' . $id));
        exit;
    }
    
    /**
     * إعادة تعيين كلمة المرور
     */
    public function resetPassword(int $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/users/' . $id));
            exit;
        }
        
        $userData = $this->userModel->find($id);
        
        if (!$userData) {
            Auth::setFlash('error', 'المستخدم غير موجود');
            header('Location: ' . url('/users'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/users/' . $id));
            exit;
        }
        
        $newPassword = trim($_POST['new_password'] ?? '');
        
        if (strlen($newPassword) < 6) {
            Auth::setFlash('error', 'كلمة المرور يجب أن تكون 6 أحرف على الأقل');
            header('Location: ' . url('/users/' . $id));
            exit;
        }
        
        $this->userModel->resetPassword($id, $newPassword);
        Auth::logActivityStatic(Auth::id(), 'reset_password', 'users', ['user_id' => $id]);
        
        Auth::setFlash('success', 'تم إعادة تعيين كلمة المرور بنجاح');
        header('Location: ' . url('/users/' . $id));
        exit;
    }
    
    /**
     * الملف الشخصي (للمستخدم نفسه)
     */
    public function profile(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $userId = Auth::id();
        $userData = $this->userModel->find($userId);
        
        if (!$userData) {
            Auth::setFlash('error', 'المستخدم غير موجود');
            header('Location: ' . url('/logout'));
            exit;
        }
        
        $permissions = $this->userModel->getRolePermissions($userData['role']);
        $recentActivity = $this->userModel->getRecentActivity($userId, 10);
        
        $user = Auth::user();
        $pageTitle = 'my_profile';
        
        require BASE_PATH . '/app/views/users/profile.php';
    }
    
    /**
     * تغيير كلمة المرور الشخصية
     */
    public function changeOwnPassword(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/profile'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/profile'));
            exit;
        }
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            Auth::setFlash('error', 'كلمة المرور الجديدة وتأكيدها غير متطابقين');
            header('Location: ' . url('/profile'));
            exit;
        }
        
        $result = $this->userModel->changeOwnPassword(Auth::id(), $currentPassword, $newPassword);
        
        if ($result['success']) {
            Auth::logActivityStatic(Auth::id(), 'change_password', 'users', []);
            Auth::setFlash('success', 'تم تغيير كلمة المرور بنجاح');
        } else {
            Auth::setFlash('error', $result['error']);
        }
        
        header('Location: ' . url('/profile'));
        exit;
    }
    
    /**
     * تحديث الملف الشخصي
     */
    public function updateProfile(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/profile'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/profile'));
            exit;
        }
        
        $userId = Auth::id();
        
        $this->db_execute(
            "UPDATE users SET full_name = :name, phone = :phone, email = :email WHERE id = :id",
            [
                'name'  => trim($_POST['full_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? '') ?: null,
                'email' => trim($_POST['email'] ?? '') ?: null,
                'id'    => $userId,
            ]
        );
        
        // تحديث الجلسة
        $_SESSION['full_name'] = trim($_POST['full_name'] ?? '');
        
        Auth::logActivityStatic($userId, 'update_profile', 'users', []);
        Auth::setFlash('success', 'تم تحديث الملف الشخصي بنجاح');
        header('Location: ' . url('/profile'));
        exit;
    }
    
    /**
     * التحقق من المدخلات
     */
    private function validate(array $data, ?int $excludeId = null, bool $requirePassword = true): array
    {
        $errors = [];
        
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'الاسم الكامل مطلوب';
        }
        
        if (empty($data['username'])) {
            $errors['username'] = 'اسم المستخدم مطلوب';
        } elseif (!preg_match('/^[a-zA-Z0-9_.]+$/', $data['username'])) {
            $errors['username'] = 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام فقط';
        } elseif ($this->userModel->usernameExists($data['username'], $excludeId)) {
            $errors['username'] = 'اسم المستخدم مستخدم بالفعل';
        }
        
        if (empty($data['role']) || !in_array($data['role'], ['admin', 'accountant', 'employee'])) {
            $errors['role'] = 'الدور غير صحيح';
        }
        
        if ($requirePassword || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'كلمة المرور مطلوبة';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
            } elseif (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
                $errors['password_confirm'] = 'كلمة المرور وتأكيدها غير متطابقين';
            }
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'البريد الإلكتروني غير صحيح';
        }
        
        return $errors;
    }
    
    /**
     * تنفيذ استعلام (مساعد)
     */
    private function db_execute(string $sql, array $params = []): int
    {
        $db = Database::getInstance();
        return $db->execute($sql, $params);
    }
}