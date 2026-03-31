<?php
/**
 * 邮件发送功能模块
 * 支持SMTP发送邮件通知
 */

// 邮件发送类
class MailSender {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    private $conn;
    private $logFile;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->logFile = __DIR__ . '/../logs/mail.log';
        $this->loadSettings();
    }
    
    // 加载邮件设置
    private function loadSettings() {
        $this->smtpHost = $this->getSetting('smtp_host');
        $this->smtpPort = intval($this->getSetting('smtp_port')) ?: 587;
        $this->smtpUsername = $this->getSetting('smtp_username');
        $this->smtpPassword = $this->getSetting('smtp_password');
        $this->fromEmail = $this->getSetting('smtp_from_email');
        $this->fromName = $this->getSetting('smtp_from_name');
    }
    
    // 获取设置项
    private function getSetting($name) {
        $stmt = $this->conn->prepare("SELECT value FROM settings WHERE name = ?");
        if (!$stmt) return '';
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['value'] ?? '';
    }
    
    // 检查邮件功能是否启用
    public function isEnabled() {
        return $this->getSetting('smtp_enabled') === '1' && 
               !empty($this->smtpHost) && 
               !empty($this->smtpUsername) && 
               !empty($this->smtpPassword);
    }
    
    // 记录日志
    private function log($message, $type = 'INFO') {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    // 发送邮件
    public function send($toEmail, $toName, $subject, $htmlBody) {
        if (!$this->isEnabled()) {
            $this->log('邮件功能未启用或配置不完整', 'WARNING');
            return ['success' => false, 'message' => '邮件功能未启用'];
        }
        
        if (empty($toEmail)) {
            $this->log('收件人邮箱为空', 'ERROR');
            return ['success' => false, 'message' => '收件人邮箱为空'];
        }
        
        try {
            $result = $this->sendViaSmtp($toEmail, $toName, $subject, $htmlBody);
            if ($result['success']) {
                $this->log("邮件发送成功: {$toEmail}", 'SUCCESS');
            } else {
                $this->log("邮件发送失败: {$toEmail} - {$result['message']}", 'ERROR');
            }
            return $result;
        } catch (Exception $e) {
            $this->log("邮件发送异常: {$toEmail} - " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // 通过SMTP发送邮件
    private function sendViaSmtp($toEmail, $toName, $subject, $htmlBody) {
        $timeout = 10; // 减少超时时间到10秒
        $errno = 0;
        $errstr = '';
        
        // 建立连接
        $socket = @fsockopen(
            $this->smtpHost,
            $this->smtpPort,
            $errno,
            $errstr,
            $timeout
        );
        
        if (!$socket) {
            return ['success' => false, 'message' => "连接SMTP服务器失败: {$errstr} ({$errno})"];
        }
        
        stream_set_timeout($socket, $timeout);
        
        // 读取服务器响应
        $response = $this->getSmtpResponse($socket);
        if (strpos($response, '220') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => 'SMTP服务器响应异常: ' . $response];
        }
        
        // 发送EHLO
        $this->sendSmtpCommand($socket, "EHLO " . gethostname());
        $response = $this->getSmtpResponse($socket);
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => 'EHLO命令失败: ' . $response];
        }
        
        // 检查是否需要STARTTLS
        $useTls = (strpos($response, 'STARTTLS') !== false);
        if ($useTls) {
            $this->sendSmtpCommand($socket, "STARTTLS");
            $response = $this->getSmtpResponse($socket);
            if (strpos($response, '220') !== 0) {
                fclose($socket);
                return ['success' => false, 'message' => 'STARTTLS失败: ' . $response];
            }
            
            // 启用TLS加密
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return ['success' => false, 'message' => 'TLS加密启用失败'];
            }
            
            // 重新发送EHLO
            $this->sendSmtpCommand($socket, "EHLO " . gethostname());
            $response = $this->getSmtpResponse($socket);
        }
        
        // 检查是否需要认证
        if (strpos($response, 'AUTH') !== false) {
            // 发送AUTH LOGIN
            $this->sendSmtpCommand($socket, "AUTH LOGIN");
            $response = $this->getSmtpResponse($socket);
            if (strpos($response, '334') !== 0) {
                fclose($socket);
                return ['success' => false, 'message' => 'AUTH LOGIN失败: ' . $response];
            }
            
            // 发送用户名（Base64编码）
            $this->sendSmtpCommand($socket, base64_encode($this->smtpUsername));
            $response = $this->getSmtpResponse($socket);
            if (strpos($response, '334') !== 0) {
                fclose($socket);
                return ['success' => false, 'message' => '用户名验证失败: ' . $response];
            }
            
            // 发送密码（Base64编码）
            $this->sendSmtpCommand($socket, base64_encode($this->smtpPassword));
            $response = $this->getSmtpResponse($socket);
            if (strpos($response, '235') !== 0) {
                fclose($socket);
                return ['success' => false, 'message' => '密码验证失败: ' . $response];
            }
        }
        
        // 发送发件人
        $this->sendSmtpCommand($socket, "MAIL FROM:<{$this->fromEmail}>");
        $response = $this->getSmtpResponse($socket);
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => 'MAIL FROM失败: ' . $response];
        }
        
        // 发送收件人
        $this->sendSmtpCommand($socket, "RCPT TO:<{$toEmail}>");
        $response = $this->getSmtpResponse($socket);
        if (strpos($response, '250') !== 0 && strpos($response, '251') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => 'RCPT TO失败: ' . $response];
        }
        
        // 发送DATA命令
        $this->sendSmtpCommand($socket, "DATA");
        $response = $this->getSmtpResponse($socket);
        if (strpos($response, '354') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => 'DATA命令失败: ' . $response];
        }
        
        // 构建邮件内容
        $boundary = md5(time());
        $message = $this->buildMimeMessage($toEmail, $toName, $subject, $htmlBody, $boundary);
        
        // 发送邮件内容
        $this->sendSmtpCommand($socket, $message . "\r\n.");
        $response = $this->getSmtpResponse($socket);
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => '邮件发送失败: ' . $response];
        }
        
        // 发送QUIT命令
        $this->sendSmtpCommand($socket, "QUIT");
        fclose($socket);
        
        return ['success' => true, 'message' => '邮件发送成功'];
    }
    
    // 发送SMTP命令
    private function sendSmtpCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
    }
    
    // 获取SMTP响应
    private function getSmtpResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
    
    // 构建MIME邮件内容
    private function buildMimeMessage($toEmail, $toName, $subject, $htmlBody, $boundary) {
        $from = "=?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->fromEmail}>";
        $to = "=?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>";
        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        
        $headers = "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$encodedSubject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        
        $message = $headers . "\r\n";
        $message .= chunk_split(base64_encode($htmlBody));
        
        return $message;
    }
}

