<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class CategoryController extends Controller {
 public function index(){return view('back.manage.index',['kind'=>'categories','items'=>Category::withCount('equipments')->latest()->paginate(12)]);}
 public function create(){return view('back.manage.form',['kind'=>'categories','item'=>new Category]);}
 public function store(Request $request){Category::create($this->data($request));return redirect()->route('admin.categories.index')->with('status','Catégorie créée.');}
 public function edit(Category $category){return view('back.manage.form',['kind'=>'categories','item'=>$category]);}
 public function update(Request $request,Category $category){$category->update($this->data($request,$category));return redirect()->route('admin.categories.index')->with('status','Catégorie mise à jour.');}
 public function destroy(Category $category){if($category->equipments()->exists())return back()->withErrors(['category'=>'Cette catégorie contient des équipements. Déplacez-les avant de la supprimer.']);$category->delete();return back()->with('status','Catégorie supprimée.');}
 private function data(Request $request,?Category $category=null){return $request->validate(['name'=>['required','string','max:255',Rule::unique('categories')->ignore($category?->id)],'description'=>['nullable','string','max:5000'],'icon'=>['required',Rule::in(['fa-solar-panel','fa-battery-full','fa-wind','fa-bolt'])]]);}
}
