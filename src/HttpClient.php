<?php

namespace Kavenegar;

use Kavenegar\Exceptions\HttpException;

class HttpClient
{
    private const USER_AGENT = 'Kavenegar-PHP/' . KavenegarApi::VERSION;
    
    private string $userAgent;
    private ?string $proxy = null;
    private array $noProxy = [];
    
    public function __construct(?string $userAgent = null)
    {
        $this->userAgent = $userAgent ?? self::USER_AGENT;
        $this->loadProxySettings();
    }
    
    /**
     * Load proxy settings from environment variables
     */
    private function loadProxySettings(): void
    {
        // Check for proxy environment variables
        $httpProxy = getenv('HTTP_PROXY') ?: getenv('http_proxy');
        $httpsProxy = getenv('HTTPS_PROXY') ?: getenv('https_proxy');
        $noProxy = getenv('NO_PROXY') ?: getenv('no_proxy');
        
        // Use HTTPS proxy if available, otherwise fall back to HTTP proxy
        $this->proxy = $httpsProxy ?: $httpProxy ?: null;
        
        // Parse NO_PROXY environment variable
        if ($noProxy) {
            $this->noProxy = array_map('trim', explode(',', $noProxy));
        }
    }
    
    /**
     * Check if a URL should bypass the proxy
     */
    private function shouldBypassProxy(string $url): bool
    {
        if (empty($this->proxy) || empty($this->noProxy)) {
            return false;
        }
        
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }
        
        foreach ($this->noProxy as $pattern) {
            // Handle wildcard patterns
            if ($pattern === '*' || $pattern === $host) {
                return true;
            }
            // Handle domain suffix patterns (e.g., .example.com)
            if (str_starts_with($pattern, '.') && str_ends_with($host, $pattern)) {
                return true;
            }
            // Handle subdomain patterns
            if (str_ends_with($host, '.' . $pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Execute an HTTP POST request
     * 
     * @param string $url The URL to send the request to
     * @param array|null $data The data to send in the request body
     * @return mixed The decoded JSON response
     * @throws HttpException
     */
    public function post(string $url, ?array $data = null): mixed
    {
        // Try to detect and use available HTTP clients in order of preference
        
        // 1. Try Laravel HTTP Client
        if (class_exists('\Illuminate\Support\Facades\Http')) {
            return $this->postWithLaravel($url, $data);
        }
        
        // 2. Try Guzzle
        if (class_exists('\GuzzleHttp\Client')) {
            return $this->postWithGuzzle($url, $data);
        }
        
        // 3. Try cURL
        if (extension_loaded('curl')) {
            return $this->postWithCurl($url, $data);
        }
        
        // 4. Fall back to file_get_contents
        return $this->postWithFileGetContents($url, $data);
    }
    
    /**
     * Execute HTTP POST request using Laravel's HTTP client
     */
    private function postWithLaravel(string $url, ?array $data): mixed
    {
        try {
            $http = \Illuminate\Support\Facades\Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => $this->userAgent,
                ]);
            
            // Configure proxy if needed
            if ($this->proxy && !$this->shouldBypassProxy($url)) {
                $http = $http->withOptions(['proxy' => $this->proxy]);
            }
            
            $response = $http->post($url, $data ?? []);
            
            if (!$response->successful()) {
                $json = $response->json();
                if ($json && isset($json['return'])) {
                    throw new HttpException($json['return']['message'] ?? 'Request failed', $response->status());
                }
                throw new HttpException('Request have errors', $response->status());
            }
            
            return $response->object();
        } catch (\Exception $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Execute HTTP POST request using Guzzle
     */
    private function postWithGuzzle(string $url, ?array $data): mixed
    {
        try {
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
                    'User-Agent' => $this->userAgent,
                ],
                'form_params' => $data ?? [],
                'http_errors' => false,
                'verify' => true,
            ];
            
            // Configure proxy if needed
            if ($this->proxy && !$this->shouldBypassProxy($url)) {
                $options['proxy'] = $this->proxy;
            }
            
            $client = new \GuzzleHttp\Client();
            $response = $client->post($url, $options);
            
            $code = $response->getStatusCode();
            $body = (string) $response->getBody();
            
            $json_response = json_decode($body);
            
            if ($code != 200 && is_null($json_response)) {
                throw new HttpException("Request have errors", $code);
            }
            
            if (isset($json_response->return) && $json_response->return->status != 200) {
                throw new HttpException($json_response->return->message, $json_response->return->status);
            }
            
            return $json_response;
        } catch (\Exception $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Execute HTTP POST request using cURL
     */
    private function postWithCurl(string $url, ?array $data): mixed
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
            'User-Agent: ' . $this->userAgent,
        ];
        
        $fields_string = "";
        if (!is_null($data)) {
            $fields_string = http_build_query($data);
        }
        
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $fields_string);
        
        // Configure proxy if needed
        if ($this->proxy && !$this->shouldBypassProxy($url)) {
            curl_setopt($handle, CURLOPT_PROXY, $this->proxy);
        }
        
        $response = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($handle);
        $curl_error = curl_error($handle);
        curl_close($handle);
        
        if ($curl_errno) {
            throw new HttpException($curl_error, $curl_errno);
        }
        
        $json_response = json_decode($response);
        
        if ($code != 200 && is_null($json_response)) {
            throw new HttpException("Request have errors", $code);
        }
        
        if (isset($json_response->return) && $json_response->return->status != 200) {
            throw new HttpException($json_response->return->message, $json_response->return->status);
        }
        
        return $json_response;
    }
    
    /**
     * Execute HTTP POST request using file_get_contents
     */
    private function postWithFileGetContents(string $url, ?array $data): mixed
    {
        $fields_string = "";
        if (!is_null($data)) {
            $fields_string = http_build_query($data);
        }
        
        $options = [
            'http' => [
                'header' => implode("\r\n", [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                    'User-Agent: ' . $this->userAgent,
                ]),
                'method' => 'POST',
                'content' => $fields_string,
            ],
        ];
        
        // Configure proxy if needed
        if ($this->proxy && !$this->shouldBypassProxy($url)) {
            $options['http']['proxy'] = $this->proxy;
            $options['http']['request_fulluri'] = true;
        }
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            throw new HttpException($error['message'] ?? 'Request failed', 0);
        }
        
        // Get HTTP response code from headers
        $code = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $code = (int) $matches[1];
                    break;
                }
            }
        }
        
        $json_response = json_decode($response);
        
        if ($code != 200 && is_null($json_response)) {
            throw new HttpException("Request have errors", $code);
        }
        
        if (isset($json_response->return) && $json_response->return->status != 200) {
            throw new HttpException($json_response->return->message, $json_response->return->status);
        }
        
        return $json_response;
    }
}
