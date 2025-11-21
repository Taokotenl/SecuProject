-- Roblox Key System SDK
-- Secure communication with key server

local SDK = {}
SDK.__index = SDK

-- Simplified constants
local SDK_VERSION = "2.1.0"

function SDK.new(proxy_url, fingerprint)
    local self = setmetatable({}, SDK)
    self.proxy_url = proxy_url
    self.fingerprint = fingerprint or "default"
    self.request_timeout = 10
    return self
end

-- Fixed request method
function SDK:_makeRequest(action, data)
    data = data or {}
    local HttpService = game:GetService("HttpService")

    data.timestamp = os.time()
    data.fingerprint = self.fingerprint

    local json = HttpService:JSONEncode(data)
    -- FIX: Use /api/action format instead of ?action=
    local url = self.proxy_url .. "/api/" .. action

    local body

    -- Executor HTTP first (Synapse, ScriptWare, KRNL, Fluxus)
    if syn and syn.request then
        local res = syn.request({
            Url = url,
            Method = "POST",
            Body = json,
            Headers = {
                ["Content-Type"] = "application/json"
            }
        })
        body = res.Body

    elseif http_request then
        local res = http_request({
            Url = url,
            Method = "POST",
            Body = json,
            Headers = {
                ["Content-Type"] = "application/json"
            }
        })
        body = res.Body

    elseif request then
        local res = request({
            Url = url,
            Method = "POST",
            Body = json,
            Headers = {
                ["Content-Type"] = "application/json"
            }
        })
        body = res.Body

    else
        -- Fallback Roblox PostAsync
        body = HttpService:PostAsync(
            url,
            json,
            Enum.HttpContentType.ApplicationJson
        )
    end

    -- decode
    local decoded = HttpService:JSONDecode(body)
    return decoded, nil
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