<?php
// includes/VNPay.php - Class tích hợp thanh toán VNPay

class VNPay {
    private $vnp_TmnCode;
    private $vnp_HashSecret;
    private $vnp_Url;
    private $vnp_ReturnUrl;
    
    public function __construct() {
        $this->vnp_TmnCode = VNPAY_TMN_CODE;
        $this->vnp_HashSecret = VNPAY_HASH_SECRET;
        $this->vnp_Url = VNPAY_URL;
        $this->vnp_ReturnUrl = VNPAY_RETURN_URL;
    }
    
    // Tạo URL thanh toán
    public function createPaymentUrl($orderData) {
        $vnp_TxnRef = $orderData['order_code']; // Mã đơn hàng
        $vnp_OrderInfo = 'Thanh toán đơn hàng ' . $vnp_TxnRef;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $orderData['total'] * 100; // VNPay yêu cầu số tiền x100
        $vnp_Locale = 'vn';
        $vnp_BankCode = $orderData['bank_code'] ?? '';
        $vnp_IpAddr = $this->getClientIp();
        
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $this->vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );
        
        if (!empty($vnp_BankCode)) {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        
        // Sắp xếp dữ liệu theo key
        ksort($inputData);
        
        $query = "";
        $i = 0;
        $hashdata = "";
        
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url = $this->vnp_Url . "?" . $query;
        
        if (isset($this->vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        
        return $vnp_Url;
    }
    
    // Xác thực callback từ VNPay
    public function verifyReturnUrl($inputData) {
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        
        // Sắp xếp dữ liệu
        ksort($inputData);
        
        $hashdata = "";
        $i = 0;
        
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
        
        if ($secureHash === $vnp_SecureHash) {
            return [
                'valid' => true,
                'response_code' => $inputData['vnp_ResponseCode'],
                'transaction_no' => $inputData['vnp_TransactionNo'] ?? '',
                'bank_code' => $inputData['vnp_BankCode'] ?? '',
                'amount' => $inputData['vnp_Amount'] / 100,
                'order_code' => $inputData['vnp_TxnRef']
            ];
        }
        
        return ['valid' => false, 'message' => 'Chữ ký không hợp lệ'];
    }
    
    // Lấy IP client
    private function getClientIp() {
        $ipaddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        
        return $ipaddress;
    }
    
    // Lưu giao dịch VNPay
    public function saveTransaction($data) {
        require_once __DIR__ . '/Database.php';
        $db = new Database();
        
        $sql = "INSERT INTO vnpay_transactions (order_id, transaction_no, bank_code, 
                amount, order_info, transaction_status, response_code) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['order_id'],
            $data['transaction_no'] ?? '',
            $data['bank_code'] ?? '',
            $data['amount'],
            $data['order_info'] ?? '',
            $data['transaction_status'] ?? 'pending',
            $data['response_code'] ?? ''
        ];
        
        return $db->query($sql, $params);
    }
    
    // Danh sách mã ngân hàng VNPay hỗ trợ
    public function getBankList() {
        return [
            'VNPAYQR' => 'Thanh toán qua QR Code',
            'VNBANK' => 'Thanh toán qua ATM/Tài khoản nội địa',
            'INTCARD' => 'Thanh toán qua thẻ quốc tế',
            'VIETCOMBANK' => 'Vietcombank',
            'VIETINBANK' => 'VietinBank',
            'BIDV' => 'BIDV',
            'AGRIBANK' => 'Agribank',
            'TECHCOMBANK' => 'Techcombank',
            'MBBANK' => 'MB Bank',
            'VPBANK' => 'VPBank',
            'SACOMBANK' => 'Sacombank',
            'ACB' => 'ACB',
            'SHB' => 'SHB',
            'TPBANK' => 'TPBank'
        ];
    }
    
    // Giải thích mã response từ VNPay
    public function getResponseMessage($responseCode) {
        $messages = [
            '00' => 'Giao dịch thành công',
            '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)',
            '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng',
            '10' => 'Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch',
            '12' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa',
            '13' => 'Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP). Xin quý khách vui lòng thực hiện lại giao dịch',
            '24' => 'Giao dịch không thành công do: Khách hàng hủy giao dịch',
            '51' => 'Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch',
            '65' => 'Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày',
            '75' => 'Ngân hàng thanh toán đang bảo trì',
            '79' => 'Giao dịch không thành công do: KH nhập sai mật khẩu thanh toán quá số lần quy định. Xin quý khách vui lòng thực hiện lại giao dịch',
            '99' => 'Các lỗi khác (lỗi còn lại, không có trong danh sách mã lỗi đã liệt kê)'
        ];
        
        return $messages[$responseCode] ?? 'Lỗi không xác định';
    }
}
?>