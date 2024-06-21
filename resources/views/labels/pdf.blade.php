<!DOCTYPE html>
<html>
<head>
    <title>Shipping Labels</title>
    <style>
        .label {
            page-break-after: always;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        .label-content {
            text-align: center;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    @foreach ($decodedLabels as $label)
        <div class="label">
            <div class="label-content">
                <img src="data:image/png;base64,{{ base64_encode($label) }}" alt="Shipping Label">
            </div>
        </div>
    @endforeach
</body>
</html>
