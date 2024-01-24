<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\Product;
use App\Models\Export;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class productController extends Controller
{
    public function index()
    {
        $products = Product::all();
        $count = $products->count();
        if($count == 0 ){
            return response()->json('no products yet');
        }
        return response()->json(['products'=>$products]); 
    }
    public function show($id)
    {
        $product = Product::where('id',$id)->get();
        if(!$product){
            return response()->json(['product not found']);
        }
        return response()->json(['response'=>$product]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required|string',
            'description'=>'required|string'
        ]);
        if($validator->fails()){
            return response()->json(['error'=>$validator->errors()]);
        }
        $product = Product::create($request->all());
        return response()->json(['product'=>$product]);
    }
    public function update($id, Request $request)
    {
        $product = Product::find($id);
        if(!$product){
            return response()->json('product not found');
        }
        $validator = Validator::make($request->all(),[
            'name' => 'sometimes|string',
            'description' => 'sometimes|string'
        ]);
        if($validator->fails()){
            return response()->json(['error'=>$validator->errors()]);
        }
        $product -> update($request->all());
        return response()->json(['response'=>$product]);
    }
    public function destroy($id){
        $product = Product::find($id);
        if(!$product){
            return response()->json('product not found');
        } 
        $product->delete();
        return response()->json('product deleted successfully');
    }
    public function saveQuantity(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric',
            ]);

            $product_id = $data['product_id'];
            $quantity = $data['quantity'];

            // Use the relationship to update or create the stock entry
            $product = Product::findOrFail($product_id);

            $product->stock()->updateOrCreate(
                ['product_id' => $product_id],
                ['quantity' => $quantity]
            );

            return response()->json(['Quantity saved successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
    public function addProductWithQuantity(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'quantity' => 'required|numeric',
            ]);

            $name = $data['name'];
            $description = $data['description'];
            $quantity = $data['quantity'];
            
            $product = Product::firstOrNew(['name' => $name]);

            $existingProduct = Product::where('name', $name)->where('description', '!=', $description)->first();

            if (!$product->exists || $existingProduct) {
                $product->description = $description;
                $product->save();
            }

            if (!$product->exists) {
                $product->save();
            }

            $product->stock()->updateOrCreate(
                [],
                ['quantity' => DB::raw("quantity + $quantity")]
            );

            $imp=$product->imports()->create([
                'quantity'=>$quantity
            ]);
            if(!$imp){
                return response()->json('import not recorded');
            }
            
            

            return response()->json(['success' => 'Product and quantity added successfully', 'updated data'=>$product]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function importProduct(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'quantity' => 'required|numeric',
            ]);
            $quantity = $data['quantity'];

            $product = Product::findOrFail($id);

            if($quantity < 0){
                return response()->json(['error'=> "you can't enter ". $quantity. ' products']);
            }

            $todate = now();
            $product->imports()->create([
                'product_id' => $id,
                'quantity' => $quantity,
                'import_date' => $todate
            ]);


            $product->stock()->updateOrCreate(
                [],
                ['quantity' => DB::raw("quantity + $quantity")]
            );
            $updatedData = Stock::findOrFail($id)->quantity;
            
            
            return response()->json(['success' => 'Product and quantity added successfully', 'updated quantity'=>$updatedData]);


        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]); 
        }
    
    }

    public function exportProduct(Request $request, $id)
    {
        try {
            
            $data = $request->validate([
                'quantity' => 'required|numeric',
            ]);
            $quantity = $data['quantity'];

            
            $product = Product::findOrFail($id);

            $qty_diff = $product->stock->quantity - $quantity;
            if($qty_diff < 0){
                return response()->json(['error' => 'insufficient stock', 'available stock' => $product->stock->quantity]);
            }

            $todate = now();
            $product->exports()->create([
                'product_id' => $id,
                'quantity' => $quantity,
                'export_date' => $todate
            ]);


            $product->stock()->updateOrCreate(
                [],
                ['quantity' => DB::raw("quantity - $quantity")]
            );
            $updatedData = Stock::findOrFail($id)->quantity;
            
            
            return response()->json(['success' => 'Product and quantity added successfully', 'updated quantity'=>$updatedData]);


        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]); 
        }
    }

    public function showImports()
    {
        try {
            $importData = [];
            $imports = Import::all();
            $count = $imports->count();
    
            foreach ($imports as $import){
                $prod_id = $import->product_id;
                $product = Product::findOrFail($prod_id);
                $stock = Stock::findOrFail($prod_id);
    
                $importData[] = [
                    'import_number' => $import->id,
                    'product' => $product->name,
                    'amount_imported' => $import->quantity,
                    'new_quantity' => $stock->quantity,
                    'date' => $import->import_date
                ];
            }
    
            return response()->json(['there found'=>$count.' records.',$importData]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]); 
        }
    }

    public function showExports()
    {
        try {
            $exportData = [];
            $exports = Export::all();
            $count = $exports->count();
    
            foreach ($exports as $export){
                $prod_id = $export->product_id; 
                $product = Product::findOrFail($prod_id);
                $stock = Stock::findOrFail($prod_id);
    
                $exportData[] = [
                    'export_number' => $export->id,
                    'product' => $product->name,
                    'amount_exported' => $export->quantity,
                    'new_quantity' => $stock->quantity, 
                    'date' => $export->export_date
                ];
            }
    
            return response()->json(['there found'=>$count.' records.',$exportData]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]); 
        }
    }
    private function getImportData() 
    {
        $importData = [];
        $imports = Import::all();
    
        foreach ($imports as $import) {
            $prod_id = $import->product_id;
            $product = Product::findOrFail($prod_id);
            $stock = Stock::findOrFail($prod_id);
    
            $importData[] = [
                'type' => 'import',
                'id' => $import->id,
                'product' => $product->name,
                'amount' => $import->quantity,
                'new_quantity' => $stock->quantity,
                'date' => $import->import_date,
            ];
        }
    
        return $importData;
    }
    
    private function getExportData() 
    {
        $exportData = [];
        $exports = Export::all();
    
        foreach ($exports as $export) {
            $prod_id = $export->product_id;
            $product = Product::findOrFail($prod_id);
            $stock = Stock::findOrFail($prod_id);
    
            $exportData[] = [
                'type' => 'export',
                'id' => $export->id,
                'product' => $product->name,
                'amount' => $export->quantity,
                'new_quantity' => $stock->quantity,
                'date' => $export->export_date,
            ];
        }
    
        return $exportData;
    }

    public function showImportsAndExports()
    {
        try {
            $importData = $this->getImportData();
            $exportData = $this->getExportData();
    
            $combinedData = array_merge($importData, $exportData);
            
            usort($combinedData, function ($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
    
            return response()->json($combinedData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]); 
        }
    }
    
    
    
    
    
    
}