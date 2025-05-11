<?php
return [
    // Cấu hình kết nối cơ sở dữ liệu
    'db_host' => 'localhost', // Host của cơ sở dữ liệu (thường là 'localhost' nếu trên máy cục bộ)
    'db_user' => 'your_db_username', // Tên người dùng cơ sở dữ liệu
    'db_pass' => 'your_db_password', // Mật khẩu cơ sở dữ liệu
    'db_name' => 'your_db_name', // Tên cơ sở dữ liệu

    // Cấu hình email (cho PHPMailer)
    'mail_user' => 'your-email@gmail.com', // Email gửi OTP (ví dụ: Gmail)
    'mail_pass' => 'your-app-password', // Mật khẩu ứng dụng (App Password) nếu dùng Gmail với 2FA
    // Nếu không dùng Gmail, thay bằng thông tin SMTP của nhà cung cấp email khác
    'mail_host' => 'smtp.gmail.com', // Host SMTP (mặc định là smtp.gmail.com cho Gmail)
    'mail_port' => 587, // Cổng SMTP (587 cho TLS, 465 cho SSL)
    'mail_smtpsecure' => 'tls', // Phương thức mã hóa (tls hoặc ssl)

    // Các cấu hình khác (tùy chọn)
    'site_url' => 'http://localhost/your_project', // URL của trang web (thay bằng URL thực tế)
];