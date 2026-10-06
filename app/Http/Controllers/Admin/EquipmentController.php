<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class EquipmentController extends Controller {
 public function index(){return view('back.manage.index',['kind'=>'equipments','items'=>Equipment::with(['category','owner'])->latest()->paginate(12)]);}
 public function create(){return $this->form(new Equipment);}
 public function edit(Equipment $equipment){return $this->form($equipment);}
 private function form(Equipment $item){return view('back.manage.form',['kind'=>'equipments','item'=>$item,'categories'=>Category::orderBy('name')->get()]);}
 public function store(Request $request){$data=$this->data($request);$data['owner_id']=$request->user()->id;$data['photos']=$this->photos($request);Equipment::create($data);return redirect()->route('admin.equipments.index')->with('status','Équipement publié.');}
 public function update(Request $request,Equipment $equipment){$data=$this->data($request);$remove=$request->input('remove_photos',[]);$current=$equipment->photos ?? [];$data['photos']=array_merge(array_values(array_diff($current,$remove)),$this->photos($request));$equipment->update($data);foreach(array_intersect($current,$remove) as $photo)$this->deletePhoto($photo);return redirect()->route('admin.equipments.index')->with('status','Équipement mis à jour.');}
 public function destroy(Equipment $equipment){$photos=$equipment->photos ?? [];$equipment->delete();foreach($photos as $photo)$this->deletePhoto($photo);return back()->with('status','Équipement supprimé.');}
 private function data(Request $request){$data=$request->validate(['title'=>['required','string','max:255'],'description'=>['required','string','max:10000'],'category_id'=>['required','exists:categories,id'],'power_capacity'=>['nullable','numeric','min:0','max:9999999999'],'power_unit'=>['required',Rule::in(['W','Wh'])],'condition'=>['required',Rule::in(['Neuf','Très bon état','Bon état','Usagé'])],'price_per_day'=>['required','numeric','min:0','max:99999999'],'deposit'=>['required','numeric','min:0','max:99999999'],'uploads'=>['nullable','array','max:8'],'uploads.*'=>['image','mimes:jpg,jpeg,png,webp','max:5120'],'remove_photos'=>['nullable','array'],'remove_photos.*'=>['string']]);unset($data['uploads'],$data['remove_photos']);return $data;}
 private function photos(Request $request):array{return array_map(fn($file)=>'storage/'.$file->store('equipments','public'),$request->file('uploads',[]));}
 private function deletePhoto(string $photo):void{if(preg_match('#^storage/equipments/[a-zA-Z0-9._-]+$#',$photo))Storage::disk('public')->delete(substr($photo,8));}
}
