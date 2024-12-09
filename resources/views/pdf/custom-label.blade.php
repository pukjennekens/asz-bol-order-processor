<html>
    <head>
        <style>
            * {
                font-family: "Arial", sans-serif; /* Vervang "Arial" door het gewenste lettertype */
            }

            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
            }
            .page {
                position: relative;
                width: 100%;
                height: 100%;
            }
            .content-wrapper {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(90deg);
                width: 100%;
                height: auto;
                text-align: center;
            }
            .page-break {
                page-break-after: always;
            }
            .sender-table th {
                text-align: left;
                padding: 0px;
                font-size: 16px;
            }
            .sender-table td {
                font-size: 14px;
                padding: 0px;
                line-height: 20px;
            }
            .retriever-table {
                border: 1px solid #000;
                width: 250px;
                min-height: 150px;
                max-width: 250px;
                margin: auto;
                padding: 5px;
            }
            .retriever-table td{
                font-size: 14px;
                line-height: 14px;
                font-weight: bold;
            }
            .m-auto{
                margin: auto;
            }
            .sender-data{
                position: absolute;
                top: -100px;
                left: -60px;
            }
            .sender-data-table td{
                font-size: 13px;
                line-height: 11px;
            }
            .sender-title{
                position: relative;
                left: -21px;
                margin-bottom: 5px;
                font-size: 14px;
            }
          
        </style>
    </head>
    <body>
        @foreach($labels as $key => $labelData)
            <div class="page">
                <div class="content-wrapper">
                    <img src="{{ $logoImage }}" style="width: 100px; position: absolute; right: -40px; top: -80px;">
                    <div class="sender-data" style="margin-top: 15px;">
                        <div class="sender-title">
                            Afz./From:
                        </div>
                        <table class="sender-data-table">
                            <tr>
                                <td>
                                    {{$senderData['Name']}}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    {{$senderData['Street']}}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>
                                        {{$senderData['Zipcode']}} {{$senderData['City']}}
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>
                                        @if($senderData['Countrycode'] == 'NL')
                                            The netherlands
                                        @endif
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <table style="width: 100%; margin-bottom: 00px;"> 
                        <tr>
                            <td style="width: 100%;">
                                <div class="m-auto">
                                    <strong style="margin-left: 70px;">
                                        Naar/ To:
                                    </strong>
                                    <table class="retriever-table">
                                        <tr>
                                            <td><strong>{{$labelData['name']}}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>{{$labelData['street']}}</td>
                                        </tr>
                                        <tr>
                                            <td>{{$labelData['zipcode']}} {{$labelData['city']}}</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                
                                                <img style="height: 75px; width: 200px; position: relative; top: 85px; margin-left: 100px;" src="{{ $labelData['barcode'] }}" alt="Barcode Image" />
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            @if($key !== count($labels) - 1)
                <div class="page-break"></div>
            @endif
        @endforeach
    </body>
</html>
