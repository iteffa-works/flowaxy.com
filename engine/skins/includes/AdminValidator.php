<?php
/**
 * Обертка над Validator для админ-панели
 * Интегрирует функционал из engine/classes/validators/Validator
 * 
 * @package Engine\Skins\Includes
 */

class AdminValidator {
    
    /**
     * Валидация email
     */
    public static function email(string $email): bool {
        if (class_exists('Validator')) {
            return Validator::validateEmail($email);
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Валидация URL
     */
    public static function url(string $url): bool {
        if (class_exists('Validator')) {
            return Validator::validateUrl($url);
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Валидация целого числа
     */
    public static function integer($value, ?int $min = null, ?int $max = null): bool {
        if (class_exists('Validator')) {
            return Validator::validateInt($value, $min, $max);
        }
        
        if (!is_numeric($value)) {
            return false;
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            return false;
        }
        
        if ($max !== null && $intValue > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация строки
     */
    public static function string($value, ?int $minLength = null, ?int $maxLength = null): bool {
        if (class_exists('Validator')) {
            return Validator::validateString($value, $minLength, $maxLength);
        }
        
        if (!is_string($value)) {
            return false;
        }
        
        $length = mb_strlen($value, 'UTF-8');
        
        if ($minLength !== null && $length < $minLength) {
            return false;
        }
        
        if ($maxLength !== null && $length > $maxLength) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация slug
     */
    public static function slug(string $slug): bool {
        if (class_exists('Validator')) {
            return Validator::validateSlug($slug);
        }
        return preg_match('/^[a-z0-9-]+$/', $slug) === 1;
    }
    
    /**
     * Валидация цвета (hex)
     */
    public static function color(string $color): bool {
        if (class_exists('Validator')) {
            return Validator::validateColor($color);
        }
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }
    
    /**
     * Валидация массива данных по правилам
     * 
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации ['field' => 'rule1|rule2', ...]
     * @return array ['valid' => bool, 'errors' => ['field' => 'error message']]
     */
    public static function validate(array $data, array $rules): array {
        $errors = [];
        $valid = true;
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleArray = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            
            foreach ($ruleArray as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;
                
                $isValid = true;
                $errorMessage = '';
                
                switch ($ruleName) {
                    case 'required':
                        $isValid = !empty($value) || $value === '0' || $value === 0;
                        $errorMessage = "Поле '{$field}' обов'язкове для заповнення";
                        break;
                        
                    case 'email':
                        $isValid = empty($value) || self::email($value);
                        $errorMessage = "Поле '{$field}' має бути валідним email адресом";
                        break;
                        
                    case 'url':
                        $isValid = empty($value) || self::url($value);
                        $errorMessage = "Поле '{$field}' має бути валідним URL";
                        break;
                        
                    case 'integer':
                    case 'int':
                        $isValid = empty($value) || self::integer($value);
                        $errorMessage = "Поле '{$field}' має бути числом";
                        break;
                        
                    case 'string':
                        $isValid = empty($value) || is_string($value);
                        $errorMessage = "Поле '{$field}' має бути рядком";
                        break;
                        
                    case 'slug':
                        $isValid = empty($value) || self::slug($value);
                        $errorMessage = "Поле '{$field}' має бути валідним slug";
                        break;
                        
                    case 'color':
                        $isValid = empty($value) || self::color($value);
                        $errorMessage = "Поле '{$field}' має бути валідним кольором (hex)";
                        break;
                        
                    case 'min':
                        if ($ruleValue !== null) {
                            if (is_numeric($value)) {
                                $isValid = (int)$value >= (int)$ruleValue;
                                $errorMessage = "Поле '{$field}' має бути не менше {$ruleValue}";
                            } elseif (is_string($value)) {
                                $isValid = mb_strlen($value, 'UTF-8') >= (int)$ruleValue;
                                $errorMessage = "Поле '{$field}' має містити не менше {$ruleValue} символів";
                            }
                        }
                        break;
                        
                    case 'max':
                        if ($ruleValue !== null) {
                            if (is_numeric($value)) {
                                $isValid = (int)$value <= (int)$ruleValue;
                                $errorMessage = "Поле '{$field}' має бути не більше {$ruleValue}";
                            } elseif (is_string($value)) {
                                $isValid = mb_strlen($value, 'UTF-8') <= (int)$ruleValue;
                                $errorMessage = "Поле '{$field}' має містити не більше {$ruleValue} символів";
                            }
                        }
                        break;
                }
                
                if (!$isValid) {
                    $valid = false;
                    if (!isset($errors[$field])) {
                        $errors[$field] = $errorMessage;
                    }
                    break; // Останавливаем проверку правил для этого поля после первой ошибки
                }
            }
        }
        
        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }
}

