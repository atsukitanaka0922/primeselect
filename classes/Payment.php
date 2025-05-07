<?php
class Payment {
    private $conn;
    private $table_name = "payments";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // クレジットカード決済処理（デモ用）
    public function processCreditCard($order_id, $card_number, $expiry, $cvv) {
        // 実際の実装ではStripeやPayPalなどのAPIを使用
        
        // デモ用の処理成功フラグ
        $success = true;
        
        if($success) {
            // 支払い情報の記録
            $query = "INSERT INTO " . $this->table_name . " SET order_id = ?, payment_method = ?, payment_status = ?, transaction_id = ?";
            $stmt = $this->conn->prepare($query);
            
            $payment_method = "credit_card";  
            $payment_status = "completed";
            $transaction_id = "DEMO_" . uniqid();
            
            $stmt->bindParam(1, $order_id);
            $stmt->bindParam(2, $payment_method);
            $stmt->bindParam(3, $payment_status);
            $stmt->bindParam(4, $transaction_id);
            
            if($stmt->execute()) {
                // 注文ステータス更新
                $query = "UPDATE orders SET status = 'processing' WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $order_id);
                $stmt->execute();
                
                return true;
            }
        }
        
        return false;
    }
    
    // 銀行振込処理
    public function processBankTransfer($order_id) {
        // 支払い情報の記録
        $query = "INSERT INTO " . $this->table_name . " SET order_id = ?, payment_method = ?, payment_status = ?, transaction_id = ?";
        $stmt = $this->conn->prepare($query);
        
        $payment_method = "bank_transfer";  
        $payment_status = "pending";  // 入金確認待ち
        $transaction_id = "BT_" . uniqid();
        
        $stmt->bindParam(1, $order_id);
        $stmt->bindParam(2, $payment_method);
        $stmt->bindParam(3, $payment_status);
        $stmt->bindParam(4, $transaction_id);
        
        if($stmt->execute()) {
            // 注文ステータス更新（保留のまま）
            $query = "UPDATE orders SET status = 'pending' WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $order_id);
            $stmt->execute();
            
            return true;
        }
        
        return false;
    }
    
    // 代金引換処理
    public function processCOD($order_id) {
        // 支払い情報の記録
        $query = "INSERT INTO " . $this->table_name . " SET order_id = ?, payment_method = ?, payment_status = ?, transaction_id = ?";
        $stmt = $this->conn->prepare($query);
        
        $payment_method = "cod";  
        $payment_status = "pending";  // 配達時に支払い
        $transaction_id = "COD_" . uniqid();
        
        $stmt->bindParam(1, $order_id);
        $stmt->bindParam(2, $payment_method);
        $stmt->bindParam(3, $payment_status);
        $stmt->bindParam(4, $transaction_id);
        
        if($stmt->execute()) {
            // 注文ステータス更新（処理中）
            $query = "UPDATE orders SET status = 'processing' WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $order_id);
            $stmt->execute();
            
            return true;
        }
        
        return false;
    }
}
?>