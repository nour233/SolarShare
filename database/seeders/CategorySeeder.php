<?php
namespace Database\Seeders;
use App\Models\Category;
use Illuminate\Database\Seeder;
class CategorySeeder extends Seeder
{
    public function run(): void {
        foreach ([['name'=>'Panneaux solaires','description'=>'Panneaux solaires portables et accessoires de production solaire.','icon'=>'fa-solar-panel'],['name'=>'Batteries','description'=>'Batteries et stations de stockage d’énergie.','icon'=>'fa-battery-full'],['name'=>'Petites éoliennes','description'=>'Équipements éoliens compacts.','icon'=>'fa-wind']] as $category) {
            Category::firstOrCreate(['name'=>$category['name']],$category);
        }
    }
}
