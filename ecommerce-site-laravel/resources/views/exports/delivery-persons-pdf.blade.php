<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial; font-size: 12px; color: #333; }
        h1 { text-align: center; margin-bottom: 10px; }
        .period, .generated-at { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background-color: #f0f0f0; }
        footer { position: fixed; bottom: 0; text-align: center; font-size: 10px; color: #777; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<div class="period"><strong>Période :</strong> {{ $period['start'] }} - {{ $period['end'] }}</div>
<div class="generated-at">Généré le : {{ $generated_at }}</div>

<table>
    <thead>
        <tr>
            <th>Nom</th><th>Email</th><th>Total livraisons</th><th>Réussies</th><th>Échouées</th><th>Taux (%)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($delivery_persons as $person)
        <tr>
            <td>{{ $person['name'] }}</td>
            <td>{{ $person['email'] }}</td>
            <td>{{ $person['total_deliveries'] }}</td>
            <td>{{ $person['successful'] }}</td>
            <td>{{ $person['failed'] }}</td>
            <td>{{ $person['success_rate'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<footer>Page {PAGE_NUM} / {PAGE_COUNT}</footer>
</body>
</html>
