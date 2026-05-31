<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkin\StoreCheckinRequest;
use App\Services\CheckinService;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function __construct(
        protected CheckinService $checkinService
    ) {}

    public function today(Request $request)
    {
        return $this->checkinService->today($request);
    }

    public function index(Request $request)
    {
        return $this->checkinService->index($request);
    }

    public function store(StoreCheckinRequest $request)
    {
        return $this->checkinService->store($request);
    }

    public function show(Request $request, int $id)
    {
        return $this->checkinService->show($request, $id);
    }
}