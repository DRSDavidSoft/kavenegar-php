<?php

// Loopback-only HTTP/TLS fixture. No external service or real credentials.
$context = stream_context_create();
if (isset($argv[1])) {
    stream_context_set_option($context, 'ssl', 'local_cert', $argv[1]);
    stream_context_set_option($context, 'ssl', 'verify_peer', false);
}
$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
if ($server === false) {
    fwrite(STDERR, $error);
    exit(1);
}
echo stream_socket_get_name($server, false) . "\n";
flush();
while ($client = stream_socket_accept($server, 30)) {
    stream_set_timeout($client, 5);
    if (isset($argv[1]) && @stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_TLS_SERVER) !== true) {
        fclose($client);
        continue;
    }
    $request = fgets($client);
    if ($request === false) {
        fclose($client);
        continue;
    }
    $headers = [];
    while (($line = fgets($client)) !== false && trim($line) !== '') {
        [$name, $value] = explode(':', $line, 2);
        $headers[strtolower($name)] = trim($value);
    }
    if (strtolower($headers['expect'] ?? '') === '100-continue') {
        fwrite($client, "HTTP/1.1 100 Continue\r\n\r\n");
    }
    $body = '';
    $length = (int) ($headers['content-length'] ?? 0);
    while (strlen($body) < $length) {
        $chunk = fread($client, $length - strlen($body));
        if ($chunk === false || $chunk === '') {
            break;
        }
        $body .= $chunk;
    }
    [$method, $target] = explode(' ', trim($request));
    $path = parse_url($target, PHP_URL_PATH);
    parse_str(parse_url($target, PHP_URL_QUERY) ?? '', $query);
    parse_str($body, $form);
    $entries = ['method' => $method, 'path' => $path, 'query' => $query, 'form' => $form, 'body' => $body, 'headers' => $headers];
    $status = 200;
    $payload = ['return' => ['status' => 200, 'message' => 'OK'], 'entries' => $entries];
    switch ($path) {
        case '/malformed': $payload = '<html>proxy error</html>'; break;
        case '/null': $payload = 'null'; break;
        case '/scalar': $payload = '42'; break;
        case '/missing-return': $payload = ['entries' => []]; break;
        case '/bad-return': $payload = ['return' => 'OK', 'entries' => []]; break;
        case '/bad-status': $payload['return']['status'] = '200'; break;
        case '/missing-message': unset($payload['return']['message']); break;
        case '/missing-entries': unset($payload['entries']); break;
        case '/null-entries': $payload['entries'] = null; break;
        case '/api-error':
            $status = 400;
            $payload = ['return' => ['status' => 401, 'message' => 'Invalid API key']];
            break;
        case '/http-error': $status = 503; break;
        case '/v1/dummy/sms/send.json/':
        case '/v1/dummy/verify/lookup.json/':
            $payload['entries'] = [['messageid' => 42, 'message' => $form['message'] ?? 'OTP', 'receptor' => $form['receptor'] ?? '', 'status' => 1]];
            break;
        case '/v1/dummy/account/info.json/':
            $payload['entries'] = ['remaincredit' => 123];
            break;
    }
    $response = is_string($payload) ? $payload : json_encode($payload, JSON_THROW_ON_ERROR);
    fwrite($client, "HTTP/1.1 $status Fixture\r\nContent-Type: application/json\r\nContent-Length: " . strlen($response) . "\r\nConnection: close\r\n\r\n" . $response);
    fclose($client);
}
fclose($server);
