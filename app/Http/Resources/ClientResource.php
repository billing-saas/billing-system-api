<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'company_name' => $this->company_name,
            'tax_number'   => $this->tax_number,
            'address'      => $this->address,
            'city'         => $this->city,
            'postal_code'  => $this->postal_code,
            'country'      => $this->country,
            'notes'        => $this->notes,
            'created_at'   => $this->created_at->toDateTimeString(),
            'updated_at'   => $this->updated_at->toDateTimeString(),
        ];
    }
}
