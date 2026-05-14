<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receipt;
use App\Http\Requests\Upload2307Request;
use App\Http\Requests\UpdatePaymentStatusRequest;
use Illuminate\Support\Facades\Storage;

class PaymentsController extends Controller
{
    public function index(Request $req)
    {
        $payments = Receipt::with('checkout','user')->orderBy('created_at','desc')->paginate(50);
        return response()->json(['data' => $payments]);
    }

    public function updateStatus(UpdatePaymentStatusRequest $req, $id)
    {
        $receipt = Receipt::findOrFail($id);

        $status = $req->input('status');
        // Map status -> paid_at: mark as paid when status is 'paid', otherwise unset paid_at
        if ($status === 'paid') {
            $receipt->paid_at = now();
        } else {
            $receipt->paid_at = null;
        }

        $receipt->save();

        return response()->json(['message' => 'Updated', 'payment' => $receipt]);
    }

    public function upload2307(Upload2307Request $req, $id)
    {
        $receipt = Receipt::findOrFail($id);
        $file = $req->file('form_2307');

        $path = $file->storePublicly('payments/2307', ['disk' => 'public']);
        // reuse existing `receipt_image` column to store uploaded 2307 URL
        $receipt->receipt_image = Storage::disk('public')->url($path);
        $receipt->save();

        return response()->json(['message' => 'Uploaded', 'url' => $receipt->receipt_image]);
    }
}
