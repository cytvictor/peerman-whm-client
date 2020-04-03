<?php

function parseAvailabelproducts($option) {
  $arr = explode("|", $option);
  $ret = [
    'product_ids' => [],
    'products' => [],
  ];

  foreach ($arr as $key => $value) {
    $productId = substr($value, 0, strpos($value, ':'));
    $addrFamily = substr($value, strpos($value, ':') + 1);
    $ret['products'][$productId] = [
      'ip4' => strpos($addrFamily, '4') !== false,
      'ip6' => strpos($addrFamily, '6') !== false,
    ];
    array_push($ret['product_ids'], $productId);
  }

  return $ret;
}

function parseRouterId($productRouterKv) {
  $rels = explode('|', $productRouterKv);
  $ret = [];
  foreach ($rels as $rel) {
    $productId = substr($rel, 0, strpos($rel, ':'));
    $ret[$productId] = substr($rel, strpos($rel, ':') + 1);
  }
  return $ret;
}

function v4() 
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}