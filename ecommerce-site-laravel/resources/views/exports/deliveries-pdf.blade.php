<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        h1 {
            text-align: center;
            margin-bottom: 10px;
        }
        .period, .generated-at {
            text-align: center;
            margin-bottom: 20px;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary table {
            width: 50%;
            margin: 0 auto;
            border-collapse: collapse;
        }
        .summary th, .summary td {
            border: 1px solid #333;
            padding: 5px;
            text-align: center;
        }
        table.deliveries {
            width: 100%;
            border-collapse: collapse;
        }
        table.deliveries th, table.deliveries td {
            border: 1px solid #333;
            padding: 5px;
            text-align: left;
        }
        table.deliveries th {
            background-color: #f0f0f0;
        }
        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>

<h1>{{ $title }}</h1>

<div class="period">
    <strong>Période :</strong> {{ $period['start'] }} - {{ $period['end'] }}
</div>

<div class="generated-at">
    Généré le : {{ $generated_at }}
</div>

<div class="summary">
    <table>
        <tr>
            <th>Total</th>
            <th>Livrées</th>
            <th>Échouées</th>
            <th>En cours</th>
            <th>Taux de réussite (%)</th>
        </tr>
        <tr>
            <td>{{ $summary['total'] }}</td>
            <td>{{ $summary['successful'] }}</td>
            <td>{{ $summary['failed'] }}</td>
            <td>{{ $summary['in_progress'] }}</td>
            <td>{{ $summary['success_rate'] }}</td>
        </tr>
    </table>
</div>

<table class="deliveries">
    <thead>
        <tr>
            <th>Code suivi</th>
            <th>Commande</th>
            <th>Client</th>
            <th>Livreur</th>
            <th>Statut</th>
            <th>Date création</th>
            <th>Date livraison</th>
        </tr>
    </thead>
    <tbody>
        @foreach($deliveries as $delivery)
        <tr>
            <td>{{ $delivery['tracking_code'] }}</td>
            <td>{{ $delivery['order_number'] }}</td>
            <td>{{ $delivery['client_name'] }}</td>
            <td>{{ $delivery['delivery_person'] }}</td>
            <td>{{ $delivery['status'] }}</td>
            <td>{{ $delivery['created_at'] }}</td>
            <td>{{ $delivery['delivered_at'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<footer>
    Page {PAGE_NUM} / {PAGE_COUNT}
</footer>

</body>
</html>
