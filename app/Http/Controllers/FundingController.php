<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Lootwright\Application\Funding\UseCases\RetrieveFundingStatus;

final class FundingController extends Controller
{
    public function __invoke(RetrieveFundingStatus $useCase): Response
    {
        return Inertia::render('Funding', [
            'funding' => $useCase->handle()->jsonSerialize(),
        ]);
    }
}
