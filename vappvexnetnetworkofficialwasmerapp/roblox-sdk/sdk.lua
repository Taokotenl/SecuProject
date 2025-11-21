-- Roblox Key System SDK
-- Secure communication with key server

local SDK = {}
SDK.__index = SDK

-- Simplified constants
local SDK_VERSION = "2.1.0"

function SDK.new(proxy_url, fingerprint, opts)
    local self = setmetatable({}, SDK)
    self.proxy_url = proxy_url
    self.fingerprint = fingerprint or "default"
    self.request_timeout = 10
    opts = opts or {}
    -- optional token/script_id used to identify script and provider
    self.token = opts.token
    self.script_id = opts.script_id
    return self
end

-- Fixed request method
function SDK:_makeRequest(action, data)
    data = data or {}
    local HttpService = game:GetService("HttpService")

    data.timestamp = os.time()
    data.fingerprint = self.fingerprint

    local ok, json = pcall(function() return HttpService:JSONEncode(data) end)
    if not ok then return nil, "json_encode_failed" end

    local url = self.proxy_url .. "/api/" .. action

    local body

    -- Build headers and include token/script id when available
    local headers = { ["Content-Type"] = "application/json" }
    if self.token then headers["X-VAPP-Token"] = tostring(self.token) end
    if self.script_id then headers["X-Script-Id"] = tostring(self.script_id) end

    local function safeRequest(req)
        local status, res = pcall(function() return req end)
        if not status then return nil, tostring(res) end
        return res
    end

    if syn and syn.request then
        local res = syn.request({ Url = url, Method = "POST", Body = json, Headers = headers })
        body = res and res.Body or nil

    elseif http_request then
        local res = http_request({ Url = url, Method = "POST", Body = json, Headers = headers })
        body = res and res.Body or nil

    elseif request then
        local res = request({ Url = url, Method = "POST", Body = json, Headers = headers })
        body = res and res.Body or nil

    else
        local ok2, res = pcall(function()
            return HttpService:PostAsync(url, json, Enum.HttpContentType.ApplicationJson)
        end)
        if ok2 then body = res else return nil, tostring(res) end
    end

    if not body then return nil, "no_response_body" end

    local dec_ok, decoded = pcall(function() return HttpService:JSONDecode(body) end)
    if not dec_ok then return nil, "json_decode_failed:" .. tostring(decoded) end
    return decoded, nil
end

-- Lightweight tamper detection helper
function SDK:detectTamper()
    local tampered = false
    if type(hookfunction) == 'function' or type(hookmetamethod) == 'function' then
        tampered = true
    end
    return tampered
end

-- Sync method to fetch nodes + metadata
function SDK:sync()
    local resp, err = self:_makeRequest('sync', { tampered = self:detectTamper() })
    if err then return nil, err end
    -- mark SDK as verified when sync returns expected structure
    if resp and (resp.nodes or resp.success) then
        self._verified = true
    end
    return resp, nil
end

function SDK:checkKey(key)
    if not key or type(key) ~= "string" then
        return false, "Invalid key format"
    end

    local response, err = self:_makeRequest("checkkey", {
        key = key
    })

    if err then
        return false, err
    end

    -- Simplified validation logic
    if response and response.success and response.valid then
        return true, nil
    end

    return false, response.error or "Check failed"
end

function SDK:loadScript(script_id, key)
    if not script_id or not key then
        return nil, "Missing script_id or key"
    end

    local response, err = self:_makeRequest("loadscript", {
        script_id = script_id,
        key = key
    })

    if err then
        return nil, err
    end

    if response and response.success then
        return response.script, nil
    end

    return nil, response.error or "Load failed"
end

return SDK