-- Script Auto Loader
-- Initialize SDK and load scripts automatically

local SDK = require(script.Parent:WaitForChild("sdk"))
local Library = require(script.Parent:WaitForChild("library"))

-- Configuration
local PROXY_URL = "https://vapp-vexnetnetworkofficial.wasmer.app/proxy.php"
local SCRIPT_ID = "main-script"

-- Initialize SDK
local sdk = SDK.new(PROXY_URL, "roblox-executor")

-- Sync with server
print("[Loader] Syncing with server...")
local sync_result, sync_err = sdk:sync()

if not sync_result then
    error("Sync failed: " .. (sync_err or "Unknown error"))
end

print("[Loader] Connected to " .. #sync_result.nodes .. " nodes")

-- Initialize library
local lib = Library.new(sdk)

-- Script to demonstrate usage
print("[Loader] Enter your key to execute script")
-- In real implementation, get key from user or environment
local key = "XXXX-XXXX-XXXX" -- Placeholder

if lib:authenticate(key) then
    print("[Loader] Authentication successful")
    
    local result = lib:execute(SCRIPT_ID)
    print("[Loader] Script executed successfully")
else
    print("[Loader] Authentication failed")
end
