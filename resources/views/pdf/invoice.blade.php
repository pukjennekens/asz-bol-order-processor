<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <body>
        <table class="container">
            <tr>
                <td>
                    <p>
                        <strong>{{ $order->shipmentDetails->firstName . ' ' . $order->shipmentDetails->surname }}</strong><br>
                        {{ $order->shipmentDetails->streetName . ' ' . $order->shipmentDetails->houseNumber }}<br>
                        {{ $order->shipmentDetails->zipCode }}, {{ $order->shipmentDetails->city }}<br>
                        {{ $order->shipmentDetails->countryCode }}
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="3">
                    <br>
                </td>
            </tr>

            <tr>
                <td>
                   <h3 style="font-size: 2rem;">Factuur</h3>
                </td>
            </tr>

            <tr>
                <td colspan="3">
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Bestelnummer:</strong> #{{ $order->orderId }}<br>
                    
                </td>
            </tr>

            <tr>
                <td colspan="3">
                    <br>
                </td>
            </tr>
        </table>

        <table class="order-details">
            <thead>
                <tr>
                    <th style="width: 300px;">Product</th>
                    <th>Aantal</th>
                    <th>EAN</th>
                    <th>Prijs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $order->orderItems as $orderItem ) : ?>
                    <tr>
                        <td>
                            {{ $orderItem->product->title }}
                        </td>

                        <td>
                            {{ $orderItem->quantity }} &times;
                        </td>

                        <td>
                            {{ $orderItem->product->ean }}
                        </td>

                        <td>
                            €{{ number_format($orderItem->unitPrice, 2) }}
                        </td>
                    </tr>
                <?php endforeach; ?>		
            </tbody>
        </table>

        <style>
            table.container {
                width: 100%;
                border: 0;
            }

            table.order-details {
                width: 100%;
                margin-bottom: 8mm;
                page-break-before: avoid;
                border-collapse: collapse;
            }

            table.order-details thead th {
                background: #000000;
                color: #ffffff;
                font-weight: bold;
                text-align: left;
            }

            table.order-details tr {
                page-break-inside: always;
                page-break-after: auto;	
            }

            table.order-details td,
            table.order-details th {
                border: none !important;
                padding: 0.375em;
            }

            table.order-details tr:nth-child(even) td {
                background: #f2f2f2;
            }

            /* Borders are still showing, remove table gap */

        </style>
    </body>
</html>