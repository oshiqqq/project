<?php
namespace App\Http\Controllers;

use App\DTO\ServerInfoDTO;
use App\DTO\ClientInfoDTO;
use App\DTO\DatabaseInfoDTO;
use Illuminate\Support\Facades\DB;

class InfoController extends Controller
{
    public function serverInfo()
    {
        // данный метод возвращает данные о версии PHP
        $dto = new ServerInfoDTO(phpversion());
        return response()->json($dto);
    }

    public function clientInfo()
    {
        // данный метод возвращает данные о клиенте (IP и User-Agent и т.д)
        $dto = new ClientInfoDTO(request()->ip(), request()->userAgent());
        return response()->json($dto);
    }

    public function databaseInfo()
    {
        // данный метод возвращает данные о базе данных
        $dto = new DatabaseInfoDTO(DB::connection()->getDatabaseName());
        return response()->json($dto);
    }
}