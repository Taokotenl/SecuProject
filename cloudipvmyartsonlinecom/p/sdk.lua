-- VexNet Key System SDK v2.0
-- Compatible with all major Roblox executors
-- Load via: loadstring(game:HttpGet("https://cloudipv.myartsonline.com/p/sdk.lua"))()

local VexNetSDK = {}
VexNetSDK.__index = VexNetSDK
VexNetSDK.VERSION = "2.0.0"

-- Added executor-compatible HTTP request function
local function getHttpRequest()
    return (
        request or 
        http_request or 
        (http and http.request) or
        (syn and syn.request) or
        (fluxus and fluxus.request) or
        (getgenv and getgenv().request)
    )
end

-- Added executor-compatible JSON encode/decode
local function jsonEncode(data)
    if game then
        local HttpService = game:GetService("HttpService")
        return HttpService:JSONEncode(data)
    end
    -- Fallback for executors without game access
    return game:GetService("HttpService"):JSONEncode(data)
end

local function jsonDecode(str)
    if game then
        local HttpService = game:GetService("HttpService")
        return HttpService:JSONDecode(str)
    end
    return game:GetService("HttpService"):JSONDecode(str)
end

local function base64Encode(str)
    if game then
        local HttpService = game:GetService("HttpService")
        return HttpService:Base64Encode(str)
    end
    return game:GetService("HttpService"):Base64Encode(str)
end

-- Security constants
local VERIFICATION_SALT = "vexnet_secure_2025"
local SDK_SIGNATURE = "VX2025ANTI"

function VexNetSDK.new(proxy_url, fingerprint)
    local self = setmetatable({}, VexNetSDK)
    self.proxy_url = proxy_url or "https://vapp-vexnetnetworkofficial.wasmer.app/"
    self.fingerprint = fingerprint or "executor-client"
    self.request_timeout = 10
    self._verified = false
    self._server_token = nil
    self._verification_token = nil -- Store verification token
    self._request_func = getHttpRequest()
    
    if not self._request_func then
        error("[VexNet] No HTTP request function found. Executor not supported.")
    end
    
    return self
end

