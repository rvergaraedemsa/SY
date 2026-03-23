<?php
function enviarSOAP($xml)
{
    file_put_contents("./debug/request_debug.xml", $xml);
    $url = "https://srv-symbiot/SymbiotSOAPIntegrator/DataService.svc";

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

    return $response;
}
function generarXMLQuery($meters){

    $items = "";

    foreach($meters as $m){

        $m = htmlspecialchars(trim($m));
        
        if(empty($m)) continue;

        $items .= "
        <ser:MeasurementPointResultTypeReferences>
            <ser:MeasurementPointName>{$m}</ser:MeasurementPointName>
            <ser:ResultTypeNames>
                <arr:string>Active_Abs_MaxDemand_CUM_BP1</arr:string>
                <arr:string>Active_Abs_MaxDemand_CUM_T1_BP1</arr:string>
                <arr:string>Active_Abs_MaxDemand_CUM_T2_BP1</arr:string>
                <arr:string>Active_Abs_MaxDemand_CUM_T3_BP1</arr:string>
                <arr:string>Active_Minus_MaxDemand_CUM_T1_BP1</arr:string>
                <arr:string>Active_Minus_MaxDemand_CUM_T2_BP1</arr:string>
                <arr:string>Active_Minus_MaxDemand_CUM_T3_BP1</arr:string>
                <arr:string>Active_Plus_MaxDemand_CUM_T1_BP1</arr:string>
                <arr:string>Active_Plus_MaxDemand_CUM_T2_BP1</arr:string>
                <arr:string>Active_Plus_MaxDemand_CUM_T3_BP1</arr:string>
                  
                <arr:string>ActiveEnergy_Abs_CUM_BP1</arr:string>
                <arr:string>ActiveEnergy_Abs_CUM_BP1_T1</arr:string>
                <arr:string>ActiveEnergy_Abs_CUM_BP1_T2</arr:string>
                <arr:string>ActiveEnergy_Abs_CUM_BP1_T3</arr:string>
                <arr:string>ActiveEnergy_Minus_CUM_BP1</arr:string>
                <arr:string>ActiveEnergy_Minus_CUM_T1_BP1</arr:string>
                <arr:string>ActiveEnergy_Minus_CUM_T2_BP1</arr:string>
                <arr:string>ActiveEnergy_Minus_CUM_T3_BP1</arr:string>
                  
                <arr:string>ActiveEnergy_Plus_CUM_BP1</arr:string>
                <arr:string>ActiveEnergy_Plus_CUM_T1_BP1</arr:string>
                <arr:string>ActiveEnergy_Plus_CUM_T2_BP1</arr:string>
                <arr:string>ActiveEnergy_Plus_CUM_T2_BP1</arr:string>
                  
                <arr:string>AparentEnergy_Minus_CUM_BP1</arr:string>
                <arr:string>AparentEnergy_Plus_CUM_BP1</arr:string>   
            </ser:ResultTypeNames>
        </ser:MeasurementPointResultTypeReferences>";
    }

    return '<?xml version="1.0" encoding="UTF-8"?>
    <soapenv:Envelope 
        xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
        xmlns:ser="http://iskraemeco.si/services" 
        xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays">

        <soapenv:Header/>

        <soapenv:Body>

            <ser:QueryResults>

                <ser:measurementPointResultTypes>
                    '.$items.'
                </ser:measurementPointResultTypes>

                <ser:intervalStart>2025-10-01T00:00:00Z</ser:intervalStart>
                <ser:intervalEnd>2026-03-23T23:00:00Z</ser:intervalEnd>

                <ser:sourceFilter>
                    <ser:Measured>true</ser:Measured>
                    <ser:Manual>true</ser:Manual>
                    <ser:Aggregated>true</ser:Aggregated>
                    <ser:Imported>true</ser:Imported>
                    <ser:Estimated>true</ser:Estimated>
                </ser:sourceFilter>

            </ser:QueryResults>

        </soapenv:Body>

    </soapenv:Envelope>';
}
function parsearResultados($response){

    libxml_use_internal_errors(true);

    // 🔥 eliminar prefijos tipo s:
    $response = str_replace(['s:', 'ser:'], '', $response);

    // 🔥 eliminar namespaces conflictivos
    $response = preg_replace('/xmlns(:\w+)?="[^"]+"/i', '', $response);

    $xml = simplexml_load_string($response);

    if(!$xml){
        return [["error"=>"XML inválido"]];
    }

    // 🔥 detectar error SOAP
    if(isset($xml->Body->Fault)){
        return [[
            "error" => "SOAP Fault",
            "mensaje" => (string)$xml->Body->Fault->faultstring
        ]];
    }

    if(!isset($xml->Body->QueryResultsResponse)){
        return [["error"=>"Sin datos"]];
    }

    $data = [];

    foreach($xml->Body->QueryResultsResponse->QueryResultsResult->MeasurementPointResults as $meter){

        $meterName = (string)$meter->MeasurementPointName;

        foreach($meter->ResultsByResultType->ResultTypeResults as $type){

            $typeName = (string)$type->ResultTypeName;

            // 🔥 IMPORTANTE: puede venir vacío <Results/>
            if(!isset($type->Results->Result)) continue;

            foreach($type->Results->Result as $result){

                $data[] = [
                    "Medidor" => $meterName,
                    "Tipo" => $typeName,
                    "Fecha" => (string)$result->Timestamp,
                    "Valor" => (string)$result->Value->Value
                ];
            }
        }
    }

    // 🔥 si no hay datos reales
    if(empty($data)){
        return [["info"=>"Sin resultados para ese tipo/rango"]];
    }

    return $data;
}
