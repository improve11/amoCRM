<?php
// submit.php

// Настройки AmoCRM
define('AMOCRM_DOMAIN', 'vlasav227'); 
define('AMOCRM_CLIENT_ID', '11688066');
define('AMOCRM_CLIENT_SECRET', '32025602');
define('AMOCRM_REDIRECT_URI', 'http://81.177.166.244:8081/tz/'); 
define('AMOCRM_ACCESS_TOKEN', 'your_access_token'); // Получить через OAuth

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из формы
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $price = htmlspecialchars($_POST['price']);
    $time_spent = isset($_POST['time_spent']) ? 1 : 0;

    // Подготовка данных для контакта
    $contact = [
        'name' => $name,
        'custom_fields' => [
            [
                'id' =>  399577, // ID поля Email в AmoCRM
                'values' => [
                    ['value' => $email]
                ]
            ],
            [
                'id' => 399575, // ID поля Телефон в AmoCRM
                'values' => [
                    ['value' => $phone]
                ]
            ]
        ]
    ];

    // Создание контакта через API
    $contactId = createContact($contact);

    if ($contactId) {
        // Подготовка данных для сделки
        $deal = [
            'name' => "Заявка от $name",
            'price' => $price,
            'status_id' => 'status_id_71111578',
            'custom_fields' => [
                [
                    'id' => 399717, // ID дополнительного поля
                    'values' => [
                        ['value' => $time_spent]
                    ]
                ]
            ],
            'contacts_id' => [$contactId]
        ];

        // Создание сделки через API
        $dealId = createDeal($deal);

        if ($dealId) {
            echo "Заявка успешно отправлена!";
        } else {

            echo "Ошибка при создании сделки.";
        }
    } else {
        echo "Ошибка при создании контакта.";
    }
} else {
    echo "Неверный метод запроса.";
}

// Функция для создания контакта
function createContact($contactData) {
    $url = "https://" . AMOCRM_DOMAIN . ".amocrm.ru/api/v4/contacts";

    $response = sendAmoCRMRequest('POST', $url, $contactData);

    if ($response && isset($response['_embedded']['contacts'][0]['id'])) {
        return $response['_embedded']['contacts'][0]['id'];
    }
    return false;
}

// Функция для создания сделки
function createDeal($dealData) {
    $url = "https://" . AMOCRM_DOMAIN . ".amocrm.ru/api/v4/leads";

    $response = sendAmoCRMRequest('POST', $url, [$dealData]);

    if ($response && isset($response['_embedded']['leads'][0]['id'])) {
        return $response['_embedded']['leads'][0]['id'];
    }
    return false;
}

// Общая функция для отправки запросов к AmoCRM
function sendAmoCRMRequest($method, $url, $data = []) {
    $ch = curl_init();

    $headers = [
        "Authorization: Bearer " . AMOCRM_ACCESS_TOKEN,
        "Content-Type: application/json"
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($result, true);
    } else {
        // Обработка ошибок
        error_log("AmoCRM API Error: " . $result);
        return false;
    }

    curl_close($ch);
}
?>
