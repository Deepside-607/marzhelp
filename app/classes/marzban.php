<?php

class MarzbanAPI {
    private $baseUrl;
    private $accessToken;
    private $timeout = 30;
    private $tokenFile = 'marzban_token.json';
    private $username;
    private $password;

    public function __construct($baseUrl, $username, $password) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->loadToken(); 
    }

    private function loadToken() {
        if (file_exists($this->tokenFile)) {
            $tokenData = json_decode(file_get_contents($this->tokenFile), true);
            if ($tokenData && isset($tokenData['access_token']) && isset($tokenData['expires_at'])) {
                if (time() < $tokenData['expires_at']) {
                    $this->accessToken = $tokenData['access_token'];
                }
            }
        }
    }

    private function saveToken($token) {
        $tokenData = [
            'access_token' => $token,
            'expires_at' => time() + (24 * 3600) 
        ];
        file_put_contents($this->tokenFile, json_encode($tokenData));
        $this->accessToken = $token;
    }

    private function authenticateIfNeeded() {
        if (!$this->accessToken || !$this->isTokenValid()) {
            $data = [
                'grant_type' => 'password',
                'username' => $this->username,
                'password' => $this->password,
                'scope' => '',
            ];
            $response = $this->sendRequest('POST', '/api/admin/token', $data, [], false);
            $this->saveToken($response['access_token']);
            return $response;
        }
        return ['access_token' => $this->accessToken];
    }

    private function isTokenValid() {
        if (!$this->accessToken || !file_exists($this->tokenFile)) {
            return false;
        }
        $tokenData = json_decode(file_get_contents($this->tokenFile), true);
        return $tokenData && time() < $tokenData['expires_at'];
    }

    private function sendRequest($method, $endpoint, $data = [], $headers = [], $useToken = true) {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $defaultHeaders = [
            'Content-Type: application/json',
        ];
        if ($useToken && $this->accessToken) {
            $defaultHeaders[] = "Authorization: Bearer {$this->accessToken}";
        }
        $headers = array_merge($defaultHeaders, $headers);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if (!empty($data)) {
            if ($method === 'POST' && $endpoint === '/api/admin/token') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                $headers[0] = 'Content-Type: application/x-www-form-urlencoded';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: $error");
        }

        $result = json_decode($response, true);
        if ($httpCode === 401 && $useToken) { 
            $this->authenticateIfNeeded();
            return $this->sendRequest($method, $endpoint, $data, $headers); 
        } elseif ($httpCode >= 400) {
            $errorMsg = $result['detail'] ?? "HTTP Error: $httpCode";
            throw new Exception($errorMsg, $httpCode);
        }

        return $result;
    }

    public function getCurrentAdmin() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', '/api/admin');
    }

    public function createAdmin($adminData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', '/api/admin', $adminData);
    }

    public function modifyAdmin($username, $adminData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('PUT', "/api/admin/{$username}", $adminData);
    }

    public function removeAdmin($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('DELETE', "/api/admin/{$username}");
    }

    public function getAdmins($offset = null, $limit = null, $username = null) {
        $this->authenticateIfNeeded();
        $query = [];
        if ($offset !== null) $query['offset'] = $offset;
        if ($limit !== null) $query['limit'] = $limit;
        if ($username !== null) $query['username'] = $username;
        $queryString = $query ? '?' . http_build_query($query) : '';
        return $this->sendRequest('GET', "/api/admins{$queryString}");
    }

    public function disableAllActiveUsers($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', "/api/admin/{$username}/users/disable");
    }

    public function activateAllDisabledUsers($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', "/api/admin/{$username}/users/activate");
    }

    public function resetAdminUsage($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', "/api/admin/usage/reset/{$username}");
    }

    public function getAdminUsage($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', "/api/admin/usage/{$username}");
    }

    public function getCoreStats() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', '/api/core');
    }

    public function restartCore() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', '/api/core/restart');
    }

    public function getCoreConfig() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', '/api/core/config');
    }

    public function modifyCoreConfig($configData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('PUT', '/api/core/config', $configData);
    }

    public function getNodeSettings() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', '/api/node/settings');
    }

    public function addNode($nodeData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', '/api/node', $nodeData);
    }

    public function getNode($nodeId) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', "/api/node/{$nodeId}");
    }

    public function modifyNode($nodeId, $nodeData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('PUT', "/api/node/{$nodeId}", $nodeData);
    }

    public function removeNode($nodeId) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('DELETE', "/api/node/{$nodeId}");
    }

    public function getNodes() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', '/api/nodes');
    }

    public function reconnectNode($nodeId) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', "/api/node/{$nodeId}/reconnect");
    }

    public function getNodesUsage($start = '', $end = '') {
        $this->authenticateIfNeeded();
        $query = http_build_query(['start' => $start, 'end' => $end]);
        return $this->sendRequest('GET', "/api/nodes/usage?{$query}");
    }

    public function getUserSubscription($token, $userAgent = '') {
        $headers = $userAgent ? ['User-Agent: ' . $userAgent] : [];
        return $this->sendRequest('GET', "/sub/{$token}/", [], $headers, false); 
    }

    public function getUserSubscriptionInfo($token) {
        return $this->sendRequest('GET', "/sub/{$token}/info", [], [], false);
    }

    public function getUserSubscriptionUsage($token, $start = '', $end = '') {
        $query = http_build_query(['start' => $start, 'end' => $end]);
        return $this->sendRequest('GET', "/sub/{$token}/usage?{$query}", [], [], false);
    }

    public function getUserSubscriptionWithClientType($token, $clientType, $userAgent = '') {
        $headers = $userAgent ? ['User-Agent: ' . $userAgent] : [];
        return $this->sendRequest('GET', "/sub/{$token}/{$clientType}", [], $headers, false);
    }

    public function getSystemStats() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', '/api/system');
    }

    public function getInbounds() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', '/api/inbounds');
    }

    public function getHosts() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', '/api/hosts');
    }

    public function modifyHosts($hostsData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('PUT', '/api/hosts', $hostsData);
    }

    public function addUserTemplate($templateData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', '/api/user_template', $templateData);
    }

    public function getUserTemplates($offset = null, $limit = null) {
        $this->authenticateIfNeeded();
        $query = [];
        if ($offset !== null) $query['offset'] = $offset;
        if ($limit !== null) $query['limit'] = $limit;
        $queryString = $query ? '?' . http_build_query($query) : '';
        return $this->sendRequest('GET', "/api/user_template{$queryString}");
    }

    public function getUserTemplate($templateId) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', "/api/user_template/{$templateId}");
    }

    public function modifyUserTemplate($templateId, $templateData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('PUT', "/api/user_template/{$templateId}", $templateData);
    }

    public function removeUserTemplate($templateId) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('DELETE', "/api/user_template/{$templateId}");
    }

    public function addUser($userData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', '/api/user', $userData);
    }

    public function getUser($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('GET', "/api/user/{$username}");
    }

    public function modifyUser($username, $userData) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('PUT', "/api/user/{$username}", $userData);
    }

    public function removeUser($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('DELETE', "/api/user/{$username}");
    }

    public function resetUserDataUsage($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', "/api/user/{$username}/reset");
    }

    public function revokeUserSubscription($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', "/api/user/{$username}/revoke_sub");
    }

    public function getUsers($params = []) {
        $this->authenticateIfNeeded();
        $queryString = $params ? '?' . http_build_query($params) : '';
        return $this->sendRequest('GET', "/api/users{$queryString}");
    }

    public function resetUsersDataUsage() {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', '/api/users/reset');
    }

    public function getUserUsage($username, $start = '', $end = '') {
        $this->authenticateIfNeeded();
        $query = http_build_query(['start' => $start, 'end' => $end]);
        return $this->sendRequest('GET', "/api/user/{$username}/usage?{$query}");
    }

    public function activeNextPlan($username) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('POST', "/api/user/{$username}/active-next");
    }

    public function getUsersUsage($start = '', $end = '', $admin = null) {
        $this->authenticateIfNeeded();
        $query = ['start' => $start, 'end' => $end];
        if ($admin) $query['admin'] = $admin;
        $queryString = http_build_query($query);
        return $this->sendRequest('GET', "/api/users/usage?{$queryString}");
    }

    public function setOwner($username, $adminUsername) {
        $this->authenticateIfNeeded();
        return $this->sendRequest('PUT', "/api/user/{$username}/set-owner?admin_username={$adminUsername}");
    }

    public function getExpiredUsers($expiredAfter = null, $expiredBefore = null) {
        $this->authenticateIfNeeded();
        $query = [];
        if ($expiredAfter) $query['expired_after'] = $expiredAfter;
        if ($expiredBefore) $query['expired_before'] = $expiredBefore;
        $queryString = $query ? '?' . http_build_query($query) : '';
        return $this->sendRequest('GET', "/api/users/expired{$queryString}");
    }

    public function deleteExpiredUsers($expiredAfter = null, $expiredBefore = null) {
        $this->authenticateIfNeeded();
        $query = [];
        if ($expiredAfter) $query['expired_after'] = $expiredAfter;
        if ($expiredBefore) $query['expired_before'] = $expiredBefore;
        $queryString = $query ? '?' . http_build_query($query) : '';
        return $this->sendRequest('DELETE', "/api/users/expired{$queryString}");
    }
}