<?php
class ControllerExtensionModuleGdeZakazy extends Controller {

    public function order_get_ajax() {
        if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            return;
        }

        $this->load->model('setting/setting');
        if (!$this->config->get('module_gde_zakazy_status')) {
            return;
        }
        $this->load->language('extension/module/gde_zakazy');
        $this->load->model('extension/module/gde_zakazy');
        $orderId = intval($this->request->post['order_id']);
        $error = null;
        $orderInfo = [];
        try {
            $orderInfo = $this->model_extension_module_gde_zakazy->getOrderInfo($this->config->get('module_gde_zakazy_api_token'), $orderId);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
        $orderInfo = array_merge([
            'order_id' => $orderId,
            'track' => null,
            'error' => $error,
        ], $orderInfo);

        $orderInfo['ajax'] = '/index.php?route=extension/module/gde_zakazy/order_add_ajax&user_token='.$this->session->data['user_token'];
        $orderInfo['ajaxArchive'] = '/index.php?route=extension/module/gde_zakazy/order_archive_ajax&user_token='.$this->session->data['user_token'];
        $orderInfo['apiStatus'] = $this->model_extension_module_gde_zakazy->getStatus($this->config->get('module_gde_zakazy_api_token'));
        $this->response->setOutput($this->load->view('extension/module/gde_zakazy_order', $orderInfo));
    }


    public function order_add_ajax() {
        if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            return;
        }

        $this->load->model('setting/setting');
        if (!$this->config->get('module_gde_zakazy_status')) {
            return;
        }
        $this->load->language('extension/module/gde_zakazy');
        $this->load->model('extension/module/gde_zakazy');
        $orderId = intval($this->request->post['order_id']);
        $track = strval($this->request->post['track']);
        $success = false;
        $error = null;
        try {
            $this->model_extension_module_gde_zakazy->addOrder(
                $this->config->get('module_gde_zakazy_api_token'),
                $orderId,
                $track,
                $this->config->get('module_gde_zakazy_fields')
            );
            $success = true;
        } catch (\Throwable $e) {
            if ($e->getMessage() == "Invalid input data\n- phone: Invalid phone") {
                try {
                    $this->model_extension_module_gde_zakazy->addOrder(
                        $this->config->get('module_gde_zakazy_api_token'),
                        $orderId,
                        $track,
                        array_diff($this->config->get('module_gde_zakazy_fields'), ['phone'])
                    );
                    $success = true;
                    $error = $this->language->get('order_tab_add_without_phone');
                } catch (\Throwable $e) {
                    $e->getMessage();
                }
            } else {
                $error = $e->getMessage();
            }
        }
        $orderInfo = [
            'order_id' => $orderId,
            'track' => ($success ? $track : null),
            'error' => $error,
        ];
        $orderInfo['ajax'] = '/index.php?route=extension/module/gde_zakazy/order_add_ajax&user_token='.$this->session->data['user_token'];
        $orderInfo['ajaxArchive'] = '/index.php?route=extension/module/gde_zakazy/order_archive_ajax&user_token='.$this->session->data['user_token'];
        $orderInfo['apiStatus'] = $this->model_extension_module_gde_zakazy->getStatus($this->config->get('module_gde_zakazy_api_token'));
        $this->response->setOutput($this->load->view('extension/module/gde_zakazy_order', $orderInfo));
    }

    public function order_archive_ajax() {
        if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            return;
        }

        $this->load->model('setting/setting');
        if (!$this->config->get('module_gde_zakazy_status')) {
            return;
        }
        $this->load->language('extension/module/gde_zakazy');
        $this->load->model('extension/module/gde_zakazy');
        $orderId = intval($this->request->post['order_id']);
        try {
            $orderInfo = $this->model_extension_module_gde_zakazy->archiveOrder($this->config->get('module_gde_zakazy_api_token'), $orderId);
        } catch (\Throwable $e) {
            $orderInfo = [
                'order_id' => $orderId,
                'track' => null,
                'error' => $e->getMessage(),
            ];
        }
        $orderInfo['ajax'] = '/index.php?route=extension/module/gde_zakazy/order_add_ajax&user_token='.$this->session->data['user_token'];
        $orderInfo['ajaxArchive'] = '/index.php?route=extension/module/gde_zakazy/order_archive_ajax&user_token='.$this->session->data['user_token'];
        $orderInfo['apiStatus'] = $this->model_extension_module_gde_zakazy->getStatus($this->config->get('module_gde_zakazy_api_token'));
        $this->response->setOutput($this->load->view('extension/module/gde_zakazy_order', $orderInfo));
    }

    public function cron() {
        $this->load->model('setting/setting');
        $this->response->setOutput('OK');
        if (!$this->config->get('module_gde_zakazy_status')) {
            return;
        }
        $this->load->model('extension/module/gde_zakazy');
        $this->model_extension_module_gde_zakazy->updateAllOrders($this->config->get('module_gde_zakazy_api_token'));
        $this->response->setOutput('OK');
    }

}