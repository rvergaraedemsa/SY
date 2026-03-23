<?php

function consultarSymbiot()
{

    $url = "https://srv-symbiot/SymbiotSOAPIntegrator/DataService.svc";

    $xml = file_get_contents("request.xml");

    $headers = [
        "Content-Type: text/xml;charset=UTF-8",
        'SOAPAction: "http://iskraemeco.si/services/QueryResultsRequest"'
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);

    curl_close($ch);

    /* limpiar namespaces para evitar errores */

    $response = str_replace(
        ['xmlns="http://iskraemeco.si/services"', 's:'],
        ['', ''],
        $response
    );

    $xml = simplexml_load_string($response);

    $data = [];

    foreach ($xml->Body->QueryResultsResponse->QueryResultsResult->MeasurementPointResults as $meter) {

        $meterName = (string)$meter->MeasurementPointName;

        //foreach ($meter->ResultsByResultType->ResultTypeResults->Results->Result as $result) {

            foreach ($meter->ResultsByResultType->ResultTypeResults as $type) {

                $typeName = (string)$type->ResultTypeName;

                foreach ($type->Results->Result as $result) {

                    $data[] = [
                        "meter" => $meterName,
                        "type" => $typeName,
                        "time" => (string)$result->Timestamp,
                        "value" => (string)$result->Value->Value
                    ];
                }
            }
       // }
    }

    return $data;
}
