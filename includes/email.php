<?php
/**
 * Email System using MailerSend API
 * 
 * Simple, standalone email sender for cPanel deployment
 * No Composer required - pure PHP implementation
 */

class EmailSender {
    private $config;
    private $api_token;
    private $api_url;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/email-config.php';
        $this->api_token = $this->config['api_token'];
        $this->api_url = $this->config['api_url'];
    }
    
    /**
     * Send email via MailerSend API
     * 
     * @param string $to_email Recipient email
     * @param string $to_name Recipient name
     * @param string $subject Email subject
     * @param string $html_body HTML email content
     * @param string $text_body Plain text fallback (optional)
     * @return array Response with success status and message
     */
    public function send($to_email, $to_name, $subject, $html_body, $text_body = '') {
        // Validate inputs
        if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid recipient email'];
        }
        
        if (empty($subject) || empty($html_body)) {
            return ['success' => false, 'message' => 'Subject and body are required'];
        }
        
        // Prepare email data
        $data = [
            'from' => [
                'email' => $this->config['from_email'],
                'name' => $this->config['from_name']
            ],
            'to' => [
                [
                    'email' => $to_email,
                    'name' => $to_name
                ]
            ],
            'subject' => $subject,
            'html' => $html_body,
            'text' => !empty($text_body) ? $text_body : strip_tags($html_body)
        ];
        
        // Add reply-to if configured
        if (!empty($this->config['reply_to_email'])) {
            $data['reply_to'] = [
                'email' => $this->config['reply_to_email'],
                'name' => $this->config['reply_to_name']
            ];
        }
        
        // Check if cURL is available
        if (!function_exists('curl_init')) {
            // Fallback to file_get_contents with stream context
            return $this->sendViaFileGetContents($data);
        }
        
        // Send via cURL
        $ch = curl_init($this->api_url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_token
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Handle response
        if ($curl_error) {
            $this->logError('cURL Error: ' . $curl_error);
            return ['success' => false, 'message' => 'Failed to connect to email service'];
        }
        
        if ($http_code === 202) {
            return ['success' => true, 'message' => 'Email sent successfully'];
        }
        
        // Parse error response
        $response_data = json_decode($response, true);
        $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
        
        $this->logError("MailerSend Error (HTTP $http_code): " . $error_message);
        
        return [
            'success' => false,
            'message' => 'Failed to send email: ' . $error_message
        ];
    }
    
    /**
     * Fallback method using file_get_contents when cURL is not available
     */
    private function sendViaFileGetContents($data) {
        try {
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->api_token
                    ],
                    'content' => json_encode($data),
                    'ignore_errors' => true
                ]
            ];
            
            $context = stream_context_create($options);
            $response = @file_get_contents($this->api_url, false, $context);
            
            if ($response === false) {
                $this->logError('file_get_contents Error: Failed to connect to email service');
                return ['success' => false, 'message' => 'Failed to connect to email service'];
            }
            
            // Parse response headers to get status code
            $status_line = $http_response_header[0] ?? '';
            preg_match('/\d{3}/', $status_line, $matches);
            $http_code = isset($matches[0]) ? (int)$matches[0] : 0;
            
            if ($http_code === 202) {
                return ['success' => true, 'message' => 'Email sent successfully'];
            }
            
            // Parse error response
            $response_data = json_decode($response, true);
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
            
            $this->logError("MailerSend Error (HTTP $http_code): " . $error_message);
            
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $error_message
            ];
        } catch (Exception $e) {
            $this->logError('Exception in sendViaFileGetContents: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send welcome email after registration
     */
    public function sendWelcomeEmail($to_email, $to_name, $lang = 'bg') {
        $template = $this->getEmailTemplate('welcome', $lang, [
            'user_name' => $to_name,
            'site_name' => $this->config['site_name'],
            'site_url' => $this->config['site_url']
        ]);
        
        $subject = $lang === 'bg' 
            ? 'Добре дошли в ' . $this->config['site_name'] 
            : 'Welcome to ' . $this->config['site_name'];
        
        return $this->send($to_email, $to_name, $subject, $template['html'], $template['text']);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($to_email, $to_name, $reset_token, $lang = 'bg') {
        $reset_link = $this->config['site_url'] . '/reset/' . $reset_token;
        
        $template = $this->getEmailTemplate('password-reset', $lang, [
            'user_name' => $to_name,
            'site_name' => $this->config['site_name'],
            'reset_link' => $reset_link,
            'reset_token' => $reset_token
        ]);
        
        $subject = $lang === 'bg' 
            ? 'Нулиране на парола - ' . $this->config['site_name']
            : 'Password Reset - ' . $this->config['site_name'];
        
        return $this->send($to_email, $to_name, $subject, $template['html'], $template['text']);
    }
    
    /**
     * Send activation email after registration
     */
    public function sendActivationEmail($to_email, $to_name, $activation_token, $lang = 'bg') {
        $activation_link = $this->config['site_url'] . '/activate/' . $activation_token;
        
        $template = $this->getEmailTemplate('activation', $lang, [
            'user_name' => $to_name,
            'user_email' => $to_email,
            'site_name' => $this->config['site_name'],
            'activation_link' => $activation_link,
            'activation_token' => $activation_token
        ]);
        
        $subject = $lang === 'bg' 
            ? 'Активирайте профила си - ' . $this->config['site_name']
            : 'Activate Your Account - ' . $this->config['site_name'];
        
        return $this->send($to_email, $to_name, $subject, $template['html'], $template['text']);
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmationEmail($to_email, $to_name, $order_data, $lang = 'bg') {
        $template = $this->getEmailTemplate('order-confirmation', $lang, [
            'user_name' => $to_name,
            'site_name' => $this->config['site_name'],
            'order_id' => $order_data['id'],
            'order_items' => $order_data['items'],
            'order_total' => $order_data['total'],
            'order_date' => $order_data['created']
        ]);
        
        $subject = $lang === 'bg'
            ? 'Потвърждение за поръчка #' . $order_data['id']
            : 'Order Confirmation #' . $order_data['id'];
        
        return $this->send($to_email, $to_name, $subject, $template['html'], $template['text']);
    }
    
    /**
     * Load email template
     */
    private function getEmailTemplate($template_name, $lang, $variables) {
        $template_file = __DIR__ . '/../email-templates/' . $template_name . '-' . $lang . '.php';
        
        if (!file_exists($template_file)) {
            // Fallback to Bulgarian if language not found
            $template_file = __DIR__ . '/../email-templates/' . $template_name . '-bg.php';
        }
        
        if (!file_exists($template_file)) {
            return [
                'html' => '<p>Email template not found</p>',
                'text' => 'Email template not found'
            ];
        }
        
        // Extract variables for use in template
        extract($variables);
        
        // Start output buffering
        ob_start();
        include $template_file;
        $html = ob_get_clean();
        
        // Generate plain text version
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        
        return [
            'html' => $html,
            'text' => trim($text)
        ];
    }
    
    /**
     * Log errors
     */
    private function logError($message) {
        $log_file = __DIR__ . '/../storage/email-errors.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message\n";
        
        @file_put_contents($log_file, $log_message, FILE_APPEND);
    }
    
    /**
     * Test email configuration
     */
    public function testConnection($test_email) {
        $html = '<h2>Test Email</h2><p>If you received this, your email system is working correctly!</p>';
        $result = $this->send($test_email, 'Test Recipient', 'Test Email from OffMeta', $html);
        
        return $result;
    }
}

/**
 * Helper function to get email sender instance
 */
function get_email_sender() {
    return new EmailSender();
}
