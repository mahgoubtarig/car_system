<?php
/**
 * متحكم العقود - مع دعم PDF والتوقيع الإلكتروني
 */

class RentalController
{
    private Rental $rentalModel;
    private Customer $customerModel;
    private Car $carModel;
    
    public function __construct()
    {
        $this->rentalModel = new Rental();
        $this->customerModel = new Customer();
        $this->carModel = new Car();
    }
    
    // ============================================================
    // قائمة العقود
    // ============================================================
    
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $filters = [
            'search'    => trim($_GET['search'] ?? ''),
            'status'    => trim($_GET['status'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];
        
        $page = max(1, (int) ($_GET['page'] ?? 1));
        
        $result = $this->rentalModel->all($filters, $page, 15);
        $stats  = $this->rentalModel->getStats();
        $paymentStats = $this->rentalModel->getPaymentStats();
        
        $rentals     = $result['items'];
        $total       = $result['total'];
        $totalPages  = $result['total_pages'];
        $currentPage = $page;
        $user        = Auth::user();
        $pageTitle   = 'rentals';
        
        require BASE_PATH . '/app/views/rentals/index.php';
    }
    
    // ============================================================
    // ✅ سجل العقود (الأرشيف) للعقود المنتهية والملغاة
    // ============================================================
    
    public function archive(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $db = Database::getInstance();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        
        $where = ["r.status IN ('completed', 'cancelled')"];
        $params = [];
        
        if (!empty($_GET['search'])) {
            $where[] = "(r.contract_no LIKE :search OR cu.full_name LIKE :search OR c.plate_number LIKE :search)";
            $params['search'] = '%' . trim($_GET['search']) . '%';
        }
        
        $whereSql = implode(' AND ', $where);
        
        $total = (int) ($db->fetchOne(
            "SELECT COUNT(*) as c 
             FROM rentals r 
             LEFT JOIN customers cu ON cu.id = r.customer_id 
             LEFT JOIN cars c ON c.id = r.car_id 
             WHERE {$whereSql}",
            $params
        )['c'] ?? 0);
        
        $rentals = $db->fetchAll(
            "SELECT r.*, cu.full_name as customer_name, c.plate_number, c.brand, c.model 
             FROM rentals r 
             LEFT JOIN customers cu ON cu.id = r.customer_id 
             LEFT JOIN cars c ON c.id = r.car_id 
             WHERE {$whereSql} 
             ORDER BY r.updated_at DESC 
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        $totalPages = (int) ceil($total / $perPage);
        $currentPage = $page;
        $pageTitle = 'rentals_archive';
        $user = Auth::user();
        
        require BASE_PATH . '/app/views/rentals/archive.php';
    }
    
    // ============================================================
    // نموذج إنشاء عقد
    // ============================================================
    
    public function create(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $customers = $this->customerModel->all([], 1, 1000)['items'];
        $cars = $this->carModel->all(['status' => 'available'], 1, 1000)['items'];
        
        $user = Auth::user();
        $pageTitle = 'create_rental';
        
        $errors = Auth::getFlash('errors');
        if (!is_array($errors)) $errors = [];
        
        $old = Auth::getFlash('old');
        if (!is_array($old)) $old = [];
        
        $nextContractNo = $this->rentalModel->generateContractNo();
        
        require BASE_PATH . '/app/views/rentals/create.php';
    }
    
    // ============================================================
    // ✅ حفظ عقد جديد (مع قراءة العداد + صورة التسليم)
    // ============================================================
    
    public function store(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/rentals'));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('errors', ['general' => 'انتهت صلاحية الجلسة']);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/rentals/create'));
            exit;
        }
        
        $errors = $this->validate($_POST);
        
        if (!empty($errors)) {
            Auth::setFlash('errors', $errors);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/rentals/create'));
            exit;
        }
        
        $data = $this->prepareData($_POST);
        $data['created_by'] = Auth::id();
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            $rentalId = $this->rentalModel->create($data);
            
