<?php
// Heading
$_['heading_title']    = 'ГДЕЗАКАЗЫ.РФ';

// Text
$_['text_module']      = 'Модули';
$_['text_success']     = 'Настройки успешно изменены!';
$_['text_edit']        = 'Настройки модуля';
$_['entry_status']     = 'Статус модуля';
$_['gde_zakazy_version'] = 'Текущая версия 1.4';
$_['gde_zakazy_diagnosis'] = 'диагностика';

// Labels
$_['label_gde_zakazy_api_token']        = 'API токен';
$_['label_gde_zakazy_fields']           = 'Поля для передачи';
$_['label_gde_zakazy_success_status']   = 'Если посылка в "Доставлены" переносить в статус';
$_['label_gde_zakazy_error_status']     = 'Переносить в статус при ошибке';
$_['label_gde_zakazy_department_status'] = 'Если посылка "В отделении" переносить в статус';
$_['label_gde_zakazy_problem_status']   = 'Если посылка в "Проблемные" переносить в статус';
$_['label_gde_zakazy_problem_success_status'] = 'Если посылка хоть раз попадала в "Проблемные", а сейчас в "Доставлены" переносить в статус';
$_['label_gde_zakazy_tracking_status']  = 'Если заказу присвоен трекинг-номер, то переносить в статус';
$_['label_gde_zakazy_notify']           = 'Уведомить покупателя';
$_['label_gde_zakazy_notify_text_note'] = 'Используйте переменную <code>[track]</code>';
$_['label_field_tracking']              = 'Трекинг';
$_['label_field_phone']                 = 'Телефон';
$_['label_field_email']                 = 'E-mail';
$_['label_field_name']                  = 'Имя';
$_['label_field_order_number']          = 'Номер заказа';
$_['label_field_order_amount']          = 'Сумма заказа';
$_['label_status_disabled']             = '[Выкл]';

// Errors
$_['gde_zakazy_api_token_empty']   = 'Неправильный формат API токена';
$_['gde_zakazy_api_token_invalid'] = 'Не удалось соединиться с сервером';
$_['gde_zakazy_fields_empty']      = 'Должны быть выделены для передачи трекинг и телефон или e-mail';
$_['gde_zakazy_fields_tracking']   = 'Трекинг обязателен для передачи';
$_['gde_zakazy_fields_contacts']   = 'Должен быть выделен телефон или e-mail';

// CRON
$_['text_cron_prepend'] = 'Для настройки обновления статусов по расписанию, добавьте следующий URL в планировщик:';
$_['text_cron_append']  = '<br /><br />1) Данный модуль уведомляет ваших клиентов о прохождении посылки на каждом ее этапе маршрута. Уменьшает количество возвратов за счет своевременных уведомлений.<br /><br />2) Для получения ключа к API необходимо зарегистрироваться на сайте ГДЕЗАКАЗЫ.РФ и сгенерировать ключ в разделе Настройки -> Настройки API.<br /><br />3) Важно! Не забудьте также настроить имя магазина, настройки email, sms для администратора магазина и ваших клиентов.<br /><br />4) Для бесплатного тарифа доступно 20 трекингов в месяц.';

// Order page
$_['order_tab_prepend_note']     = <<<EOT
<p><b>Отслеживание посылок, уведомления клиентам, смена статуса.</b></p>
<p>Для настройки модуля перейдите в раздел: Модули/Расширения - ГДЕЗАКАЗЫ.РФ</p>
<p>Полную информацию отслеживания трекинга вы можете посмотреть в личном кабинете на сайте <a href="https://гдезаказы.рф/" target="_blank">ГДЕЗАКАЗЫ.РФ</a></p>
EOT;
$_['order_tab_status_label']     = 'Подключение по API';
$_['order_tab_status_enabled']   = 'Подключено';
$_['order_tab_status_disabled']  = 'Нет подключения. Перейдите в личный кабинет на сайт <a href="https://гдезаказы.рф/" target="_blank">ГДЕЗАКАЗЫ.РФ</a> для генерации ключа API, который необходимо ввести в настройках модуля Модули/Расширения - ГДЕЗАКАЗЫ.РФ';
$_['order_tab_limits_label']     = '';
$_['order_tab_limits_free']      = 'Ограничения использования Бесплатный тариф, осталось трекингов: %limit%</p><p>Выберите подписку для снятия ограничения по количеству отслеживаемых трекингов в личном кабинете на сайте <a href="https://xn--80aahefmcw9m.xn--p1ai/api/settings" target="_blank">ГДЕЗАКАЗЫ.РФ</a>';
$_['order_tab_limits_paid']      = 'Тариф: подписка действует до %expired%';
$_['order_tab_title']            = 'ГДЕЗАКАЗЫ.РФ';
$_['order_tab_form_caption']     = 'Добавить трекинг к этому заказу:';
$_['order_tab_label_track']      = 'Трекинг номер Почта России';
$_['order_tab_form_submit']      = 'Добавить';
$_['order_tab_track']            = 'Трек отслеживается';
$_['order_tab_archive_btn']      = 'Перенести в архив';
$_['order_tab_archive_confirm']  = 'Вы уверены что хотите перенести отслеживание в архив?';

$_['order_tab_add_without_phone']  = 'Ошибка в телефоне. Трек добавлен без телефона';

$_['order_tab_track']             = 'Трек отслеживается';
$_['order_tab_updated']           = 'Последнее обновление';
$_['order_tab_status']            = 'Текущий статус';
$_['order_tab_not_updated']       = 'Информация о статусе еще не обновлялась';
$_['order_tab_not_obtained']      = 'Информация с сервера еще не поступала';
$_['order_tab_status_new']        = 'Новый';
$_['order_tab_status_notregistered'] = 'Незарегистрированный трекинг';
$_['order_tab_status_ontheway']   = 'В пути';
$_['order_tab_status_problem']    = 'Проблема';
$_['order_tab_status_department'] = 'В отделении';
$_['order_tab_status_delivered']  = 'Доставлено';
$_['order_tab_status_archive']    = 'Архив';