// 邮件通知类
class MailNotifier {
    private $mailSender;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->mailSender = new MailSender($conn);
    }
    
    // 获取设置项
    private function getSetting($name) {
        $stmt = $this->conn->prepare("SELECT value FROM settings WHERE name = ?");
        if (!$stmt) return '';
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['value'] ?? '';
    }
    
    // 发送博主通知（有新评论时）
    public function notifyAdminOnComment($commentId, $postId, $commenterName, $commentContent) {
        if ($this->getSetting('notify_admin_on_comment') !== '1') {
            return ['success' => false, 'message' => '博主通知已关闭'];
        }
        
        $adminEmail = $this->getSetting('admin_email');
        if (empty($adminEmail)) {
            return ['success' => false, 'message' => '博主邮箱未设置'];
        }
        
        $siteName = $this->getSetting('site_title');
        $siteUrl = $this->getSiteUrl();
        $postUrl = $siteUrl . '#post-' . $postId;
        
        $subject = "嗨，{$siteName} 博客里面又有评论啦！";
        $htmlBody = $this->buildAdminNotificationTemplate($siteName, $siteUrl, $postUrl, $commenterName, $commentContent);
        
        return $this->mailSender->send($adminEmail, '博主', $subject, $htmlBody);
    }
    
    // 发送用户回复通知
    public function notifyUserOnReply($parentCommentId, $replyCommentId, $replyName, $replyContent, $postId) {
        if ($this->getSetting('notify_user_on_reply') !== '1') {
            return ['success' => false, 'message' => '用户回复通知已关闭'];
        }
        
        // 获取被回复的评论信息
        $stmt = $this->conn->prepare("SELECT name, email, content FROM comments WHERE id = ?");
        $stmt->bind_param("i", $parentCommentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $parentComment = $result->fetch_assoc();
        
        if (!$parentComment || empty($parentComment['email'])) {
            return ['success' => false, 'message' => '被回复用户邮箱不存在'];
        }
        
        // 不通知博主（博主有单独的通知）
        $adminEmail = $this->getSetting('admin_email');
        if ($parentComment['email'] === $adminEmail) {
            return ['success' => false, 'message' => '跳过博主通知'];
        }
        
        $siteName = $this->getSetting('site_title');
        $siteUrl = $this->getSiteUrl();
        $postUrl = $siteUrl . '#post-' . $postId;
        
        $subject = "嗨！您在 {$siteName} 博客里面的评论收到回复啦！";
        $htmlBody = $this->buildUserReplyTemplate(
            $siteName, 
            $siteUrl, 
            $postUrl, 
            $parentComment['name'],
            $parentComment['content'],
            $replyName,
            $replyContent
        );
        
        return $this->mailSender->send($parentComment['email'], $parentComment['name'], $subject, $htmlBody);
    }
    
    // 获取网站URL
    private function getSiteUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        // 获取根目录路径
        $basePath = dirname(dirname($scriptName));
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        return $protocol . '://' . $host . $basePath;
    }
    
    // 构建博主通知邮件模板
    private function buildAdminNotificationTemplate($siteName, $siteUrl, $postUrl, $commenterName, $commentContent) {
        $avatar = $this->getSetting('friend_avatar');
        $background = $this->getSetting('friend_background');
        
        $bgStyle = '';
        if (!empty($background)) {
            $bgStyle = "background-image: url('{$background}'); background-size: cover; background-position: center center;";
        }
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family: Arial, sans-serif;">
  <tr>
    <td align="center" style="background-color: #fccb1a; {$bgStyle} height: 320px; position: relative;">
      <table cellpadding="0" cellspacing="0" border="0" style="position: relative; height: 100%;">
        <tr>
          <td align="center" valign="bottom" style="padding-bottom: 76px;">
            <img src="{$avatar}" width="152" height="152" style="width: 152px; height: 152px; background-size: cover; border-radius: 1000px; display: block; position: relative; top: 150px;">
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td align="center" style="padding-top: 92px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px;">
        <tr>
          <td align="center" style="padding: 0 20px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="center">
                  <span style="font-size: 26px; font-family: PingFang-SC-Bold, PingFang-SC, Arial; font-weight: bold; color: #000000; line-height: 37px; text-align: center; display: block;">
                    嗨，{$siteName} 博客里面又有评论啦！
                  </span>
                </td>
              </tr>
              <tr>
                <td align="center" style="padding-top: 21px;">
                  <span style="font-size: 16px; font-family: PingFang-SC-Bold, PingFang-SC, Arial; font-weight: bold; color: #00000030; line-height: 22px; text-align: center; display: block;">
                    {$siteName} 博客中来自 {$commenterName} 的评论：
                  </span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td align="center" style="padding: 34px 20px 0 20px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: #F7F7F7; border-radius: 12px; min-height: 128px;">
              <tr>
                <td style="padding: 32px 16px;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="padding: 0 30px;">
                        <span style="height: 22px; font-size: 16px; font-family: PingFang-SC-Bold, PingFang-SC, Arial; font-weight: bold; color: #C5343E; line-height: 22px; display: block;">
                          {$commenterName}
                        </span>
                        <span style="margin-top: 6px; margin-right: 22px; font-size: 16px; font-family: PingFangSC-Regular, PingFang SC, Arial; font-weight: 400; color: #000000; line-height: 22px; display: block;">
                          {$commentContent}
                        </span>
                      </td>
                    </tr>
                  </table>
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 32px;">
                    <tr>
                      <td align="center">
                        <a href="{$postUrl}" style="min-width: 106px; height: 38px; background: #e2a31c38; border-radius: 32px; display: inline-block; text-decoration: none; line-height: 38px; text-align: center;">
                          <span style="color: #e2a31c; font-size: 16px;">查看详情</span>
                        </a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td align="center" style="padding-top: 34px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="center">
                  <span style="height: 17px; font-size: 12px; font-family: PingFangSC-Regular, PingFang SC, Arial; font-weight: 400; color: #00000045; line-height: 17px; display: block;">
                    别忘了回复哦！
                  </span>
                  <a href="{$siteUrl}" style="height: 17px; font-size: 12px; font-family: PingFangSC-Regular, PingFang SC, Arial; font-weight: 400; color: #fccb1a; line-height: 17px; margin-top: 6px; text-decoration: none; display: inline-block;">
                    前往博客
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }
    
    // 构建用户回复通知邮件模板
    private function buildUserReplyTemplate($siteName, $siteUrl, $postUrl, $parentNick, $parentComment, $replyNick, $replyComment) {
        $avatar = $this->getSetting('friend_avatar');
        $background = $this->getSetting('friend_background');
        
        $bgStyle = '';
        if (!empty($background)) {
            $bgStyle = "background-image: url('{$background}'); background-size: cover; background-position: center center;";
        }
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family: Arial, sans-serif;">
  <tr>
    <td align="center" style="background-color: #fccb1a; {$bgStyle} height: 320px; position: relative;">
      <table cellpadding="0" cellspacing="0" border="0" style="position: relative; height: 100%;">
        <tr>
          <td align="center" valign="bottom" style="padding-bottom: 76px;">
            <img src="{$avatar}" width="152" height="152" style="width: 152px; height: 152px; background-size: cover; border-radius: 1000px; display: block; position: relative; top: 150px;">
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td align="center" style="padding-top: 92px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px;">
        <tr>
          <td align="center" style="padding: 0 20px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="center">
                  <span style="font-size: 26px; font-family: PingFang-SC-Bold, PingFang-SC, Arial; font-weight: bold; color: #000000; line-height: 37px; text-align: center; display: block;">
                    嗨！您在 {$siteName} 博客里面的评论收到回复啦！
                  </span>
                </td>
              </tr>
              <tr>
                <td align="center" style="padding-top: 21px;">
                  <span style="font-size: 16px; font-family: PingFang-SC-Bold, PingFang-SC, Arial; font-weight: bold; color: #00000030; line-height: 22px; text-align: center; display: block;">
                    你之前在 {$siteName} 博客中的评论收到来自 {$replyNick} 的回复
                  </span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td align="center" style="padding: 34px 20px 0 20px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background: #F7F7F7; border-radius: 12px; min-height: 128px;">
              <tr>
                <td style="padding: 32px 16px;">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="padding: 0 30px 16px 30px;">
                        <span style="height: 22px; font-size: 16px; font-family: PingFang-SC-Bold, PingFang-SC, Arial; font-weight: bold; color: #fccb1a; line-height: 22px; display: block;">
                          {$parentNick}
                        </span>
                        <span style="margin-top: 6px; margin-right: 22px; font-size: 16px; font-family: PingFangSC-Regular, PingFang SC, Arial; font-weight: 400; color: #000000; line-height: 22px; display: block;">
                          {$parentComment}
                        </span>
                      </td>
                    </tr>
                  </table>
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="padding: 0 0 16px 0;">
                        <hr style="border: 1px dashed #e2a31c2e; height: 0px; margin: 0; width: 100%;">
                      </td>
                    </tr>
                  </table>
                  <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                      <td style="padding: 0 30px;">
                        <span style="height: 22px; font-size: 16px; font-family: PingFang-SC-Bold, PingFang-SC, Arial; font-weight: bold; color: #C5343E; line-height: 22px; display: block;">
                          {$replyNick}
                        </span>
                        <span style="margin-top: 6px; margin-right: 22px; font-size: 16px; font-family: PingFangSC-Regular, PingFang SC, Arial; font-weight: 400; color: #000000; line-height: 22px; display: block;">
                          {$replyComment}
                        </span>
                      </td>
                    </tr>
                  </table>
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 32px;">
                    <tr>
                      <td align="center">
                        <a href="{$postUrl}" style="min-width: 106px; height: 38px; background: #e2a31c38; border-radius: 32px; display: inline-block; text-decoration: none; line-height: 38px; text-align: center;">
                          <span style="color: #e2a31c; font-size: 16px;">查看详情</span>
                        </a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td align="center" style="padding-top: 34px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="center">
                  <span style="height: 17px; font-size: 12px; font-family: PingFangSC-Regular, PingFang SC, Arial; font-weight: 400; color: #00000045; line-height: 17px; display: block;">
                    这封邮件是由评论服务自动发出的，请到博客里回复哦
                  </span>
                  <a href="{$siteUrl}" style="height: 17px; font-size: 12px; font-family: PingFangSC-Regular, PingFang SC, Arial; font-weight: 400; color: #fccb1a; line-height: 17px; margin-top: 6px; text-decoration: none; display: inline-block;">
                    前往博客
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }
}
