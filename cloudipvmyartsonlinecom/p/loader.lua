-- VexNet Quick Loader
-- Usage: loadstring(game:HttpGet("https://cloudipv.myartsonline.com/p/loader.lua"))()

-- Load SDK from server
local SDK_URL = "https://cloudipv.myartsonline.com/p/sdk.lua"
local PROXY_URL = "https://vapp-vexnetnetworkofficial.wasmer.app/"

print("[VexNet] Loading SDK...")

local sdk_code = game:HttpGet(SDK_URL)
local SDK = loadstring(sdk_code)()

print("[VexNet] SDK loaded successfully!")

-- Create global instance
local vexnet = SDK.new(PROXY_URL, "executor-" .. tostring(game.PlaceId))

-- Sync with server
print("[VexNet] Syncing with server...")
local sync_result, sync_err = vexnet:sync()

if not sync_result then
    warn("[VexNet] Sync failed: " .. tostring(sync_err))
    return nil
end

print("[VexNet] Connected! Nodes: " .. tostring(#sync_result.nodes))

-- Export to global
getgenv().VexNet = vexnet

-- Helper function for easy script execution
getgenv().VexNetExecute = function(key, script_id)
    script_id = script_id or "main-script"
    
    print("[VexNet] Validating key...")
    local success, result = vexnet:executeScript(script_id, key)
    
    if success then
        print("[VexNet] Script executed successfully!")
        return true
    else
        warn("[VexNet] Execution failed: " .. tostring(result))
        return false
    end
end

print("[VexNet] Ready! Use: VexNetExecute('YOUR-KEY-HERE')")

return vexnet
