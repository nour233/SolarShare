<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Review;
use Illuminate\Http\Request;
class EquipmentController extends Controller
{
    public function home(Request $request) {
        $data=$request->validate(['category'=>['nullable','integer','exists:categories,id'], 'q'=>['nullable','string','max:120']]);
        $categories = Category::withCount('equipments')->orderBy('name')->get();
        $query = $this->search(Equipment::with('category'), ($data['q'] ?? ''))->latest()->orderByDesc('id');
        $equipments = $query->when($data['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id))->paginate(10)->withQueryString()->fragment('equipments');
        $viewData = [
            'categories' => $categories,
            'equipments' => $equipments,
            'reviews' => Review::with('user')->latest()->limit(6)->get(),
        ];
        return view($request->ajax() ? 'front.partials.equipments' : 'front.home', $viewData);
    }
    public function index(Request $request) {
        $data=$request->validate(['category'=>['nullable','integer','exists:categories,id'], 'q'=>['nullable','string','max:120']]);
        return view('equipments.index', ['categories'=>Category::withCount('equipments')->orderBy('name')->get(), 'equipments'=>$this->search(Equipment::with('category'), ($data['q'] ?? ''))->when($data['category'] ?? null, fn($query,$id)=>$query->where('category_id',$id))->latest()->orderByDesc('id')->paginate(10)->withQueryString()->fragment('equipments')]);
    }
    private function search($query, string $search) {
        foreach (preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word).'%';
            $query->where(fn ($part) => $part->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern]));
        }
        return $query;
    }
    public function show(Equipment $equipment) {
        return view('equipments.show', ['equipment'=>$equipment->load(['category','owner'])]);
    }
}
