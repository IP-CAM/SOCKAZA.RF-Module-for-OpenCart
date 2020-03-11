<?php

class ControllerExtensionModuleGdeZakazy extends Controller {
	private $error = array();

	protected function migrations() {
        $result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order_gdezakazy` WHERE Field = 'status'");
        if (strpos($result->rows[0]['Type'], 'notregistered') === false) {
            $sql = "ALTER TABLE `" . DB_PREFIX . "order_gdezakazy` CHANGE `status` `status` ENUM('new','ontheway','problem','department','delivered','archive','notregistered') NULL DEFAULT NULL;";
            $this->db->query($sql);
        }
    }

	public function index() {
	    $this->migrations();

		$this->load->language('extension/module/gde_zakazy');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
        $this->load->model('extension/module/gde_zakazy');
        $this->load->model('localisation/order_status');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_gde_zakazy', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'));
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/gde_zakazy', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
		$data['action'] = $this->url->link('extension/module/gde_zakazy', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['cron_action'] = HTTPS_CATALOG . 'index.php?route=extension/module/gde_zakazy/cron';

        if (isset($this->request->post['module_gde_zakazy_status'])) {
            $data['gde_zakazy_status'] = $this->request->post['module_gde_zakazy_status'];
        } else {
            $data['gde_zakazy_status'] = $this->config->get('module_gde_zakazy_status');
        }
        if (isset($this->request->post['module_gde_zakazy_api_token'])) {
            $data['gde_zakazy_api_token'] = $this->request->post['module_gde_zakazy_api_token'];
        } else {
            $data['gde_zakazy_api_token'] = $this->config->get('module_gde_zakazy_api_token');
        }
        foreach (['error', 'success', 'department', 'problem', 'problem_success', 'tracking'] as $status) {
            if (isset($this->request->post["module_gde_zakazy_{$status}_status"])) {
                $data["gde_zakazy_{$status}_status"] = $this->request->post["module_gde_zakazy_{$status}_status"];
            } else {
                $data["gde_zakazy_{$status}_status"] = $this->config->get("module_gde_zakazy_{$status}_status");
            }
            if (isset($this->request->post["module_gde_zakazy_{$status}_notify"])) {
                $data["gde_zakazy_{$status}_notify"] = $this->request->post["module_gde_zakazy_{$status}_notify"];
            } else {
                $data["gde_zakazy_{$status}_notify"] = $this->config->get("module_gde_zakazy_{$status}_notify");
            }
            if (isset($this->request->post["module_gde_zakazy_{$status}_notify_text"])) {
                $data["gde_zakazy_{$status}_notify_text"] = $this->request->post["module_gde_zakazy_{$status}_notify_text"];
            } else {
                $data["gde_zakazy_{$status}_notify_text"] = $this->config->get("module_gde_zakazy_{$status}_notify_text");
            }
        }
        if (isset($this->request->post['module_gde_zakazy_fields'])) {
            $data['gde_zakazy_fields'] = $this->request->post['module_gde_zakazy_fields'];
        } else {
            $data['gde_zakazy_fields'] = $this->config->get('module_gde_zakazy_fields');
        }
        if (!is_array($data['gde_zakazy_fields'])) {
            $data['gde_zakazy_fields'] = ['tracking'];
        }

        $data['fields'] = [
            'tracking' => $this->language->get('label_field_tracking'),
            'phone' => $this->language->get('label_field_phone'),
            'email' => $this->language->get('label_field_email'),
            'name' => $this->language->get('label_field_name'),
            'order_number' => $this->language->get('label_field_order_number'),
            'order_amount' => $this->language->get('label_field_order_amount'),
        ];

		$data['error'] = $this->error;

        $data['statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/gde_zakazy', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/gde_zakazy')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

        if (intval($this->request->post['module_gde_zakazy_status'])) {
            $apiToken = trim($this->request->post['module_gde_zakazy_api_token']);
            if (strlen($apiToken) < 10) {
                $this->error['gde_zakazy_api_token'] = $this->language->get('gde_zakazy_api_token_empty');
            } elseif (!$this->model_extension_module_gde_zakazy->checkApiToken($apiToken)) {
                $this->error['gde_zakazy_api_token'] = $this->language->get('gde_zakazy_api_token_invalid');
            }
            $fields = isset($this->request->post['module_gde_zakazy_fields']) ? $this->request->post['module_gde_zakazy_fields'] : null;
            if (!is_array($fields)) {
                $this->error['gde_zakazy_fields'] = $this->language->get('gde_zakazy_fields_empty');
            } elseif (!in_array('tracking', $fields)) {
                $this->error['gde_zakazy_fields'] = $this->language->get('gde_zakazy_fields_tracking');
            } elseif (count(array_intersect(['email', 'phone'], $fields)) == 0) {
                $this->error['gde_zakazy_fields'] = $this->language->get('gde_zakazy_fields_contacts');
            }
        }

		return !$this->error;
	}

	public function order(&$route, &$data, &$template) {
        $this->load->model('setting/setting');
        if (!$this->config->get('module_gde_zakazy_status')) {
            return;
        }
        $this->load->language('extension/module/gde_zakazy');
        $data['tabs'][] = array(
            'code'    => 'gde_zakazy',
            'title'   => $this->language->get('order_tab_title'),
            'content' => $this->load->view('extension/module/gde_zakazy_order', [
                'orderId' => intval($data['order_id']),
                'ajax' => '/index.php?route=extension/module/gde_zakazy/order_get_ajax&user_token='.$this->session->data['user_token']
            ])
        );
    }

	public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_gdezakazy` ( ".
            "`order_id` INT NOT NULL , ".
            "`is_active` TINYINT NOT NULL DEFAULT '1' , ".
            "`track` VARCHAR(64) NOT NULL , ".
            "`updated_at` INT NULL , ".
            "`status` ENUM('new','ontheway','problem','department','delivered','archive','notregistered') NULL , ".
            "`is_problem` TINYINT NOT NULL DEFAULT '0' , ".
            "`on_server_updated_at` INT NULL , ".
            "`error` VARCHAR(255) NULL , ".
            "PRIMARY KEY (`order_id`)) ENGINE = InnoDB;";
        $this->db->query($sql);

		$this->load->model('setting/event');

        $this->model_setting_event->deleteEventByCode('admin_module_gde_zakazy_order');
		$this->model_setting_event->addEvent('admin_module_gde_zakazy_order', 'admin/view/sale/order_info/before', 'extension/module/gde_zakazy/order');
	}

	public function uninstall() {
        $sql = "DROP TABLE IF EXISTS `" . DB_PREFIX . "order_gdezakazy`;";
        $this->db->query($sql);
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('admin_module_gde_zakazy_order');
	}
	
}