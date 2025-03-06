<?php

namespace BriefWOO\Hooks\Actions;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WC_Product_Addons_Helper;
use SemiorbitGuid\Guid;

class RestApiAddToCartAction extends Action
{
  public function __construct()
  {
    $this->name = 'rest_api_init';
  }

  public function handle()
  {
    register_rest_route(BRIEFWOO_DOMAIN . '/' . self::$version, '/add_to_cart', [
      'methods'  => WP_REST_Server::CREATABLE,
      'callback' => [$this, 'add'],
      'permission_callback' => '__return_true',
    ]);
  }

  public function add(WP_REST_Request $request)
  {

    $request_addons = $request->get_param('addons');
    if (empty($request_addons) || !is_array($request_addons)) {
      return new WP_REST_Response([
        'message' => 'Invalid addons'
      ], 400);
    }

    $global_addons = WC_Product_Addons_Helper::get_product_addons(BRIEFWOO_PRODUCT_ID);
    if (empty($global_addons)) {
      return new WP_REST_Response([
        'message' => 'Addons not found'
      ], 400);
    }


    $addons = [];

    foreach ($request_addons as $key => $value) {
      $global_addon = array_values(array_filter($global_addons, function ($addon) use ($value) {
        return floatval(trim($addon['id'])) === floatval(trim($value['id']));
      }))[0];

      $option = array_values(array_filter($global_addon['options'], function ($option) use ($value) {
        return trim($option['label']) === trim($value['value']);
      }))[0];

      $addons[] = [
        "id" => $value['id'],
        "name" => $global_addon["name"],
        "value" => $value['value'],
        "price" => floatval($option["price"]) ?? 0,
        "field_name" => $global_addon["field_name"],
        "field_type" => $global_addon["type"],
        "price_type" => $option["price_type"]
      ];
    }

    $added = WC()->cart->add_to_cart(BRIEFWOO_PRODUCT_ID, 1, 0, [], [
      'addons' => $addons,
      'unique_key' => Guid::NewGuid()
    ]);

    if ($added) {
      return new WP_REST_Response([
        'message' => 'Product added to cart'
      ], 200);
    } else {
      return new WP_REST_Response([
        'message' => 'Failed to add product to cart'
      ], 400);
    }
  }
}
