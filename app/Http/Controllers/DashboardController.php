<?php

namespace App\Http\Controllers;

use App\API\BolClient;
use App\Jobs\SendShipmentToBol;
use App\Mail\Invoice;
use App\Mail\TrackingCode;
use App\Models\BolAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Picqer\BolRetailerV10\Exception\ResponseException;
use Picqer\BolRetailerV10\Model\OrderItem;
use Picqer\BolRetailerV10\Model\ShipmentRequest;
use Picqer\BolRetailerV10\Model\TransportInstruction;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\TcpdfFpdi;
use TCPDF;
use TCPDF_STATIC;

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
        Log::debug('Processing orders', [
            'request' => $request->all(),
        ]);

        $request->validate([
            'order_ids'          => 'required|array',
            'bol_com_account_id' => 'required|integer|exists:bol_accounts,id',
            'is_parcel'          => 'nullable|in:on,off',
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
            $labels = [];
            foreach($orders as $order) {
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
                    'phone_number' => $order->shipmentDetails->deliveryPhoneNumber ?? '0612345678',
                    'email'        => $order->shipmentDetails->email,
                ];
            }

            $postNL = Http::withHeader('apikey', env('POSTNL_API_KEY'));

            $shipments = [];

            foreach($labels as $key => $label) {
                $parameters = [
                    'CustomerCode'   => env('POSTNL_CUSTOMER_CODE'),
                    'CustomerNumber' => env('POSTNL_CUSTOMER_NUMBER'),
                    'Type'           => '3S',
                ];
    
                if( $label['country'] != 'NL' ) {
                    $parameters['Type']  = 'UE';
                    $parameters['Serie'] = '00000000-99999999';
                    $parameters['Range'] = 'NL';
                }

                $barcodeResponse = $postNL->get('https://api.postnl.nl/shipment/v1_1/barcode', $parameters);
                Log::debug('PostNL barcode response', [
                    'response' => $barcodeResponse,
                ]);
                $label['barcode'] = $barcodeResponse['Barcode'];

                $product_code_delivery = '2929';

                $country = $label['country'];
                $type    = $request->is_parcel == 'on' ? 2 : 1;

                if ($country == 'NL' && $type == 1) {
                    // Envelop nl-nl:2928
                    $product_code_delivery = '2929';
                } else if ($country == 'NL' && $type == 2) {
                    // Pakket nl-nl:3085
                    $product_code_delivery = '3085';
                } else if ($country == 'BE' && $type == 1) {
                    // Envelop nl-be:4946
                    $product_code_delivery = '6945';
                } else if ($country == 'BE' && $type == 2) {
                    // Pakket nl-be:4912
                    $product_code_delivery = '6945';
                }

                $shipments[] = [
                    'Addresses'           => [
                        // Reciever
                        [
                            'AddressType' => '01',
                            'City'        => $label['city'],
                            'Countrycode' => $label['country'],
                            'Name'        => $label['name'],
                            'Street'      => $label['street'],
                            'Zipcode'     => $label['zipcode'],
                        ],
                        // Sender we need to add some settings for this
                        [
                            'AddressType' => '02',
                            'City'        => 'Zwolle',
                            'Countrycode' => 'NL',
                            'Name'        => 'E-Commerce',
                            'Street'      => 'Paxtonstraat 4',
                            'Zipcode'     => '8013RP',
                        ],
                    ],
                    'Barcode'             => $label['barcode'],
                    'Contacts'            => [
                        [
                            'ContactType' => '01',
                            'Email'       => $label['email'],
                            'SMSNr'       => $label['phone_number'],
                        ],
                    ],
                    'DeliveryDate'        => date( 'd-m-Y H:i:s', strtotime( '+1 day' ) ),
                    'Dimension'           => [
                        'Weight' => '2000',
                    ],
                    'ProductCodeDelivery' => $product_code_delivery,
                    'Reference'           => $order->orderId,
                    'Remark'              => 'Fragile',
                    'OrderNr'             => $order->orderId,
                ];
            }

            $body = [
                'Customer' => [
                    'CustomerCode'   => env('POSTNL_CUSTOMER_CODE'),
                    'CustomerNumber' => env('POSTNL_CUSTOMER_NUMBER'),
                ],
                'Message' => [
                    'MessageID'        => $order->orderId,
                    'MessageTimeStamp' => date( 'd-m-Y H:i:s' ),
                    'Printertype'      => 'GraphicFile|PDF',
                ],
                'Shipments' => $shipments,
            ];

            foreach($shipments as $shipment) {
                Log::debug('Sending an tracking code mail to: ' . $shipment['Contacts'][0]['Email']);
                Mail::to($shipment['Contacts'][0]['Email'])->send(new TrackingCode($shipment['Barcode'], $shipment['Addresses'][0]['Countrycode'], $shipment['Addresses'][0]['Zipcode']));
            }

            Log::debug('PostNL request', [
                'body' => $body,
            ]);

            $response = $postNL->post('https://api.postnl.nl/shipment/v2_2/label', $body)->json();

            Log::debug('PostNL response', [
                'response' => $response,
            ]);

            $pdf = new TcpdfFpdi('P', 'mm', array(148, 105), true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            foreach($response['ResponseShipments'] as $shipment) {
                
            }

            foreach ($response['ResponseShipments'] as $shipment) {
                if (!isset($shipment['Labels'][0]['Content'])) {
                    continue;
                }

                $decodedLabel = base64_decode($shipment['Labels'][0]['Content']);
                if ($decodedLabel === false) {
                    continue;
                }

                $tmpFile = tempnam(sys_get_temp_dir(), 'pdf');
                file_put_contents($tmpFile, $decodedLabel);

                $pdf->AddPage();

                $pdf->setSourceFile($tmpFile);
                $tplIdx = $pdf->importPage(1);

                // Rotate and place the label correctly
                $pdf->Rotate(90); // Rotate 90 degrees around the center of the page
                $pdf->useTemplate($tplIdx, -125, 0, 148, 105); // Adjust the placement accordingly
                $pdf->Rotate(0);

                unlink($tmpFile);
            }

            $pdfOutput = $pdf->Output('labels.pdf', 'S'); // Output to a string

            return response($pdfOutput, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="labels.pdf"',
            ]);
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

            foreach($orders as $order) {
                /**
                 * @var \Picqer\BolRetailerV10\Model\Order $order
                 */

                Mail::to($order->shipmentDetails->email)->send(new Invoice($order));
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

    public function manualLabels()
    {
        return view('dashboard.manual-labels', [
            'apiUrl' => route('dashboard.manual-labels.post'),
        ]);
    }

    public function createManualLabels()
    {
        $labels = request()->validate([
            '*.name'         => 'required',
            '*.street'       => 'required',
            '*.zipcode'      => 'required',
            '*.city'         => 'required',
            '*.country'      => 'required|in:NL,BE',
            '*.phone_number' => 'nullable',
            '*.email'        => 'email',
            '*.isParcel'    => 'nullable',
        ]);
        
        $postNL = Http::withHeader('apikey', env('POSTNL_API_KEY'));

            $shipments = [];

            foreach($labels as $key => $label) {
                $parameters = [
                    'CustomerCode'   => env('POSTNL_CUSTOMER_CODE'),
                    'CustomerNumber' => env('POSTNL_CUSTOMER_NUMBER'),
                    'Type'           => '3S',
                ];
    
                if( $label['country'] != 'NL' ) {
                    $parameters['Type']  = 'UE';
                    $parameters['Serie'] = '00000000-99999999';
                    $parameters['Range'] = 'NL';
                }

                $barcodeResponse = $postNL->get('https://api.postnl.nl/shipment/v1_1/barcode', $parameters);
                Log::debug('PostNL barcode response', [
                    'response' => $barcodeResponse,
                ]);
                $label['barcode'] = $barcodeResponse['Barcode'];

                $product_code_delivery = '2929';

                $country = $label['country'];
                $type    = $label['isParcel'] ? 2 : 1;

                if ($country == 'NL' && $type == 1) {
                    // Envelop nl-nl:2928
                    $product_code_delivery = '2929';
                } else if ($country == 'NL' && $type == 2) {
                    // Pakket nl-nl:3085
                    $product_code_delivery = '3085';
                } else if ($country == 'BE' && $type == 1) {
                    // Envelop nl-be:4946
                    $product_code_delivery = '6945';
                } else if ($country == 'BE' && $type == 2) {
                    // Pakket nl-be:4912
                    $product_code_delivery = '6945';
                }

                $shipments[] = [
                    'Addresses'           => [
                        // Reciever
                        [
                            'AddressType' => '01',
                            'City'        => $label['city'],
                            'Countrycode' => $label['country'],
                            'Name'        => $label['name'],
                            'Street'      => $label['street'],
                            'Zipcode'     => $label['zipcode'],
                        ],
                        // Sender we need to add some settings for this
                        [
                            'AddressType' => '02',
                            'City'        => 'Zwolle',
                            'Countrycode' => 'NL',
                            'Name'        => 'E-Commerce',
                            'Street'      => 'Paxtonstraat 4',
                            'Zipcode'     => '8013RP',
                        ],
                    ],
                    'Barcode'             => $label['barcode'],
                    'Contacts'            => [
                        [
                            'ContactType' => '01',
                            'Email'       => $label['email'],
                            'SMSNr'       => $label['phone_number'] ?? '0612345678',
                        ],
                    ],
                    'DeliveryDate'        => date( 'd-m-Y H:i:s', strtotime( '+1 day' ) ),
                    'Dimension'           => [
                        'Weight' => '2000',
                    ],
                    'ProductCodeDelivery' => $product_code_delivery,
                    'Reference'           => $key,
                    'Remark'              => 'Fragile',
                    'OrderNr'             => $key,
                ];
            }

            $body = [
                'Customer' => [
                    'CustomerCode'   => env('POSTNL_CUSTOMER_CODE'),
                    'CustomerNumber' => env('POSTNL_CUSTOMER_NUMBER'),
                ],
                'Message' => [
                    'MessageID'        => $key,
                    'MessageTimeStamp' => date( 'd-m-Y H:i:s' ),
                    'Printertype'      => 'GraphicFile|PDF',
                ],
                'Shipments' => $shipments,
            ];

            Log::debug('PostNL request', [
                'body' => $body,
            ]);

            $response = $postNL->post('https://api.postnl.nl/shipment/v2_2/label', $body)->json();

            Log::debug('PostNL response', [
                'response' => $response,
            ]);

            $pdf = new TcpdfFpdi('P', 'mm', array(148, 105), true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            foreach ($response['ResponseShipments'] as $shipment) {
                if (!isset($shipment['Labels'][0]['Content'])) {
                    continue;
                }

                $decodedLabel = base64_decode($shipment['Labels'][0]['Content']);
                if ($decodedLabel === false) {
                    continue;
                }

                $tmpFile = tempnam(sys_get_temp_dir(), 'pdf');
                file_put_contents($tmpFile, $decodedLabel);

                $pdf->AddPage();

                $pdf->setSourceFile($tmpFile);
                $tplIdx = $pdf->importPage(1);

                // Rotate and place the label correctly
                $pdf->Rotate(90); // Rotate 90 degrees around the center of the page
                $pdf->useTemplate($tplIdx, -125, 0, 148, 105); // Adjust the placement accordingly
                $pdf->Rotate(0);

                unlink($tmpFile);
            }

            $pdfOutput = $pdf->Output('labels.pdf', 'S'); // Output to a string

            return response($pdfOutput, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="labels.pdf"',
            ]);
    }
}
