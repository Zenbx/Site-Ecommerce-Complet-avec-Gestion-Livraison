<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement diffusé quand un livreur met à jour sa position GPS
 * 
 * Cet événement est déclenché depuis le DeliveryController quand un livreur
 * envoie sa position actuelle via l'endpoint /api/delivery-person/deliveries/{id}/location
 * 
 * L'événement est diffusé sur deux canaux :
 * 1. Un canal privé pour cette livraison spécifique (pour les admins qui suivent cette livraison)
 * 2. Un canal global des livraisons (pour le tableau de bord qui affiche toutes les livraisons)
 * 
 * ShouldBroadcast indique à Laravel que cet événement doit être envoyé via WebSocket
 */
class DeliveryLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * L'instance de la livraison dont la position a été mise à jour
     * 
     * @var \App\Models\Delivery
     */
    public $delivery;
    
    /**
     * La latitude de la nouvelle position
     * 
     * @var float
     */
    public $latitude;
    
    /**
     * La longitude de la nouvelle position
     * 
     * @var float
     */
    public $longitude;
    
    /**
     * La vitesse du livreur en km/h (optionnelle)
     * 
     * @var float|null
     */
    public $speed;
    
    /**
     * La direction du mouvement en degrés (0-360)
     * 
     * @var float|null
     */
    public $heading;
    
    /**
     * Timestamp de cette mise à jour
     * 
     * @var string
     */
    public $timestamp;

    /**
     * Créer une nouvelle instance de l'événement
     * 
     * @param \App\Models\Delivery $delivery
     * @param array $location Tableau contenant latitude, longitude, speed, heading
     */
    public function __construct(Delivery $delivery, array $location)
    {
        $this->delivery = $delivery;
        $this->latitude = $location['latitude'];
        $this->longitude = $location['longitude'];
        $this->speed = $location['speed'] ?? null;
        $this->heading = $location['heading'] ?? null;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Définit le ou les canaux sur lesquels l'événement doit être diffusé
     * 
     * Nous diffusons sur deux canaux :
     * 1. delivery.{id} : canal privé spécifique à cette livraison
     *    Seuls les utilisateurs autorisés (admin, client) peuvent s'y abonner
     * 2. deliveries : canal privé pour tous les admins qui suivent toutes les livraisons
     * 
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('delivery.' . $this->delivery->id),
            new PrivateChannel('deliveries'),
        ];
    }
    
    /**
     * Définit les données qui seront envoyées aux clients
     * 
     * Par défaut, Laravel envoie toutes les propriétés publiques de l'événement.
     * Nous personnalisons ici pour envoyer exactement les données nécessaires
     * dans un format optimisé pour la carte en temps réel.
     * 
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'tracking_code' => $this->delivery->tracking_code,
            'delivery_person' => [
                'id' => $this->delivery->deliveryPerson->id,
                'name' => $this->delivery->deliveryPerson->name,
            ],
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'speed' => $this->speed,
                'heading' => $this->heading,
            ],
            'timestamp' => $this->timestamp,
            'status' => $this->delivery->status,
        ];
    }
    
    /**
     * Le nom de l'événement tel qu'il apparaîtra côté client
     * 
     * Par défaut, Laravel utilise le nom complet de la classe.
     * Nous le personnalisons pour avoir un nom plus court et plus propre.
     * 
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
    }
}