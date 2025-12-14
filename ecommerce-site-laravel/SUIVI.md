Je dois gérer les évenements au niveau des changment de statuts avec le broadcasting

// Déclencher l'événement de changement de statut
    broadcast(new DeliveryStatusChanged($delivery, $oldStatus, 'IN_TRANSIT'));

