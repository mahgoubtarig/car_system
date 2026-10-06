<?php
/**
 * متحكم الإعدادات
 */

class SettingController
{
    private Setting $settingModel;
    private Database $db;
    
    public function __construct()
    {
        $this->settingModel = new Setting();$this->db = Database::getInstance();
    }
    
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if (!Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح — الإعدادات للمدير فقط');
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $settings = $this->settingModel->getAll();$office = [
            'office_name' => $settings['office_name'] ?? '',
            'office_name_en' => $settings['office_name_en'] ?? '',
            'office_phone' => $settings['office_phone'] ?? '',
            'office_address' => $settings['office_address'] ?? '',
            'office_email' => $settings['office_email'] ?? '',
            'tax_number' => $settings['tax_number'] ?? '',
        ];
        
        $notifications = [
            'notification_days_before_payment' => $settings['notification_days_before_payment'] ?? 3,             'notification_days_before_doc_expiry' =>$settings['notification_days_before_doc_expiry'] ?? 30,
            'notification_days_before_rental_end' => $settings['notification_days_before_rental_end'] ?? 1,         ];$tax = [
            'vat_enabled' => $settings['vat_enabled'] ?? 0,
            'vat_rate' => $settings['vat_rate'] ?? 15,         ];$terms = [
            'contract_terms_ar' => $settings['contract_terms_ar'] ?? '',
            'contract_terms_en' => $settings['contract_terms_en'] ?? '',
        ];
        
        $stats =$this->getStats();
        
        $activityLog =$this->db->fetchAll(
            "SELECT a.*, u.full_name AS user_name
             FROM activity_log a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT 20"
        );
        
        $user = Auth::user();$pageTitle = 'settings';
        
        $activeTab =$_GET['tab'] ?? 'office';
        
        require BASE_PATH . '/app/views/settings/index.php';
    }
    
    public function saveOffice(): void
    {
        $this->saveFields(['office_name', 'office_name_en', 'office_phone', 'office_address', 'office_email', 'tax_number'], 'تم حفظ بيانات المكتب بنجاح', 'office');
    }
    
    public function saveNotifications(): void
    {
        $this->saveFields(['notification_days_before_payment', 'notification_days_before_doc_expiry', 'notification_days_before_rental_end'], 'تم حفظ إعدادات التنبيهات بنجاح', 'notifications');
    }
    
