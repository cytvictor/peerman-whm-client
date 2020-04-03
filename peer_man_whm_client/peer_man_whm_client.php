<?php
/**
 * Authorization client for WHMCS
 * @author Victor, CHEN <m@i.ls>
 * Disclaimer: This source code file is confidential and is limited for 
 * internal development purposes solely. Any part of this file is 
 * strictly prohibited should not be disclosed.
 */

include '../../../vendor/autoload.php';

require_once 'common_functions.php';

function peer_man_whm_client_config() {
  return [
    'name' => 'Peering Manager - WHMCS Client <span class="label completed">Beta</span>',
    'description' => 'Authorization client of Peering Manager for WHMCS billing system.',
    'version' => '0.1',
    'author' => 'Martian <a href="mailto:m@i.ls">m@i.ls</a>',
    'fields' => [
      'frontend_address' => [
        'FriendlyName' => '前端地址',
        'Type' => 'text',
        'Size' => '200',
        'Description' => '带 <code>https://</code>，不带最后的 <code>/</code>',
      ],
      'backend_address' => [
        'FriendlyName' => '后端地址',
        'Type' => 'text',
        'Size' => '500',
        'Description' => '带 <code>http://</code>，不带最后的 <code>/</code>',
      ],
      'product_ids' => [
        'FriendlyName' => 'Product IDs',
        'Type' => 'text',
        'Size' => '500',
        'Description' => '开启此功能的产品 ID，以半角逗号 "," 分隔。例如: "121:4,|37:4,6|9:6"（不包含引号）。',
      ],
      'identifier' => [
        'FriendlyName' => ' Identidier',
        'Type' => 'text',
        'Size' => '200',
        'Description' => '后端通信标识符',
      ],
      'product_router_kv' => [
        'FriendlyName' => ' 产品:路由对应 ID',
        'Type' => 'text',
        'Size' => '200',
        'Description' => '例如产品 ID 1 对应路由 ID 3，产品 ID 12 对应 路由 ID 1, 填写："1:3|12:1"（不包含引号）。',
      ],
    ]
  ];
}

function peer_man_whm_client_clientarea($vars) {
  if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'request_authorization_url') {
    header('Content-Type', 'application/json');

    $clients_products = localAPI('GetClientsProducts', [
      'clientid' => $_SESSION['uid']
    ]);

    $match_flag = false;

    foreach ($clients_products['products']['product'] as $item) {
      if ($item['id'] == $_REQUEST['product_id'] && ($item['dedicatedip'] == $_REQUEST['ip_address'] || false)) {
        $match_flag = true;
      break;
      }
    }

    if (!$match_flag) {
      echo json_encode([
        'success' => false,
        'message' => '无法定位产品。',
      ]);
      exit;
    }

    $http_client = new \GuzzleHttp\Client();
    $token = v4();
    try {
      $resp = $http_client->post($vars['backend_address'] . '/admin/token?identifier=' . $vars['identifier'], [
        'connect_timeout' => 10,
        'timeout' => 10,
        'json' => [
          'token' => $token,
          'clientid' => $_SESSION['uid'],
          'router_id' => intval(parseRouterId($vars['product_router_kv'])[$_REQUEST['product_id']]),
          'remote_address' => $_REQUEST['ip_address'],
        ]
      ]);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      logModuleCall('peer_man_whm_client', 'RetrieveToken', $vars, $e, '', []);
      if ($e->hasResponse()) {
        $data = json_decode($e->getResponse()->getBody(), true);
        echo json_encode([
        'success' => false,
        'message' => '无法初始化控制面板。Err: ' . (isset($data['error']['message']) ? $data['error']['message'] : '无法收到控制面板确认信。'),
        ]);
      }
      exit;
    }



    $data = json_decode($resp->getBody(), true);
    logModuleCall('peer_man_whm_client', 'RetrieveToken', $vars, $resp, '', []);

    if ($data['success']) {
      echo json_encode([
        'success' => true,
        'bgp_configure_url' => $vars['frontend_address'] . '/?login_challenge=' . $token,
      ]); 
    } else {
      echo json_encode([
        'success' => false,
        'message' => isset($data['error']['message']) ? $data['error']['message'] : '无法收到控制面板确认信。'
      ]); 
    }

    exit;

  } else {
    $clients_products = localAPI('GetClientsProducts', [
      'clientid' => $_SESSION['uid']
    ]);

    $available_products = parseAvailabelproducts($vars['product_ids']);

    $products = $clients_products['products']['product'];

    $bgp_products = [];

    foreach ($products as $key => $value) {
      if ($value['status'] == 'Active') {
        if (in_array($value['pid'], $available_products['product_ids'])) {
          array_push($bgp_products, [
            'product_id' => $value['id'],
            'product_name' => $value['name'],
            'product_domain' => $value['domain'],
            'ipaddr' => [
              [
                'address' => $value['dedicatedip'],
                'bgp_available' => (preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/i", $value['dedicatedip']) ? ($available_products['products'][$value['pid']]['ip4'] ? true : false) : ($available_products['products'][$value['pid']]['ip6'] ? true : false) ),
              ],
              [
                'address' => $value['dedicatedip'],
                'bgp_available' => !(preg_match("/^([0-9]{1,3}\.){3}[0-9]{1,3}$/i", $value['dedicatedip']) ? ($available_products['products'][$value['pid']]['ip4'] ? true : false) : ($available_products['products'][$value['pid']]['ip6'] ? true : false) ),
              ],
            ],
          ]);
        }
      }
    }

    return [
      'pagetitle' => 'BGP Sessions',
      'breadcrumb' => ['index.php?m=peer_man_whm_client' => 'BGP Sessions'],
      'templatefile' => 'clientarea',
      'requirelogin' => true,
      'forcessl' => false,
      'vars' => [
        'bgp_products' => $bgp_products,
      ]
    ];
  }
}
