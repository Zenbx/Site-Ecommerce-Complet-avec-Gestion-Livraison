<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $serialId = strtoupper($this->faker->bothify('???-####-???'));
        $price = $this->faker->numberBetween(5, 500) * 10000;

        $quantity = $this->faker->randomElement([
            $this->faker->numberBetween(0, 5),
            $this->faker->numberBetween(10, 50),
            $this->faker->numberBetween(50, 200),
        ]);

        $brands = ['Apple', 'Samsung', 'Sony', 'Dell', 'HP', 'Lenovo', 'Canon', 'Nikon'];
        $brand = $this->faker->randomElement($brands);

        return [
            'category_id' => Category::factory(),
            'name' => $this->generateProductName($brand),
            'description' => $this->generateDescription(),
            'price' => $price,
            'quantity' => $quantity,
            'serial_id' => $serialId,
            'brand' => $brand,
            'image_url' => $this->getImageUrl($brand),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    private function generateProductName(string $brand): string
    {
        $models = ['Pro', 'Ultra', 'Max', 'Plus', 'Lite', 'Air', 'Mini', 'Edge'];
        $numbers = ['10', '12', '13', '14', '15', 'X'];

        return "{$brand} {$this->faker->randomElement($models)} {$this->faker->randomElement($numbers)}";
    }

    private function generateDescription(): string
    {
        $features = [
            'Écran OLED haute qualité',
            'Processeur dernière génération',
            'Batterie longue durée',
            'Design premium aluminium',
            'Système refroidissement avancé',
            'Connectivité 5G/WiFi 6',
        ];

        return implode(', ', $this->faker->randomElements($features, 3));
    }

    /**
     * Génère une URL Picsum aléatoire basée sur la marque
     */
    private function getImageUrl(string $brand): string
    {
        $safeBrand = strtolower(str_replace(' ', '-', $brand));
        return "https://picsum.photos/seed/{$safeBrand}/800/800";
    }

    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(10, 100),
            'is_active' => true,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'is_active' => false,
        ]);
    }
}
