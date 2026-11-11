<?php

namespace App\Domains\Fiscal\Services;

use App\Domains\Fiscal\Contracts\SunatGateway;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;

/**
 * Stub SUNAT: no realiza llamadas externas.
 *
 * TODO: reemplazar por implementación real cuando se configure el OSE / certificados.
 */
class NullSunatGateway implements SunatGateway
{
    public function sendDocument(Sale $sale): array
    {
        Log::info('[SUNAT stub] sendDocument omitido', [
            'sale_id' => $sale->id,
            'tenant_id' => $sale->tenant_id,
        ]);

        return [
            'status' => 'stubbed',
            'message' => 'SUNAT no configurado: emisión simulada (TODO integración real).',
            'external_id' => null,
        ];
    }

    public function queryStatus(Sale $sale): array
    {
        Log::info('[SUNAT stub] queryStatus omitido', [
            'sale_id' => $sale->id,
        ]);

        return [
            'status' => 'stubbed',
            'message' => 'Consulta SUNAT no implementada (TODO).',
        ];
    }
}
