<?php
/**
 * User Management Class
 * TechSavvyGenLtd Project
 */

class User {
    private $db;
    private $table = TBL_USERS;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field {$field} is required"];
            }
        }
        
        // Validate email format
        if (!validateEmail($data['email'])) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Validate username format
        if (!preg_match(REGEX_USERNAME, $data['username'])) {
            return ['success' => false, 'message' => 'Username must be 3-20 characters and contain only letters, numbers, and underscores'];
        }
        
        // Validate password strength
        if (!preg_match(REGEX_PASSWORD, $data['password'])) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and number'];
        }
        
        // Check if username already exists
        if ($this->usernameExists($data['username'])) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        try {
            // Prepare user data
            $userData = [
                'username' => cleanInput($data['username']),
                'email' => cleanInput($data['email']),
                'password' => hashPassword($data['password']),
                'first_name' => cleanInput($data['first_name']),
                'last_name' => cleanInput($data['last_name']),
                'phone' => cleanInput($data['phone'] ?? ''),
                'address' => cleanInput($data['address'] ?? ''),
                'city' => cleanInput($data['city'] ?? ''),
                'country' => cleanInput($data['country'] ?? ''),
                'postal_code' => cleanInput($data['postal_code'] ?? ''),
                'role' => USER_ROLE_CUSTOMER,
                'verification_token' => generateRandomString(64),
                'is_verified' => false,
                'preferred_language' => $data['preferred_language'] ?? DEFAULT_LANGUAGE,
                'registration_date' => date('Y-m-d H:i:s')
            ];
            
            $userId = $this->db->insert($this->table, $userData);
            
            if ($userId) {
                // Send verification email
                $this->sendVerificationEmail($userId, $userData['email'], $userData['verification_token']);
                
                // Log registration
                logActivity('user_registered', "User {$userData['username']} registered", $userId);
                
                return [
                    'success' => true, 
                    'message' => 'Registration successful. Please check your email for verification.',
                    'user_id' => $userId
                ];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
            
        } catch (Exception $e) {
            error_log("User registration failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Login user
     */
    public function login($identifier, $password, $remember = false) {
        // Check if identifier is email or username
        $field = validateEmail($identifier) ? 'email' : 'username';
        
        // Check login attempts
        if ($this->isAccountLocked($identifier)) {
            return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed login attempts'];
        }
        
        try {
            $user = $this->db->fetch(
                "SELECT * FROM {$this->table} WHERE {$field} = ? AND status = ?",
                [$identifier, USER_STATUS_ACTIVE]
            );
            
            if (!$user || !verifyPassword($password, $user['password'])) {
                $this->recordFailedLogin($identifier);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if account is verified
            if (!$user['is_verified']) {
                return ['success' => false, 'message' => 'Please verify your email address before logging in'];
            }
            
            // Update last login
            $this->db->update(
                $this->table,
                ['last_login' => date('Y-m-d H:i:s')],
                'id = ?',
                [$user['id']]
            );
            
            // Clear failed login attempts
            $this->clearFailedLogins($identifier);
            
            // Set session
            $this->setUserSession($user);
            
            // Set remember me cookie if requested
            if ($remember) {
                $this->setRememberMeCookie($user['id']);
            }
            
            // Log login
            logActivity('user_login', "User {$user['username']} logged in", $user['id']);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $this->sanitizeUserData($user)
            ];
            
        } catch (Exception $e) {
            error_log("User login failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            $username = $_SESSION['username'] ?? '';
            
            // Log logout
            logActivity('user_logout', "User {$username} logged out", $userId);
            
            // Clear remember me cookie
            if (isset($_COOKIE['remember_me'])) {
                setcookie('remember_me', '', time() - 3600, '/');
                $this->clearRememberMeToken($userId);
            }
        }
        
        // Destroy session
        session_destroy();
        session_start();
        
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $user = $this->db->fetch(
                "SELECT * FROM {$this->table} WHERE id = ?",
                [$id]
            );
            
            return $user ? $this->sanitizeUserData($user) : null;
            
        } catch (Exception $e) {
            error_log("Get user by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        try {
            $user = $this->db->fetch(
                "SELECT * FROM {$this->table} WHERE email = ?",
                [$email]
            );
            
            return $user ? $this->sanitizeUserData($user) : null;
            
        } catch (Exception $e) {
            error_log("Get user by email failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user by username
     */
    public function getByUsername($username) {
        try {
            $user = $this->db->fetch(
                "SELECT * FROM {$this->table} WHERE username = ?",
                [$username]
            );
            
            return $user ? $this->sanitizeUserData($user) : null;
            
        } catch (Exception $e) {
            error_log("Get user by username failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $allowedFields = [
                'first_name', 'last_name', 'phone', 'address', 
                'city', 'country', 'postal_code', 'preferred_language'
            ];
            
            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = cleanInput($data[$field]);
                }
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $updated = $this->db->update(
                $this->table,
                $updateData,
                'id = ?',
                [$userId]
            );
            
            if ($updated) {
                // Update session data if current user
                if ($_SESSION['user_id'] == $userId) {
                    foreach ($updateData as $key => $value) {
                        if ($key !== 'updated_at') {
                            $_SESSION[$key] = $value;
                        }
                    }
                }
                
                logActivity('profile_updated', 'User profile updated', $userId);
                return ['success' => true, 'message' => 'Profile updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Profile update failed'];
            
        } catch (Exception $e) {
            error_log("Profile update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Profile update failed. Please try again.'];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user data
            $user = $this->db->fetch(
                "SELECT password FROM {$this->table} WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!verifyPassword($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (!preg_match(REGEX_PASSWORD, $newPassword)) {
                return ['success' => false, 'message' => 'New password must be at least 8 characters with uppercase, lowercase, and number'];
            }
            
            // Update password
            $updated = $this->db->update(
                $this->table,
                ['password' => hashPassword($newPassword)],
                'id = ?',
                [$userId]
            );
            
            if ($updated) {
                logActivity('password_changed', 'User changed password', $userId);
                return ['success' => true, 'message' => 'Password changed successfully'];
            }
            
            return ['success' => false, 'message' => 'Password change failed'];
            
        } catch (Exception $e) {
            error_log("Password change failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password change failed. Please try again.'];
        }
    }
    
    /**
     * Reset password request
     */
    public function requestPasswordReset($email) {
        try {
            $user = $this->db->fetch(
                "SELECT id, username FROM {$this->table} WHERE email = ? AND status = ?",
                [$email, USER_STATUS_ACTIVE]
            );
            
            if (!$user) {
                // Don't reveal if email exists
                return ['success' => true, 'message' => 'If the email exists, a reset link has been sent'];
            }
            
            $resetToken = generatePasswordResetToken();
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $updated = $this->db->update(
                $this->table,
                [
                    'reset_token' => $resetToken,
                    'reset_token_expiry' => $expiry
                ],
                'id = ?',
                [$user['id']]
            );
            
            if ($updated) {
                $this->sendPasswordResetEmail($email, $resetToken);
                logActivity('password_reset_requested', 'Password reset requested', $user['id']);
                return ['success' => true, 'message' => 'Password reset link sent to your email'];
            }
            
            return ['success' => false, 'message' => 'Failed to process reset request'];
            
        } catch (Exception $e) {
            error_log("Password reset request failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Reset request failed. Please try again.'];
        }
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword) {
        try {
            $user = $this->db->fetch(
                "SELECT id, username FROM {$this->table} WHERE reset_token = ? AND reset_token_expiry > NOW()",
                [$token]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            // Validate new password
            if (!preg_match(REGEX_PASSWORD, $newPassword)) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and number'];
            }
            
            // Update password and clear reset token
            $updated = $this->db->update(
                $this->table,
                [
                    'password' => hashPassword($newPassword),
                    'reset_token' => null,
                    'reset_token_expiry' => null
                ],
                'id = ?',
                [$user['id']]
            );
            
            if ($updated) {
                logActivity('password_reset_completed', 'Password reset completed', $user['id']);
                return ['success' => true, 'message' => 'Password reset successfully'];
            }
            
            return ['success' => false, 'message' => 'Password reset failed'];
            
        } catch (Exception $e) {
            error_log("Password reset failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed. Please try again.'];
        }
    }
    
    /**
     * Verify email address
     */
    public function verifyEmail($token) {
        try {
            $user = $this->db->fetch(
                "SELECT id, username FROM {$this->table} WHERE verification_token = ? AND is_verified = 0",
                [$token]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid verification token'];
            }
            
            $updated = $this->db->update(
                $this->table,
                [
                    'is_verified' => 1,
                    'verification_token' => null
                ],
                'id = ?',
                [$user['id']]
            );
            
            if ($updated) {
                logActivity('email_verified', 'Email address verified', $user['id']);
                return ['success' => true, 'message' => 'Email verified successfully'];
            }
            
            return ['success' => false, 'message' => 'Email verification failed'];
            
        } catch (Exception $e) {
            error_log("Email verification failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email verification failed. Please try again.'];
        }
    }
    
    /**
     * Upload profile image
     */
    public function uploadProfileImage($userId, $file) {
        $upload = uploadFile($file, UPLOAD_PATH_USERS);
        
        if ($upload['success']) {
            try {
                // Get current profile image
                $currentImage = $this->db->fetchColumn(
                    "SELECT profile_image FROM {$this->table} WHERE id = ?",
                    [$userId]
                );
                
                // Update profile image
                $updated = $this->db->update(
                    $this->table,
                    ['profile_image' => $upload['filename']],
                    'id = ?',
                    [$userId]
                );
                
                if ($updated) {
                    // Delete old image
                    if ($currentImage && $currentImage !== 'default.png') {
                        deleteFile(UPLOAD_PATH_USERS . '/' . $currentImage);
                    }
                    
                    // Update session
                    if ($_SESSION['user_id'] == $userId) {
                        $_SESSION['profile_image'] = $upload['filename'];
                    }
                    
                    logActivity('profile_image_updated', 'Profile image updated', $userId);
                    return ['success' => true, 'filename' => $upload['filename']];
                }
                
                // Clean up uploaded file if database update failed
                deleteFile($upload['path']);
                return ['success' => false, 'message' => 'Failed to update profile image'];
                
            } catch (Exception $e) {
                error_log("Profile image upload failed: " . $e->getMessage());
                deleteFile($upload['path']);
                return ['success' => false, 'message' => 'Profile image upload failed'];
            }
        }
        
        return $upload;
    }
    
    /**
     * Get all users (admin function)
     */
    public function getAllUsers($page = 1, $limit = ADMIN_ITEMS_PER_PAGE, $search = '', $role = '', $status = '') {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            if (!empty($search)) {
                $conditions[] = "(username LIKE ? OR email LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($role)) {
                $conditions[] = "role = ?";
                $params[] = $role;
            }
            
            if (!empty($status)) {
                $conditions[] = "status = ?";
                $params[] = $status;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $totalQuery = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
            $total = $this->db->fetchColumn($totalQuery, $params);
            
            // Get users
            $query = "SELECT * FROM {$this->table} {$whereClause} ORDER BY registration_date DESC LIMIT {$limit} OFFSET {$offset}";
            $users = $this->db->fetchAll($query, $params);
            
            // Sanitize user data
            $users = array_map([$this, 'sanitizeUserData'], $users);
            
            return [
                'users' => $users,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
        } catch (Exception $e) {
            error_log("Get all users failed: " . $e->getMessage());
            return ['users' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
        }
    }
    
    /**
     * Update user wallet balance
     */
    public function updateWalletBalance($userId, $amount, $description = '') {
        try {
            $this->db->beginTransaction();
            
            // Get current balance
            $currentBalance = $this->db->fetchColumn(
                "SELECT wallet_balance FROM {$this->table} WHERE id = ?",
                [$userId]
            );
            
            $newBalance = $currentBalance + $amount;
            
            if ($newBalance < 0) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Insufficient wallet balance'];
            }
            
            // Update balance
            $updated = $this->db->update(
                $this->table,
                ['wallet_balance' => $newBalance],
                'id = ?',
                [$userId]
            );
            
            if ($updated) {
                // Log wallet transaction
                logActivity(
                    'wallet_transaction',
                    "Wallet balance changed by {$amount}. {$description}",
                    $userId
                );
                
                $this->db->commit();
                return [
                    'success' => true,
                    'previous_balance' => $currentBalance,
                    'new_balance' => $newBalance
                ];
            }
            
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to update wallet balance'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Wallet balance update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Wallet update failed'];
        }
    }
    
    /**
     * Helper methods
     */
    
    private function usernameExists($username) {
        return $this->db->exists($this->table, 'username = ?', [$username]);
    }
    
    private function emailExists($email) {
        return $this->db->exists($this->table, 'email = ?', [$email]);
    }
    
    private function sanitizeUserData($user) {
        unset($user['password']);
        unset($user['verification_token']);
        unset($user['reset_token']);
        unset($user['reset_token_expiry']);
        return $user;
    }
    
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['profile_image'] = $user['profile_image'];
        $_SESSION['preferred_language'] = $user['preferred_language'];
        $_SESSION['is_verified'] = $user['is_verified'];
        $_SESSION['wallet_balance'] = $user['wallet_balance'];
    }
    
    private function setRememberMeCookie($userId) {
        $token = generateRandomString(64);
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        setcookie('remember_me', $token, $expiry, '/', '', true, true);
        
        // Store token in database (you'd need to create a remember_tokens table)
        // This is a simplified implementation
    }
    
    private function clearRememberMeToken($userId) {
        // Clear remember me token from database
        // Implementation depends on your remember_tokens table structure
    }
    
    private function isAccountLocked($identifier) {
        // Check failed login attempts (simplified implementation)
        // You would implement a failed_logins table for production use
        return false;
    }
    
    private function recordFailedLogin($identifier) {
        // Record failed login attempt
        // Implementation depends on your failed_logins table
    }
    
    private function clearFailedLogins($identifier) {
        // Clear failed login attempts
        // Implementation depends on your failed_logins table
    }
    
    private function sendVerificationEmail($userId, $email, $token) {
        $verificationUrl = SITE_URL . "/verify-email.php?token={$token}";
        $subject = "Please verify your email address";
        $body = "Click the following link to verify your email: {$verificationUrl}";
        
        return sendEmail($email, $subject, $body);
    }
    
    private function sendPasswordResetEmail($email, $token) {
        $resetUrl = SITE_URL . "/reset-password.php?token={$token}";
        $subject = "Password Reset Request";
        $body = "Click the following link to reset your password: {$resetUrl}";
        
        return sendEmail($email, $subject, $body);
    }
}
?>