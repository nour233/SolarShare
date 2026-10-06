<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use Illuminate\Http\Request;
class EquipmentController extends Controller
{
    public function home(Request $request) {
        $data=$request->validate(['category'=>['nullable','integer','exists:categories,id']]);
        $viewData = [
            'categories' => Category::withCount('equipments')->orderBy('name')->get(),
            'equipments' => Equipment::with('category')->when($data['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id))->latest()->limit(6)->get(),
            'reviews' => Review::with('user')->latest()->limit(6)->get(),
        ];
        return view($request->ajax() ? 'front.partials.equipments' : 'front.home', $viewData);
    }
    public function index(Request $request) {
        $data=$request->validate(['category'=>['nullable','integer','exists:categories,id']]);
        return view('equipments.index', ['categories'=>Category::withCount('equipments')->orderBy('name')->get(), 'equipments'=>Equipment::with('category')->when($data['category'] ?? null, fn($query,$id)=>$query->where('category_id',$id))->latest()->paginate(12)->withQueryString()]);
    }
    public function show(Equipment $equipment) {
        return view('equipments.show', ['equipment'=>$equipment->load(['category','owner'])]);
    }
}
