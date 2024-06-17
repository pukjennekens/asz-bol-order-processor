<?php

namespace App\Http\Controllers;

use App\API\BolClient;
use App\Jobs\SendShipmentToBol;
use App\Models\BolAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Picqer\BolRetailerV10\Exception\ResponseException;
use Picqer\BolRetailerV10\Model\OrderItem;
use Picqer\BolRetailerV10\Model\ShipmentRequest;
use Picqer\BolRetailerV10\Model\TransportInstruction;

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

        if(Cache::get('orders-' . $bolAccount->id) && Cache::get('orders-' . $bolAccount->id) != 'recently_pushed') {
            $reducedOrders = Cache::get('orders-' . $bolAccount->id);
        } else {
            // Get the $reducedOrders = $client->getOrders();. If the page size is 50 we need to get the next page as well, keep doing this until the page size is less than 50.
            $page          = 1;
            $pageSize      = 50;
            $reducedOrders = $client->getOrders($page);

            while(count($reducedOrders) % $pageSize == 0) {
                $page++;
                $orders = $client->getOrders($page);
                if(count($orders) == 0) break;
                $reducedOrders = array_merge($reducedOrders, $orders);
            }

            Cache::put('orders-' . $bolAccount->id, $reducedOrders, 60 * 5);
        }

        /**
         * @var \Picqer\BolRetailerV10\Model\Order[] $orders
         */
        $orders = [];

        foreach($reducedOrders as $order) {
            if(Cache::get('order-' . $order->orderId)) {
                if(Cache::get('order-' . $order->orderId) === 'recently_deleted') continue;
                $order = Cache::get('order-' . $order->orderId);
            } else {
                $order = $client->getOrder($order->orderId);
                Cache::put('order-' . $order->orderId, $order, 60 * 120);
            }

            $orders[] = $order;
        }

        // Sort orders by orderId (descending)
        usort($orders, function($a, $b) {
            return $b->orderId <=> $a->orderId;
        });

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
            'action'             => 'required|in:packing_slips,shipping_labels,send_to_bol',
        ]);

        $action   = $request->action;
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

        // Sort orders by orderId (descending)
        usort($orders, function($a, $b) {
            return $b->orderId <=> $a->orderId;
        });

        if($action == 'shipping_labels') {
            // Make the API request to create the labels
            $actionID = uniqid();
            $apiURL   = 'https://bestetelefoonhoesjes.nl/wp-json/asz-order-processing/v1';
            
            $data = [
                'action_id' => $actionID,
                'parcel'    => $request->is_parcel,
            ];

            // Split the orders into chunks of 4
            // $chunks = array_chunk($orders, 4);
            $chunks = array_chunk($orders, 1);

            foreach($chunks as $chunk) {
                $labels = [];
                foreach($chunk as $order) {
                    /**
                     * @var \Picqer\BolRetailerV10\Model\Order $order
                     */
                    
                    $labels[] = [
                        'id'           => $order->orderId,
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

            $response = Http::get($apiURL . '/merge-manual-postnl-labels', ['action_id' => $actionID]);
            if($response->json()['message'] == 'labels_merged') {
                return redirect($response->json()['url']);
            }
        }

        if($action == 'send_to_bol') {
            Log::info('Sending orders to Bol.com', [
                'order_ids' => $orderIds,
            ]);
            // Set the orders in Bol to completed
            $delay      = 0;

            foreach($orders as $order) {
                $orderItems = [];
                /**
                 * @var \Picqer\BolRetailerV10\Model\Order $order
                 */
                foreach($order->orderItems as $orderItem) {
                    $_order_item = new OrderItem();
                    $_order_item->orderItemId = $orderItem->orderItemId;
                    $_order_item->quantity    = $orderItem->quantity;
                    $orderItems[] = $_order_item;
                }

                // Send the order to Bol.com
                SendShipmentToBol::dispatch($orderItems, $bolAccount)->delay(now()->addMilliseconds($delay));

                // Rate limit is 25 requests per second, so we need to delay the requests. Also add 5ms to the delay for each order to make sure we don't hit the rate limit.
                $delay += 45;
            }

            // Empty the order cache for the order items and the bol account's orders
            Log::info('Emptying the cache for the order items and bol account\'s orders', [
                'order_ids' => $orderIds,
            ]);
            foreach($orderIds as $orderId) {
                Cache::forget('order-' . $orderId);
                Cache::put('order-' . $orderId, 'recently_deleted', 60 * 120);
            };

            Cache::forget('orders-' . $bolAccount->id);
            Cache::put('orders-' . $bolAccount->id, 'recently_pushed', 60 * 5);

            return redirect(route('dashboard.account', $bolAccount->id));
        }

        if($action == 'packing_slips') {
            $mpdf = new \Mpdf\Mpdf( array(
                'mode'                 => 'utf-8',
                'format'               => 'A4',
                'orientation'          => 'P',
                'margin_left'          => 10,
                'margin_right'         => 10,
                'margin_top'           => 10,
                'margin_bottom'        => 10,
                'margin_header'        => 0,
                'margin_footer'        => 0,
                'setAutoTopMargin'     => 'stretch',
                'setAutoBottomMargin'  => 'stretch',
                'default_font_size'    => 10,
                'default_font'         => 'helvetica',
            ) );

            foreach($orders as $order) {
                $mpdf->WriteHTML(view('pdf.packing-slip', [
                    'order' => $order,
                ])->render());

                // Check if we need to add a page break
                if($order != end($orders)) {
                    $mpdf->AddPage();
                }
            }

            $pdf = $mpdf->Output('', 'S');

            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="packing_slips.pdf"',
            ]);
        }
    }
}
