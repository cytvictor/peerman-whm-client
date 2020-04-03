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

add_hook('AfterModuleTerminate', 1, function($vars) {
	$module_configuration = \WHMCS\Database\Capsule::table('tbladdonmodules')->where([
		[ 'module', 'peer_man_whm_client' ],
		[ 'setting', 'product_ids' ],
	])->first();
	$available_products = parseAvailabelproducts($module_configuration->value);
	if (in_array($vars['params']['pid'], $available_products['product_ids'])) {
		$module_configuration = \WHMCS\Database\Capsule::table('tbladdonmodules')->where([
			[ 'module', 'peer_man_whm_client' ],
			[ 'setting', 'product_ids' ],
		])->first();

		$identifier = \WHMCS\Database\Capsule::table('tbladdonmodules')->where([
			[ 'module', 'peer_man_whm_client' ],
			[ 'setting', 'identifier' ],
		])->first();

		$backend_address = \WHMCS\Database\Capsule::table('tbladdonmodules')->where([
			[ 'module', 'peer_man_whm_client' ],
			[ 'setting', 'backend_address' ],
		])->first();

		$product_router_kv = \WHMCS\Database\Capsule::table('tbladdonmodules')->where([
			[ 'module', 'peer_man_whm_client' ],
			[ 'setting', 'product_router_kv' ],
		])->first();

		$http_client = new \GuzzleHttp\Client();

		try {
	      $resp = $http_client->post($backend_address->value . '/admin/remove_user_sessions?identifier=' . $identifier->value, [
	        'connect_timeout' => 10,
	        'timeout' => 10,
	        'json' => [
	          'router_id' => intval(parseRouterId($product_router_kv->value)[$vars['params']['pid']]),
	          'remote_address' => $vars['params']['model']['dedicatedip'],
	        ]
	      ]);
	    } catch (Exception $e) {
	    	if ($e->hasResponse()) {
	    		logModuleCall('peer_man_whm_client', 'AfterModuleTerminateException', $vars, json_encode($e->getResponse()->json()), '', []);
	    	}
	    }
	    $data = json_decode($resp, true);
	    if ($data['success']) {
	    	logModuleCall('peer_man_whm_client', 'AfterModuleTerminateSuccess', $vars, $data, '', []);
	    } else {
	    	logModuleCall('peer_man_whm_client', 'AfterModuleTerminateFaile', $vars, $data, '', []);
	    }
	}
});

add_hook('ClientAreaProductDetailsOutput', 1, function($service) {
	if (!is_null($service)) {
		if ($service['service']['status'] == 'Active') {
			$module_configuration = \WHMCS\Database\Capsule::table('tbladdonmodules')->where([
				[ 'module', 'peer_man_whm_client' ],
				[ 'setting', 'product_ids' ],
			])->first();
			$available_products = parseAvailabelproducts($module_configuration->value);
			if (in_array($service['service']['productId'], $available_products['product_ids'])) {
				return '<button onclick="window.location=\'index.php?m=peer_man_whm_client\'" class="btn btn-xs btn-info" style="margin:6px 0;">BGP Session</button>';
			}
		}
	}
});