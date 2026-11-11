<?php

namespace App\Domains\Fiscal\Contracts;

use App\Models\Sale;

/**
 * Contrato del conector fiscal SUNAT (Perú).
 *
 * TODO: implementar envío real (OSE / API SUNAT), firmado XML/UBL,
 * consulta de CDR y manejo de rechazos. Sin llamadas reales en el MVP demo.
 */
interface SunatGateway
{
    /**
     * @return array{status: string, message: string, external_id?: string|null}
     */
    public function sendDocument(Sale $sale): array;

    /**
     * @return array{status: string, message: string}
     */
    public function queryStatus(Sale $sale): array;
}
