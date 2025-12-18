<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class DeliveryPersonWelcome extends Mailable
{
    public $deliveryPerson;
    public $temporaryPassword;

    public function __construct($deliveryPerson, $temporaryPassword)
    {
        $this->deliveryPerson = $deliveryPerson;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function build()
    {
        $name = $this->deliveryPerson->name;
        $email = $this->deliveryPerson->email;
        $password = $this->temporaryPassword;

        return $this
            ->subject('Vos accès livreur – TechStorm Delivery')
            ->html("
                <h2>Bienvenue {$name}</h2>
                <p>Votre compte livreur a été créé avec succès.</p>
                <p><strong>Email :</strong> {$email}</p>
                <p><strong>Mot de passe temporaire :</strong> {$password}</p>
                <p>Vous devez changer votre mot de passe à la première connexion.</p>
                <p>Cordialement,<br>Équipe TechStorm Delivery</p>
            ");
    }
}
