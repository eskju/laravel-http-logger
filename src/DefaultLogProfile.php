<?php

namespace Eskju\HttpLogger;

use Illuminate\Http\Request;

class DefaultLogProfile implements LogProfile
{
    public function shouldLogRequest(Request $request): bool
    {
        return in_array(strtolower($request->method()), ['get', 'post', 'put', 'patch', 'delete']);
    }
}
