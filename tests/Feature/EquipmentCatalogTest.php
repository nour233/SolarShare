<?php
namespace Tests\Feature;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class EquipmentCatalogTest extends TestCase
{
    use RefreshDatabase;
    public function test_dashboard_lists_equipment_only_for_admin(): void {
        $category=Category::create(['name'=>'Batteries','icon'=>'fa-battery-full']);
        $admin=User::factory()->create();
        $admin->forceFill(['role'=>'admin'])->save();
        Equipment::create(['title'=>'Batterie dashboard','description'=>'Test','condition'=>'Bon état','price_per_day'=>20,'category_id'=>$category->id,'owner_id'=>$admin->id]);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Batterie dashboard')->assertSee('Batteries');
        $user=User::factory()->create();
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }
    public function test_catalog_displays_categories_equipment_and_details(): void {
        $category=Category::create(['name'=>'Panneaux solaires','icon'=>'fa-solar-panel']);
        $other=Category::create(['name'=>'Batteries','icon'=>'fa-battery-full']);
        $owner=User::factory()->create();
        $equipment=Equipment::create(['title'=>'Panneau portable 100W','description'=>'Un panneau pliable.','power_capacity'=>100,'power_unit'=>'W','condition'=>'Bon état','price_per_day'=>25,'deposit'=>100,'photos'=>['front/img/gallery-1.jpg'],'category_id'=>$category->id,'owner_id'=>$owner->id]);
        $this->get('/')->assertOk()->assertSee('Panneau portable 100W');
        $this->get('/?category='.$other->id, ['X-Requested-With'=>'XMLHttpRequest'])
            ->assertOk()->assertDontSee('Panneau portable 100W')->assertSee('Aucun équipement')->assertDontSee('<html', false);
        $this->get('/equipments')->assertOk()->assertSee('Panneaux solaires')->assertSee('Panneau portable 100W');
        $this->get('/equipments?category='.$other->id)->assertOk()->assertDontSee('Panneau portable 100W')->assertSee('Aucun équipement');
        $this->get('/equipments/'.$equipment->id)->assertOk()->assertSee($owner->name)->assertSee('Bon état');
        $this->assertTrue($equipment->category->is($category));
        $this->assertTrue($category->equipments->first()->is($equipment));
        $this->assertSame(['front/img/gallery-1.jpg'],$equipment->photos);
        $this->get('/equipments/99999')->assertNotFound();
    }
    public function test_search_matches_words_across_fields_and_preserves_category_and_literal_wildcards(): void {
        $category = Category::create(['name'=>'Batteries','icon'=>'fa-battery-full']);
        $other = Category::create(['name'=>'Solaires','icon'=>'fa-solar-panel']);
        $owner = User::factory()->create();
        foreach ([['Batterie nomade','Pour camping 100% pratique',$category->id], ['Panneau portable','Pour camping',$other->id]] as [$title,$description,$categoryId]) {
            Equipment::create(['title'=>$title,'description'=>$description,'condition'=>'Bon �tat','price_per_day'=>10,'category_id'=>$categoryId,'owner_id'=>$owner->id]);
        }
        foreach (['/', '/equipments'] as $path) {
            $this->get($path.'?q=batterie+camping')->assertOk()->assertSee('Batterie nomade')->assertDontSee('Panneau portable');
            $this->get($path.'?q=camping&category='.$other->id)->assertOk()->assertSee('Panneau portable')->assertDontSee('Batterie nomade');
            $this->get($path.'?q=100%25')->assertOk()->assertSee('Batterie nomade')->assertDontSee('Panneau portable');
            $this->get($path.'?q=introuvable')->assertOk()->assertSee('Aucun ')->assertDontSee('Batterie nomade');
        }
    }
}
