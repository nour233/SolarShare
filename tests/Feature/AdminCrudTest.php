<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class AdminCrudTest extends TestCase {
 use RefreshDatabase;
 public function test_admin_crud_with_photo_and_category_protection():void {
  Storage::fake('public');$admin=User::factory()->create();$admin->forceFill(['role'=>'admin'])->save();$this->actingAs($admin);
  $this->get('/admin/categories/create')->assertOk();$this->post('/admin/categories',['name'=>'Test solaire','description'=>'Test','icon'=>'fa-solar-panel'])->assertRedirect('/admin/categories');$category=Category::first();
  $this->get('/admin/categories/'.$category->id.'/edit')->assertOk();$this->put('/admin/categories/'.$category->id,['name'=>'Solaire','icon'=>'fa-solar-panel'])->assertRedirect();
  $this->get('/admin/equipments/create')->assertOk();
  $data=['title'=>'Panneau test','description'=>'Description','category_id'=>$category->id,'owner_id'=>$admin->id,'power_capacity'=>100,'power_unit'=>'W','condition'=>'Bon état','price_per_day'=>15,'deposit'=>50];
  $this->post('/admin/equipments',$data+['uploads'=>[UploadedFile::fake()->image('panel.jpg')]])->assertRedirect('/admin/equipments');$item=Equipment::firstOrFail();$photo=$item->photos[0];Storage::disk('public')->assertExists(substr($photo,8));
  $this->get('/admin/equipments')->assertOk()->assertSee('Panneau test');$this->get('/admin/equipments/'.$item->id.'/edit')->assertOk();
  $this->delete('/admin/categories/'.$category->id)->assertSessionHasErrors('category');$this->assertDatabaseHas('categories',['id'=>$category->id]);
  $this->put('/admin/equipments/'.$item->id,array_merge($data,['title'=>'Panneau modifié']))->assertRedirect();$this->assertDatabaseHas('equipment',['title'=>'Panneau modifié']);
  $this->delete('/admin/equipments/'.$item->id)->assertRedirect();Storage::disk('public')->assertMissing(substr($photo,8));$this->delete('/admin/categories/'.$category->id)->assertRedirect();$this->assertDatabaseCount('categories',0);
 }
 public function test_validation_and_user_access():void {
  $user=User::factory()->create();$this->actingAs($user)->get('/admin/categories')->assertForbidden();$this->post('/admin/equipments',[])->assertForbidden();
  $user->forceFill(['role'=>'admin'])->save();$this->actingAs($user)->post('/admin/equipments',[])->assertSessionHasErrors(['title','category_id','price_per_day']);
  $this->post('/admin/categories',['name'=>'Test','icon'=>'bad-icon'])->assertSessionHasErrors('icon');
 }
 public function test_admin_login_redirects_to_dashboard():void {
  $user=User::factory()->create(['password'=>'password123']);$user->forceFill(['role'=>'admin'])->save();$this->post('/login',['email'=>$user->email,'password'=>'password123'])->assertRedirect('/admin/dashboard');
 }
}
