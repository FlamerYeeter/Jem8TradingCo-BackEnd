<?php
$ctrl = app(\App\Http\Controllers\ProductRequestController::class);
$req = Illuminate\Http\Request::create('/product-requests','POST',['product'=>json_encode(['id'=>1,'name'=>'Smoke Test Product']),'description'=>'smoke test']);
$create = $ctrl->store($req);
echo "CREATE:\n"; echo $create->getContent(); echo "\n\n";
echo "ADMIN LIST (first 2000 chars):\n"; $list = $ctrl->index(); $c = $list->getContent(); echo substr($c,0,2000); echo "\n\n";
$body = json_decode($create->getContent(), true); $id = $body['data']['id'] ?? null; if ($id) {
    $req2 = Illuminate\Http\Request::create('/admin/product-requests/'.$id.'/status','PATCH',['status'=>'found']);
    $upd = $ctrl->updateStatus($req2,$id); echo "UPDATE:\n"; echo $upd->getContent(); echo "\n\n";
    $req3 = Illuminate\Http\Request::create('/admin/product-requests/'.$id.'/create-order','POST',[]);
    $co = $ctrl->createOrder($req3,$id); echo "CREATE_ORDER:\n"; echo $co->getContent(); echo "\n\n";
} else { echo "No id returned from create\n"; }
