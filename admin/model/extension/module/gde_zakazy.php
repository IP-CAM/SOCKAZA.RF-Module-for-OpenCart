<?php

class ModelExtensionModuleGdeZakazy extends Model {

    const BASE = 'https://xn--80aahefmcw9m.xn--p1ai/api/v1/';

    protected function request($token, $method, $path, $data = []) {
        $ch = curl_init();
        if (!is_array($data)) {
            $data = [];
        }
        if ($method == 'GET') {
            $data['token'] = $token;
            $path .= '?'.http_build_query($data);
        } else {
            $path .= '?'.http_build_query(['token' => $token]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; API client)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, self::BASE.$path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        $return = [
            'code' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            'data' => json_decode($response, true),
        ];
        curl_close($ch);
        return $return;
    }

    public function checkApiToken($token) {
        $data = $this->request($token, 'GET', 'track/0');
        if ($data['code'] != 404 || !is_array($data['data']) || !isset($data['data']['message']) || $data['data']['message'] != 'Track not found') {
            return false;
        }
        return true;
    }

    public function getStatus($token) {
        if (!$token) {
            return [
                'status' => false,
                'canAdd' => false,
            ];
        }
        $data = $this->request($token, 'GET', '');
        if ($data['code'] != 200 || !is_array($data['data'])) {
            return [
                'status' => false,
                'canAdd' => false,
            ];
        }
        $limit = ($data['data']['opencartSubscription'] === null ? $data['data']['opencartLimit'] : null);
        $expired = ($data['data']['opencartSubscription'] !== null ? (new DateTime($data['data']['opencartSubscription']))->format('d.m.Y') : null);
        return [
            'status' => true,
            'limit' => $limit,
            'expired' => $expired,
            'canAdd' => ($limit > 0 || $limit === null),
        ];
    }

    public function addOrder($token, $orderId, $track, $fields) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "order_gdezakazy` WHERE order_id = $orderId AND status = 'archive'");
        $orderExistsQuery = $this->db->query("SELECT COUNT(*) as cnt FROM `" . DB_PREFIX . "order_gdezakazy` WHERE order_id = $orderId");
        if ($orderExistsQuery->row['cnt'] > 0) {
            throw new InvalidArgumentException('Order is already exist');
        }
        $orderQuery = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = $orderId");
        if (!$orderQuery->row) {
            throw new InvalidArgumentException('Order not found');
        }

