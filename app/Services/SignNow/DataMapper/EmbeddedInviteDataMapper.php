<?php

declare(strict_types=1);

namespace App\Services\SignNow\DataMapper;

use App\Data\EmbeddedInviteInput;
use App\Http\Requests\EmbeddedInviteRequest;

class EmbeddedInviteDataMapper
{
    public function map(EmbeddedInviteRequest $request): EmbeddedInviteInput
    {
        return new EmbeddedInviteInput(
            $request->input('fields.first_name'),
            $request->input('fields.last_name'),
            $request->input('fields.comment')
        );
    }
}
