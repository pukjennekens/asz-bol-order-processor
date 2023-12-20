<?php

namespace App\Http\Controllers;

use App\API\BolClient;
use App\Models\BolAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Picqer\BolRetailerV10\Model\ShipmentRequest;

class DashboardController extends Controller
{
    public function home()
    {
        return view('dashboard.home');
    }

    public function settings()
    {
        return view('dashboard.settings');
    }

    public function addBolAccount()
    {
        return view('dashboard.add-bol-account');
    }

    public function storeBolAccount(Request $request)
    {
        $request->validate([
            'name'          => 'required',
            'client_id'     => 'required',
            'client_secret' => 'required',
        ]);

        // Check if the client ID and secret are valid
        BolClient::setClientId($request->client_id);
        BolClient::setClientSecret($request->client_secret);

        try {
            $client = BolClient::getClient();
        } catch (\Picqer\BolRetailerV10\Exception\UnauthorizedException $e) {
            return redirect()->back()->withErrors(['client_id' => 'The client ID and secret are not valid.']);
        } catch(\Exception $e) {
            return redirect()->back()->withErrors(['client_id' => 'The client ID and secret are not valid.']);
        }

        $bolAccount = new BolAccount();
        $bolAccount->name          = $request->name;
        $bolAccount->client_id     = $request->client_id;
        $bolAccount->client_secret = $request->client_secret;
        $bolAccount->save();

        return redirect(route('dashboard.settings'));
    }

    public function deleteBolAccount($id)
    {
        $bolAccount = BolAccount::findOrFail($id);
        $bolAccount->delete();

        return redirect(route('dashboard.settings'));
    }

    public function ordersOverview($id)
    {
        $bolAccount = BolAccount::findOrFail($id);
        BolClient::setClientId($bolAccount->client_id);
        BolClient::setClientSecret($bolAccount->client_secret);

        $client = BolClient::getClient();

        /**
         * @var \Picqer\BolRetailerV10\Model\ReducedOrder[] $orders
         */
        $reducedOrders = [];

        if(Cache::get('orders-' . $bolAccount->id)) {
            $reducedOrders = Cache::get('orders-' . $bolAccount->id);
        } else {
            $reducedOrders = $client->getOrders();
            Cache::put('orders-' . $bolAccount->id, $reducedOrders, 60 * 5);
        }

        /**
         * @var \Picqer\BolRetailerV10\Model\Order[] $orders
         */
        $orders = [];

        foreach($reducedOrders as $order) {
            if(Cache::get('order-' . $order->orderId)) {
                $order = Cache::get('order-' . $order->orderId);
            } else {
                $order = $client->getOrder($order->orderId);
                Cache::put('order-' . $order->orderId, $order, 60 * 120);
            }

            $orders[] = $order;
        }

        return view('dashboard.orders-overview', [
            'bolAccount' => $bolAccount,
            'orders'     => $orders,
            'orderIds'   => array_map(function($order) { return $order->orderId; }, $orders),
        ]);
    }

    public function processOrders(Request $request)
    {
        $request->validate([
            'order_ids'          => 'required|array',
            'bol_com_account_id' => 'required|integer|exists:bol_accounts,id',
            'is_parcel'          => 'nullable|boolean',
        ]);

        $orderIds = $request->order_ids;

        if(!is_array($orderIds) || empty($orderIds)) return redirect('dashboard.account', ['id' => $request->bol_com_account_id]);
        
        /**
         * @var \Picqer\BolRetailerV10\Model\Order[] $orders
         */
        $orders = [];

        $bolAccount = BolAccount::findOrFail($request->bol_com_account_id);
        BolClient::setClientId($bolAccount->client_id);
        BolClient::setClientSecret($bolAccount->client_secret);
        $client = BolClient::getClient();

        foreach($orderIds as $orderId) {
            if(Cache::get('order-' . $orderId)) {
                $order = Cache::get('order-' . $orderId);
            } else {
                $order = $client->getOrder($orderId);
                Cache::put('order-' . $orderId, $order, 60 * 120);
            }

            $orders[] = $order;
        }

        // Make the API request to create the labels
        $actionID = uniqid();
        $apiURL   = 'https://bestetelefoonhoesjes.nl/wp-json/asz-order-processing/v1';
        
        $data = [
            'action_id' => $actionID,
            'parcel'    => $request->is_parcel,
        ];

        // Split the orders into chunks of 4
        $chunks = array_chunk($orders, 4);
        foreach($chunks as $chunk) {
            $labels = [];
            foreach($chunk as $order) {
                /**
                 * @var \Picqer\BolRetailerV10\Model\Order $order
                 */
                
                $labels[] = [
                    'name'         => $order->shipmentDetails->firstName . ' ' . $order->shipmentDetails->surname,
                    'street'       => $order->shipmentDetails->streetName . ' ' . $order->shipmentDetails->houseNumber,
                    'zipcode'      => $order->shipmentDetails->zipCode,
                    'city'         => $order->shipmentDetails->city,
                    'country'      => $order->shipmentDetails->countryCode,
                    'phone_number' => $order->shipmentDetails->deliveryPhoneNumber,
                    'email'        => $order->shipmentDetails->email,
                ];
            }

            // Make the POST request
            $response = Http::post($apiURL . '/generate-manual-postnl-labels', array_merge($data, ['labels' => $labels]));
        }

        // Set the orders in Bol to completed
        $orderItemIds = [];
        foreach($orders as $order) {
            /**
             * @var \Picqer\BolRetailerV10\Model\Order $order
             */
            foreach($order->orderItems as $orderItem) {
                $orderItemIds[] = $orderItem->orderItemId;
            }
        }

        // Split the order item IDs into chunks of 100
        $chunks = array_chunk($orderItemIds, 100);
        foreach($chunks as $chunk) {
            $shipmentRequest = new ShipmentRequest();
            $shipmentRequest->setOrderItemIds($chunk);
            $client->shipOrderItem($shipmentRequest);
        }

        // Empty the order cache for the order items and the bol account's orders
        Cache::forget('orders-' . $bolAccount->id);
        foreach($orderIds as $orderId) {
            Cache::forget('order-' . $orderId);
        }

        $response = Http::get($apiURL . '/merge-manual-postnl-labels', ['action_id' => $actionID]);
        if($response->json()['message'] == 'labels_merged') {
            return redirect($response->json()['url']);
        }
    }
}
