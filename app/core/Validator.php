<?php
/**
 * التحقق من صحة المدخلات
 */

class Validator
{
    private array $errors = [];
    private array $data = [];
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function required(string $field, string $label = null): self
    {
        $label = $label ?? $field;
        $value = trim((string)($this->data[$field] ?? ''));
        
        if ($value === '') {
            $this->errors[$field] = "حقل {$label} مطلوب";
        }
        
        return $this;
    }
    
    public function minLength(string $field, int $min, string $label = null): self
    {
        $label = $label ?? $field;
        $value = (string)($this->data[$field] ?? '');
        
        if (mb_strlen($value) < $min) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون {$min} أحرف على الأقل";
        }
        
        return $this;
    }
    
    public function maxLength(string $field, int $max, string $label = null): self
    {
        $label = $label ?? $field;
        $value = (string)($this->data[$field] ?? '');
        
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = "حقل {$label} يجب أن لا يتجاوز {$max} حرف";
        }
        
        return $this;
    }
    
    public function email(string $field, string $label = null): self
    {
        $label = $label ?? $field;
        $value = (string)($this->data[$field] ?? '');
        
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون بريداً إلكترونياً صحيحاً";
        }
        
        return $this;
    }
    
    public function numeric(string $field, string $label = null): self
    {
        $label = $label ?? $field;
        $value = (string)($this->data[$field] ?? '');
        
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "حقل {$label} يجب أن يحتوي على أرقام فقط";
        }
        
        return $this;
    }
    
    public function isValid(): bool
    {
        return empty($this->errors);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    public function getCleanData(): array
    {
        return array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->data);
    }
}