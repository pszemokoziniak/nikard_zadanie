<?php

namespace App\Http\Controllers;

use App\Services\Rest\JsonPlaceholderClient;
use App\Services\Soap\CountryInfoClient;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $restPosts = [];
        $soapCapital = null;
        $restError = null;
        $soapError = null;

        try {
            $restPosts = app(JsonPlaceholderClient::class)->getPosts(5);
        } catch (\Throwable $e) {
            $restError = $e->getMessage();
        }

        try {
            if (!class_exists(\SoapClient::class)) {
                throw new \RuntimeException('PHP extension ext-soap is not enabled.');
            }
            $soapCapital = app(CountryInfoClient::class)->getCapitalCity('PL');
        } catch (\Throwable $e) {
            $soapError = $e->getMessage();
        }

        return Inertia::render('Dashboard/Index', [
            'restPosts' => $restPosts,
            'soapCapital' => $soapCapital,
            'restError' => $restError,
            'soapError' => $soapError,
        ]);
    }
}
