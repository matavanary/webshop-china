<?php
/**
 * Validator Helper Class
 * 
 * Provides validation methods for various data types
 */

namespace helpers;

class Validator {
    private $errors = [];
    private $data = [];

    public function __construct($data = []) {
        $this->data = $data;
        $this->errors = [];
    }

    /**
     * Set data to validate
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * Add validation rule
     */
    public function rule($field, $rules, $message = null) {
        $value = $this->data[$field] ?? null;
        $rulesArray = is_string($rules) ? explode('|', $rules) : $rules;

        foreach ($rulesArray as $rule) {
            if (!$this->validateRule($field, $value, $rule, $message)) {
                break; // Stop on first error for this field
            }
        }

        return $this;
    }

    /**
     * Validate single rule
     */
    private function validateRule($field, $value, $rule, $customMessage = null) {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleValue = $ruleParts[1] ?? null;

        $isValid = true;
        $message = '';

        switch ($ruleName) {
            case 'required':
                $isValid = !empty($value) && $value !== '';
                $message = $customMessage ?: "{$field} 不能为空";
                break;

            case 'email':
                $isValid = empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL);
                $message = $customMessage ?: "{$field} 邮箱格式不正确";
                break;

            case 'min':
                $isValid = empty($value) || strlen($value) >= (int)$ruleValue;
                $message = $customMessage ?: "{$field} 最少需要 {$ruleValue} 个字符";
                break;

            case 'max':
                $isValid = empty($value) || strlen($value) <= (int)$ruleValue;
                $message = $customMessage ?: "{$field} 最多允许 {$ruleValue} 个字符";
                break;

            case 'numeric':
                $isValid = empty($value) || is_numeric($value);
                $message = $customMessage ?: "{$field} 必须是数字";
                break;

            case 'integer':
                $isValid = empty($value) || filter_var($value, FILTER_VALIDATE_INT) !== false;
                $message = $customMessage ?: "{$field} 必须是整数";
                break;

            case 'alpha':
                $isValid = empty($value) || ctype_alpha($value);
                $message = $customMessage ?: "{$field} 只能包含字母";
                break;

            case 'alphanumeric':
                $isValid = empty($value) || ctype_alnum($value);
                $message = $customMessage ?: "{$field} 只能包含字母和数字";
                break;

            case 'url':
                $isValid = empty($value) || filter_var($value, FILTER_VALIDATE_URL);
                $message = $customMessage ?: "{$field} URL格式不正确";
                break;

            case 'date':
                $isValid = empty($value) || strtotime($value) !== false;
                $message = $customMessage ?: "{$field} 日期格式不正确";
                break;

            case 'in':
                $allowedValues = explode(',', $ruleValue);
                $isValid = empty($value) || in_array($value, $allowedValues);
                $message = $customMessage ?: "{$field} 值不在允许范围内";
                break;

            case 'regex':
                $isValid = empty($value) || preg_match($ruleValue, $value);
                $message = $customMessage ?: "{$field} 格式不正确";
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = $this->data[$confirmField] ?? null;
                $isValid = $value === $confirmValue;
                $message = $customMessage ?: "{$field} 确认不匹配";
                break;

            case 'unique':
                // This would require database checking
                // Format: unique:table,column,except_id
                $isValid = $this->validateUnique($value, $ruleValue);
                $message = $customMessage ?: "{$field} 已存在";
                break;

            case 'exists':
                // This would require database checking
                // Format: exists:table,column
                $isValid = $this->validateExists($value, $ruleValue);
                $message = $customMessage ?: "{$field} 不存在";
                break;
        }

        if (!$isValid) {
            $this->errors[$field] = $message;
        }

        return $isValid;
    }

    /**
     * Validate unique value in database
     */
    private function validateUnique($value, $rule) {
        if (empty($value)) return true;

        global $db;
        if (!$db) return true;

        $parts = explode(',', $rule);
        $table = $parts[0];
        $column = $parts[1] ?? 'id';
        $exceptId = $parts[2] ?? null;

        $conditions = [$column => $value];
        
        if ($exceptId) {
            $sql = "SELECT id FROM {$table} WHERE {$column} = ? AND id != ?";
            $result = $db->queryOne($sql, [$value, $exceptId]);
        } else {
            $result = $db->findOne($table, $conditions);
        }

        return empty($result);
    }

    /**
     * Validate value exists in database
     */
    private function validateExists($value, $rule) {
        if (empty($value)) return true;

        global $db;
        if (!$db) return true;

        $parts = explode(',', $rule);
        $table = $parts[0];
        $column = $parts[1] ?? 'id';

        $result = $db->findOne($table, [$column => $value]);
        return !empty($result);
    }

    /**
     * Check if validation passed
     */
    public function isValid() {
        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get first error
     */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Get error for specific field
     */
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }

    /**
     * Add custom error
     */
    public function addError($field, $message) {
        $this->errors[$field] = $message;
        return $this;
    }

    /**
     * Clear errors
     */
    public function clearErrors() {
        $this->errors = [];
        return $this;
    }

    /**
     * Static validation method
     */
    public static function make($data, $rules) {
        $validator = new self($data);
        
        foreach ($rules as $field => $rule) {
            $validator->rule($field, $rule);
        }

        return $validator;
    }

    /**
     * Validate array of data with rules
     */
    public static function validate($data, $rules) {
        $validator = self::make($data, $rules);
        
        if ($validator->isValid()) {
            return ['valid' => true, 'data' => $data];
        } else {
            return ['valid' => false, 'errors' => $validator->getErrors()];
        }
    }
}