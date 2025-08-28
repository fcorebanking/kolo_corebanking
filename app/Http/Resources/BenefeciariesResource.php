<?php
namespace App\Http\Resources;

use App\Models\banks;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BenefeciariesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $bank = banks::where('id', $this->bank_id)->first();
        return [
            'bank_id'   => $this->bank_id,
            'bank_name' => $bank->bank_name,
            'bank_code' => $bank->bank_code,
            'name'      => $this->name,
            'accountNo' => $this->accountNo,

        ];
    }
}
