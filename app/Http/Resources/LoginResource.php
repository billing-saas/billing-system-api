<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'userId'    => $this['user']['userId'],
                'email'     => $this['user']['email'],
                'fullName'  => $this['profile']['first_name'] . ' ' . $this['profile']['last_name'],
            ],
            'accessToken' => $this['accessToken'],
            'sessionId'   => $this['sessionId'],
            'profile'     => [
                'firstName'   => $this['profile']['first_name'],
                'lastName'    => $this['profile']['last_name'],
                'phone'       => $this['profile']['phone'],
                'companyName' => $this['profile']['company_name'],
                'currency'    => $this['profile']['currency'],
            ],
        ];
    }
}