    public function saveTax(): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/settings'));
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/settings'));
            exit;
        }
        $csrfToken =$_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/settings'));
            exit;
        }

        try {
            $vatEnabled = isset($_POST['vat_enabled']) ? '1' : '0';
            $vatRate =$_POST['vat_rate'] ?? '15';

            $this->settingModel->set('vat_enabled',$vatEnabled);
            $this->settingModel->set('vat_rate',$vatRate);

            Auth::logActivityStatic(Auth::id(), 'update', 'settings', ['section' => 'tax']);
            Auth::setFlash('success', 'تم حفظ إعدادات الضريبة بنجاح');
        } catch (Exception $e) {
            error_log('Tax settings save error: ' . $e->getMessage());
            Auth::setFlash('error', 'خطأ قاعدة البيانات: ' . $e->getMessage());
        }

        header('Location: ' . url('/settings') . '?tab=tax');
        exit;
    }
    
    public function saveTerms(): void
    {
        $this->saveFields(['contract_terms_ar', 'contract_terms_en'], 'تم حفظ شروط العقد بنجاح', 'terms');
    }
    
    private function saveFields(array $fields, string $successMessage, string$tab): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/settings'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/settings'));
            exit;
        }
        
        $csrfToken =$_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة، يرجى المحاولة مرة أخرى');
            header('Location: ' . url('/settings') . '?tab=' . $tab);
            exit;
        }
        
        try {
            foreach ($fields as $field) {$value = $_POST[$field] ?? '';
                if ($tab !== 'terms') {
                    $value = trim($value);
                }
                $this->settingModel->set($field,$value);
            }
            
            Auth::logActivityStatic(Auth::id(), 'update', 'settings', ['section' => $tab]);
            Auth::setFlash('success', $successMessage);
        } catch (Exception $e) {
            error_log('Settings save error: ' . $e->getMessage());
            // ✅ التعديل هنا: طباعة الخطأ الفعلي بدلاً من "حدث خطأ" لمعرفة المشكلة بدقة
            Auth::setFlash('error', 'مشكلة فنية: ' . $e->getMessage());
        }
        
        header('Location: ' . url('/settings') . '?tab=' . $tab);
        exit;
    }

    public function backup(): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح');
            header('Location: ' . url('/settings'));
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/settings'));
            exit;
        }
        
        $csrfToken =$_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/settings'));
            exit;
        }
        
        try {
            $config = require BASE_PATH . '/app/config/database.php';$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = STORAGE_PATH . '/backups/' .$filename;
            
            if (!is_dir(STORAGE_PATH . '/backups')) {
                @mkdir(STORAGE_PATH . '/backups', 0755, true);
            }
            
            $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            
            if (file_exists($mysqldump)) {$command = sprintf(
                    '"%s" --user=%s --password=%s --host=%s %s > "%s" 2>&1',
                    $mysqldump,$config['username'],
                    $config['password'],$config['host'],
                    $config['database'],$filepath
                );
                exec($command, $output,$returnCode);
                
                if ($returnCode === 0 && file_exists($filepath)) {
                    Auth::logActivityStatic(Auth::id(), 'backup', 'settings', ['filename' => $filename]);
                    Auth::setFlash('success', 'تم إنشاء النسخة الاحتياطية: ' . $filename);
                } else {
                    throw new Exception('فشل إنشاء النسخة الاحتياطية');
                }
            } else {
                $this->backupViaPhp($filepath);
                Auth::logActivityStatic(Auth::id(), 'backup', 'settings', ['filename' => $filename]);
                Auth::setFlash('success', 'تم إنشاء النسخة الاحتياطية: ' . $filename);
            }
            
        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            Auth::setFlash('error', 'حدث خطأ أثناء النسخ الاحتياطي: ' . $e->getMessage());
        }
        
        header('Location: ' . url('/settings') . '?tab=backup');
        exit;
    }
    
    private function backupViaPhp(string $filepath): void
    {
        $handle = fopen($filepath, 'w');
        if (!$handle) throw new Exception('لا يمكن إنشاء ملف النسخ الاحتياطي');
        
        fwrite($handle, "-- Car Rental System Backup\n");
        fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Database: car_rental_db\n\n");
        fwrite($handle, "SET NAMES utf8mb4;\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");
        
        $tables =$this->db->fetchAll("SHOW TABLES");
        
        foreach ($tables as$tableRow) {
            $tableName = reset($tableRow);
            $create =$this->db->fetchOne("SHOW CREATE TABLE `{$tableName}`");
            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            fwrite($handle,$create['Create Table'] . ";\n\n");
            
            $rows =$this->db->fetchAll("SELECT * FROM `{$tableName}`");
            if (!empty($rows)) {
                foreach ($rows as$row) {
                    $values = array_map(function($v) {
                        if ($v === null) return 'NULL';
                        return "'" . addslashes((string)$v) . "'";
                    }, array_values($row));
                    
                    $columns = array_map(fn($c) => "`{$c}`", array_keys($row));
                    fwrite($handle, "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n");
                }
                fwrite($handle, "\n");
            }
        }
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($handle);
    }
    
    public function clearLog(): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح');
            header('Location: ' . url('/settings'));
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/settings'));
            exit;
        }
        
        $csrfToken =$_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/settings') . '?tab=log');
            exit;
        }
        
        try {
            $days = (int) ($_POST['days'] ?? 30);
            $deleted =$this->db->execute(
                "DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)",
                ['days' => $days]
            );
            
            Auth::logActivityStatic(Auth::id(), 'clear_log', 'settings', ['deleted' => $deleted]);
            Auth::setFlash('success', "تم حذف {$deleted} سجل قديم");
        } catch (Exception $e) {
            error_log('Clear log error: ' . $e->getMessage());
            Auth::setFlash('error', 'مشكلة فنية: ' . $e->getMessage());
        }
        
        header('Location: ' . url('/settings') . '?tab=log');
        exit;
    }
    
    private function getStats(): array
    {
        $dbSize = (float) ($this->db->fetchOne(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE()"
        )['size'] ?? 0);
        
        return [
            'users'         => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0),
            'cars'          => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM cars")['c'] ?? 0),
            'customers'     => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM customers")['c'] ?? 0),
            'rentals'       => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM rentals")['c'] ?? 0),
            'payments'      => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM payments")['c'] ?? 0),
            'expenses'      => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM car_expenses")['c'] ?? 0),
            'notifications' => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM notifications")['c'] ?? 0),
            'activity'      => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM activity_log")['c'] ?? 0),
            'db_size'       => $dbSize,
        ];
    }
}