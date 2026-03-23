<?php
require 'soapClient.php';

function parseMeters($input){
    return array_map('trim', explode(',', $input));
}

/* 🔍 CONSULTA */
function consultarMedidores($input){

    $meters = parseMeters($input);

    $xml = generarXMLQuery($meters);

    $response = enviarSOAP($xml);

    return parsearResultados($response);
}

/* 📜 EVENTOS (base) */
function consultarEventos($input){
    return [
        ["info" => "Eventos aún no implementados"]
    ];
}

/* ⚡ CONTROL (base) */
function controlarMedidor($meter,$cmd){
    return [
        ["medidor"=>$meter,"accion"=>$cmd,"estado"=>"Enviado"]
    ];
}