            $this->carModel->changeStatus((int) $data['car_id'], 'rented');
            
            $odometerReading = (int) ($_POST['odometer_reading'] ?? 0);
            if ($odometerReading > 0) {
                $db->execute(
                    "UPDATE cars SET odometer = :odometer WHERE id = :id",
                    ['odometer' => $odometerReading, 'id' => (int) $data['car_id']]
                );
            }
            
            if (!empty($_FILES['car_image_at_delivery']['name'])) {
                $this->saveDeliveryImage($rentalId, $_POST, $_FILES);
            }
            
            if (!empty($_POST['add_first_payment']) && !empty($_POST['first_payment_amount'])) {
                $paymentAmount = (float) $_POST['first_payment_amount'];
                if ($paymentAmount > 0) {
                    $this->createFirstPayment($rentalId, $paymentAmount, $_POST);
                }
            }
            
            $this->createPaymentSchedules($rentalId, $data['total_amount'], $_POST);
            
            Auth::logActivityStatic(Auth::id(), 'create', 'rentals', [
                'rental_id' => $rentalId,
                'odometer'  => $odometerReading,
            ]);
            
            $db->commit();
            
            Auth::setFlash('success', 'تم إنشاء العقد بنجاح');
            header('Location: ' . url('/rentals/' . $rentalId));
            exit;
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            
            error_log('Rental create error: ' . $e->getMessage());
            Auth::setFlash('errors', ['general' => 'حدث خطأ: ' . $e->getMessage()]);
            Auth::setFlash('old', $_POST);
            header('Location: ' . url('/rentals/create'));
            exit;
        }
    }
    
    // ============================================================
    // عرض تفاصيل عقد
    // ============================================================
    
    public function show(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $rental = $this->rentalModel->find($id);
        
        if (!$rental) {
            Auth::setFlash('error', 'العقد غير موجود');
            header('Location: ' . url('/rentals'));
            exit;
        }
        
        $payments = $this->rentalModel->getPayments($id);
        $schedules = $this->rentalModel->getPaymentSchedules($id);
        $inspections = $this->rentalModel->getInspections($id);
        
        $paidAmount = $this->rentalModel->getPaidAmount($id);
        $remaining = (float) $rental['total_amount'] - $paidAmount;
        $nextPayment = $this->rentalModel->getNextPayment($id);
        
        $user = Auth::user();
        $pageTitle = 'rental_details';
        
        require BASE_PATH . '/app/views/rentals/show.php';
    }
    
    // ============================================================
    // ✅ إغلاق عقد (بشروط صارمة: سداد + تأمين + عداد)
    // ============================================================
    
    public function close(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }
        
        $rental = $this->rentalModel->find($id);
        if (!$rental) {
            Auth::setFlash('error', 'العقد غير موجود');
            header('Location: ' . url('/rentals'));
            exit;
        }

        $db = Database::getInstance();
        
        // 🚨 الشروط الصارمة قبل السماح بإغلاق العقد 🚨

        $totalPaid = (float) $db->fetchOne(
            "SELECT SUM(amount) as total FROM payments WHERE rental_id = :id AND payment_type IN ('rent', 'penalty')", 
            ['id' => $id]
        )['total'];
        $totalAmount = (float) $rental['total_amount'];
        $remaining = $totalAmount - $totalPaid;

        if ($remaining > 0.01) {
            Auth::setFlash('error', '❌ لا يمكن إغلاق العقد! يجب سداد المبلغ المتبقي أولاً: ' . number_format($remaining, 2) . ' ريال.');
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }
        
        $odometerReading = (int) ($_POST['odometer_reading'] ?? 0);
        $car = $this->carModel->find((int) $rental['car_id']);
        
        if ($odometerReading <= 0 || $odometerReading < (int) $car['odometer']) {
            Auth::setFlash('error', '❌ لا يمكن إغلاق العقد! يجب إدخال قراءة عداد صحيحة (تساوي أو أكبر من القراءة السابقة للسيارة: ' . $car['odometer'] . ' كم).');
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }

        $refundDeposit = !empty($_POST['refund_deposit']);
        if ((float) $rental['deposit_amount'] > 0 && !$refundDeposit) {
            Auth::setFlash('error', '❌ لا يمكن إغلاق العقد! يجب الموافقة على إنشاء سند إرجاع مبلغ التأمين للعميل.');
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }

        $applyOverduePenalty = !empty($_POST['apply_overdue_penalty']);
        $overduePenaltyAmount = (float) ($_POST['overdue_penalty_amount'] ?? 0);
        
        try {
            $db->beginTransaction();
            
            if ($applyOverduePenalty && $overduePenaltyAmount > 0) {
                $newTotalAmount = $totalAmount + $overduePenaltyAmount;
                $db->execute("UPDATE rentals SET total_amount = :newTotal WHERE id = :id", [
                    'newTotal' => $newTotalAmount,
                    'id' => $id
                ]);

                $penaltyReceiptNo = $this->generateReceiptNo();
                $db->execute(
                    "INSERT INTO payments (receipt_no, rental_id, payment_type, method, amount, paid_at, received_by, notes)
                     VALUES (:receipt_no, :rental_id, 'penalty', 'cash', :amount, NOW(), :received_by, :notes)",
                    [
                        'receipt_no'  => $penaltyReceiptNo,
                        'rental_id'   => $id,
                        'amount'      => $overduePenaltyAmount,
                        'received_by' => Auth::id(),
                        'notes'       => 'تكلفة تأخير التسليم (أيام إضافية) وقت إغلاق العقد',
                    ]
                );
            }

            if ($odometerReading > 0) {
                $db->execute(
                    "UPDATE cars SET odometer = :odometer WHERE id = :id",
                    ['odometer' => $odometerReading, 'id' => (int) $rental['car_id']]
                );
            }
            
            $this->rentalModel->close($id);
            $this->carModel->changeStatus((int) $rental['car_id'], 'available');
            $this->closeRentalNotifications($id);
            
            if ($refundDeposit && (float) $rental['deposit_amount'] > 0) {
                $refundReceiptNo = $this->generateReceiptNo();
                
                $db->execute(
                    "INSERT INTO payments (receipt_no, rental_id, payment_type, method, amount, paid_at, received_by, notes)
                     VALUES (:receipt_no, :rental_id, 'deposit_refund', 'cash', :amount, NOW(), :received_by, :notes)",
                    [
                        'receipt_no'  => $refundReceiptNo,
                        'rental_id'   => $id,
                        'amount'      => (float) $rental['deposit_amount'],
                        'received_by' => Auth::id(),
                        'notes'       => 'رد مبلغ التأمين النقدي وقت الإغلاق',
                    ]
                );
            }
            
            Auth::logActivityStatic(Auth::id(), 'close', 'rentals', [
                'rental_id' => $id,
                'odometer'  => $odometerReading,
                'refund'    => $refundDeposit,
                'overdue_penalty' => $applyOverduePenalty ? $overduePenaltyAmount : 0
            ]);
            
            $db->commit();
            
            Auth::setFlash('success', '✅ تم إغلاق العقد بنجاح ومعالجة المدفوعات وتحديث قراءة العداد. السيارة متاحة الآن.');
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log('Rental close error: ' . $e->getMessage());
            Auth::setFlash('error', 'حدث خطأ: ' . $e->getMessage());
        }
        
        header('Location: ' . url('/rentals/' . $id));
        exit;
    }
    
    // ============================================================
    // ✅ إلغاء عقد (مع استرجاع التأمين)
    // ============================================================
    
    public function cancel(int $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح');
            header('Location: ' . url('/rentals'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }
        
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }
        
        $rental = $this->rentalModel->find($id);
        if (!$rental) {
            Auth::setFlash('error', 'العقد غير موجود');
            header('Location: ' . url('/rentals'));
            exit;
        }
        
        $reason = trim($_POST['cancellation_reason'] ?? '');
        $refundDeposit = !empty($_POST['refund_deposit']);
        
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
            
            $this->rentalModel->cancel($id, $reason);
            $this->carModel->changeStatus((int) $rental['car_id'], 'available');
            $this->closeRentalNotifications($id);
            
            if ($refundDeposit && (float) $rental['deposit_amount'] > 0) {
                $receiptNo = $this->generateReceiptNo();
                
                $db->execute(
                    "INSERT INTO payments 
                        (receipt_no, rental_id, payment_type, method, amount, paid_at, received_by, notes)
                     VALUES 
                        (:receipt_no, :rental_id, 'deposit_refund', 'cash', :amount, NOW(), :received_by, :notes)",
                    [
                        'receipt_no'  => $receiptNo,
                        'rental_id'   => $id,
                        'amount'      => (float) $rental['deposit_amount'],
                        'received_by' => Auth::id(),
                        'notes'       => 'رد تأمين عند إلغاء العقد',
                    ]
                );
            }
            
            Auth::logActivityStatic(Auth::id(), 'cancel', 'rentals', [
                'rental_id' => $id,
                'reason'    => $reason,
                'refund'    => $refundDeposit,
            ]);
            
            $db->commit();
            
            Auth::setFlash('success', 'تم إلغاء العقد وإرجاع السيارة لـ "متاحة"');
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log('Rental cancel error: ' . $e->getMessage());
            Auth::setFlash('error', 'حدث خطأ: ' . $e->getMessage());
        }
        
        header('Location: ' . url('/rentals/' . $id));
        exit;
    }
    
    // ============================================================
    // ✅ طباعة العقد
    // ============================================================
    
    public function print(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $rental = $this->rentalModel->find($id);
        
        if (!$rental) {
            Auth::setFlash('error', 'العقد غير موجود');
            header('Location: ' . url('/rentals'));
            exit;
        }
        
        $car = $this->carModel->find((int) $rental['car_id']);
        $customer = $this->customerModel->find((int) $rental['customer_id']);
        $payments = $this->rentalModel->getPayments($id);
        $schedules = $this->rentalModel->getPaymentSchedules($id);
        $inspections = $this->rentalModel->getInspections($id);
        
        $paidAmount = $this->rentalModel->getPaidAmount($id);
        $remaining = (float) $rental['total_amount'] - $paidAmount;
        
        require_once BASE_PATH . '/app/models/Setting.php';
        $settingModel = new Setting();
        $office = $settingModel->getAll();
        $terms = $settingModel->getContractTerms();
        
        $printDate = date('Y-m-d H:i:s');
        
        require BASE_PATH . '/app/views/rentals/print.php';
    }
    
    // ============================================================
    // 📄 توليد PDF العقد (Server-side باستخدام Dompdf)
    // ============================================================
    
    public function downloadPdf(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $rental = $this->rentalModel->find($id);
        
        if (!$rental) {
            Auth::setFlash('error', 'العقد غير موجود');
            header('Location: ' . url('/rentals'));
            exit;
        }
        
        // جلب البيانات المطلوبة
        $car = $this->carModel->find((int) $rental['car_id']);
        $customer = $this->customerModel->find((int) $rental['customer_id']);
        $payments = $this->rentalModel->getPayments($id);
        $paidAmount = $this->rentalModel->getPaidAmount($id);
        $remaining = (float) $rental['total_amount'] - $paidAmount;
        
        require_once BASE_PATH . '/app/models/Setting.php';
        $settingModel = new Setting();
        $office = $settingModel->getAll();
        
        // التحقق من وجود Composer autoload
        $autoloadPath = BASE_PATH . '/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            http_response_code(500);
            die('❌ مكتبة Dompdf غير مثبتة. قم بتشغيل: composer require dompdf/dompdf');
        }
        require_once $autoloadPath;
        
        // تحميل قالب الطباعة كـ HTML
        ob_start();
        $pdfMode = true; // علامة لتفعيل وضع PDF في القالب
        require BASE_PATH . '/app/views/rentals/print.php';
        $html = ob_get_clean();
        
        // إعداد Dompdf
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', BASE_PATH);
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // اسم الملف
        $filename = 'contract-' . preg_replace('/[^A-Za-z0-9\-]/', '', $rental['contract_no']) . '.pdf';
        
        // إرسال PDF للمتصفح
        $dompdf->stream($filename, [
            'Attachment' => false, // false = عرض في المتصفح، true = تحميل مباشر
        ]);
        
        // حفظ مسار PDF في قاعدة البيانات
        try {
            $db = Database::getInstance();
            $db->execute(
                "UPDATE rentals SET pdf_generated_at = NOW() WHERE id = :id",
                ['id' => $id]
            );
        } catch (Throwable $e) {
            error_log('PDF log error: ' . $e->getMessage());
        }
        
        exit;
    }
    
    // ============================================================
    // 💾 حفظ PDF في السيرفر (AJAX من html2pdf.js)
    // ============================================================
    
    public function savePdfToServer(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!Auth::check()) {
                throw new Exception('غير مصرح');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('طريقة غير مسموحة');
            }
            
            // التحقق من CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!Csrf::validateToken($csrfToken)) {
                throw new Exception('انتهت صلاحية الجلسة');
            }
            
            $rental = $this->rentalModel->find($id);
            if (!$rental) {
                throw new Exception('العقد غير موجود');
            }
            
            if (empty($_FILES['pdf']['tmp_name'])) {
                throw new Exception('لم يتم رفع ملف PDF');
            }
            
            // التحقق من نوع الملف
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['pdf']['tmp_name']);
            finfo_close($finfo);
            
            if ($mimeType !== 'application/pdf') {
                throw new Exception('الملف ليس PDF صحيح');
            }
            
            // مجلد الحفظ
            $dir = STORAGE_PATH . '/uploads/contracts';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // اسم الملف
            $filename = 'contract-' . $id . '-' . date('Ymd-His') . '.pdf';
            $filepath = $dir . '/' . $filename;
            
            if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $filepath)) {
                throw new Exception('فشل حفظ الملف');
            }
            
            // حفظ المسار في قاعدة البيانات
            $db = Database::getInstance();
            $db->execute(
                "UPDATE rentals SET pdf_path = :path, pdf_generated_at = NOW() WHERE id = :id",
                ['path' => $filename, 'id' => $id]
            );
            
            echo json_encode([
                'success'  => true,
                'filename' => $filename,
                'url'      => url('/storage/uploads/contracts/' . $filename),
                'message'  => 'تم الحفظ بنجاح',
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Throwable $e) {
            error_log('Save PDF error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    // ============================================================
    // ✍️ حفظ التوقيع الإلكتروني
    // ============================================================
    
    public function saveSignature(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }
        
        // التحقق من CSRF
        $csrfToken = $_POST['_csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة');
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }
        
        $rental = $this->rentalModel->find($id);
        if (!$rental) {
            Auth::setFlash('error', 'العقد غير موجود');
            header('Location: ' . url('/rentals'));
            exit;
        }
        
        $signatureData = $_POST['signature_data'] ?? '';
        $signatureType = $_POST['signature_type'] ?? 'customer'; // customer أو office
        
        // التحقق من الصيغة
        if (!preg_match('/^data:image\/png;base64,/', $signatureData)) {
            Auth::setFlash('error', 'صيغة التوقيع غير صحيحة');
            header('Location: ' . url('/rentals/' . $id));
            exit;
        }
        
        try {
            // استخراج البيانات
            $imageData = base64_decode(preg_replace('/^data:image\/png;base64,/', '', $signatureData));
            
            if (strlen($imageData) < 100) {
                throw new Exception('التوقيع فارغ');
            }
            
            // مجلد الحفظ
            $dir = STORAGE_PATH . '/uploads/signatures';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // اسم الملف
            $prefix = $signatureType === 'office' ? 'sig-office' : 'sig-customer';
            $filename = $prefix . '-' . $id . '-' . time() . '.png';
            $filepath = $dir . '/' . $filename;
            
            if (file_put_contents($filepath, $imageData) === false) {
                throw new Exception('فشل حفظ التوقيع');
            }
            
            // حفظ المسار في قاعدة البيانات
            $db = Database::getInstance();
            $column = $signatureType === 'office' ? 'office_signature' : 'customer_signature';
            
            $db->execute(
                "UPDATE rentals SET {$column} = :file, signed_at = NOW() WHERE id = :id",
                ['file' => $filename, 'id' => $id]
            );
            
            // تسجيل النشاط
            Auth::logActivityStatic(Auth::id(), 'sign', 'rentals', [
                'rental_id' => $id,
                'type'      => $signatureType,
            ]);
            
            Auth::setFlash('success', '✅ تم حفظ التوقيع بنجاح');
            
        } catch (Throwable $e) {
            error_log('Save signature error: ' . $e->getMessage());
            Auth::setFlash('error', 'حدث خطأ: ' . $e->getMessage());
        }
        
        header('Location: ' . url('/rentals/' . $id));
        exit;
    }
    
    // ============================================================
    // جلب السيارات المتاحة (AJAX)
    // ============================================================
    
    public function availableCars(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'غير مصرح']);
            exit;
        }
        
        $pickup = $_GET['pickup'] ?? '';
        $return = $_GET['return'] ?? '';
        
        if (empty($pickup) || empty($return)) {
            echo json_encode(['success' => false, 'message' => 'تواريخ غير صحيحة']);
            exit;
        }
        
        $cars = $this->rentalModel->getAvailableCars($pickup, $return);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'cars' => $cars], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ============================================================
    // ✅ الدوال الخاصة
    // ============================================================
    
    private function saveDeliveryImage(int $rentalId, array $post, array $files): void
    {
        $uploadDir = STORAGE_PATH . '/uploads/inspections';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        
        $file = $files['car_image_at_delivery'];
        
        if ($file['size'] > 5 * 1024 * 1024) {
            error_log('Delivery image too large');
            return;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            error_log('Invalid delivery image type: ' . $mimeType);
            return;
        }
        
        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        
        $filename = 'delivery_' . $rentalId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $filePath = $uploadDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            error_log('Failed to move delivery image');
            return;
        }
        
        $db = Database::getInstance();
        
        $inspectionId = $db->insert(
            "INSERT INTO rental_inspections 
                (rental_id, type, odometer, fuel_level, general_condition, cleanliness, inspected_by, inspected_at)
             VALUES 
                (:rental_id, 'handover', :odometer, :fuel, 'good', 'clean', :user_id, NOW())",
            [
                'rental_id' => $rentalId,
                'odometer'  => (int) ($post['odometer_reading'] ?? 0),
                'fuel'      => $post['fuel_level'] ?? 'full',
                'user_id'   => Auth::id(),
            ]
        );
        
        $db->execute(
            "INSERT INTO inspection_photos (inspection_id, angle, image_path) 
             VALUES (:inspection_id, 'front', :image_path)",
            [
                'inspection_id' => $inspectionId,
                'image_path'    => $filename,
            ]
        );
    }
    
    private function createFirstPayment(int $rentalId, float $amount, array $data): void
    {
        $db = Database::getInstance();
        $receiptNo = $this->generateReceiptNo();
        
        $db->execute(
            "INSERT INTO payments 
                (receipt_no, rental_id, schedule_id, payment_type, method, 
                 amount, reference_no, paid_at, received_by, notes)
             VALUES 
                (:receipt_no, :rental_id, NULL, :payment_type, :method,
                 :amount, :reference_no, NOW(), :received_by, :notes)",
            [
                'receipt_no'    => $receiptNo,
                'rental_id'     => $rentalId,
                'payment_type'  => $data['first_payment_type'] ?? 'rent',
                'method'        => $data['first_payment_method'] ?? 'cash',
                'amount'        => $amount,
                'reference_no'  => !empty($data['first_payment_reference']) ? trim($data['first_payment_reference']) : null,
                'received_by'   => Auth::id(),
                'notes'         => !empty($data['first_payment_notes']) ? trim($data['first_payment_notes']) : null,
            ]
        );
    }
    
    private function createPaymentSchedules(int $rentalId, float $totalAmount, array $data): void
    {
        $db = Database::getInstance();
        
        $paidAmount = !empty($data['add_first_payment']) && !empty($data['first_payment_amount'])
            ? (float) $data['first_payment_amount']
            : 0;
        
        $remaining = $totalAmount - $paidAmount;
        
        if ($remaining <= 0) return;
        
        $nextPaymentDate = !empty($data['next_payment_date']) 
            ? $data['next_payment_date'] 
            : date('Y-m-d', strtotime('+30 days'));
        
        $nextPaymentAmount = !empty($data['next_payment_amount']) 
            ? (float) $data['next_payment_amount'] 
            : $remaining;
        
        if ($nextPaymentAmount > $remaining) {
            $nextPaymentAmount = $remaining;
        }
        
        $reminderDays = (int) ($data['payment_reminder_days'] ?? 3);
        
        $installmentNo = 1;
        $scheduleId1 = $db->insert(
            "INSERT INTO payment_schedules 
                (rental_id, installment_no, due_date, amount, paid_amount, status)
             VALUES 
                (:rental_id, :installment_no, :due_date, :amount, 0, 'pending')",
            [
                'rental_id'      => $rentalId,
                'installment_no' => $installmentNo,
                'due_date'       => $nextPaymentDate,
                'amount'         => $nextPaymentAmount,
            ]
        );
        
        $this->createPaymentNotification($scheduleId1, $nextPaymentDate, $reminderDays);
        
        $afterSecond = $remaining - $nextPaymentAmount;
        if ($afterSecond > 0) {
            $installmentNo++;
            $thirdDate = date('Y-m-d', strtotime($nextPaymentDate . ' +30 days'));
            
            $scheduleId2 = $db->insert(
                "INSERT INTO payment_schedules 
                    (rental_id, installment_no, due_date, amount, paid_amount, status)
                 VALUES 
                    (:rental_id, :installment_no, :due_date, :amount, 0, 'pending')",
                [
                    'rental_id'      => $rentalId,
                    'installment_no' => $installmentNo,
                    'due_date'       => $thirdDate,
                    'amount'         => $afterSecond,
                ]
            );
            
            $this->createPaymentNotification($scheduleId2, $thirdDate, $reminderDays);
        }
    }
    
    private function createPaymentNotification(int $scheduleId, string $dueDate, int $daysBefore): void
    {
        try {
            $db = Database::getInstance();
            $reminderDate = date('Y-m-d', strtotime($dueDate . ' -' . $daysBefore . ' days'));
            
            $db->execute(
                "INSERT IGNORE INTO notifications 
                    (type, ref_table, ref_id, due_date, is_read)
                 VALUES 
                    ('payment_due', 'payment_schedules', :ref_id, :due_date, 0)",
                [
                    'ref_id'   => $scheduleId,
                    'due_date' => $reminderDate,
                ]
            );
        } catch (Exception $e) {
            error_log('Create payment notification error: ' . $e->getMessage());
        }
    }
    
    private function closeRentalNotifications(int $rentalId): void
    {
        try {
            $db = Database::getInstance();
            
            $db->execute(
                "UPDATE notifications n
                 JOIN payment_schedules ps ON ps.id = n.ref_id
                 SET n.is_read = 1, n.processed_at = NOW()
                 WHERE n.ref_table = 'payment_schedules'
                 AND ps.rental_id = :rental_id
                 AND n.is_read = 0",
                ['rental_id' => $rentalId]
            );
            
            $db->execute(
                "UPDATE notifications 
                 SET is_read = 1, processed_at = NOW()
                 WHERE ref_table = 'rentals'
                 AND ref_id = :rental_id
                 AND is_read = 0",
                ['rental_id' => $rentalId]
            );
        } catch (Exception $e) {
            error_log('Close rental notifications error: ' . $e->getMessage());
        }
    }
    
    private function generateReceiptNo(): string
    {
        $db = Database::getInstance();
        $year = date('Y');
        
        $last = $db->fetchOne(
            "SELECT receipt_no FROM payments 
             WHERE receipt_no LIKE :pattern
             ORDER BY id DESC LIMIT 1",
            ['pattern' => "RCP-{$year}-%"]
        );
        
        if ($last) {
            $parts = explode('-', $last['receipt_no']);
            $number = (int) end($parts) + 1;
        } else {
            $number = 1;
        }
        
        return sprintf('RCP-%s-%04d', $year, $number);
    }
    
    private function validate(array $data): array
    {
        $errors = [];
        
        if (empty($data['customer_id'])) $errors['customer_id'] = 'المستأجر مطلوب';
        if (empty($data['car_id'])) $errors['car_id'] = 'السيارة مطلوبة';
        if (empty($data['pickup_datetime'])) $errors['pickup_datetime'] = 'موعد الاستلام مطلوب';
        if (empty($data['return_due_datetime'])) $errors['return_due_datetime'] = 'موعد التسليم مطلوب';
        
        if (!empty($data['car_id']) && !empty($data['pickup_datetime']) && !empty($data['return_due_datetime'])) {
            $hasOverlap = $this->rentalModel->hasOverlap(
                (int) $data['car_id'],
                $data['pickup_datetime'],
                $data['return_due_datetime']
            );
            
            if ($hasOverlap) {
                $errors['car_id'] = 'السيارة محجوزة في هذه الفترة';
            }
        }
        
        return $errors;
    }
    
    private function prepareData(array $data): array
    {
        $pickup = new DateTime($data['pickup_datetime']);
        $return = new DateTime($data['return_due_datetime']);
        $days = max(1, $pickup->diff($return)->days);
        
        $rateAmount = (float) ($data['rate_amount'] ?? 0);
        $subtotal = $rateAmount * $days;
        $discount = (float) ($data['discount'] ?? 0);
        $taxAmount = (float) ($data['tax_amount'] ?? 0);
        $extraCharges = (float) ($data['extra_charges'] ?? 0);
        $depositAmount = (float) ($data['deposit_amount'] ?? 0);
        
        $totalAmount = $subtotal - $discount + $taxAmount + $extraCharges + $depositAmount;
        
        return [
            'car_id'              => (int) $data['car_id'],
            'customer_id'         => (int) $data['customer_id'],
            'pickup_datetime'     => $data['pickup_datetime'],
            'pickup_location'     => trim($data['pickup_location'] ?? ''),
            'return_due_datetime' => $data['return_due_datetime'],
            'return_location'     => trim($data['return_location'] ?? ''),
            'rate_type'           => $data['rate_type'] ?? 'daily',
            'rate_amount'         => $rateAmount,
            'rental_days'         => $days,
            'subtotal'            => $subtotal,
            'discount'            => $discount,
            'tax_amount'          => $taxAmount,
            'extra_charges'       => $extraCharges,
            'total_amount'        => $totalAmount,
            'deposit_amount'      => $depositAmount,
            'km_limit_per_day'    => !empty($data['km_limit_per_day']) ? (int) $data['km_limit_per_day'] : null,
            'status'              => 'active',
            'notes'               => trim($data['notes'] ?? ''),
        ];
    }
}