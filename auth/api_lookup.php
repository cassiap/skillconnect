<?php
// api_lookup.php
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';
$doc  = preg_replace('/\D/', '', $_GET['doc'] ?? '');

if (!in_array($type, ['cpf','cnpj'])) {
  http_response_code(400);
  echo json_encode(['message'=>'Parâmetro type inválido']);
  exit;
}

if ($type === 'cpf' && strlen($doc) !== 11) {
  http_response_code(400);
  echo json_encode(['message'=>'CPF inválido']);
  exit;
}
if ($type === 'cnpj' && strlen($doc) !== 14) {
  http_response_code(400);
  echo json_encode(['message'=>'CNPJ inválido']);
  exit;
}

function validaCPF($cpf) {
  // validação básica de CPF (dígitos verificadores)
  if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;
  for ($t = 9; $t < 11; $t++) {
    $d = 0;
    for ($c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
    $d = ((10 * $d) % 11) % 10;
    if ($cpf[$t] != $d) return false;
  }
  return true;
}

if ($type === 'cpf') {
  if (!validaCPF($doc)) {
    http_response_code(400);
    echo json_encode(['message'=>'CPF inválido (DV)']);
    exit;
  }
  // SIMULAÇÃO: devolve um payload mínimo
  echo json_encode([
    'nome'      => 'Usuário CPF ' . substr($doc,0,3) . '***',
    'cep'       => '70000-000',
    'uf'        => 'DF',
    'municipio' => 'Brasília',
    'logradouro'=> 'SQN 100 Bloco A',
    'bairro'    => 'Asa Norte'
  ]);
  exit;
}

if ($type === 'cnpj') {
  $url = "https://brasilapi.com.br/api/cnpj/v1/$doc";
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
  ]);
  $res = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);

  if ($res === false || $http >= 400) {
    http_response_code(502);
    echo json_encode(['message'=>"Erro ao consultar CNPJ ($http): $err"]);
    exit;
  }

  $data = json_decode($res, true);
  // Normaliza campos úteis
  $out = [
    'razao_social' => $data['razao_social'] ?? null,
    'nome'         => $data['razao_social'] ?? null,
    'nome_fantasia'=> $data['nome_fantasia'] ?? null,
    'cep'          => $data['cep'] ?? null,
    'uf'           => $data['uf'] ?? null,
    'municipio'    => $data['municipio'] ?? null,
    'logradouro'   => $data['logradouro'] ?? null,
    'bairro'       => $data['bairro'] ?? null,
    'cnae_principal'=> $data['cnae_fiscal'] ?? null
  ];
  echo json_encode($out);
  exit;
}
