<?php
/**
 * نموذج السيارة
 */

class Car
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * جلب كل السيارات مع الفلترة والبحث
     */
    public function all(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(c.plate_number LIKE :search_plate 
                        OR c.brand LIKE :search_brand 
                        OR c.model LIKE :search_model 
                        OR c.chassis_number LIKE :search_chassis)";
            
            $sv = '%' . $filters['search'] . '%';
            $params['search_plate']   = $sv;
            $params['search_brand']   = $sv;
            $params['search_model']   = $sv;
            $params['search_chassis'] = $sv;
        }
        
        if (!empty($filters['status'])) {
            $where[] = "c.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['car_type_id'])) {
            $where[] = "c.car_type_id = :car_type_id";
            $params['car_type_id'] = $filters['car_type_id'];
        }
        
        if (!empty($filters['brand'])) {
            $where[] = "c.brand = :brand";
            $params['brand'] = $filters['brand'];
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $total = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM cars c {$whereSql}",
            $params
        )['c'] ?? 0);
        
        $offset = ($page - 1) * $perPage;
        
        $cars = $this->db->fetchAll(
            "SELECT c.*, ct.name_ar AS type_name_ar, ct.name_en AS type_name_en,
                    (SELECT COUNT(*) FROM car_images ci WHERE ci.car_id = c.id) AS images_count
             FROM cars c
             LEFT JOIN car_types ct ON ct.id = c.car_type_id
             {$whereSql}
             ORDER BY c.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        return [
            'items'      => $cars,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages'=> (int) ceil($total / $perPage),
        ];
    }
    
    /**
     * جلب سيارة بالمعرف
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT c.*, ct.name_ar AS type_name_ar, ct.name_en AS type_name_en
             FROM cars c
             LEFT JOIN car_types ct ON ct.id = c.car_type_id
             WHERE c.id = :id
             LIMIT 1",
            ['id' => $id]
        );
    }
    
    /**
     * إضافة سيارة
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO cars 
                (plate_number, car_type_id, brand, model, year, color, chassis_number,
                 fuel_type, transmission, seats, odometer,
                 daily_price, weekly_price, monthly_price,
                 registration_expiry, insurance_expiry, inspection_expiry,
                 purchase_price, status, notes)
                VALUES 
                (:plate_number, :car_type_id, :brand, :model, :year, :color, :chassis_number,
                 :fuel_type, :transmission, :seats, :odometer,
                 :daily_price, :weekly_price, :monthly_price,
                 :registration_expiry, :insurance_expiry, :inspection_expiry,
                 :purchase_price, :status, :notes)";
        
        return $this->db->insert($sql, $data);
    }
    
    /**
     * تحديث سيارة
     */
    public function update(int $id, array $data): int
    {
        $data['id'] = $id;
        
        $sql = "UPDATE cars SET
                plate_number = :plate_number,
                car_type_id = :car_type_id,
                brand = :brand,
                model = :model,
                year = :year,
                color = :color,
                chassis_number = :chassis_number,
                fuel_type = :fuel_type,
                transmission = :transmission,
                seats = :seats,
                odometer = :odometer,
                daily_price = :daily_price,
                weekly_price = :weekly_price,
                monthly_price = :monthly_price,
                registration_expiry = :registration_expiry,
                insurance_expiry = :insurance_expiry,
                inspection_expiry = :inspection_expiry,
                purchase_price = :purchase_price,
                status = :status,
                notes = :notes
                WHERE id = :id";
        
        return $this->db->execute($sql, $data);
    }
    
    /**
     * حذف سيارة
     */
    public function delete(int $id): bool
    {
        $hasRentals = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM rentals WHERE car_id = :id",
            ['id' => $id]
        )['c'] ?? 0);
        
        if ($hasRentals > 0) {
            return false;
        }
        
        // حذف الصور
        $this->deleteAllImages($id);
        
        $this->db->execute("DELETE FROM cars WHERE id = :id", ['id' => $id]);
        return true;
    }
    
    /**
     * تغيير حالة السيارة
     */
    public function changeStatus(int $id, string $status): int
    {
        return $this->db->execute(
            "UPDATE cars SET status = :status WHERE id = :id",
            ['status' => $status, 'id' => $id]
        );
    }
    
    /**
     * التحقق من وجود رقم اللوحة
     */
    public function plateExists(string $plate, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as c FROM cars WHERE plate_number = :plate";
        $params = ['plate' => $plate];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        return (int) ($this->db->fetchOne($sql, $params)['c'] ?? 0) > 0;
    }
    
    /**
     * التحقق من وجود رقم الهيكل
     */
    public function chassisExists(string $chassis, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as c FROM cars WHERE chassis_number = :chassis";
        $params = ['chassis' => $chassis];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        return (int) ($this->db->fetchOne($sql, $params)['c'] ?? 0) > 0;
    }
    
    /**
     * جلب أنواع السيارات
     */
    public function getTypes(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name_ar, name_en FROM car_types WHERE is_active = 1 ORDER BY name_ar"
        );
    }
    
    /**
     * جلب الماركات المتاحة
     */
    public function getBrands(): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT brand FROM cars ORDER BY brand"
        );
    }
    
    /**
     * إحصائيات السيارات
     */
    public function getStats(): array
    {
        return [
            'total'         => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM cars")['c'] ?? 0),
            'available'     => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM cars WHERE status = 'available'")['c'] ?? 0),
            'rented'        => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM cars WHERE status = 'rented'")['c'] ?? 0),
            'maintenance'   => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM cars WHERE status = 'maintenance'")['c'] ?? 0),
            'out_of_service'=> (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM cars WHERE status = 'out_of_service'")['c'] ?? 0),
        ];
    }
    
    // ============================================================
    // ✅ دوال الصور
    // ============================================================
    
    /**
     * تحديث الصورة الرئيسية
     */
    public function updateMainImage(int $id, string $imageName): void
    {
        $this->db->execute(
            "UPDATE cars SET main_image = :image WHERE id = :id",
            ['image' => $imageName, 'id' => $id]
        );
    }
    
    /**
     * حذف الصورة الرئيسية
     */
    public function deleteMainImage(int $id): void
    {
        $car = $this->find($id);
        if ($car && !empty($car['main_image'])) {
            $filePath = STORAGE_PATH . '/uploads/cars/' . $car['main_image'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        $this->db->execute(
            "UPDATE cars SET main_image = NULL WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * جلب صور السيارة الإضافية
     */
    public function getImages(int $carId): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM car_images 
                 WHERE car_id = :id 
                 ORDER BY is_main DESC, sort_order ASC, id ASC",
                ['id' => $carId]
            );
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * إضافة صورة للسيارة
     */
    public function addImage(int $carId, string $imageName, bool $isMain = false, string $caption = ''): int
    {
        try {
            return $this->db->insert(
                "INSERT INTO car_images (car_id, image_name, is_main, caption) 
                 VALUES (:car_id, :image, :is_main, :caption)",
                [
                    'car_id'   => $carId,
                    'image'    => $imageName,
                    'is_main'  => $isMain ? 1 : 0,
                    'caption'  => $caption,
                ]
            );
        } catch (Exception $e) {
            error_log('Add car image error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * جلب صورة بالمعرف
     */
    public function findImage(int $imageId): ?array
    {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM car_images WHERE id = :id",
                ['id' => $imageId]
            );
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * حذف صورة
     */
    public function deleteImage(int $imageId): bool
    {
        try {
            $image = $this->findImage($imageId);
            
            if (!$image) {
                return false;
            }
            
            // حذف الملف
            $filePath = STORAGE_PATH . '/uploads/cars/' . $image['image_name'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // حذف من قاعدة البيانات
            $this->db->execute(
                "DELETE FROM car_images WHERE id = :id",
                ['id' => $imageId]
            );
            
            return true;
        } catch (Exception $e) {
            error_log('Delete car image error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * حذف كل صور السيارة
     */
    public function deleteAllImages(int $carId): void
    {
        try {
            // حذف الملفات
            $images = $this->getImages($carId);
            foreach ($images as $img) {
                $filePath = STORAGE_PATH . '/uploads/cars/' . $img['image_name'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            // حذف الصورة الرئيسية
            $car = $this->find($carId);
            if ($car && !empty($car['main_image'])) {
                $filePath = STORAGE_PATH . '/uploads/cars/' . $car['main_image'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            // حذف من قاعدة البيانات
            $this->db->execute(
                "DELETE FROM car_images WHERE car_id = :id",
                ['id' => $carId]
            );
        } catch (Exception $e) {
            error_log('Delete all car images error: ' . $e->getMessage());
        }
    }
}