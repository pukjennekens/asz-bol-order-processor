<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\API\BolClient;
use Picqer\BolRetailerV10\Model\ShipmentRequest;
use Picqer\BolRetailerV10\Model\TransportInstruction;
use Illuminate\Support\Facades\Log;

class SendShipmentToBol implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Picqer\BolRetailerV10\Model\OrderItem[] $orderItems
     */
    private $orderItems;

    /**
     * @var \App\Models\BolAccount $bolAccount The BolAccount that is used to send the shipment
     */
    private $bolAccount;

    /**
     * Create a new job instance.
     * @param \Picqer\BolRetailerV10\Model\OrderItem[] $orderItems
     * @param \App\Models\BolAccount $bolAccount The BolAccount that is used to send the shipment
     * @return void
     */
    public function __construct($orderItems, $bolAccount)
    {
        $this->orderItems = $orderItems;
        $this->bolAccount = $bolAccount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        BolClient::setClientId($this->bolAccount->client_id);
        BolClient::setClientSecret($this->bolAccount->client_secret);
        $client = BolClient::getClient();

        $shipmentRequest = new ShipmentRequest();
        $shipmentRequest->orderItems = $this->orderItems;
        
        $transport = new TransportInstruction();
        $transport->transporterCode = 'TNT_BRIEF';

        $shipmentRequest->transport = $transport;

        Log::info('Sending single order to Bol.com', [
            'shipment_request' => $shipmentRequest,
        ]);

        $response = $client->createShipment($shipmentRequest);

        Log::info('Response from Bol.com', [
            'response' => $response,
        ]);
    }
}