-- Generate random nonce for request uniqueness
function VexNetSDK:_generateNonce()
    local chars = "0123456789abcdef"
    local nonce = ""
    for i = 1, 32 do
        local rand = math.random(1, #chars)
        nonce = nonce .. chars:sub(rand, rand)
    end
    return nonce
end

-- Generate request signature
function VexNetSDK:_generateSignature(timestamp, nonce, data)
    local combined = tostring(timestamp) .. nonce .. jsonEncode(data)
    return base64Encode(combined)
end

-- Generate server token for verification
function VexNetSDK:_generateServerToken(response_data)
    local raw = SDK_SIGNATURE .. tostring(response_data.timestamp or 0) .. VERIFICATION_SALT
    return base64Encode(raw)
end

-- Make HTTP request using executor's request function
function VexNetSDK:_makeRequest(action, data)
    data = data or {}
    local timestamp = math.floor(tick())
    local nonce = self:_generateNonce()
    
    local payload = {
        cipher = base64Encode(jsonEncode(data)),
        timestamp = timestamp,
        nonce = nonce,
        signature = self:_generateSignature(timestamp, nonce, data),
        client_fingerprint = self.fingerprint
    }
    
    local url = self.proxy_url
    if not url:match("/$") then
        url = url .. "/"
    end
    url = url .. "?action=" .. action
    
    local success, response = pcall(function()
        local result = self._request_func({
            Url = url,
            Method = "POST",
            Headers = {
                ["Content-Type"] = "application/json",
                ["User-Agent"] = "VexNet-SDK/" .. VexNetSDK.VERSION
            },
            Body = jsonEncode(payload)
        })
        
        if result and result.Body then
            return result.Body
        elseif result and result.body then
            return result.body
        end
        
        return result
    end)
    
    if not success then
        return nil, "Request failed: " .. tostring(response)
    end
    
    local decoded = jsonDecode(response)
    
    if decoded and decoded.timestamp then
        self._server_token = self:_generateServerToken(decoded)
    end
    
    return decoded, nil
end

-- Added kick function for security violations
function VexNetSDK:_kickPlayer(reason)
    if game and game.Players and game.Players.LocalPlayer then
        pcall(function()
            game.Players.LocalPlayer:Kick(reason or "Security Violation")
        end)
        -- Freeze client as backup
        while true do end
    end
end

-- Hidden verification to prevent bypass
function VexNetSDK:_hiddenVerify(key_response)
    local timestamp = math.floor(tick())
    local nonce = self:_generateNonce()
    
    local verify_data = {
        sdk_version = VexNetSDK.VERSION,
        sdk_sig = SDK_SIGNATURE,
        server_token = self._server_token,
        key_hash = base64Encode(tostring(key_response)),
        verify_salt = VERIFICATION_SALT
    }
    
    local payload = {
        cipher = base64Encode(jsonEncode(verify_data)),
        timestamp = timestamp,
        nonce = nonce,
        signature = self:_generateSignature(timestamp, nonce, verify_data),
        client_fingerprint = self.fingerprint
    }
    
    local url = self.proxy_url
    if not url:match("/$") then
        url = url .. "/"
    end
    url = url .. "?action=verify"
    
    local success, response = pcall(function()
        local result = self._request_func({
            Url = url,
            Method = "POST",
            Headers = {
                ["Content-Type"] = "application/json",
                ["User-Agent"] = "VexNet-SDK/" .. VexNetSDK.VERSION
            },
            Body = jsonEncode(payload)
        })
        
        if result and result.Body then
            return result.Body
        elseif result and result.body then
            return result.body
        end
        
        return result
    end)
    
    if not success then
        return false, "Unauthorized"
    end
    
    local decoded = jsonDecode(response)
    
    if not decoded or not decoded.verified or not decoded.server_signature then
        return false, "Unauthorized"
    end
    
    local expected_sig = self:_generateServerToken({timestamp = decoded.timestamp})
    if decoded.server_signature ~= expected_sig then
        return false, "Unauthorized"
    end
    
    self._verified = true
    self._verification_token = decoded.verification_token -- Save token
    return true, nil
end

-- Sync with server
function VexNetSDK:sync()
    local response, err = self:_makeRequest("sync", {})
    
    if err then
        return nil, err
    end
    
    if response and response.status == "success" then
        local verify_ok, verify_err = self:_hiddenVerify(response)
        
        if not verify_ok then
            self:_kickPlayer("Security Check Failed") -- Kick on verify fail
            return false, verify_err
        end
        
        return response.valid, response
    end
    
    return false, "Check failed"
end

-- Load and execute script
function VexNetSDK:loadScript(script_id, key)
    if not script_id or not key then
        return nil, "Missing script_id or key"
    end
    
    if not self._verified or not self._verification_token then -- Check token
        self:_kickPlayer("Unauthorized Access Attempt")
        return nil, "SDK not verified. Call checkKey first."
    end
    
    local response, err = self:_makeRequest("loadscript", {
        script_id = script_id,
        key = key,
        verification_token = self._verification_token -- Send token
    })
    
    if err then
        -- Check for ban/auth errors
        if string.find(tostring(err), "BANNED") or string.find(tostring(err), "Authentication failed") then
            self:_kickPlayer("You are banned from using this script.")
        end
        return nil, err
    end
    
    if response and response.status == "success" and response.script then
        return response.script, nil
    end
    
    -- If we got a response but it wasn't success, suspicious
    if response and response.error then
        if response.error == "BANNED" then
            self:_kickPlayer("You are permanently banned.")
        end
    end

    return nil, "Load failed"
end

-- Execute script with key validation
function VexNetSDK:executeScript(script_id, key)
    local valid, check_response = self:checkKey(key)
    
    if not valid then
        return false, "Invalid key: " .. tostring(check_response)
    end
    
    local script_code, err = self:loadScript(script_id, key)
    
    if not script_code then
        return false, "Failed to load script: " .. tostring(err)
    end
    
    local func, load_err = loadstring(script_code)
    
    if not func then
        return false, "Failed to compile script: " .. tostring(load_err)
    end
    
    local exec_success, exec_result = pcall(func)
    
    if not exec_success then
        return false, "Script execution error: " .. tostring(exec_result)
    end
    
    return true, exec_result
end

-- Export global instance
getgenv().VexNet = VexNetSDK

return VexNetSDK
