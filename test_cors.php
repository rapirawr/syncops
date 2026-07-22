<?php
$ch = curl_init('http://127.0.0.1:8000/api/v1/telemetry/collect');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Origin: http://127.0.0.1:5500', 'Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['project' => 'amsle-mas-budi', 'path' => '/test']));
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo $res;