        $phone = preg_replace('/\D+/', '', $orderQuery->row['telephone']);
        if (strlen($phone) > 10) {
            $phone = preg_replace('/^[87]/', '', $phone);
        }
        $requestData = $this->request($token, 'POST', 'track', [
            'track_code' => $track,
            'phone' => (in_array('phone', $fields) ? '8'.$phone : ''),
            'email' => (in_array('email', $fields) ? $orderQuery->row['email'] : ''),
            'name' => (in_array('name', $fields) ? $orderQuery->row['firstname'] : ''),
            'order_number' => (in_array('order_number', $fields) ? $orderQuery->row['order_id'] : ''),
            'order_amount' => (in_array('order_amount', $fields) ? $orderQuery->row['total'] : ''),
        ]);
        if ($requestData['code'] != 200) {
            $message = isset($requestData['data']['message']) ? $requestData['data']['message'] : 'Request error';
            if (isset($requestData['data']['errors']) && is_array($requestData['data']['errors'])) {
                $message .= "\n".implode("\n", array_map(function ($v) {
                    return '- '.$v['field'].': '.$v['message'];
                }, $requestData['data']['errors']));
            }
            throw new Exception($message);
        }
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_gdezakazy` SET order_id = $orderId, track = '" . $this->db->escape($track) . "'");
        if ($this->config->get('module_gde_zakazy_tracking_status')) {
            $this->updateOrderStatus($orderId, 'tracking', [
                'track' => $track,
            ]);
        }
    }

    public function updateOrderInfo($token, $orderId) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_gdezakazy` WHERE order_id = $orderId");
        if (!$query->row) {
            return;
        }
        if ($query->row['is_active'] == 0 || ($query->row['updated_at'] !== null && abs(time() - $query->row['updated_at']) < 15 * 60)) {
            return;
        }
        $requestData = $this->request($token, 'GET', 'track/'.$query->row['track']);
        if ($requestData['code'] != 200) {
            $message = isset($requestData['data']['message']) ? $requestData['data']['message'] : 'Request error';
            throw new Exception($message);
        }
        $isActive = 1;
        $isProblem = ($requestData['data']['was_problem'] ? 1 : 0);
        $newStatus = $this->db->escape($requestData['data']['status']);
        if ($newStatus == 'archive' || $newStatus == 'delivered') {
            $isActive = 0;
        }
        $updatedAt = strtotime($requestData['data']['updated_at']);
        $error = $requestData['data']['had_error'] ? 'ERROR' : '';
        $now = time();
        $this->db->query("UPDATE `" . DB_PREFIX . "order_gdezakazy` SET updated_at = $now, status = '$newStatus', is_problem = $isProblem, is_active = $isActive, on_server_updated_at = '$updatedAt', error = '$error' WHERE order_id = $orderId");
        if ($newStatus == 'delivered' && $query->row['status'] != 'delivered' && $isProblem) {
            $this->updateOrderStatus($orderId, 'problem_success', $query->row);
        }
        if ($newStatus == 'delivered' && $query->row['status'] != 'delivered' && !$isProblem) {
            $this->updateOrderStatus($orderId, 'success', $query->row);
        }
        if ($newStatus == 'department' && $query->row['status'] != 'department') {
            $this->updateOrderStatus($orderId, 'department', $query->row);
        }
        if ($newStatus == 'problem' && $query->row['status'] != 'problem') {
            $this->updateOrderStatus($orderId, 'problem', $query->row);
        }
        if ($error && !$query->row['error']) {
            $this->updateOrderStatus($orderId, 'error', $query->row);
        }
    }

    public function getOrderInfo($token, $orderId, $catch = true) {
        $error = null;
        try {
            $this->updateOrderInfo($token, $orderId);
        } catch (\Throwable $e) {
            if (!$catch) {
                throw $e;
            }
            $error = $e->getMessage();
        }
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_gdezakazy` WHERE order_id = $orderId");
        return array_merge($query->row, [
            'error' => $error
        ]);
    }

    public function archiveOrder($token, $orderId) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_gdezakazy` WHERE order_id = $orderId");
        if (!$query->row) {
            throw new \Exception('Track not found');
        }
        $track = $query->row;
        $requestData = $this->request($token, 'POST', 'track/'.$track['track'].'/stop');
        if ($requestData['code'] != 200 || !isset($requestData['data']['success']) || $requestData['data']['success'] != true) {
            $message = isset($requestData['data']['message']) ? $requestData['data']['message'] : 'Request error';
            throw new Exception($message);
        }
        $this->db->query("UPDATE `" . DB_PREFIX . "order_gdezakazy` SET status = 'archive', is_active = 0 WHERE order_id = $orderId");
        $track['status'] = 'archive';
        return $track;
    }

    public function updateAllOrders($token) {
        $query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order_gdezakazy` WHERE is_active = 1");
        foreach ($query->rows as $row) {
            try {
                $this->updateOrderInfo($token, $row['order_id']);
            } catch (\Throwable $e) {
            }
        }
    }

    protected function updateOrderStatus($order_id, $status, $order) {
        $status_id = $this->config->get("module_gde_zakazy_{$status}_status");
        if (!$status_id) {
            return;
        }
        $this->load->model('checkout/order');
        $message = strtr($this->config->get("module_gde_zakazy_{$status}_notify_text"), [
            '[track]' => $order['track'],
        ]);
        $this->model_checkout_order->addOrderHistory($order_id, $status_id, $message, boolval($this->config->get("module_gde_zakazy_{$status}_notify")));
    }

}