-- Script Auto Loader
-- Initialize SDK and load scripts automatically

local SDK = require(script.Parent:WaitForChild("sdk"))
local Library = require(script.Parent:WaitForChild("library"))

-- Configuration
local PROXY_URL = "https://vapp-vexnetnetworkofficial.wasmer.app"
local SCRIPT_ID = "main-script" -- replace with your script id
local TOKEN = "" -- replace with your token if required

-- Initialize SDK (provide token/script_id so proxy can route requests)
local sdk = SDK.new(PROXY_URL, "roblox-executor", { token = TOKEN, script_id = SCRIPT_ID })

-- Sync with server safely
print("[Loader] Syncing with server...")
local ok, sync_result = pcall(function() return sdk:sync() end)
if not ok or not sync_result or type(sync_result) ~= 'table' then
    warn("[Loader] Sync failed or returned invalid data: ", tostring(sync_result))
else
    if sync_result.nodes and type(sync_result.nodes) == 'table' then
        print("[Loader] Connected to ", #sync_result.nodes, " nodes")
    else
        print("[Loader] Sync completed")
    end
end

-- Initialize library helper
local lib = Library.new(sdk)

-- Example usage: authenticate + load script
print("[Loader] Enter your key to execute script")
local key = "XXXX-XXXX-XXXX" -- Replace with runtime provided key

local success, auth_err = pcall(function()
    return lib:authenticate(key)
end)

if not success or not auth_err then
    print("[Loader] Authentication failed: ", tostring(auth_err))
    return
end

print("[Loader] Authentication successful")

-- Use library to execute the script (library handles loading and sandboxing)
local ok_exec, exec_err = pcall(function() return lib:execute(SCRIPT_ID) end)
if not ok_exec then
    error("Script execution failed: " .. tostring(exec_err))
end

print("[Loader] Script executed successfully via Library")
