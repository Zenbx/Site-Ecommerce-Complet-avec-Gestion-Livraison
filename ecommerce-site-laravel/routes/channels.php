<?php
// routes/channels.php

use App\Models\Admin;
use App\Models\Client;
use App\Models\Order;
use App\Models\Delivery;
use Illuminate\Support\Facades\Broadcast;

// Canal pour les commandes d'un client
Broadcast::channel('client.{clientId}', function ($user, $clientId) {
    return (int) $user->id === (int) $clientId;
});

// Canal pour une commande spécifique
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);
    return $user->id === $order->client_id;
});

// Canal pour les livraisons
Broadcast::channel('delivery.{deliveryId}', function ($user, $deliveryId) {
    $delivery = Delivery::find($deliveryId);
    return $user->id === $delivery->order->client_id;
});

// Canal admin (tous les admins)
Broadcast::channel('admin-dashboard', function ($user) {
    return $user instanceof Admin;
});

// Canal pour un livreur spécifique
Broadcast::channel('delivery-person.{deliveryPersonId}', function ($user, $deliveryPersonId) {
    return (int) $user->id === (int) $deliveryPersonId;
});